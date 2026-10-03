<?php

namespace Base\Scholar\Entity\Cv;

use Doctrine\ORM\Mapping as ORM;

/**
 * A position held: "Professeur des universités" at "ENS Paris-Saclay", since 2015 - the title,
 * the organization, the department or the laboratory.
 */
#[ORM\Entity]
#[ORM\Table(name: 'scholar_position')]
#[ORM\Index(columns: ['visible', 'position'], name: 'scholar_position_shown_idx')]
class Position extends CvEntry
{
    /** The department or the laboratory: "PPSM, CNRS UMR 8531". */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $department = null;

    public function getDepartment(): ?string { return $this->department; }
    public function setDepartment(?string $department): static { $this->department = $department ?: null; return $this; }
}
