<?php

namespace Base\Scholar\Entity\Cv;

use Doctrine\ORM\Mapping as ORM;

/**
 * A degree: its title ("PhD in Chemistry", "Habilitation"), where and when (`end`, or `start`
 * alone), its thesis, and who supervised it.
 */
#[ORM\Entity]
#[ORM\Table(name: 'scholar_degree')]
#[ORM\Index(columns: ['visible', 'position'], name: 'scholar_degree_shown_idx')]
class Degree extends CvEntry
{
    /** The thesis or the dissertation, when there was one. */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $thesis = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $advisor = null;

    public function getThesis(): ?string { return $this->thesis; }
    public function setThesis(?string $thesis): static { $this->thesis = $thesis ? trim($thesis) : null; return $this; }

    public function getAdvisor(): ?string { return $this->advisor; }
    public function setAdvisor(?string $advisor): static { $this->advisor = $advisor ?: null; return $this; }

    protected function openEnded(): bool
    {
        return false;
    }
}
