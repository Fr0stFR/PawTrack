<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MedicalEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Renseigne côté serveur les champs qui ne peuvent pas être confiés au client :
 * `createdBy` (donnée de sécurité) et `doneAt` (dérivée de `isDone`).
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
        // Uniquement à la création : sur un PUT/PATCH, l'objet dénormalisé est
        // l'entité existante, dont l'auteur d'origine doit être préservé.
        if (null === $data->getCreatedBy()) {
            $data->setCreatedBy($this->security->getUser());
        }

        if ($data->isDone()) {
            // Un événement déjà fait qu'on remodifie garde sa date de réalisation.
            $data->setDoneAt($data->getDoneAt() ?? new \DateTimeImmutable());
        } else {
            $data->setDoneAt(null);
        }

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
