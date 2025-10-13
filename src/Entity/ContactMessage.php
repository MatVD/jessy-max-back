<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\ContactMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ContactMessageRepository::class)]
#[ORM\Table(name: 'contact_messages')]
#[ORM\Index(name: 'idx_contact_messages_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_contact_messages_email', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['contact:read', 'contact:detail']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: 'Only admins can view contact messages.'
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['contact:read']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: 'Only admins can list contact messages.'
        ),
        new Post(
            denormalizationContext: ['groups' => ['contact:write']],
            normalizationContext: ['groups' => ['contact:read', 'contact:detail']]
        ),
    ],
    order: ['createdAt' => 'DESC'],
    paginationEnabled: true,
    paginationItemsPerPage: 30,
)]
class ContactMessage
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['contact:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The name cannot be blank.')]
    #[Assert\Length(max: 255, maxMessage: 'The name cannot be longer than {{ limit }} characters.')]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The email cannot be blank.')]
    #[Assert\Email(message: 'The email "{{ value }}" is not a valid email.')]
    #[Assert\Length(max: 255)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'The message cannot be blank.')]
    #[Assert\Length(
        min: 10,
        max: 5000,
        minMessage: 'The message must be at least {{ limit }} characters long.',
        maxMessage: 'The message cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['contact:read', 'contact:write', 'contact:detail'])]
    private ?string $message = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['contact:read', 'contact:detail'])]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
