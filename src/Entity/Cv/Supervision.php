<?php

namespace Base\Scholar\Entity\Cv;

use Base\Scholar\Enum\SupervisionLevel;
use Doctrine\ORM\Mapping as ORM;

/**
 * A supervision: who (`title` holds the student's name), at what level (PhD, master, post-doc),
 * the subject, with whom, and what became of it.
 */
#[ORM\Entity]
#[ORM\Table(name: 'scholar_supervision')]
#[ORM\Index(columns: ['visible', 'position'], name: 'scholar_supervision_shown_idx')]
class Supervision extends CvEntry
{
    #[ORM\Column(type: 'string', length: 16, enumType: SupervisionLevel::class)]
    protected SupervisionLevel $level = SupervisionLevel::PHD;

    /** The thesis' or the internship's subject. */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $subject = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $coSupervisors = null;

    /** What became of it: "defended in 2021", "now at CNRS". */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $outcome = null;

    public function getStudent(): ?string { return $this->title; }

    public function getLevel(): SupervisionLevel { return $this->level; }
    public function setLevel(SupervisionLevel|string $level): static { $this->level = $level instanceof SupervisionLevel ? $level : (SupervisionLevel::tryFrom($level) ?? SupervisionLevel::OTHER); return $this; }
    /** The level as the back office's select reads and writes it. */
    public function getLevelValue(): string { return $this->level->value; }
    public function setLevelValue(?string $level): static { return $this->setLevel((string) $level); }

    public function getSubject(): ?string { return $this->subject; }
    public function setSubject(?string $subject): static { $this->subject = $subject ? trim($subject) : null; return $this; }

    public function getCoSupervisors(): ?string { return $this->coSupervisors; }
    public function setCoSupervisors(?string $coSupervisors): static { $this->coSupervisors = $coSupervisors ?: null; return $this; }

    public function getOutcome(): ?string { return $this->outcome; }
    public function setOutcome(?string $outcome): static { $this->outcome = $outcome ?: null; return $this; }
}
