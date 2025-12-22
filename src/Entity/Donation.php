<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\DonationRepository;
use ApiPlatform\Metadata\Post;
use App\State\DonationCheckoutProcessor;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DonationRepository::class)]
#[ApiResource(
    operations: [
        new Post(processor: DonationCheckoutProcessor::class)
    ]
)]
class Donation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private ?string $donorName = null;

    #[ORM\Column(length: 255)]
    private ?string $donorEmail = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    private ?string $stripeCheckoutUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getDonorName(): ?string
    {
        return $this->donorName;
    }

    public function setDonorName(string $donorName): static
    {
        $this->donorName = $donorName;

        return $this;
    }

    public function getDonorEmail(): ?string
    {
        return $this->donorEmail;
    }

    public function setDonorEmail(string $donorEmail): static
    {
        $this->donorEmail = $donorEmail;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): static
    {
        $this->stripeSessionId = $stripeSessionId;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
