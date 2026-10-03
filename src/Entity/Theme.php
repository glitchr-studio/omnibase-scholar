<?php

namespace Base\Scholar\Entity;

use Base\Database\Attribute\Uploader;
use Base\Scholar\Enum\ThemeKind;
use Base\Scholar\Repository\ThemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A line of research ("Photochromism and fluorescence switching") or a
 * project (with its years, its funder, its page): a page of its own on the
 * site, and the publications filed under it.
 */
#[ORM\Entity(repositoryClass: ThemeRepository::class)]
#[ORM\Table(name: 'scholar_theme')]
#[UniqueEntity(fields: ['slug'])]
class Theme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    /** The ThemeKind's value: a string column, as omnibase's Uploader rebuilds an entity's previous state from raw values. */
    #[ORM\Column(type: 'string', length: 16)]
    protected string $kind = 'theme';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    protected ?string $title = null;

    #[ORM\Column(length: 120, unique: true)]
    protected ?string $slug = null;

    /** A sentence or two: the card and the page's description. */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $summary = null;

    /** Plain text, a blank line between paragraphs. */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Uploader(max_size: '16MB', mime_types: ['image/*'])]
    protected $image = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $startYear = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $endYear = null;

    /** A project's funder: "ANR", "ERC", "Région Île-de-France". */
    #[ORM\Column(length: 160, nullable: true)]
    protected ?string $funder = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(requireTld: true)]
    protected ?string $url = null;

    #[ORM\Column(type: 'integer')]
    protected int $position = 0;

    #[ORM\Column(type: 'boolean')]
    protected bool $visible = true;

    /** @var Collection<int, Publication> */
    #[ORM\ManyToMany(targetEntity: Publication::class, mappedBy: 'themes')]
    protected Collection $publications;

    public function __construct(?string $title = null, ThemeKind $kind = ThemeKind::THEME)
    {
        $this->publications = new ArrayCollection();
        $this->kind = $kind->value;
        $this->setTitle($title);
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }

    public function getId(): ?int { return $this->id; }

    public function getKind(): ThemeKind { return ThemeKind::tryFrom($this->kind) ?? ThemeKind::THEME; }
    public function setKind(ThemeKind|string $kind): self { $this->kind = ($kind instanceof ThemeKind ? $kind : (ThemeKind::tryFrom($kind) ?? ThemeKind::THEME))->value; return $this; }
    public function isProject(): bool { return ThemeKind::PROJECT === $this->getKind(); }
    /** The kind as the back office's select reads and writes it. */
    public function getKindValue(): string { return $this->kind; }
    public function setKindValue(?string $kind): self { return $this->setKind((string) $kind); }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): self
    {
        $this->title = $title ? trim($title) : null;
        if (null === $this->slug && $this->title) {
            $this->slug = (new AsciiSlugger())->slug($this->title)->lower()->truncate(120)->toString();
        }

        return $this;
    }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(?string $slug): self { $this->slug = $slug ? (new AsciiSlugger())->slug($slug)->lower()->truncate(120)->toString() : $this->slug; return $this; }

    public function getSummary(): ?string { return $this->summary; }
    public function setSummary(?string $summary): self { $this->summary = $summary ? trim($summary) : null; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description ? trim(str_replace(["\r\n", "\r"], "\n", $description)) : null; return $this; }

    /** @return list<string> */
    public function getParagraphs(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', (string) $this->description) ?: [])));
    }

    public function getImage(): ?string { return Uploader::getPublic($this, 'image'); }
    public function getImageFile(): ?File { return Uploader::get($this, 'image'); }
    public function setImage($image): self { $this->image = $image; return $this; }

    /** The picture's address on the site ("/uploads/…"). */
    public function getImageUrl(): ?string
    {
        $path = null !== $this->image && '' !== $this->image ? $this->getImage() : null;
        if (!\is_string($path) || '' === $path || preg_match('#^(?:https?:)?//#i', $path)) {
            return $path ?: null;
        }
        $public = strpos($path, '/public/');

        return false !== $public ? substr($path, $public + \strlen('/public')) : $path;
    }

    public function getStartYear(): ?int { return $this->startYear; }
    public function setStartYear(?int $startYear): self { $this->startYear = $startYear ?: null; return $this; }

    public function getEndYear(): ?int { return $this->endYear; }
    public function setEndYear(?int $endYear): self { $this->endYear = $endYear ?: null; return $this; }

    /** "2019–2023", "since 2021". */
    public function getYears(): ?string
    {
        if (!$this->startYear && !$this->endYear) {
            return null;
        }

        return $this->startYear && $this->endYear ? $this->startYear.'–'.$this->endYear : (string) ($this->startYear ?? $this->endYear);
    }

    public function getFunder(): ?string { return $this->funder; }
    public function setFunder(?string $funder): self { $this->funder = $funder ?: null; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): self { $this->url = $url ?: null; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(?int $position): self { $this->position = (int) $position; return $this; }

    public function isVisible(): bool { return $this->visible; }
    public function setVisible(bool $visible): self { $this->visible = $visible; return $this; }

    /** @return Collection<int, Publication> */
    public function getPublications(): Collection { return $this->publications; }
}
