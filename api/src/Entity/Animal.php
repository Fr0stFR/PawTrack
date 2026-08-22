<?php

namespace App\Entity;

use App\Repository\AnimalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use App\State\AnimalProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AnimalRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['animal:read']],
    operations: [
        new GetCollection(),
        new Get(security: "object.getOwner() == user or is_granted('ROLE_ADMIN')"),
        new Post(processor: AnimalProcessor::class),
        new Put(security: "object.getOwner() == user or is_granted('ROLE_ADMIN')"),
        new Patch(security: "object.getOwner() == user or is_granted('ROLE_ADMIN')"),
        new Delete(security: "object.getOwner() == user or is_granted('ROLE_ADMIN')"),
    ]
)]
class Animal
{
    // Exposé : le front en a besoin pour construire les URL /animals/:id.
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['animal:read'])]
    private ?int $id = null;

    // Également lu lorsqu'un Animal est embarqué dans un MedicalEvent.
    #[ORM\Column(length: 255)]
    #[Groups(['animal:read', 'event:read'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'animals')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['animal:read'])]
    private ?AnimalType $animalType = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['animal:read'])]
    private ?\DateTime $birthdate = null;

    #[ORM\Column(length: 1)]
    #[Groups(['animal:read'])]
    private ?string $gender = null;

    #[ORM\ManyToOne(inversedBy: 'animals')]
    #[Groups(['animal:read'])]
    private ?Breed $breed = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\ManyToOne(inversedBy: 'animals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * @var Collection<int, WeightRecord>
     */
    #[ORM\OneToMany(targetEntity: WeightRecord::class, mappedBy: 'animal')]
    private Collection $weightRecords;

    /**
     * @var Collection<int, MedicalEvent>
     */
    #[ORM\OneToMany(targetEntity: MedicalEvent::class, mappedBy: 'animal')]
    private Collection $medicalEvents;

    /**
     * @var Collection<int, MedicalPlan>
     */
    #[ORM\OneToMany(targetEntity: MedicalPlan::class, mappedBy: 'animal')]
    private Collection $medicalPlans;

    public function __construct()
    {
        $this->weightRecords = new ArrayCollection();
        $this->medicalEvents = new ArrayCollection();
        $this->medicalPlans = new ArrayCollection();
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

    public function getAnimalType(): ?AnimalType
    {
        return $this->animalType;
    }

    public function setAnimalType(?AnimalType $animalType): static
    {
        $this->animalType = $animalType;

        return $this;
    }

    public function getBirthdate(): ?\DateTime
    {
        return $this->birthdate;
    }

    public function setBirthdate(\DateTime $birthdate): static
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getBreed(): ?Breed
    {
        return $this->breed;
    }

    public function setBreed(?Breed $breed): static
    {
        $this->breed = $breed;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, WeightRecord>
     */
    public function getWeightRecords(): Collection
    {
        return $this->weightRecords;
    }

    public function addWeightRecord(WeightRecord $weightRecord): static
    {
        if (!$this->weightRecords->contains($weightRecord)) {
            $this->weightRecords->add($weightRecord);
            $weightRecord->setAnimal($this);
        }

        return $this;
    }

    public function removeWeightRecord(WeightRecord $weightRecord): static
    {
        if ($this->weightRecords->removeElement($weightRecord)) {
            // set the owning side to null (unless already changed)
            if ($weightRecord->getAnimal() === $this) {
                $weightRecord->setAnimal(null);
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
            $medicalEvent->setAnimal($this);
        }

        return $this;
    }

    public function removeMedicalEvent(MedicalEvent $medicalEvent): static
    {
        if ($this->medicalEvents->removeElement($medicalEvent)) {
            // set the owning side to null (unless already changed)
            if ($medicalEvent->getAnimal() === $this) {
                $medicalEvent->setAnimal(null);
            }
        }

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
            $medicalPlan->setAnimal($this);
        }

        return $this;
    }

    public function removeMedicalPlan(MedicalPlan $medicalPlan): static
    {
        if ($this->medicalPlans->removeElement($medicalPlan)) {
            // set the owning side to null (unless already changed)
            if ($medicalPlan->getAnimal() === $this) {
                $medicalPlan->setAnimal(null);
            }
        }

        return $this;
    }
}
