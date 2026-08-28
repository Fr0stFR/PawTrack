<?php

namespace App\Service;

use App\Entity\Reminder;
use App\Entity\User;
use App\Repository\ReminderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Envoie les rappels arrivés à échéance, un digest par destinataire.
 *
 * Contrairement à ReminderScheduler qui ne fait que décrire un état, ce service
 * produit un effet irréversible : un mail parti ne revient pas. Toute sa
 * mécanique découle de là — le plancher, l'ordre du `sentAt`, le flush par
 * utilisateur.
 */
class ReminderSender
{
    /**
     * Au-delà de ce retard, un rappel n'est plus envoyé — il est simplement
     * ignoré, et l'échéance reste visible dans l'application.
     *
     * Sans ce plancher, trois jours de cron en panne (ou un import de données)
     * feraient partir d'un coup tous les rappels dormants : l'utilisateur
     * reçoit onze mails au réveil. C'est le bug qui n'apparaît jamais en dev,
     * parce qu'en dev le cron ne tombe pas en panne pendant trois jours.
     */
    private const GRACE_PERIOD_IN_DAYS = 2;

    public function __construct(
        private readonly ReminderRepository $reminderRepository,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * L'instant de référence est un paramètre, jamais un `new DateTimeImmutable()`
     * pris à l'intérieur : c'est ce qui permettra de tester « et si on était
     * mardi prochain ? » sans toucher à l'horloge du système.
     *
     * @return array{users: int, reminders: int, failures: int}
     */
    public function sendDue(\DateTimeImmutable $now): array
    {
        $due = $this->reminderRepository->findDue(
            $now,
            $now->modify(sprintf('-%d days', self::GRACE_PERIOD_IN_DAYS)),
        );

        $report = ['users' => 0, 'reminders' => 0, 'failures' => 0];

        foreach ($this->groupByUser($due) as $reminders) {
            $user = $reminders[0]->getUser();

            try {
                $this->mailer->send($this->buildDigest($user, $reminders, $now));
            } catch (TransportExceptionInterface $e) {
                // Un destinataire injoignable ne doit pas priver les autres de
                // leur mail : on compte l'échec et on continue. Les rappels de
                // cet utilisateur gardent `sentAt` à null, donc ils repartiront
                // au prochain passage — tant qu'ils sont dans le plancher.
                ++$report['failures'];

                continue;
            }

            // `sentAt` APRÈS l'envoi, jamais avant : entre les deux échecs
            // possibles, on préfère le doublon au silence. Si le process meurt
            // ici, l'utilisateur recevra deux fois le même digest demain — c'est
            // désagréable, alors qu'un rappel médical jamais reçu, c'est raté.
            foreach ($reminders as $reminder) {
                $reminder->setSentAt($now);
                ++$report['reminders'];
            }

            // Flush par utilisateur, et pas un seul à la fin : si le process est
            // tué au milieu du lot, seuls les digests non encore marqués
            // repartiront. Un flush global rejouerait tout.
            $this->em->flush();

            ++$report['users'];
        }

        return $report;
    }

    /**
     * Regroupe les rappels par destinataire.
     *
     * La requête les a déjà triés par utilisateur ; ce regroupement n'a donc
     * rien à trier lui-même, il ne fait qu'assembler les paquets.
     *
     * @param Reminder[] $reminders
     *
     * @return array<int, Reminder[]>
     */
    private function groupByUser(array $reminders): array
    {
        $grouped = [];

        foreach ($reminders as $reminder) {
            $grouped[$reminder->getUser()->getId()][] = $reminder;
        }

        return $grouped;
    }

    /**
     * Un seul mail récapitulant tous les rappels du matin pour un utilisateur.
     *
     * Pas de `->from()` : l'expéditeur par défaut vient de `mailer.yaml`. Le
     * code métier n'a pas à connaître l'identité de l'application.
     *
     * @param Reminder[] $reminders
     */
    private function buildDigest(User $user, array $reminders, \DateTimeImmutable $now): TemplatedEmail
    {
        $lines = array_map(
            fn (Reminder $reminder): array => [
                'event' => $reminder->getMedicalEvent(),
                'daysUntil' => $this->daysUntil($reminder->getMedicalEvent()->getDate(), $now),
            ],
            $reminders,
        );

        return (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject(1 === count($reminders)
                ? 'Un soin arrive pour votre animal'
                : sprintf('%d soins arrivent pour vos animaux', count($reminders)))
            ->htmlTemplate('emails/reminder_digest.html.twig')
            ->context(['lines' => $lines]);
    }

    /**
     * Nombre de jours **calendaires** entre aujourd'hui et une échéance.
     *
     * Le calcul est ici, et pas dans le template, parce que ce n'est pas une
     * soustraction : une différence en secondes répondrait « 2 heures » entre
     * le 28 à 23 h et le 29 à 1 h, alors que la bonne réponse est « demain ».
     * D'où le `setTime(0, 0)` des deux côtés — on compare des dates civiles.
     *
     * Peut être négatif ou nul : le rappel est parti en retard, ou le jour même.
     */
    private function daysUntil(\DateTime $date, \DateTimeImmutable $now): int
    {
        $from = $now->setTime(0, 0);
        $to = \DateTimeImmutable::createFromMutable($date)->setTime(0, 0);

        // '%r%a' : %a est le nombre total de jours (contrairement à ->days sur
        // certaines constructions), %r ajoute le signe quand la date est passée.
        return (int) $from->diff($to)->format('%r%a');
    }
}
