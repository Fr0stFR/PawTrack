<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MedicalEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Affecte l'auteur d'un événement médical côté serveur : `createdBy` est une
 * donnée de sécurité qui ne peut pas être confiée au client.
 */
class MedicalEventProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MedicalEvent
    {
        $data->setCreatedBy($this->security->getUser());

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
