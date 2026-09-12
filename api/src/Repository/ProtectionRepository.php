<?php

namespace App\Repository;

use App\Entity\AnimalType;
use App\Entity\Protection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Protection>
 */
class ProtectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Protection::class);
    }

    /**
     * Le catalogue des protections applicables à une espèce.
     *
     * @return Protection[]
     */
    public function findForAnimalType(AnimalType $animalType): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.animalTypes', 'at')
            ->where('at = :animalType')
            ->setParameter('animalType', $animalType)
            // Ordre d'affichage décidé ici plutôt que côté client.
            ->orderBy('p.category', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
