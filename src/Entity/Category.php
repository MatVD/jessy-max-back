<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\CategoryType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'UNIQ_CATEGORY_NAME', columns: ['name'])]
#[ApiResource(
    normalizationContext: ['groups' => ['category:read']],
    denormalizationContext: ['groups' => ['category:write']]
)]
class Category
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['category:read', 'event:read', 'formation:read'])]
    private Uuid $id;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['category:read', 'category:write', 'event:read', 'formation:read'])]
    private string $name;

    #[ORM\Column(type: 'string', enumType: CategoryType::class)]
    #[Groups(['category:read', 'category:write', 'event:read', 'formation:read'])]
    private CategoryType $type;

    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'categories')]
    #[Groups(['category:read'])]
    private Collection $events;

    #[ORM\ManyToMany(targetEntity: Formation::class, mappedBy: 'categories')]
    #[Groups(['category:read'])]
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

    public function getType(): CategoryType
    {
        return $this->type;
    }

    public function setType(CategoryType $type): self
    {
        $this->type = $type;
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
