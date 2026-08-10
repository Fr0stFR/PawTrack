<?php

namespace App\Repository;

use App\Entity\MedicalEvent;
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
}
