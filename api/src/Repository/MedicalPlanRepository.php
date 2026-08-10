<?php

namespace App\Repository;

use App\Entity\MedicalPlan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MedicalPlan>
 */
class MedicalPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MedicalPlan::class);
    }

    public function getByOwner(User $owner): array
    {
        return $this->createQueryBuilder('mp')
            ->join('mp.animal', 'a')
            ->where('a.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getResult();
    }
}
