<?php

namespace App\Repository;

use App\Entity\Reminder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reminder>
 */
class ReminderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reminder::class);
    }

    /**
     * Les rappels qu'il est temps d'envoyer.
     *
     * Les deux bornes viennent de l'appelant plutôt que d'un `NOW()` en SQL :
     * une requête qui lit l'horloge toute seule ne se teste pas.
     *
     * @param \DateTimeImmutable $notBefore le plancher — en deçà, un rappel a
     *                                      trop de retard pour valoir la peine
     *
     * @return Reminder[] triés par destinataire, prêts à être regroupés en digest
     */
    public function findDue(\DateTimeImmutable $now, \DateTimeImmutable $notBefore): array
    {
        return $this->createQueryBuilder('r')
            // Fetch joins : le mail affiche le nom de l'animal et celui du soin,
            // et s'adresse à l'utilisateur. Sans eux, chaque rappel déclenche
            // trois requêtes de plus au moment de composer le message.
            ->join('r.user', 'u')->addSelect('u')
            ->join('r.medicalEvent', 'e')->addSelect('e')
            ->join('e.animal', 'a')->addSelect('a')
            ->where('r.sentAt IS NULL')
            ->andWhere('r.scheduledAt <= :now')
            ->andWhere('r.scheduledAt >= :notBefore')
            // Garde-fou : par construction, un rappel non envoyé appartient à une
            // échéance ouverte — ReminderScheduler purge les autres. Mais si cet
            // invariant se trouvait cassé (import SQL, bug), le prix serait un
            // mail annonçant un soin déjà fait. Le prédicat n'est pas redondant :
            // il ne l'est que *si* un autre composant est correct.
            ->andWhere('e.isDone = false')
            ->setParameter('now', $now)
            ->setParameter('notBefore', $notBefore)
            // Le tri par destinataire est ce qui rend le digest possible : le
            // service n'a plus qu'à parcourir la liste en changeant de mail
            // quand l'utilisateur change.
            ->orderBy('u.id', 'ASC')
            ->addOrderBy('r.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
