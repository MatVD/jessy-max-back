<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\RefundRequestRepository;
use App\State\RefundRequestProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RefundRequestRepository::class)]
#[ORM\Table(name: 'refund_requests')]
#[ORM\Index(name: 'idx_refund_requests_ticket', columns: ['ticket_id'])]
#[ORM\Index(name: 'idx_refund_requests_status', columns: ['status'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['refund:read', 'refund:detail']],
            security: "is_granted('ROLE_ADMIN') or object.getTicket().getCustomerEmail() == user.getEmail()",
            securityMessage: 'Access denied.'
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['refund:read']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: 'Only admins can list all refund requests.'
        ),
        new Post(
            denormalizationContext: ['groups' => ['refund:write']],
            normalizationContext: ['groups' => ['refund:read', 'refund:detail']],
            processor: RefundRequestProcessor::class
        ),
        new Patch(
            denormalizationContext: ['groups' => ['refund:update']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: 'Only admins can update refund requests.'
        ),
    ],
    order: ['createdAt' => 'DESC'],
    paginationEnabled: true,
    paginationItemsPerPage: 30,
)]
#[ApiFilter(SearchFilter::class, properties: ['status' => 'exact'])]
class RefundRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['refund:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'refundRequests')]
    #[ORM\JoinColumn(name: 'ticket_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    #[Assert\NotNull(message: 'The ticket cannot be null.')]
    #[Groups(['refund:read', 'refund:write'])]
    private ?Ticket $ticket = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'The reason cannot be blank.')]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: 'The reason must be at least {{ limit }} characters long.',
        maxMessage: 'The reason cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['refund:read', 'refund:write', 'refund:detail'])]
    private ?string $reason = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['pending', 'approved', 'rejected'], message: 'Invalid status.')]
    #[Groups(['refund:read', 'refund:update'])]
    private string $status = 'pending';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['refund:read', 'refund:detail'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'processed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['refund:read', 'refund:update'])]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and Setters

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): static
    {
        $this->ticket = $ticket;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        // Automatiquement mettre à jour processedAt si le status change
        if ($status !== 'pending') {
            $this->processedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): static
    {
        $this->processedAt = $processedAt;
        return $this;
    }
}
