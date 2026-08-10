<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\MedicalPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MedicalPlanRepository::class)]
#[ApiFilter(SearchFilter::class, properties: ['animal' => 'exact'])]
#[ApiResource(
    normalizationContext: ['groups' => ['plan:read']],
    operations: [
        new GetCollection(),
        new Get(security: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')"),
        new Post(
            security: "is_granted('ROLE_USER')",
            securityPostDenormalize: "object.getAnimal().getOwner() == user",
        ),
        new Put(security: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')"),
        new Patch(security: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')"),
        new Delete(security: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')"),
    ]
)]
class MedicalPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['plan:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['plan:read'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'medicalPlans')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['plan:read'])]
    private ?MedicalType $medicalType = null;

    // Unité de récurrence, combinée à frequencyValue : ('month', 3) => tous les 3 mois.
    #[ORM\Column(length: 255)]
    #[Groups(['plan:read'])]
    private ?string $frequency = null;

    #[ORM\Column]
    #[Groups(['plan:read'])]
    private ?int $frequencyValue = null;

    #[ORM\ManyToOne(inversedBy: 'medicalPlans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Animal $animal = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['plan:read'])]
    private ?\DateTimeImmutable $lastExecutedAt = null;

    /**
     * @var Collection<int, MedicalEvent>
     */
    #[ORM\OneToMany(targetEntity: MedicalEvent::class, mappedBy: 'medicalPlan')]
    private Collection $medicalEvents;

    public function __construct()
    {
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

    public function getMedicalType(): ?MedicalType
    {
        return $this->medicalType;
    }

    public function setMedicalType(?MedicalType $medicalType): static
    {
        $this->medicalType = $medicalType;

        return $this;
    }

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(string $frequency): static
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function getFrequencyValue(): ?int
    {
        return $this->frequencyValue;
    }

    public function setFrequencyValue(int $frequencyValue): static
    {
        $this->frequencyValue = $frequencyValue;

        return $this;
    }

    public function getAnimal(): ?Animal
    {
        return $this->animal;
    }

    public function setAnimal(?Animal $animal): static
    {
        $this->animal = $animal;

        return $this;
    }

    public function getLastExecutedAt(): ?\DateTimeImmutable
    {
        return $this->lastExecutedAt;
    }

    public function setLastExecutedAt(?\DateTimeImmutable $lastExecutedAt): static
    {
        $this->lastExecutedAt = $lastExecutedAt;

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
            $medicalEvent->setMedicalPlan($this);
        }

        return $this;
    }

    public function removeMedicalEvent(MedicalEvent $medicalEvent): static
    {
        if ($this->medicalEvents->removeElement($medicalEvent)) {
            // set the owning side to null (unless already changed)
            if ($medicalEvent->getMedicalPlan() === $this) {
                $medicalEvent->setMedicalPlan(null);
            }
        }

        return $this;
    }
}
