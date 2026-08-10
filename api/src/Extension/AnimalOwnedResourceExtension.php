<?php

namespace App\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\MedicalEvent;
use App\Entity\MedicalPlan;
use App\Entity\WeightRecord;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

class AnimalOwnedResourceExtension implements QueryCollectionExtensionInterface
{
    private const SUPPORTED_CLASSES = [
        MedicalEvent::class,
        WeightRecord::class,
        MedicalPlan::class,
    ];

    public function __construct(private readonly Security $security)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (!in_array($resourceClass, self::SUPPORTED_CLASSES, strict: true)) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->join("$alias.animal", 'a')
            ->andWhere('a.owner = :currentUser')
            ->setParameter('currentUser', $this->security->getUser());
    }
}
