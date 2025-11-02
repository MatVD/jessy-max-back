<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['location:read']],
    denormalizationContext: ['groups' => ['location:write']]
)]
class Location
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['location:read', 'event:read', 'formation:read'])]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['location:read', 'location:write', 'event:read', 'formation:read', 'event:write', 'formation:write'])]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['location:read', 'location:write', 'event:read', 'formation:read', 'event:write', 'formation:write'])]
    private string $address;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8)]
    #[Assert\NotNull]
    #[Assert\Range(min: -90, max: 90)]
    #[Groups(['location:read', 'location:write', 'event:read', 'formation:read', 'event:write', 'formation:write'])]
    private string $latitude;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8)]
    #[Assert\NotNull]
    #[Assert\Range(min: -180, max: 180)]
    #[Groups(['location:read', 'location:write', 'event:read', 'formation:read', 'event:write', 'formation:write'])]
    private string $longitude;

    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'location')]
    #[Groups(['location:read'])]
    private Collection $events;

    #[ORM\OneToMany(targetEntity: Formation::class, mappedBy: 'location')]
    #[Groups(['location:read'])]
    private Collection $formations;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->events = new ArrayCollection();
        $this->formations = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getLatitude(): string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function getFormations(): Collection
    {
        return $this->formations;
    }
}
