<?php

namespace Base\Scholar\Entity;

use Base\Database\Attribute\Uploader;
use Base\Scholar\Repository\ScholarRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The person the site is about: how they are named and titled, where they
 * work, their identifiers (ORCID, idHAL, OpenAlex), where their works are
 * read from (the profiles: an omnischolar source's name => the author
 * there), the pages that are theirs elsewhere (sameAs), a portrait and a
 * biography - both left empty until they are given: the pages then show a
 * marked draft, never an invented text.
 */
#[ORM\Entity(repositoryClass: ScholarRepository::class)]
#[ORM\Table(name: 'scholar_scholar')]
class Scholar
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 160)]
    protected ?string $name = null;

    #[ORM\Column(length: 80, nullable: true)]
    protected ?string $givenName = null;

    #[ORM\Column(length: 80, nullable: true)]
    protected ?string $familyName = null;

    /** "Dr.", "Pr" - printed before the name where the site wants it. */
    #[ORM\Column(length: 40, nullable: true)]
    protected ?string $honorific = null;

    /** "Professeur des universités", "International Mathematics Consultant". */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $jobTitle = null;

    /** "ENS Paris-Saclay, PPSM (CNRS UMR 8531)". */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $affiliation = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(requireTld: true)]
    protected ?string $affiliationUrl = null;

    #[ORM\Column(length: 19, nullable: true)]
    #[Assert\Regex('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/')]
    protected ?string $orcid = null;

    /**
     * Where the works are read from: a configured omnischolar source's name
     * => one or several authors there (an id, an ORCID, a name).
     *
     * @var array<string, list<string>>
     */
    #[ORM\Column(type: 'json')]
    protected array $profiles = [];

    /** @var list<string> the person's pages elsewhere: ORCID, HAL, OpenAlex, a lab's page, Wikipedia */
    #[ORM\Column(type: 'json')]
    protected array $sameAs = [];

    /** @var list<string> research interests */
    #[ORM\Column(type: 'json')]
    protected array $keywords = [];

    #[ORM\Column(type: 'text', nullable: true)]
    #[Uploader(max_size: '16MB', mime_types: ['image/*'])]
    protected $portrait = null;

    /** Plain text, a blank line between paragraphs. Empty: a marked draft is shown. */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $biography = null;

    /** A new work goes online at once instead of waiting to be validated. */
    #[ORM\Column(type: 'boolean')]
    protected bool $autoApprove = false;

    /** The works before that year are not read (a homonym's older works...). */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $fromYear = null;

    /** @var array<string, mixed>|null the counts of the metrics source: works, citations, hIndex, i10Index, source, at */
    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $metrics = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $lastSyncAt = null;

    /** @var array<string, mixed>|null what the last sync did (Model\SyncSummary::toArray()) */
    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $lastSync = null;

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): self { $this->name = $name ? trim($name) : null; return $this; }

    public function getGivenName(): ?string { return $this->givenName; }
    public function setGivenName(?string $givenName): self { $this->givenName = $givenName ?: null; return $this; }

    public function getFamilyName(): ?string { return $this->familyName; }
    public function setFamilyName(?string $familyName): self { $this->familyName = $familyName ?: null; return $this; }

    public function getHonorific(): ?string { return $this->honorific; }
    public function setHonorific(?string $honorific): self { $this->honorific = $honorific ?: null; return $this; }

    /** "Dr. Monica Neagoy". */
    public function getFullName(): string
    {
        return trim(($this->honorific ? $this->honorific.' ' : '').$this->name);
    }

    public function getJobTitle(): ?string { return $this->jobTitle; }
    public function setJobTitle(?string $jobTitle): self { $this->jobTitle = $jobTitle ?: null; return $this; }

    public function getAffiliation(): ?string { return $this->affiliation; }
    public function setAffiliation(?string $affiliation): self { $this->affiliation = $affiliation ?: null; return $this; }

    public function getAffiliationUrl(): ?string { return $this->affiliationUrl; }
    public function setAffiliationUrl(?string $affiliationUrl): self { $this->affiliationUrl = $affiliationUrl ?: null; return $this; }

    public function getOrcid(): ?string { return $this->orcid; }
    public function setOrcid(?string $orcid): self
    {
        $orcid = $orcid ? strtoupper(trim((string) preg_replace('#^https?://orcid\.org/#i', '', trim($orcid)))) : null;
        $this->orcid = $orcid ?: null;

        return $this;
    }

    /** @return array<string, list<string>> */
    public function getProfiles(): array { return $this->profiles; }

    /** @param array<string, list<string>|string> $profiles */
    public function setProfiles(array $profiles): self
    {
        $clean = [];
        foreach ($profiles as $source => $authors) {
            $authors = array_values(array_filter(array_map('trim', (array) $authors), 'strlen'));
            if ('' !== trim((string) $source) && $authors) {
                $clean[trim((string) $source)] = $authors;
            }
        }
        $this->profiles = $clean;

        return $this;
    }

    public function addProfile(string $source, string $author): self
    {
        $profiles = $this->profiles;
        $profiles[$source][] = $author;

        return $this->setProfiles(array_map('array_unique', $profiles));
    }

    /** The profiles as the back office types them: one "source: author" a line. */
    public function getProfilesText(): string
    {
        $lines = [];
        foreach ($this->profiles as $source => $authors) {
            foreach ($authors as $author) {
                $lines[] = $source.': '.$author;
            }
        }

        return implode("\n", $lines);
    }

    public function setProfilesText(?string $text): self
    {
        $profiles = [];
        foreach (preg_split('/\R/', (string) $text) ?: [] as $line) {
            if (str_contains($line, ':') && '' !== trim($line)) {
                [$source, $author] = array_map('trim', explode(':', $line, 2));
                $profiles[strtolower($source)][] = $author;
            }
        }

        return $this->setProfiles($profiles);
    }

    /** @return list<string> */
    public function getSameAs(): array { return $this->sameAs; }

    /** @param list<string> $sameAs */
    public function setSameAs(array $sameAs): self
    {
        $this->sameAs = array_values(array_unique(array_filter(array_map('trim', $sameAs), static fn (string $u) => (bool) preg_match('#^https?://#i', $u))));

        return $this;
    }

    public function getSameAsText(): string { return implode("\n", $this->sameAs); }
    public function setSameAsText(?string $text): self { return $this->setSameAs(preg_split('/\R/', (string) $text) ?: []); }

    /**
     * Every page that is this person's: the ones typed, and the profiles
     * read from that have an address (ORCID, OpenAlex, HAL's idHAL).
     *
     * @return list<string>
     */
    public function getIdentityUrls(): array
    {
        $urls = $this->sameAs;
        if ($this->orcid) {
            $urls[] = 'https://orcid.org/'.$this->orcid;
        }
        foreach ($this->profiles['openalex'] ?? [] as $id) {
            if (preg_match('/^A\d+$/i', $id)) {
                $urls[] = 'https://openalex.org/'.strtoupper($id);
            }
        }
        foreach ($this->profiles['hal'] ?? [] as $id) {
            if (preg_match('/^[a-z0-9][a-z0-9-]*-[a-z0-9-]+$/', $id) && !str_contains($id, ' ')) {
                $urls[] = 'https://cv.hal.science/'.$id;
            }
        }

        return array_values(array_unique($urls));
    }

    /** @return list<string> */
    public function getKeywords(): array { return $this->keywords; }
    /** @param list<string> $keywords */
    public function setKeywords(array $keywords): self { $this->keywords = array_values(array_unique(array_filter(array_map('trim', $keywords), 'strlen'))); return $this; }
    public function getKeywordsText(): string { return implode(', ', $this->keywords); }
    public function setKeywordsText(?string $text): self { return $this->setKeywords(preg_split('/\s*[,;\n]\s*/', (string) $text) ?: []); }

    public function getPortrait(): ?string { return Uploader::getPublic($this, 'portrait'); }
    public function getPortraitFile(): ?File { return Uploader::get($this, 'portrait'); }
    public function setPortrait($portrait): self { $this->portrait = $portrait; return $this; }
    public function hasPortrait(): bool { return null !== $this->portrait && '' !== $this->portrait; }

    /** The portrait's address on the site ("/uploads/…"), what an <img> takes. */
    public function getPortraitUrl(): ?string
    {
        $path = $this->hasPortrait() ? $this->getPortrait() : null;
        if (!\is_string($path) || '' === $path || preg_match('#^(?:https?:)?//#i', $path)) {
            return $path ?: null;
        }
        $public = strpos($path, '/public/');

        return false !== $public ? substr($path, $public + \strlen('/public')) : $path;
    }

    public function getBiography(): ?string { return $this->biography; }
    public function setBiography(?string $biography): self
    {
        $biography = null !== $biography ? trim(str_replace(["\r\n", "\r"], "\n", $biography)) : '';
        $this->biography = '' !== $biography ? $biography : null;

        return $this;
    }

    /** @return list<string> */
    public function getBiographyParagraphs(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', (string) $this->biography) ?: [])));
    }

    public function isAutoApprove(): bool { return $this->autoApprove; }
    public function setAutoApprove(bool $autoApprove): self { $this->autoApprove = $autoApprove; return $this; }

    public function getFromYear(): ?int { return $this->fromYear; }
    public function setFromYear(?int $fromYear): self { $this->fromYear = $fromYear ?: null; return $this; }

    /** @return array<string, mixed>|null */
    public function getMetrics(): ?array { return $this->metrics; }
    /** @param array<string, mixed>|null $metrics */
    public function setMetrics(?array $metrics): self { $this->metrics = $metrics; return $this; }

    public function getLastSyncAt(): ?\DateTimeImmutable { return $this->lastSyncAt; }
    /** @return array<string, mixed>|null */
    public function getLastSync(): ?array { return $this->lastSync; }

    /** @param array<string, mixed> $summary */
    public function rememberSync(array $summary, \DateTimeImmutable $at): self
    {
        $this->lastSync = $summary;
        $this->lastSyncAt = $at;

        return $this;
    }
}
