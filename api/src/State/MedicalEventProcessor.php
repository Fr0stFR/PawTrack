<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MedicalEvent;
use App\Service\MedicalPlanRunner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Renseigne côté serveur les champs qui ne peuvent pas être confiés au client :
 * `createdBy` (donnée de sécurité) et `doneAt` (dérivée de `isDone`), puis fait
 * avancer le soin récurrent dont l'événement est issu.
 */
class MedicalEventProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly MedicalPlanRunner $planRunner,
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

        // L'ordre compte : syncPlan() interroge la base pour savoir s'il reste
        // une échéance ouverte, il faut donc que le flush ci-dessus ait déjà
        // enregistré celle qu'on vient de fermer.
        $plan = $data->getMedicalPlan();

        if (null !== $plan && $data->isDone()) {
            // Le curseur avance à la date réelle de réalisation, pas à la date
            // prévue : un vermifuge dû le 28/07 mais fait le 16/08 décale toute
            // la série, plutôt que de reprogrammer le suivant trop tôt.
            $plan->setLastExecutedAt($data->getDoneAt());
            $this->planRunner->syncPlan($plan);
            $this->em->flush();
        }

        return $data;
    }
}
