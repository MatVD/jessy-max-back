<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use App\Enum\RefundStatus;
use App\State\RefundRequestProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER') and object.getUser() === user"),
        new GetCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_USER')", processor: RefundRequestProcessor::class),
        new Patch(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER') and object.getUser() === user"),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER') and object.getUser() === user")
    ],
    normalizationContext: ['groups' => ['refund_request:read']],
    denormalizationContext: ['groups' => ['refund_request:write']]
)]
class RefundRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['refund_request:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'refundRequests')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['refund_request:read', 'refund_request:write'])]
    private Ticket $ticket;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['refund_request:read', 'refund_request:write'])]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['refund_request:read', 'refund_request:write'])]
    private string $customerName;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['refund_request:read', 'refund_request:write'])]
    private string $customerEmail;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['refund_request:read', 'refund_request:write'])]
    private string $reason;

    #[ORM\Column(type: 'string', enumType: RefundStatus::class)]
    #[Assert\NotBlank]
    #[Groups(['refund_request:read', 'refund_request:write'])]
    private RefundStatus $status;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    #[Groups(['refund_request:read', 'refund_request:write'])]
    private string $refundAmount;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['refund_request:read', 'refund_request:write'])]
    private ?string $stripeRefundId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->status = RefundStatus::PENDING;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }

    public function setTicket(Ticket $ticket): self
    {
        $this->ticket = $ticket;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): self
    {
        $this->customerName = $customerName;
        return $this;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): self
    {
        $this->customerEmail = $customerEmail;
        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getStatus(): RefundStatus
    {
        return $this->status;
    }

    public function setStatus(RefundStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getRefundAmount(): string
    {
        return $this->refundAmount;
    }

    public function setRefundAmount(string $refundAmount): self
    {
        $this->refundAmount = $refundAmount;
        return $this;
    }

    public function getStripeRefundId(): ?string
    {
        return $this->stripeRefundId;
    }

    public function setStripeRefundId(?string $stripeRefundId): self
    {
        $this->stripeRefundId = $stripeRefundId;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): self
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    public function markAsProcessed(): self
    {
        $this->processedAt = new \DateTimeImmutable();
        $this->status = RefundStatus::PROCESSED;
        return $this;
    }
}
