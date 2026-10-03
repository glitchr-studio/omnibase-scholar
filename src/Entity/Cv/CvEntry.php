<?php

namespace Base\Scholar\Entity\Cv;

use Base\Scholar\Entity\Scholar;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A line of the CV: what (`title`), where (`organization`, `city`,
 * `country`), when (`start` and `end`, ISO 8601 as precise as known:
 * "2001", "2001-09"), a few words more, a link - and where it came from: typed
 * in the back office, or read from ORCID by scholar:sync (`origin`,
 * `externalKey`), in which case it waits hidden until it is checked.
 */
#[ORM\MappedSuperclass]
abstract class CvEntry
{
    public const ORIGIN_SITE = 'site';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\ManyToOne(targetEntity: Scholar::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?Scholar $scholar = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    protected ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $organization = null;

    #[ORM\Column(length: 120, nullable: true)]
    protected ?string $city = null;

    #[ORM\Column(length: 2, nullable: true)]
    protected ?string $country = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Regex('/^\d{4}(-\d{2}(-\d{2})?)?$/')]
    protected ?string $start = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Regex('/^\d{4}(-\d{2}(-\d{2})?)?$/')]
    protected ?string $end = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    protected ?string $url = null;

    #[ORM\Column(type: 'integer')]
    protected int $position = 0;

    #[ORM\Column(type: 'boolean')]
    protected bool $visible = true;

    #[ORM\Column(length: 32)]
    protected string $origin = self::ORIGIN_SITE;

    /** The record's key at its origin (ORCID's put-code...): read again, it is brought up to date, not doubled. */
    #[ORM\Column(length: 120, nullable: true)]
    protected ?string $externalKey = null;

    public function __construct(?Scholar $scholar = null, ?string $title = null)
    {
        $this->scholar = $scholar;
        $this->title = $title;
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }

    public function getId(): ?int { return $this->id; }

    public function getScholar(): ?Scholar { return $this->scholar; }
    public function setScholar(?Scholar $scholar): static { $this->scholar = $scholar; return $this; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): static { $this->title = $title ? trim($title) : null; return $this; }

    public function getOrganization(): ?string { return $this->organization; }
    public function setOrganization(?string $organization): static { $this->organization = $organization ? trim($organization) : null; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): static { $this->city = $city ?: null; return $this; }

    public function getCountry(): ?string { return $this->country; }
    public function setCountry(?string $country): static { $this->country = $country ? strtoupper(substr(trim($country), 0, 2)) : null; return $this; }

    public function getStart(): ?string { return $this->start; }
    public function setStart(int|string|null $start): static { $this->start = self::date($start); return $this; }

    public function getEnd(): ?string { return $this->end; }
    public function setEnd(int|string|null $end): static { $this->end = self::date($end); return $this; }

    public function getStartYear(): ?int { return $this->start ? (int) substr($this->start, 0, 4) : null; }
    public function getEndYear(): ?int { return $this->end ? (int) substr($this->end, 0, 4) : null; }

    /** Started and not ended. */
    public function isCurrent(): bool { return null !== $this->start && null === $this->end; }

    /** "2019–2023", "2019–" (current), "2019". */
    public function getPeriod(): ?string
    {
        $start = $this->getStartYear();
        $end = $this->getEndYear();
        if (!$start && !$end) {
            return null;
        }
        if ($start && $end) {
            return $start === $end ? (string) $start : $start.'–'.$end;
        }

        return $start ? $start.($this->openEnded() ? '–' : '') : (string) $end;
    }

    /** Whether a start without an end means "since": a position does, a degree does not. */
    protected function openEnded(): bool
    {
        return true;
    }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description ? trim($description) : null; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): static { $this->url = $url ?: null; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(?int $position): static { $this->position = (int) $position; return $this; }

    public function isVisible(): bool { return $this->visible; }
    public function setVisible(bool $visible): static { $this->visible = $visible; return $this; }

    public function getOrigin(): string { return $this->origin; }
    public function setOrigin(?string $origin): static { $this->origin = $origin ?: self::ORIGIN_SITE; return $this; }

    public function getExternalKey(): ?string { return $this->externalKey; }
    public function setExternalKey(?string $externalKey): static { $this->externalKey = $externalKey ?: null; return $this; }

    /** "2001", "2001-09", "2001-09-01" from what was typed or read ("2001-9", 2001...). */
    private static function date(int|string|null $value): ?string
    {
        $value = trim((string) $value);
        if ('' === $value) {
            return null;
        }
        if (preg_match('/^(\d{4})(?:-(\d{1,2})(?:-(\d{1,2}))?)?/', $value, $m)) {
            return $m[1].(isset($m[2]) ? sprintf('-%02d', $m[2]) : '').(isset($m[3]) ? sprintf('-%02d', $m[3]) : '');
        }

        return null;
    }
}
