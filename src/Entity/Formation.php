<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Table(name: 'formations')]
#[ORM\Index(name: 'idx_formations_start_date', columns: ['start_date'])]
#[ORM\Index(name: 'idx_formations_category', columns: ['category'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['formation:read', 'formation:detail']]),
        new GetCollection(normalizationContext: ['groups' => ['formation:read']]),
        new Post(
            denormalizationContext: ['groups' => ['formation:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: 'Only admins can create formations.'
        ),
        new Put(
            denormalizationContext: ['groups' => ['formation:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: 'Only admins can update formations.'
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: 'Only admins can delete formations.'
        ),
    ],
    order: ['startDate' => 'ASC'],
    paginationEnabled: true,
    paginationItemsPerPage: 30,
)]
#[ApiFilter(DateFilter::class, properties: ['startDate'])]
#[ApiFilter(SearchFilter::class, properties: ['category' => 'exact'])]
class Formation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['formation:read', 'ticket:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The title cannot be blank.')]
    #[Assert\Length(max: 255, maxMessage: 'The title cannot be longer than {{ limit }} characters.')]
    #[Groups(['formation:read', 'formation:write', 'ticket:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'The description cannot be blank.')]
    #[Groups(['formation:read', 'formation:write', 'formation:detail'])]
    private ?string $description = null;

    #[ORM\Column(name: 'image_url', length: 500)]
    #[Assert\NotBlank(message: 'The image URL cannot be blank.')]
    #[Assert\Url(message: 'The image URL "{{ value }}" is not a valid URL.')]
    #[Groups(['formation:read', 'formation:write'])]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'start_date', type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'The start date cannot be null.')]
    #[Assert\GreaterThanOrEqual('today', message: 'The formation start date must be today or in the future.')]
    #[Groups(['formation:read', 'formation:write', 'ticket:read'])]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'The duration cannot be blank.')]
    #[Assert\Length(max: 100)]
    #[Groups(['formation:read', 'formation:write', 'formation:detail'])]
    private ?string $duration = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'The price cannot be null.')]
    #[Assert\PositiveOrZero(message: 'The price must be positive or zero.')]
    #[Groups(['formation:read', 'formation:write', 'ticket:read'])]
    private ?string $price = null;

    #[ORM\Column(name: 'max_participants', type: Types::INTEGER)]
    #[Assert\PositiveOrZero(message: 'Maximum participants must be positive or zero.')]
    #[Groups(['formation:read', 'formation:write'])]
    private int $maxParticipants = 0;

    #[ORM\Column(name: 'current_participants', type: Types::INTEGER)]
    #[Assert\PositiveOrZero(message: 'Current participants must be positive or zero.')]
    #[Groups(['formation:read', 'formation:write'])]
    private int $currentParticipants = 0;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The instructor name cannot be blank.')]
    #[Assert\Length(max: 255)]
    #[Groups(['formation:read', 'formation:write', 'formation:detail'])]
    private ?string $instructor = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'The category cannot be blank.')]
    #[Assert\Length(max: 100)]
    #[Groups(['formation:read', 'formation:write'])]
    private string $category = 'music';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['formation:read', 'formation:detail'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['formation:read', 'formation:detail'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'formation', cascade: ['remove'])]
    private Collection $tickets;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[Assert\Callback]
    public function validate(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if ($this->currentParticipants > $this->maxParticipants) {
            $context->buildViolation('Current participants cannot be greater than maximum participants.')
                ->atPath('currentParticipants')
                ->addViolation();
        }
    }

    // Getters and Setters

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(string $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getMaxParticipants(): int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;
        return $this;
    }

    public function getCurrentParticipants(): int
    {
        return $this->currentParticipants;
    }

    public function setCurrentParticipants(int $currentParticipants): static
    {
        $this->currentParticipants = $currentParticipants;
        return $this;
    }

    public function getInstructor(): ?string
    {
        return $this->instructor;
    }

    public function setInstructor(string $instructor): static
    {
        $this->instructor = $instructor;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setFormation($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            if ($ticket->getFormation() === $this) {
                $ticket->setFormation(null);
            }
        }

        return $this;
    }
}
