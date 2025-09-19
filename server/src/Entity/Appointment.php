<?php
// src/Entity/Appointment.php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(
    formats: ['jsonld', 'json'],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']],
    operations: [
        new GetCollection(),
        new Post(processor: 'appointment.processor'),
        new Delete(
            uriTemplate: '/appointments/cancel/{token}',
            uriVariables: [
                'token' => 'token'
            ],
            processor: \App\State\AppointmentCancellationProcessor::class,
            name: 'cancel_appointment',
            read: false
        ),
    ]
)]
class Appointment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups(['read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Slot::class)]
    #[Groups(['read','write'])]
    private ?Slot $slot = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['read','write'])]
    private string $name = '';

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['read','write'])]
    private string $email = '';

    #[ORM\Column(type: "boolean")]
    #[Groups(['read','write'])]
    private bool $confirmed = false;

    // --- Nouveaux champs ---
    #[ORM\Column(type: 'boolean')]
    #[Groups(['read'])]
    private bool $cancelled = false;

    #[ORM\Column(type: 'string', length: 64, nullable: true, unique: true)]
    private ?string $cancelToken = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        try {
            $this->cancelToken = bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            $this->cancelToken = uniqid('', true);
        }
    }

    public function getId(): ?int { return $this->id; }

    public function getSlot(): ?Slot { return $this->slot; }
    public function setSlot(?Slot $slot): self { $this->slot = $slot; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function isConfirmed(): bool { return $this->confirmed; }
    public function setConfirmed(bool $confirmed): self { $this->confirmed = $confirmed; return $this; }

    public function isCancelled(): bool { return $this->cancelled; }
    public function setCancelled(bool $cancelled): self { $this->cancelled = $cancelled; return $this; }

    public function getCancelToken(): ?string { return $this->cancelToken; }
    public function setCancelToken(?string $cancelToken): self { $this->cancelToken = $cancelToken; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
