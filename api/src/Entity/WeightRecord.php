<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\WeightRecordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeightRecordRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(security: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_USER') and object.getAnimal().getOwner() == user"),
        new Put(security: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')"),
        new Patch(security: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')"),
        new Delete(security: "object.getAnimal().getOwner() == user or is_granted('ROLE_ADMIN')"),
    ]
)]
class WeightRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $weight = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $recordedAt = null;

    #[ORM\ManyToOne(inversedBy: 'weightRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Animal $animal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getRecordedAt(): ?\DateTimeImmutable
    {
        return $this->recordedAt;
    }

    public function setRecordedAt(\DateTimeImmutable $recordedAt): static
    {
        $this->recordedAt = $recordedAt;

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
}
