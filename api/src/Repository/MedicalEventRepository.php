<?php

namespace App\Repository;

use App\Entity\MedicalEvent;
use App\Entity\MedicalPlan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MedicalEvent>
 */
class MedicalEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MedicalEvent::class);
    }

    public function getByOwner(User $owner, ?int $animalId = null): array
    {
        $qb = $this->createQueryBuilder('me')
            ->join('me.animal', 'a')
            ->addSelect('a')         // fetch join : charge l'animal dans la même requête
            ->where('a.owner = :owner')
            ->setParameter('owner', $owner);

        if ($animalId !== null) {
            $qb->andWhere('a.id = :animalId')
               ->setParameter('animalId', $animalId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Un plan a-t-il encore une échéance en attente ?
     *
     * Pendant du NOT EXISTS de MedicalPlanRepository::findNeedingOccurrence(),
     * mais pour un seul plan : la question se pose aussi hors du cron, dès qu'on
     * vient de cocher une occurrence comme faite.
     */
    public function hasOpenOccurrence(MedicalPlan $plan): bool
    {
        return (bool) $this->createQueryBuilder('me')
            ->select('COUNT(me.id)')
            ->andWhere('me.medicalPlan = :plan')
            ->andWhere('me.isDone = false')
            ->setParameter('plan', $plan)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
