<?php

namespace App\Service;

use App\Entity\MedicalEvent;
use App\Entity\Reminder;
use App\Repository\MedicalEventRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Maintient les rappels d'une échéance en accord avec sa date.
 *
 * Règle unique : une échéance à faire porte un rappel non envoyé pour chacun
 * des décalages prévus, et rien d'autre.
 *
 * Le service ne cherche jamais à savoir *ce qui a changé* — création, décalage
 * de date, validation, tout passe par le même chemin. Il compare l'ensemble
 * attendu à l'ensemble en base et comble l'écart, ce qui le rend rejouable sans
 * rien dupliquer. Ce qui distingue deux rappels, c'est leur date d'envoi : deux
 * rappels au même instant sont le même rappel, il n'y a donc rien à « déplacer »
 * quand la date de l'échéance change — les mauvais partent, les bons naissent.
 */
class ReminderScheduler
{
    /**
     * Délais en jours pour les rappels des différents évènements
     */
    private const OFFSETS_IN_DAYS = [7, 1];

    /**
     * Heure d'envoi, en heure locale. Sans elle, un rappel hériterait de
     * l'heure de l'échéance — minuit, puisque le formulaire envoie une date
     * seule. Elle rend aussi la comparaison de dates stable : deux calculs
     * successifs tombent forcément sur le même instant.
     */
    private const SEND_AT_HOUR = 8;

    public function __construct(
        private readonly MedicalEventRepository $medicalEventRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Remet les rappels d'une échéance en accord avec sa date.
     *
     * Ne persiste ni ne flushe rien explicitement : `cascade: ['persist']` et
     * `orphanRemoval` sur MedicalEvent::$reminders suffit.
     *
     * @return array{created: int, removed: int} de quoi rendre compte en CLI
     */
    public function sync(MedicalEvent $event): array
    {
        $expected = $this->expectedSchedule($event);

        // 1. Retirer les rappels qui ne sont plus au programme.
        // on ne touche qu'aux rappels en attente.
        $obsolete = [];

        foreach ($event->getReminders() as $reminder) {
            if (null !== $reminder->getSentAt()) {
                continue;
            }

            if (!isset($expected[$this->dateKey($reminder->getScheduledAt())])) {
                $obsolete[] = $reminder;
            }
        }

        foreach ($obsolete as $reminder) {
            $event->removeReminder($reminder);
        }

        // 2. Ce qui reste en base couvre déjà sa date — rappel envoyé compris,
        //    sinon on renverrait le même mail à chaque passage du cron.
        foreach ($event->getReminders() as $reminder) {
            unset($expected[$this->dateKey($reminder->getScheduledAt())]);
        }

        // 3. Créer les manquants.
        foreach ($expected as $scheduledAt) {
            $event->addReminder(
                (new Reminder())
                    ->setScheduledAt($scheduledAt)
                    ->setUser($event->getAnimal()->getOwner())
            );
        }

        return ['created' => count($expected), 'removed' => count($obsolete)];
    }

    /**
     * Repasse sur toutes les échéances dont les rappels peuvent avoir dérivé.
     *
     * @return array{events: int, created: int, removed: int}
     */
    public function syncAll(): array
    {
        $report = ['events' => 0, 'created' => 0, 'removed' => 0];

        foreach ($this->medicalEventRepository->findNeedingReminderSync() as $event) {
            $changes = $this->sync($event);

            ++$report['events'];
            $report['created'] += $changes['created'];
            $report['removed'] += $changes['removed'];
        }

        // Un seul flush pour tout le lot : les modifications de collection
        // ci-dessus n'ont fait qu'empiler des changements en mémoire.
        $this->em->flush();

        return $report;
    }

    /**
     * Les rappels que cette échéance devrait porter, indexés par date d'envoi.
     *
     * Une échéance faite n'a plus rien à annoncer : l'ensemble attendu est vide,
     * et l'étape 1 de sync() se charge de la purge. C'est le même mécanisme qui
     * traite la validation et le décalage de date — aucun cas particulier.
     *
     * @return array<string, \DateTimeImmutable>
     */
    private function expectedSchedule(MedicalEvent $event): array
    {
        if ($event->isDone()) {
            return [];
        }

        $due = \DateTimeImmutable::createFromMutable($event->getDate());
        $schedule = [];

        foreach (self::OFFSETS_IN_DAYS as $offset) {
            $at = $due
                ->modify(sprintf('-%d days', $offset))
                ->setTime(self::SEND_AT_HOUR, 0);

            $schedule[$this->dateKey($at)] = $at;
        }

        return $schedule;
    }

    /**
     * Une date d'envoi réduite à une chaîne, pour servir de clé de tableau.
     *
     * Rien à voir avec un identifiant d'entité : c'est juste ce qui permet de
     * dire que deux rappels tombent au même moment, sans comparer des objets
     * DateTime (dont l'égalité tient aussi compte du fuseau horaire).
     *
     * Le format doit rester identique à ses trois appelants, sans quoi plus
     * aucun rappel en base ne correspondrait à un rappel attendu — d'où cette
     * méthode plutôt qu'un format() recopié.
     */
    private function dateKey(\DateTimeImmutable $at): string
    {
        return $at->format('Y-m-d H:i');
    }
}
