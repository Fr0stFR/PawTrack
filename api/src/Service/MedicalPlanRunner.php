<?php

namespace App\Service;

use App\Entity\MedicalEvent;
use App\Entity\MedicalPlan;
use App\Repository\MedicalEventRepository;
use App\Repository\MedicalPlanRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Transforme les soins récurrents en échéances concrètes.
 *
 * Règle unique : un plan doit toujours avoir exactement une occurrence ouverte.
 * Le service ne compte pas le temps écoulé depuis son dernier passage, il
 * constate l'état de la base et comble l'écart — ce qui le rend rejouable
 * autant de fois qu'on veut sans rien dupliquer.
 */
class MedicalPlanRunner
{
    /**
     * Unités de récurrence acceptées. `frequency` vient du client et finit dans
     * un DateTime::modify() : sans cette liste blanche, une valeur fantaisiste
     * ferait échouer le calcul de date au lieu d'être rejetée proprement.
     */
    private const UNITS = ['day', 'week', 'month', 'year'];

    public function __construct(
        private readonly MedicalPlanRepository $medicalPlanRepository,
        private readonly MedicalEventRepository $medicalEventRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Remet un plan en conformité avec la règle : s'il lui manque son occurrence
     * ouverte, on la crée.
     *
     * Appelée hors du cron — à la création d'un plan, ou juste après avoir coché
     * une échéance comme faite — pour que l'utilisateur voie la suite tout de
     * suite plutôt qu'au prochain passage nocturne.
     *
     * @return MedicalEvent|null null si le plan était déjà en règle
     */
    public function syncPlan(MedicalPlan $plan): ?MedicalEvent
    {
        if ($this->medicalEventRepository->hasOpenOccurrence($plan)) {
            return null;
        }

        return $this->createNextOccurrence($plan);
    }

    /**
     * Engendre l'échéance manquante de chaque plan qui n'en a plus.
     *
     * @return MedicalEvent[] les événements créés, pour que l'appelant en rende compte
     */
    public function runAll(): array
    {
        $created = [];

        foreach ($this->medicalPlanRepository->findNeedingOccurrence() as $plan) {
            $created[] = $this->createNextOccurrence($plan);
        }

        // Un seul flush pour tout le lot : les persist() ci-dessus n'ont fait
        // qu'empiler des insertions en mémoire.
        $this->em->flush();

        return $created;
    }

    /**
     * Crée la prochaine occurrence d'un plan, sans flush (c'est à l'appelant
     * de décider quand la transaction se referme).
     */
    public function createNextOccurrence(MedicalPlan $plan): MedicalEvent
    {
        $event = new MedicalEvent();
        $event
            ->setMedicalPlan($plan)
            ->setAnimal($plan->getAnimal())
            ->setMedicalType($plan->getMedicalType())
            ->setName($plan->getName())
            ->setDate($this->nextDueDate($plan))
            ->setIsDone(false)
            ->setCreatedBy($plan->getAnimal()->getOwner());

        $this->em->persist($event);

        return $event;
    }

    /**
     * Date de la prochaine échéance : le dernier passage décalé de la fréquence.
     *
     * Un plan jamais exécuté est dû immédiatement — sans quoi une automatisation
     * « tous les 3 mois » créée aujourd'hui n'afficherait rien avant 3 mois et
     * passerait pour cassée. C'est au formulaire de proposer une date de dernier
     * passage à l'utilisateur qui vient justement de faire le soin.
     *
     * La date obtenue peut être dans le passé (plan négligé depuis longtemps) :
     * c'est voulu, l'échéance apparaîtra « en retard ». On ne rattrape jamais
     * les occurrences manquées — un vermifuge oublié ne se fait pas trois fois.
     */
    private function nextDueDate(MedicalPlan $plan): \DateTime
    {
        $last = $plan->getLastExecutedAt();

        if (null === $last) {
            return \DateTime::createFromImmutable($plan->getStartsAt());
        }

        $unit = $plan->getFrequency();
        if (!in_array($unit, self::UNITS, true)) {
            throw new \LogicException(sprintf(
                'Unité de récurrence inconnue "%s" sur le plan #%d.',
                $unit,
                $plan->getId(),
            ));
        }

        // modify() décale de mois calendaires, pas de 30 jours : le 31 janvier
        // + 1 month donne le 3 mars. Acceptable pour un soin récurrent, à ne pas
        // reproduire tel quel pour une échéance contractuelle.
        $next = $last->modify(sprintf('+%d %s', $plan->getFrequencyValue(), $unit));

        // MedicalEvent::$date est un DateTime mutable, lastExecutedAt un immutable.
        return \DateTime::createFromImmutable($next);
    }
}
