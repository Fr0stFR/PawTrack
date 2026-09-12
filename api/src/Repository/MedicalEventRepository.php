<?php

namespace App\Repository;

use App\Entity\Animal;
use App\Entity\MedicalEvent;
use App\Entity\MedicalPlan;
use App\Entity\Reminder;
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

    /**
     * Les échéances dont les rappels peuvent avoir dérivé de leur date.
     *
     * Deux populations, et deux seulement :
     *  - les échéances **ouvertes**, qui doivent porter leurs rappels ;
     *  - les échéances **fermées qui traînent encore un rappel en attente** —
     *    reliquat d'un chemin d'écriture qu'on n'a pas prévu, ou d'une ligne
     *    créée avant que la règle n'existe.
     *
     * Tout le reste a un écart nul par construction : inutile de rouvrir chaque
     * nuit un historique qui ne fait que grossir.
     *
     * @return MedicalEvent[]
     */
    public function findNeedingReminderSync(): array
    {
        // Sous-requête sur une seconde instance du QueryBuilder : on n'en tire
        // que le DQL, la corrélation se fait par l'alias `me` de la requête
        // externe. Le SELECT d'un EXISTS n'est jamais évalué, d'où le `1`.
        $pendingReminder = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(Reminder::class, 'r')
            ->where('r.medicalEvent = me')
            ->andWhere('r.sentAt IS NULL');

        return $this->createQueryBuilder('me')
            // Fetch joins : sync() lit la collection de rappels et le
            // propriétaire de l'animal pour chaque échéance. Sans eux, une
            // centaine d'échéances déclencherait deux cents requêtes de plus.
            ->leftJoin('me.reminders', 'rem')->addSelect('rem')
            ->join('me.animal', 'a')->addSelect('a')
            ->where('me.isDone = false')
            ->orWhere(sprintf('EXISTS (%s)', $pendingReminder->getDQL()))
            ->getQuery()
            ->getResult();
    }

    /**
     * Pour un animal, la date du dernier acte réalisé de chaque protection.
     *
     * Une seule requête agrégée, et non une par protection. Seuls les événements
     * faits comptent : un acte prévu ne protège de rien.
     *
     * @return array<int, \DateTimeImmutable> indexé par id de protection
     */
    public function findLastDoneAtByProtection(Animal $animal): array
    {
        $rows = $this->createQueryBuilder('me')
            ->select('IDENTITY(me.protection) AS protectionId', 'MAX(me.doneAt) AS lastDoneAt')
            ->where('me.animal = :animal')
            ->andWhere('me.isDone = true')
            ->andWhere('me.protection IS NOT NULL')
            ->andWhere('me.doneAt IS NOT NULL')
            ->groupBy('me.protection')
            ->setParameter('animal', $animal)
            ->getQuery()
            ->getResult();

        $lastDoneAt = [];

        foreach ($rows as $row) {
            // MAX() sur une colonne datetime renvoie une chaîne : Doctrine ne
            // retype pas le résultat d'un agrégat.
            $lastDoneAt[(int) $row['protectionId']] = new \DateTimeImmutable($row['lastDoneAt']);
        }

        return $lastDoneAt;
    }
}
