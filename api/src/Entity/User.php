<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\UserRepository;
use App\State\UserPasswordHasherProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(security: "is_granted('ROLE_ADMIN') or object == user"),
        new Post(processor: UserPasswordHasherProcessor::class),
        new Put(security: "is_granted('ROLE_ADMIN') or object == user", processor: UserPasswordHasherProcessor::class),
        new Patch(security: "is_granted('ROLE_ADMIN') or object == user", processor: UserPasswordHasherProcessor::class),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user:read'])]  // lecture seule — jamais modifiable par l'utilisateur
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    // Sans groupe de sérialisation : jamais exposé par l'API.
    private ?string $password = null;

    // Non persisté : transporte le mot de passe en clair jusqu'au processor
    // qui le hashe.
    #[Groups(['user:write'])]
    private ?string $plainPassword = null;

    /**
     * @var Collection<int, Animal>
     */
    #[ORM\OneToMany(targetEntity: Animal::class, mappedBy: 'owner')]
    // Les animaux sont exposés via /api/animals, pas en sous-ressource de /api/users.
    private Collection $animals;

    /**
     * @var Collection<int, MedicalEvent>
     */
    #[ORM\OneToMany(targetEntity: MedicalEvent::class, mappedBy: 'createdBy')]
    private Collection $medicalEvents;

    public function __construct()
    {
        $this->animals = new ArrayCollection();
        $this->medicalEvents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Animal>
     */
    public function getAnimals(): Collection
    {
        return $this->animals;
    }

    public function addAnimal(Animal $animal): static
    {
        if (!$this->animals->contains($animal)) {
            $this->animals->add($animal);
            $animal->setOwner($this);
        }

        return $this;
    }

    public function removeAnimal(Animal $animal): static
    {
        if ($this->animals->removeElement($animal)) {
            // set the owning side to null (unless already changed)
            if ($animal->getOwner() === $this) {
                $animal->setOwner(null);
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
            $medicalEvent->setCreatedBy($this);
        }

        return $this;
    }

    public function removeMedicalEvent(MedicalEvent $medicalEvent): static
    {
        if ($this->medicalEvents->removeElement($medicalEvent)) {
            // set the owning side to null (unless already changed)
            if ($medicalEvent->getCreatedBy() === $this) {
                $medicalEvent->setCreatedBy(null);
            }
        }

        return $this;
    }
}
