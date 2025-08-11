<?php
// src/Entity/Appointment.php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    formats: ['jsonld', 'json'],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']]
)]
class Appointment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Slot::class)]
    private ?Slot $slot = null;

    #[ORM\Column]
    private string $name;

    #[ORM\Column]
    private string $email;

    #[ORM\Column(type: "boolean")]
    private bool $confirmed = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlot(): ?Slot
    {
        return $this->slot;
    }

    public function setSlot(?Slot $slot): self
    {
        $this->slot = $slot;
        return $this;
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): self
    {
        $this->confirmed = $confirmed;
        return $this;
    }
}
