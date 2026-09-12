<?php

namespace App\Repository;

use App\Entity\Animal;
use App\Entity\MedicalEvent;
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

    /**
     * Les plans qui n'ont plus aucune occurrence ouverte, donc dont la prochaine
     * échéance reste à engendrer.
     *
     * La question posée est « existe-t-il un événement non-fait ? », pas « combien
     * y en a-t-il » : un NOT EXISTS l'exprime tel quel, là où un LEFT JOIN
     * imposerait de placer `e.isDone = false` dans le ON — et non dans le WHERE,
     * sous peine de transformer la jointure en INNER JOIN silencieux.
     *
     * @return MedicalPlan[]
     */
    public function findNeedingOccurrence(): array
    {
        $sub = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(MedicalEvent::class,'e')
            ->andWhere('e.medicalPlan = mp')
            ->andWhere('e.isDone = false');

        $qb = $this->createQueryBuilder('mp');
        
        return $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())))
            ->getQuery()
            ->getResult();
    }

    /**
     * Pour un animal, la durée retenue pour chaque protection qu'il entretient.
     *
     * Alimente le calcul « à jour ? », où la fréquence du plan prime sur
     * Protection::$defaultFrequency. Plusieurs plans peuvent viser la même
     * protection : le tri par id croissant fait gagner le plus récent, chaque
     * ligne écrasant la précédente dans le tableau indexé.
     *
     * @return array<int, array{frequency: string, value: int}> indexé par id de protection
     */
    public function findDurationsByProtection(Animal $animal): array
    {
        $rows = $this->createQueryBuilder('mp')
            ->select('IDENTITY(mp.protection) AS protectionId', 'mp.frequency', 'mp.frequencyValue')
            ->where('mp.animal = :animal')
            ->andWhere('mp.protection IS NOT NULL')
            ->orderBy('mp.id', 'ASC')
            ->setParameter('animal', $animal)
            ->getQuery()
            ->getResult();

        $durations = [];

        foreach ($rows as $row) {
            $durations[(int) $row['protectionId']] = [
                'frequency' => $row['frequency'],
                'value' => (int) $row['frequencyValue'],
            ];
        }

        return $durations;
    }
}
