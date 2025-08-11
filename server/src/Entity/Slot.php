<?php
// src/Entity/Slot.php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(
    formats: ['jsonld', 'json'],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']]
)]
class Slot
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups(['read'])]
    private ?int $id = null;

    #[ORM\Column(type: "datetimetz")]  // <-- modifié ici pour gérer offset timezone
    #[Assert\NotNull(message: "La date et l'heure sont obligatoires.")]
    #[Groups(['read', 'write'])]
    private ?\DateTimeInterface $datetime = null;

    #[ORM\Column(type: "boolean")]
    #[Groups(['read', 'write'])]
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
