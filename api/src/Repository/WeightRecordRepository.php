<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WeightRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeightRecord>
 */
class WeightRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeightRecord::class);
    }

    public function getByOwner(User $owner): array
    {
        return $this->createQueryBuilder('wr')
            ->join('wr.animal', 'a')
            ->addSelect('a')
            ->where('a.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getResult();
    }
}
