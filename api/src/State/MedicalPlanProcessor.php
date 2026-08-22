<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MedicalPlan;
use App\Service\MedicalPlanRunner;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Donne au plan sa première échéance dès sa création, plutôt que d'attendre le
 * passage nocturne de `app:plans:run` : une automatisation qui n'affiche rien
 * pendant vingt-quatre heures passe pour cassée.
 */
class MedicalPlanProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MedicalPlanRunner $runner,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MedicalPlan
    {
        $this->em->persist($data);
        $this->em->flush();

        // Après le flush : syncPlan() interroge la base pour savoir si le plan a
        // déjà une échéance ouverte, ce qui suppose qu'il y existe.
        $this->runner->syncPlan($data);
        $this->em->flush();

        return $data;
    }
}
