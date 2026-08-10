<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\MedicalTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MedicalTypeRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ]
)]
class MedicalType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['event:read', 'plan:read'])]
    private ?string $name = null;

    /**
     * @var Collection<int, MedicalPlan>
     */
    #[ORM\OneToMany(targetEntity: MedicalPlan::class, mappedBy: 'medicalType')]
    private Collection $medicalPlans;

    /**
     * @var Collection<int, MedicalEvent>
     */
    #[ORM\OneToMany(targetEntity: MedicalEvent::class, mappedBy: 'medicalType')]
    private Collection $medicalEvents;

    public function __construct()
    {
        $this->medicalPlans = new ArrayCollection();
        $this->medicalEvents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, MedicalPlan>
     */
    public function getMedicalPlans(): Collection
    {
        return $this->medicalPlans;
    }

    public function addMedicalPlan(MedicalPlan $medicalPlan): static
    {
        if (!$this->medicalPlans->contains($medicalPlan)) {
            $this->medicalPlans->add($medicalPlan);
            $medicalPlan->setMedicalType($this);
        }

        return $this;
    }

    public function removeMedicalPlan(MedicalPlan $medicalPlan): static
    {
        if ($this->medicalPlans->removeElement($medicalPlan)) {
            // set the owning side to null (unless already changed)
            if ($medicalPlan->getMedicalType() === $this) {
                $medicalPlan->setMedicalType(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MedicalEvent>
     */
    public function getMedicalEvents(): Collection
    {
        return $this->medicalEvents;
    }

    public function addMedicalEvent(MedicalEvent $medicalEvent): static
    {
        if (!$this->medicalEvents->contains($medicalEvent)) {
            $this->medicalEvents->add($medicalEvent);
            $medicalEvent->setMedicalType($this);
        }

        return $this;
    }

    public function removeMedicalEvent(MedicalEvent $medicalEvent): static
    {
        if ($this->medicalEvents->removeElement($medicalEvent)) {
            // set the owning side to null (unless already changed)
            if ($medicalEvent->getMedicalType() === $this) {
                $medicalEvent->setMedicalType(null);
            }
        }

        return $this;
    }
}
