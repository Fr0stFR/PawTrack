<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ProtectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;

#[ORM\Entity(repositoryClass: ProtectionRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['protection:read']],
    // Lecture seule, volontairement : ce référentiel se remplit par migration de données.
    operations: [
        new GetCollection(),
        new Get(),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['animalTypes' => 'exact'])]
class Protection
{
    /** Mêmes unités que MedicalPlan::$frequency, dont ce référentiel pré-remplit les valeurs. */
    public const UNITS = ['day', 'week', 'month', 'year'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['protection:read', 'event:read', 'plan:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['protection:read', 'event:read', 'plan:read'])]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    #[Groups(['protection:read', 'event:read', 'plan:read', 'health:read'])]
    private ?string $name = null;

    /** Ce que le sigle recouvre : « Carré, Rubarth, Parvovirose ». */
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['protection:read', 'event:read', 'health:read'])]
    private ?string $description = null;

    /** 'vaccine' | 'antiparasitic' — pour regrouper à l'affichage. */
    #[ORM\Column(length: 50)]
    #[Groups(['protection:read'])]
    private ?string $category = null;

    /**
     * Espèces concernées, pour filtrer la saisie. Unidirectionnelle : rien ne
     * remonte d'une espèce vers ses protections.
     *
     * @var Collection<int, AnimalType>
     */
    #[ORM\ManyToMany(targetEntity: AnimalType::class)]
    #[Groups(['protection:read'])]
    private Collection $animalTypes;

    /**
     * Durée de protection par défaut. Simple valeur d'amorce : la fréquence du
     * plan de l'animal, si elle existe, prime dessus (cf. ProtectionStatusProvider).
     */
    #[ORM\Column(length: 10)]
    #[Groups(['protection:read'])]
    private ?string $defaultFrequency = null;

    #[ORM\Column]
    #[Groups(['protection:read'])]
    private ?int $defaultFrequencyValue = null;

    public function __construct()
    {
        $this->animalTypes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, AnimalType>
     */
    public function getAnimalTypes(): Collection
    {
        return $this->animalTypes;
    }

    public function addAnimalType(AnimalType $animalType): static
    {
        if (!$this->animalTypes->contains($animalType)) {
            $this->animalTypes->add($animalType);
        }

        return $this;
    }

    public function removeAnimalType(AnimalType $animalType): static
    {
        $this->animalTypes->removeElement($animalType);

        return $this;
    }

    public function getDefaultFrequency(): ?string
    {
        return $this->defaultFrequency;
    }

    public function setDefaultFrequency(string $defaultFrequency): static
    {
        $this->defaultFrequency = $defaultFrequency;

        return $this;
    }

    public function getDefaultFrequencyValue(): ?int
    {
        return $this->defaultFrequencyValue;
    }

    public function setDefaultFrequencyValue(int $defaultFrequencyValue): static
    {
        $this->defaultFrequencyValue = $defaultFrequencyValue;

        return $this;
    }
}