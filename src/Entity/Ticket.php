<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\TicketRepository;
use App\State\TicketProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\Table(name: 'tickets')]
#[ORM\Index(name: 'idx_tickets_event', columns: ['event_id'])]
#[ORM\Index(name: 'idx_tickets_formation', columns: ['formation_id'])]
#[ORM\Index(name: 'idx_tickets_customer_email', columns: ['customer_email'])]
#[ORM\Index(name: 'idx_tickets_qr_code', columns: ['qr_code'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['ticket:read', 'ticket:detail']]),
        new GetCollection(normalizationContext: ['groups' => ['ticket:read']]),
        new Post(
            denormalizationContext: ['groups' => ['ticket:write']],
            normalizationContext: ['groups' => ['ticket:read', 'ticket:detail']],
            processor: TicketProcessor::class
        ),
    ],
    order: ['purchasedAt' => 'DESC'],
    paginationEnabled: true,
    paginationItemsPerPage: 30,
)]
#[ApiFilter(SearchFilter::class, properties: ['customerEmail' => 'exact'])]
class Ticket
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['ticket:read', 'refund:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: true)]
    #[Groups(['ticket:read', 'ticket:write'])]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(name: 'formation_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: true)]
    #[Groups(['ticket:read', 'ticket:write'])]
    private ?Formation $formation = null;

    #[ORM\Column(name: 'customer_name', length: 255)]
    #[Assert\NotBlank(message: 'The customer name cannot be blank.')]
    #[Assert\Length(max: 255, maxMessage: 'The customer name cannot be longer than {{ limit }} characters.')]
    #[Groups(['ticket:read', 'ticket:write', 'refund:read'])]
    private ?string $customerName = null;

    #[ORM\Column(name: 'customer_email', length: 255)]
    #[Assert\NotBlank(message: 'The customer email cannot be blank.')]
    #[Assert\Email(message: 'The email "{{ value }}" is not a valid email.')]
    #[Assert\Length(max: 255)]
    #[Groups(['ticket:read', 'ticket:write', 'refund:read'])]
    private ?string $customerEmail = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull(message: 'The quantity cannot be null.')]
    #[Assert\Positive(message: 'The quantity must be at least 1.')]
    #[Assert\LessThanOrEqual(10, message: 'The quantity cannot exceed 10 tickets per order.')]
    #[Groups(['ticket:read', 'ticket:write', 'refund:read'])]
    private ?int $quantity = null;

    #[ORM\Column(name: 'total_price', type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'The total price cannot be null.')]
    #[Assert\PositiveOrZero(message: 'The total price must be positive or zero.')]
    #[Groups(['ticket:read', 'refund:read'])]
    private ?string $totalPrice = null;

    #[ORM\Column(name: 'payment_status', length: 50)]
    #[Assert\Choice(choices: ['pending', 'completed', 'refunded'], message: 'Invalid payment status.')]
    #[Groups(['ticket:read', 'ticket:detail'])]
    private string $paymentStatus = 'pending';

    #[ORM\Column(name: 'qr_code', length: 255, unique: true)]
    #[Groups(['ticket:read', 'ticket:detail'])]
    private ?string $qrCode = null;

    #[ORM\Column(name: 'purchased_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['ticket:read', 'ticket:detail'])]
    private ?\DateTimeImmutable $purchasedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['ticket:read', 'ticket:detail'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(targetEntity: RefundRequest::class, mappedBy: 'ticket', cascade: ['remove'])]
    private Collection $refundRequests;

    public function __construct()
    {
        $this->refundRequests = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->purchasedAt = new \DateTimeImmutable();
    }

    /**
     * Constraint de validation personnalisée : un ticket doit avoir soit event_id soit formation_id, jamais les deux
     */
    #[Assert\Callback]
    public function validate(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        // Contrainte XOR : exactement un des deux doit être défini
        if (($this->event === null && $this->formation === null) || ($this->event !== null && $this->formation !== null)) {
            $context->buildViolation('A ticket must be associated with either an event or a formation, but not both.')
                ->atPath('event')
                ->addViolation();
        }
    }

    // Getters and Setters

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;
        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): static
    {
        $this->customerName = $customerName;
        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function setQrCode(string $qrCode): static
    {
        $this->qrCode = $qrCode;
        return $this;
    }

    public function getPurchasedAt(): ?\DateTimeImmutable
    {
        return $this->purchasedAt;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, RefundRequest>
     */
    public function getRefundRequests(): Collection
    {
        return $this->refundRequests;
    }

    public function addRefundRequest(RefundRequest $refundRequest): static
    {
        if (!$this->refundRequests->contains($refundRequest)) {
            $this->refundRequests->add($refundRequest);
            $refundRequest->setTicket($this);
        }

        return $this;
    }

    public function removeRefundRequest(RefundRequest $refundRequest): static
    {
        if ($this->refundRequests->removeElement($refundRequest)) {
            if ($refundRequest->getTicket() === $this) {
                $refundRequest->setTicket(null);
            }
        }

        return $this;
    }
}
