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
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
#[ORM\Index(name: 'idx_events_date', columns: ['date'])]
#[ORM\Index(name: 'idx_events_event_type', columns: ['event_type'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['event:read', 'event:detail']]),
        new GetCollection(normalizationContext: ['groups' => ['event:read']]),
        new Post(
            denormalizationContext: ['groups' => ['event:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: 'Only admins can create events.'
        ),
        new Put(
            denormalizationContext: ['groups' => ['event:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: 'Only admins can update events.'
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: 'Only admins can delete events.'
        ),
    ],
    order: ['date' => 'ASC'],
    paginationEnabled: true,
    paginationItemsPerPage: 30,
)]
#[ApiFilter(DateFilter::class, properties: ['date'])]
#[ApiFilter(SearchFilter::class, properties: ['eventType' => 'exact'])]
#[ApiFilter(RangeFilter::class, properties: ['availableTickets'])]
class Event
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['event:read', 'ticket:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The title cannot be blank.')]
    #[Assert\Length(max: 255, maxMessage: 'The title cannot be longer than {{ limit }} characters.')]
    #[Groups(['event:read', 'event:write', 'ticket:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'The description cannot be blank.')]
    #[Groups(['event:read', 'event:write', 'event:detail'])]
    private ?string $description = null;

    #[ORM\Column(name: 'event_type', length: 100)]
    #[Assert\NotBlank(message: 'The event type cannot be blank.')]
    #[Assert\Length(max: 100)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $eventType = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'The date cannot be null.')]
    #[Assert\GreaterThanOrEqual('today', message: 'The event date must be today or in the future.')]
    #[Groups(['event:read', 'event:write', 'ticket:read'])]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The location cannot be blank.')]
    #[Assert\Length(max: 255)]
    #[Groups(['event:read', 'event:write', 'ticket:read'])]
    private ?string $location = null;

    #[ORM\Column(name: 'image_url', length: 500)]
    #[Assert\NotBlank(message: 'The image URL cannot be blank.')]
    #[Assert\Url(message: 'The image URL "{{ value }}" is not a valid URL.')]
    #[Groups(['event:read', 'event:write'])]
    private ?string $imageUrl = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'The price cannot be null.')]
    #[Assert\PositiveOrZero(message: 'The price must be positive or zero.')]
    #[Groups(['event:read', 'event:write', 'ticket:read'])]
    private ?string $price = null;

    #[ORM\Column(name: 'available_tickets', type: Types::INTEGER)]
    #[Assert\PositiveOrZero(message: 'Available tickets must be positive or zero.')]
    #[Groups(['event:read', 'event:write'])]
    private int $availableTickets = 0;

    #[ORM\Column(name: 'total_tickets', type: Types::INTEGER)]
    #[Assert\PositiveOrZero(message: 'Total tickets must be positive or zero.')]
    #[Groups(['event:read', 'event:write'])]
    private int $totalTickets = 0;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['event:read', 'event:detail'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['event:read', 'event:detail'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'event', cascade: ['remove'])]
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
        if ($this->availableTickets > $this->totalTickets) {
            $context->buildViolation('Available tickets cannot be greater than total tickets.')
                ->atPath('availableTickets')
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

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;
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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getAvailableTickets(): int
    {
        return $this->availableTickets;
    }

    public function setAvailableTickets(int $availableTickets): static
    {
        $this->availableTickets = $availableTickets;
        return $this;
    }

    public function getTotalTickets(): int
    {
        return $this->totalTickets;
    }

    public function setTotalTickets(int $totalTickets): static
    {
        $this->totalTickets = $totalTickets;
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
            $ticket->setEvent($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            if ($ticket->getEvent() === $this) {
                $ticket->setEvent(null);
            }
        }

        return $this;
    }
}
