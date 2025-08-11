<?php
// src/Entity/Slot.php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    formats: ['jsonld', 'json'],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']]
)]
class Slot
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $datetime = null;

    #[ORM\Column(type: "boolean")]
    private bool $reserved = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatetime(): ?\DateTimeInterface
    {
        return $this->datetime;
    }

    public function setDatetime(\DateTimeInterface $datetime): self
    {
        $this->datetime = $datetime;
        return $this;
    }

    public function isReserved(): bool
    {
        return $this->reserved;
    }

    public function setReserved(bool $reserved): self
    {
        $this->reserved = $reserved;
        return $this;
    }
}
