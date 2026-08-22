<?php

namespace App\Entity;

use App\Repository\MedicalEventRepository;
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
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\State\MedicalEventProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MedicalEventRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['event:read']],
    denormalizationContext: ['groups' => ['event:write']],
    operations: [
        new GetCollection(),
        new Get(security: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')"),
        new Post(
            // `security` s'évalue avant dénormalisation, quand l'objet n'existe
            // pas encore : le contrôle de propriété passe donc par
            // securityPostDenormalize.
            security: "is_granted('ROLE_USER')",
            securityPostDenormalize: "object.getAnimal().getOwner() == user",
            processor: MedicalEventProcessor::class,
        ),
        // `animal` étant modifiable, les deux contrôles sont nécessaires :
        // `security` vérifie qu'on possède l'événement d'origine,
        // `securityPostDenormalize` qu'on possède aussi l'animal de destination.
        new Put(
            security: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')",
            securityPostDenormalize: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')",
            processor: MedicalEventProcessor::class,
        ),
        new Patch(
            security: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')",
            securityPostDenormalize: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')",
            processor: MedicalEventProcessor::class,
        ),
        new Delete(security: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')"),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['animal' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['date', 'doneAt'])]
#[ApiFilter(DateFilter::class, properties: ['date'])]
#[ApiFilter(BooleanFilter::class, properties: ['isDone'])]
class MedicalEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['event:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'medicalEvents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['event:read', 'event:write'])]
    private ?MedicalType $medicalType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'medicalEvents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['event:read', 'event:write'])]
    private ?Animal $animal = null;

    #[ORM\Column]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTime $date = null;

    #[ORM\Column]
    #[Groups(['event:read', 'event:write'])]
    private ?bool $isDone = null;

    // Un événement encore à faire n'a pas de date de réalisation.
    //
    // Écrivable, mais facultatif : omis, le processor le fixe à maintenant. Sans
    // ça, la date de réalisation serait en fait la date de *saisie* — et un soin
    // récurrent noté avec trois semaines de retard décalerait toute sa série
    // d'autant, sans que personne ne s'en aperçoive.
    #[ORM\Column(nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeImmutable $doneAt = null;

    #[ORM\ManyToOne(inversedBy: 'medicalEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    // Unidirectionnelle : User#medicalEvents est déjà l'inverse de `createdBy`,
    // et rien n'a besoin de remonter d'un vétérinaire vers ses actes. Le jour où
    // ROLE_VET arrivera, on ajoutera une collection dédiée côté User.
    #[ORM\ManyToOne]
    private ?User $veterinarian = null;

    #[ORM\ManyToOne(inversedBy: 'medicalEvents')]
    private ?MedicalPlan $medicalPlan = null;

    /**
     * @var Collection<int, Reminder>
     */
    #[ORM\OneToMany(targetEntity: Reminder::class, mappedBy: 'medicalEvent')]
    private Collection $reminders;

    public function __construct()
    {
        $this->reminders = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function isDone(): ?bool
    {
        return $this->isDone;
    }

    /**
     * Le serializer interprète isDone() comme l'accesseur d'une propriété `done`.
     * Cet accesseur est nécessaire pour exposer `isDone` en JSON.
     */
    public function getIsDone(): ?bool
    {
        return $this->isDone;
    }

    public function setIsDone(bool $isDone): static
    {
        $this->isDone = $isDone;

        return $this;
    }

    public function getDoneAt(): ?\DateTimeImmutable
    {
        return $this->doneAt;
    }

    public function setDoneAt(?\DateTimeImmutable $doneAt): static
    {
        $this->doneAt = $doneAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getVeterinarian(): ?User
    {
        return $this->veterinarian;
    }

    public function setVeterinarian(?User $veterinarian): static
    {
        $this->veterinarian = $veterinarian;

        return $this;
    }

    public function getMedicalPlan(): ?MedicalPlan
    {
        return $this->medicalPlan;
    }

    public function setMedicalPlan(?MedicalPlan $medicalPlan): static
    {
        $this->medicalPlan = $medicalPlan;

        return $this;
    }

    /**
     * @return Collection<int, Reminder>
     */
    public function getReminders(): Collection
    {
        return $this->reminders;
    }

    public function addReminder(Reminder $reminder): static
    {
        if (!$this->reminders->contains($reminder)) {
            $this->reminders->add($reminder);
            $reminder->setMedicalEvent($this);
        }

        return $this;
    }

    public function removeReminder(Reminder $reminder): static
    {
        if ($this->reminders->removeElement($reminder)) {
            // set the owning side to null (unless already changed)
            if ($reminder->getMedicalEvent() === $this) {
                $reminder->setMedicalEvent(null);
            }
        }

        return $this;
    }
}
