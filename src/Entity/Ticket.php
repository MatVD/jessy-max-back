<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\PaymentStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['ticket:read']],
    denormalizationContext: ['groups' => ['ticket:write']],
)]
class Ticket
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['ticket:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['ticket:read', 'ticket:write'])]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['ticket:read', 'ticket:write'])]
    private ?Formation $formation = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['ticket:read', 'ticket:write'])]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['ticket:read', 'ticket:write'])]
    private string $customerName;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['ticket:read', 'ticket:write'])]
    private string $customerEmail;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    #[Groups(['ticket:read', 'ticket:write'])]
    private string $price;

    #[ORM\Column(type: 'string', enumType: PaymentStatus::class)]
    #[Groups(['ticket:read'])]
    private PaymentStatus $paymentStatus;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['ticket:read'])]
    private ?string $stripeCheckoutSessionId = null;

    /**
     * URL de checkout Stripe (non persistée, utilisée uniquement lors de la création)
     */
    #[Groups(['ticket:read'])]
    private ?string $stripeCheckoutUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['ticket:read'])]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['ticket:read'])]
    private ?string $qrCode = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $usedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['ticket:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['ticket:read', 'ticket:write'])]
    private ?\DateTimeImmutable $purchasedAt = null;

    #[ORM\OneToMany(targetEntity: RefundRequest::class, mappedBy: 'ticket')]
    private Collection $refundRequests;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->refundRequests = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->paymentStatus = PaymentStatus::PENDING;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): self
    {
        $this->formation = $formation;
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

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getPaymentStatus(): PaymentStatus
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(PaymentStatus $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    public function getStripeCheckoutSessionId(): ?string
    {
        return $this->stripeCheckoutSessionId;
    }

    public function setStripeCheckoutSessionId(?string $stripeCheckoutSessionId): self
    {
        $this->stripeCheckoutSessionId = $stripeCheckoutSessionId;
        return $this;
    }

    public function getStripeCheckoutUrl(): ?string
    {
        return $this->stripeCheckoutUrl;
    }

    public function setStripeCheckoutUrl(?string $stripeCheckoutUrl): self
    {
        $this->stripeCheckoutUrl = $stripeCheckoutUrl;
        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): self
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;
        return $this;
    }

    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function setQrCode(?string $qrCode): self
    {
        $this->qrCode = $qrCode;
        return $this;
    }

    public function getUsedAt(): ?\DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function setUsedAt(?\DateTimeImmutable $usedAt): self
    {
        $this->usedAt = $usedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPurchasedAt(): ?\DateTimeImmutable
    {
        return $this->purchasedAt;
    }

    public function setPurchasedAt(?\DateTimeImmutable $purchasedAt): self
    {
        $this->purchasedAt = $purchasedAt;
        return $this;
    }

    public function getRefundRequests(): Collection
    {
        return $this->refundRequests;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function markAsUsed(): self
    {
        $this->usedAt = new \DateTimeImmutable();
        return $this;
    }
}
