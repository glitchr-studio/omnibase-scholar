<?php

namespace Base\Scholar\Entity;

use Base\Database\Attribute\Uploader;
use Base\Scholar\Enum\PublicationStatus;
use Base\Scholar\Repository\PublicationRepository;
use Base\Scholar\Service\WorkData;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Omnischolar\Model\Identifiers;
use Omnischolar\Model\Venue;
use Omnischolar\Model\Work;
use Omnischolar\Model\WorkType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A work of the scholar's, as the site shows it: the glitchr/omnischolar
 * Work the sources gave, kept whole (`work`, JSON) and refreshed at each
 * sync, with a few columns copied out of it to sort and filter by; and what
 * the site adds, which no sync ever touches - hidden, pinned, in the
 * selection, a PDF of its own, a link to buy it or to the publisher's page,
 * a cover, a note, its themes, and its status (a new work waits to be
 * validated). A publication typed by hand (`manual`) is the site's alone:
 * its columns are the record.
 */
#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ORM\Table(name: 'scholar_publication')]
#[ORM\Index(columns: ['status', 'hidden', 'year'], name: 'scholar_publication_shown_idx')]
#[ORM\Index(columns: ['doi'], name: 'scholar_publication_doi_idx')]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\ManyToOne(targetEntity: Scholar::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?Scholar $scholar = null;

    /** The PublicationStatus's value: a string column, as omnibase's Uploader rebuilds an entity's previous state from raw values. */
    #[ORM\Column(type: 'string', length: 16)]
    protected string $status = 'pending';

    // --- what the sources say (the columns are copied from `work`) ------------

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    protected ?string $title = null;

    #[ORM\Column(length: 16)]
    protected string $type = 'other';

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $year = null;

    /** The journal, the conference, the book's series - or a book's publisher. */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $venue = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $authors = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $doi = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $citations = null;

    /** @var array<string, mixed>|null WorkData::toArray() of the merged record */
    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $work = null;

    /** @var list<string> every identifier key ever seen for it: how the next sync finds it again */
    #[ORM\Column(type: 'json')]
    protected array $matchKeys = [];

    /** Its title without accents nor punctuation, and its year (WorkData::fingerprint()). */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $fingerprint = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $fetchedAt = null;

    /** The last sync that found it: one that no longer does leaves it as it is. */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $lastSeenAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $createdAt;

    // --- what the site adds (never touched by a sync) --------------------------

    #[ORM\Column(type: 'boolean')]
    protected bool $manual = false;

    #[ORM\Column(type: 'boolean')]
    protected bool $hidden = false;

    /** At the top of the list. */
    #[ORM\Column(type: 'boolean')]
    protected bool $pinned = false;

    /** In the selection: the home page's few, the "selected publications" of a CV. */
    #[ORM\Column(type: 'boolean')]
    protected bool $selected = false;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Uploader(max_size: '64MB', mime_types: ['application/pdf'])]
    protected $pdf = null;

    /** A full text elsewhere, preferred to the sources' (HAL's deposit, arXiv's). */
    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(requireTld: true)]
    protected ?string $pdfUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(requireTld: true)]
    protected ?string $buyUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(requireTld: true)]
    protected ?string $publisherUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Uploader(max_size: '16MB', mime_types: ['image/*'])]
    protected $cover = null;

    /** A word from the scholar: what it is, why it matters, a correction. */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $note = null;

    /** @var Collection<int, Theme> */
    #[ORM\ManyToMany(targetEntity: Theme::class, inversedBy: 'publications')]
    #[ORM\JoinTable(name: 'scholar_publication_theme')]
    protected Collection $themes;

    public function __construct(?Scholar $scholar = null, ?Work $work = null)
    {
        $this->scholar = $scholar;
        $this->themes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        if ($work) {
            $this->refresh($work);
        }
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }

    /**
     * The sources' record, again: the columns follow it, nothing the site
     * added moves. Answers whether anything the page shows changed.
     */
    public function refresh(Work $work, ?\DateTimeImmutable $at = null): bool
    {
        $at ??= new \DateTimeImmutable();
        $this->matchKeys = array_values(array_unique([...$this->matchKeys, ...WorkData::keys($work)]));
        $this->fingerprint = WorkData::fingerprint($work);
        $this->lastSeenAt = $at;
        if ($this->manual) {
            return false;
        }

        $data = WorkData::toArray($work);
        $before = [$this->title, $this->type, $this->year, $this->venue, $this->authors, $this->doi, $this->citations, $this->work];
        $this->work = $data;
        $this->title = $work->fullTitle() ?: $this->title;
        $this->type = $work->type->value;
        $this->year = $work->year;
        $this->venue = self::venueOf($work);
        $this->authors = WorkData::authorsLine($work, 40) ?: null;
        $this->doi = $work->doi();
        $this->citations = $work->citations;
        $this->fetchedAt = $at;

        // Loosely: MySQL hands a JSON column back with its keys in its own order.
        return $before != [$this->title, $this->type, $this->year, $this->venue, $this->authors, $this->doi, $this->citations, $this->work];
    }

    private static function venueOf(Work $work): ?string
    {
        if ($work->venue && Venue::REPOSITORY !== $work->venue->type && '' !== $work->venue->name) {
            return mb_substr($work->venue->name, 0, 255);
        }

        return $work->publisher ? mb_substr($work->publisher, 0, 255) : null;
    }

    /**
     * The work as the site shows and exports it: the sources' record with
     * what the site changed - the columns of a manual one, its own PDF, the
     * publisher's page it gave.
     */
    public function toWork(): Work
    {
        $work = $this->work && !$this->manual ? WorkData::fromArray($this->work) : null;
        if (!$work) {
            $base = $this->work ? WorkData::fromArray($this->work) : null;
            $work = new Work(
                title: (string) $this->title,
                type: WorkType::tryFrom($this->type) ?? WorkType::OTHER,
                authors: $this->getAuthorList(),
                year: $this->year,
                date: $base?->date,
                venue: $this->venue && WorkType::BOOK !== WorkType::tryFrom($this->type) ? new Venue($this->venue) : null,
                publisher: WorkType::BOOK === WorkType::tryFrom($this->type) ? $this->venue : $base?->publisher,
                abstract: $base?->abstract,
                cover: $base?->cover,
                identifiers: $this->doi ? Identifiers::fromArray(['doi' => [$this->doi]])->merge($base?->identifiers ?? new Identifiers()) : ($base?->identifiers ?? new Identifiers()),
                source: 'site',
                sources: ['site'],
            );
        }
        $changes = array_filter([
            'pdfUrl' => $this->pdfUrl,
            'url' => $this->publisherUrl,
        ]);

        return $changes ? $work->with($changes) : $work;
    }

    /** @return list<\Omnischolar\Model\Contributor> the authors typed, one a comma */
    private function getAuthorList(): array
    {
        $names = array_filter(array_map('trim', explode(',', (string) preg_replace('/\s+et al\.?$/u', '', (string) $this->authors))));

        return array_values(array_map(static fn (string $n) => \Omnischolar\Model\Contributor::fromName($n), $names));
    }

    public function getId(): ?int { return $this->id; }

    public function getScholar(): ?Scholar { return $this->scholar; }
    public function setScholar(?Scholar $scholar): self { $this->scholar = $scholar; return $this; }

    public function getStatus(): PublicationStatus { return PublicationStatus::tryFrom($this->status) ?? PublicationStatus::PENDING; }
    public function setStatus(PublicationStatus|string $status): self { $this->status = ($status instanceof PublicationStatus ? $status : (PublicationStatus::tryFrom($status) ?? PublicationStatus::PENDING))->value; return $this; }
    public function isPending(): bool { return PublicationStatus::PENDING === $this->getStatus(); }
    public function isPublished(): bool { return PublicationStatus::PUBLISHED === $this->getStatus(); }
    public function approve(): self { return $this->setStatus(PublicationStatus::PUBLISHED); }
    public function reject(): self { return $this->setStatus(PublicationStatus::REJECTED); }
    /** The status as the back office's select reads and writes it. */
    public function getStatusValue(): string { return $this->status; }
    public function setStatusValue(?string $status): self { return $this->setStatus((string) $status); }

    /** On the site: validated and not hidden. */
    public function isShown(): bool { return $this->isPublished() && !$this->hidden; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): self { $this->title = $title ? trim($title) : null; return $this->fingerprinted(); }

    public function getType(): string { return $this->type; }
    public function setType(WorkType|string|null $type): self { $this->type = ($type instanceof WorkType ? $type : (WorkType::tryFrom((string) $type) ?? WorkType::OTHER))->value; return $this; }
    public function getWorkType(): WorkType { return WorkType::tryFrom($this->type) ?? WorkType::OTHER; }
    public function isBook(): bool { return WorkType::BOOK === $this->getWorkType(); }

    public function getYear(): ?int { return $this->year; }
    public function setYear(?int $year): self { $this->year = $year ?: null; return $this->fingerprinted(); }

    /** A record typed by hand is told by its title and year too, until a sync gives it identifiers. */
    private function fingerprinted(): self
    {
        if ($this->title && (null === $this->work || $this->manual)) {
            $this->fingerprint = WorkData::fingerprint(new Work(title: $this->title, year: $this->year));
        }

        return $this;
    }

    public function getVenue(): ?string { return $this->venue; }
    public function setVenue(?string $venue): self { $this->venue = $venue ? trim($venue) : null; return $this; }

    public function getAuthors(): ?string { return $this->authors; }
    public function setAuthors(?string $authors): self { $this->authors = $authors ? trim($authors) : null; return $this; }

    public function getDoi(): ?string { return $this->doi; }
    public function setDoi(?string $doi): self
    {
        $doi = $doi ? strtolower(trim((string) preg_replace('#^(?:https?://(?:dx\.)?doi\.org/|doi:)#i', '', trim($doi)))) : null;
        $this->doi = $doi ?: null;
        if ($this->doi && !\in_array('doi:'.$this->doi, $this->matchKeys, true)) {
            $this->matchKeys[] = 'doi:'.$this->doi;
        }

        return $this;
    }

    public function getCitations(): ?int { return $this->citations; }

    /** @return array<string, mixed>|null */
    public function getWork(): ?array { return $this->work; }

    /** The sources' abstract. */
    public function getAbstract(): ?string { return $this->work['abstract'] ?? null; }

    /** @return list<string> */
    public function getSources(): array { return $this->work['sources'] ?? ($this->manual ? ['site'] : []); }

    /** @return array<string, list<string>> identifiers by scheme */
    public function getIdentifiers(): array
    {
        $ids = $this->work['identifiers'] ?? [];
        if ($this->doi) {
            $ids['doi'] = array_values(array_unique([$this->doi, ...($ids['doi'] ?? [])]));
        }

        return $ids;
    }

    public function getHalId(): ?string { return $this->work['identifiers']['hal'][0] ?? null; }
    public function getArxivId(): ?string { return $this->work['identifiers']['arxiv'][0] ?? null; }

    /** @return list<string> ISBN-13s, the editions' included */
    public function getIsbns(): array
    {
        $isbns = $this->work['identifiers']['isbn'] ?? [];
        foreach ($this->work['editions'] ?? [] as $edition) {
            array_push($isbns, ...($edition['identifiers']['isbn'] ?? []));
        }

        return array_values(array_unique($isbns));
    }

    /** @return list<array{year: ?int, publisher: ?string, isbns: list<string>}> a book's editions, newest first */
    public function getEditions(): array
    {
        return array_map(static fn (array $e) => ['year' => $e['year'] ?? null, 'publisher' => $e['publisher'] ?? null, 'isbns' => $e['identifiers']['isbn'] ?? []], $this->work['editions'] ?? []);
    }

    /** @return list<string> */
    public function getMatchKeys(): array { return $this->matchKeys; }
    public function getFingerprint(): ?string { return $this->fingerprint; }
    public function getFetchedAt(): ?\DateTimeImmutable { return $this->fetchedAt; }
    public function getLastSeenAt(): ?\DateTimeImmutable { return $this->lastSeenAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isManual(): bool { return $this->manual; }
    public function setManual(bool $manual): self { $this->manual = $manual; return $this; }

    public function isHidden(): bool { return $this->hidden; }
    public function setHidden(bool $hidden): self { $this->hidden = $hidden; return $this; }

    public function isPinned(): bool { return $this->pinned; }
    public function setPinned(bool $pinned): self { $this->pinned = $pinned; return $this; }

    public function isSelected(): bool { return $this->selected; }
    public function setSelected(bool $selected): self { $this->selected = $selected; return $this; }

    public function getPdf(): ?string { return Uploader::getPublic($this, 'pdf'); }
    public function getPdfFile(): ?File { return Uploader::get($this, 'pdf'); }
    public function setPdf($pdf): self { $this->pdf = $pdf; return $this; }
    public function hasPdf(): bool { return null !== $this->pdf && '' !== $this->pdf; }

    public function getPdfUrl(): ?string { return $this->pdfUrl; }
    public function setPdfUrl(?string $pdfUrl): self { $this->pdfUrl = $pdfUrl ?: null; return $this; }

    public function getBuyUrl(): ?string { return $this->buyUrl; }
    public function setBuyUrl(?string $buyUrl): self { $this->buyUrl = $buyUrl ?: null; return $this; }

    public function getPublisherUrl(): ?string { return $this->publisherUrl; }
    public function setPublisherUrl(?string $publisherUrl): self { $this->publisherUrl = $publisherUrl ?: null; return $this; }

    public function getCover(): ?string { return Uploader::getPublic($this, 'cover'); }
    public function getCoverFile(): ?File { return Uploader::get($this, 'cover'); }
    public function setCover($cover): self { $this->cover = $cover; return $this; }
    public function hasCover(): bool { return null !== $this->cover && '' !== $this->cover; }

    /** The cover to show: the one uploaded, else the sources' (Open Library's). */
    public function getCoverUrl(): ?string
    {
        if ($this->hasCover()) {
            $path = (string) $this->getCover();
            $public = strpos($path, '/public/');

            return false !== $public ? substr($path, $public + \strlen('/public')) : $path;
        }

        return $this->work['cover'] ?? null;
    }

    /** The full text to offer: the PDF uploaded, the address given, else the sources'. */
    public function getFullTextUrl(): ?string
    {
        if ($this->hasPdf()) {
            $path = (string) $this->getPdf();
            $public = strpos($path, '/public/');

            return false !== $public ? substr($path, $public + \strlen('/public')) : $path;
        }

        return $this->pdfUrl ?? ($this->work['pdfUrl'] ?? null);
    }

    /** Where the work is read: the publisher's page given, else the DOI, else the sources' page. */
    public function getLink(): ?string
    {
        return $this->publisherUrl ?? ($this->doi ? 'https://doi.org/'.$this->doi : ($this->work['url'] ?? null));
    }

    public function isOpenAccess(): bool
    {
        return (bool) ($this->work['openAccess'] ?? false) || null !== $this->getFullTextUrl();
    }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): self { $this->note = $note ? trim($note) : null; return $this; }

    /** @return Collection<int, Theme> */
    public function getThemes(): Collection { return $this->themes; }
    public function addTheme(Theme $theme): self { if (!$this->themes->contains($theme)) { $this->themes->add($theme); } return $this; }
    public function removeTheme(Theme $theme): self { $this->themes->removeElement($theme); return $this; }

    /** The page's address piece: "acid-sensitive-photoswitches". */
    public function getSlug(): string
    {
        return (new AsciiSlugger())->slug((string) $this->title)->lower()->truncate(80, '', false)->trim('-')->toString() ?: 'publication';
    }
}
