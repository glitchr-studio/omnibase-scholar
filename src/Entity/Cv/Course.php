<?php

namespace Base\Scholar\Entity\Cv;

use Doctrine\ORM\Mapping as ORM;

/**
 * A course taught: its title, where (`organization`), the level ("L3", "Master 2", "teacher
 * training"), its years, its volume, a link to its material.
 */
#[ORM\Entity]
#[ORM\Table(name: 'scholar_course')]
#[ORM\Index(columns: ['visible', 'position'], name: 'scholar_course_shown_idx')]
class Course extends CvEntry
{
    /** "L3", "M2", "Grades 3-5 teachers". */
    #[ORM\Column(length: 120, nullable: true)]
    protected ?string $level = null;

    /** "24 h", "a semester". */
    #[ORM\Column(length: 60, nullable: true)]
    protected ?string $volume = null;

    public function getLevel(): ?string { return $this->level; }
    public function setLevel(?string $level): static { $this->level = $level ?: null; return $this; }

    public function getVolume(): ?string { return $this->volume; }
    public function setVolume(?string $volume): static { $this->volume = $volume ?: null; return $this; }
}
