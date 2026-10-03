<?php

namespace Base\Scholar\Entity\Cv;

use Doctrine\ORM\Mapping as ORM;

/**
 * A grant or a funded project: its title, the funder (`organization`), its reference, the
 * scholar's role ("Principal investigator", "Partner"), its years, its amount as it should be printed.
 */
#[ORM\Entity]
#[ORM\Table(name: 'scholar_grant')]
#[ORM\Index(columns: ['visible', 'position'], name: 'scholar_grant_shown_idx')]
class Grant extends CvEntry
{
    #[ORM\Column(length: 120, nullable: true)]
    protected ?string $reference = null;

    /** "Principal investigator", "Partner". */
    #[ORM\Column(length: 120, nullable: true)]
    protected ?string $role = null;

    /** As it should be printed: "450 000 €", or nothing. */
    #[ORM\Column(length: 60, nullable: true)]
    protected ?string $amount = null;

    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $reference): static { $this->reference = $reference ?: null; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): static { $this->role = $role ?: null; return $this; }

    public function getAmount(): ?string { return $this->amount; }
    public function setAmount(?string $amount): static { $this->amount = $amount ?: null; return $this; }
}
