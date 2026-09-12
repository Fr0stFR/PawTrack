<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ProtectionStatus;
use App\Entity\Animal;
use App\Entity\Protection;
use App\Repository\AnimalRepository;
use App\Repository\MedicalEventRepository;
use App\Repository\MedicalPlanRepository;
use App\Repository\ProtectionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Sert `GET /animals/{animalId}/protection_statuses`.
 *
 * Pour chaque protection concernant l'espèce de l'animal : le dernier acte
 * réalisé qui la portait, plus la durée retenue.
 */
final class ProtectionStatusProvider implements ProviderInterface
{
    /** En deçà de ce délai, une protection encore valable est signalée comme bientôt échue. */
    private const EXPIRING_SOON_IN_DAYS = 30;

    public function __construct(
        private readonly AnimalRepository $animalRepository,
        private readonly ProtectionRepository $protectionRepository,
        private readonly MedicalEventRepository $medicalEventRepository,
        private readonly MedicalPlanRepository $medicalPlanRepository,
        private readonly Security $security,
    ) {
    }

    /**
     * @return ProtectionStatus[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $animal = $this->animalRepository->find($uriVariables['animalId'] ?? null);

        if (null === $animal) {
            throw new NotFoundHttpException('Animal introuvable.');
        }

        // Pas d'attribut `security:` possible : la ressource n'étant pas une
        // entité, aucun `object` n'existe dans l'expression.
        if ($animal->getOwner() !== $this->security->getUser()) {
            throw new AccessDeniedHttpException("Cet animal n'est pas le vôtre.");
        }

        // Trois requêtes fixes, quel que soit le nombre de protections.
        $protections = $this->protectionRepository->findForAnimalType($animal->getAnimalType());
        $lastDoneAt = $this->medicalEventRepository->findLastDoneAtByProtection($animal);
        $planDurations = $this->medicalPlanRepository->findDurationsByProtection($animal);

        $now = new \DateTimeImmutable();
        $statuses = [];

        foreach ($protections as $protection) {
            $statuses[] = $this->buildStatus(
                $protection,
                $lastDoneAt[$protection->getId()] ?? null,
                $planDurations[$protection->getId()] ?? null,
                $now,
            );
        }

        return $statuses;
    }

    /**
     * Une ligne de statut. Sans accès à la base : tout lui est passé, `$now`
     * compris, pour rester testable.
     *
     * @param array{frequency: string, value: int}|null $planDuration fréquence du
     *        plan couvrant cette protection, s'il en existe un
     */
    private function buildStatus(
        Protection $protection,
        ?\DateTimeImmutable $lastDoneAt,
        ?array $planDuration,
        \DateTimeImmutable $now,
    ): ProtectionStatus {
        // « Jamais reçue » n'est pas « expirée » : primo-vaccination contre
        // simple rappel, les deux protocoles diffèrent.
        if (null === $lastDoneAt) {
            return new ProtectionStatus(
                id: $protection->getCode(),
                protection: $protection,
                status: ProtectionStatus::STATUS_NEVER,
            );
        }

        // La fréquence du plan prime : `defaultFrequency` est commune à tous les
        // animaux, alors que les protocoles varient de l'un à l'autre.
        $expiresAt = $lastDoneAt->modify(sprintf(
            '+%d %s',
            $planDuration['value'] ?? $protection->getDefaultFrequencyValue(),
            $planDuration['frequency'] ?? $protection->getDefaultFrequency(),
        ));

        return new ProtectionStatus(
            id: $protection->getCode(),
            protection: $protection,
            status: $this->statusFor($expiresAt, $now),
            lastDoneAt: $lastDoneAt,
            expiresAt: $expiresAt,
        );
    }

    private function statusFor(\DateTimeImmutable $expiresAt, \DateTimeImmutable $now): string
    {
        if ($expiresAt < $now) {
            return ProtectionStatus::STATUS_EXPIRED;
        }

        if ($expiresAt < $now->modify(sprintf('+%d days', self::EXPIRING_SOON_IN_DAYS))) {
            return ProtectionStatus::STATUS_EXPIRING_SOON;
        }

        return ProtectionStatus::STATUS_UP_TO_DATE;
    }
}
