<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Entity\Animal;
use App\Entity\Protection;
use App\State\ProtectionStatusProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Où en est un animal pour une protection donnée.
 *
 * Ressource sans entité : chaque ligne est calculée à la demande en croisant le
 * catalogue de l'espèce et les actes déjà réalisés. Rien n'est stocké — le
 * statut change avec le temps, sans qu'aucune écriture ne se produise.
 */
#[ApiResource(
    shortName: 'ProtectionStatus',
    operations: [
        new GetCollection(
            uriTemplate: '/animals/{animalId}/protection_statuses',
            uriVariables: [
                'animalId' => new Link(fromClass: Animal::class, identifiers: ['id']),
            ],
            provider: ProtectionStatusProvider::class,
        ),
    ],
    // Une ressource imbriquée est sérialisée avec le contexte du parent : les
    // champs de Protection à publier ici portent donc `health:read`.
    normalizationContext: ['groups' => ['health:read']],
)]
class ProtectionStatus
{
    /** Jamais reçue. */
    public const STATUS_NEVER = 'never';

    /** Reçue, et encore valable. */
    public const STATUS_UP_TO_DATE = 'up_to_date';

    /** Encore valable, mais plus pour longtemps. */
    public const STATUS_EXPIRING_SOON = 'expiring_soon';

    /** La durée de protection est écoulée. */
    public const STATUS_EXPIRED = 'expired';

    public function __construct(
        /** Le code de la protection : une ressource calculée n'a pas d'auto-increment. */
        #[ApiProperty(identifier: true)]
        #[Groups(['health:read'])]
        public readonly string $id,

        #[Groups(['health:read'])]
        public readonly Protection $protection,

        /** L'une des constantes STATUS_* ci-dessus. */
        #[Groups(['health:read'])]
        public readonly string $status,

        /** Date du dernier acte réalisé portant cette protection. */
        #[Groups(['health:read'])]
        public readonly ?\DateTimeImmutable $lastDoneAt = null,

        /** `lastDoneAt` + la durée retenue (plan de l'animal, sinon défaut du référentiel). */
        #[Groups(['health:read'])]
        public readonly ?\DateTimeImmutable $expiresAt = null,
    ) {
    }
}
