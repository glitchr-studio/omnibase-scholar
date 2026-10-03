<?php

namespace Base\Scholar\Service;

use Base\Scholar\Entity\Cv\Award;
use Base\Scholar\Entity\Cv\CvEntry;
use Base\Scholar\Entity\Cv\Degree;
use Base\Scholar\Entity\Cv\Position;
use Base\Scholar\Entity\Publication;
use Base\Scholar\Entity\Scholar;
use Base\Scholar\Enum\PublicationStatus;
use Base\Scholar\Model\SyncSummary;
use Base\Scholar\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Omnischolar\Collector;
use Omnischolar\Model\Affiliation;
use Omnischolar\Model\Work;
use Omnischolar\Registry;
use Omnischolar\Source\Query;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * A scholar's works read again from every source of their profiles
 * (glitchr/omnischolar's Collector: every page, merged), and laid over the
 * publications the site has: a work it does not know comes in waiting to
 * be validated (online at once if the scholar auto-approves), a known one
 * has its sources' record refreshed - and nothing the site added to it
 * moves (hidden, pinned, selection, PDF, links, cover, note, themes, and a
 * work typed by hand). A rejected work found again stays rejected. A work
 * the sources no longer give is left as it is. The counts (h-index...)
 * come from the metrics source, the CV lines from the CV source (ORCID),
 * hidden until checked.
 */
class Synchronizer
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PublicationRepository $publications,
        private readonly ?Registry $registry = null,
        private readonly ?Collector $collector = null,
        #[Autowire('%scholar.max_per_source%')] private readonly int $max = 2000,
        #[Autowire('%scholar.metrics_source%')] private readonly ?string $metricsSource = 'openalex',
        #[Autowire('%scholar.cv_source%')] private readonly ?string $cvSource = 'orcid',
    ) {
    }

    /** Whether omnischolar is there to be asked. */
    public function isAvailable(): bool
    {
        return null !== $this->registry && null !== $this->collector;
    }

    public function sync(Scholar $scholar, ?\DateTimeImmutable $at = null): SyncSummary
    {
        $at ??= new \DateTimeImmutable();
        $summary = new SyncSummary();
        if (!$this->isAvailable()) {
            $summary->errors[] = 'glitchr/omnischolar is not installed (Omnischolar\Bridge\Symfony\OmnischolarBundle).';
            $scholar->rememberSync($summary->toArray(), $at);
            $this->entityManager->flush();

            return $summary;
        }

        $profiles = [];
        foreach ($scholar->getProfiles() as $source => $authors) {
            if ($this->registry->has($source)) {
                $profiles[$source] = $authors;
            } else {
                $summary->errors[] = sprintf('%s: no such source in omnischolar.sources', $source);
            }
        }

        $works = [];
        if ($profiles) {
            try {
                $works = $this->collector->collect($profiles, new Query(from: $scholar->getFromYear(), limit: 100), $this->max);
                $summary->incomplete = $this->collector->incomplete;
            } catch (\Throwable $e) {
                $summary->errors[] = $e->getMessage();
            }
        }

        $this->merge($scholar, $works, $summary, $at);
        $this->metrics($scholar, $summary, $at);
        $this->curriculum($scholar, $summary);

        $scholar->rememberSync($summary->toArray(), $at);
        $this->entityManager->flush();

        return $summary;
    }

    /**
     * Works laid over the scholar's publications, as a sync does after
     * reading them (public: a test, an import of a file, call it directly).
     * Persisted, not flushed.
     *
     * @param iterable<Work> $works
     */
    public function merge(Scholar $scholar, iterable $works, ?SyncSummary $summary = null, ?\DateTimeImmutable $at = null): SyncSummary
    {
        $summary ??= new SyncSummary();
        $at ??= new \DateTimeImmutable();
        $index = new PublicationIndex(null !== $scholar->getId() ? $this->publications->findAllOf($scholar) : []);

        foreach ($works as $work) {
            ++$summary->read;
            $publication = $index->find($work);
            if (!$publication) {
                $publication = new Publication($scholar, $work);
                if ($scholar->isAutoApprove()) {
                    $publication->approve();
                }
                $this->entityManager->persist($publication);
                $index->add($publication);
                ++$summary->created;
                continue;
            }

            $changed = $publication->refresh($work, $at);
            $index->add($publication);
            if (PublicationStatus::REJECTED === $publication->getStatus()) {
                ++$summary->ignored;
            } elseif ($changed) {
                ++$summary->updated;
            } else {
                ++$summary->unchanged;
            }
        }

        return $summary;
    }

    /** The counts of the metrics source's profile (OpenAlex: works, citations, h-index, i10-index). */
    private function metrics(Scholar $scholar, SyncSummary $summary, \DateTimeImmutable $at): void
    {
        $source = $this->metricsSource;
        $id = $source ? ($scholar->getProfiles()[$source][0] ?? null) : null;
        if (!$id || !$this->registry->has($source)) {
            return;
        }
        try {
            $metrics = $this->registry->get($source)->author($id)?->metrics;
        } catch (\Throwable $e) {
            $summary->incomplete[$source.' (metrics)'] = $e->getMessage();

            return;
        }
        if ($metrics) {
            $scholar->setMetrics(array_filter([
                'works' => $metrics->works,
                'citations' => $metrics->citations,
                'hIndex' => $metrics->hIndex,
                'i10Index' => $metrics->i10Index,
                'source' => $source,
                'at' => $at->format(\DATE_ATOM),
            ], static fn ($v) => null !== $v));
        }
    }

    /**
     * The CV lines the CV source knows (ORCID: employments, education,
     * distinctions...), each once: a line read again is brought up to
     * date, never doubled; a new one waits hidden until it is checked.
     */
    private function curriculum(Scholar $scholar, SyncSummary $summary): void
    {
        $source = $this->cvSource;
        $id = $source ? ($scholar->getProfiles()[$source][0] ?? ('orcid' === $source ? $scholar->getOrcid() : null)) : null;
        if (!$id || !$this->registry->has($source)) {
            return;
        }
        try {
            $author = $this->registry->get($source)->author($id);
        } catch (\Throwable $e) {
            $summary->incomplete[$source.' (cv)'] = $e->getMessage();

            return;
        }

        foreach ($author?->affiliations ?? [] as $affiliation) {
            $class = match ($affiliation->kind) {
                Affiliation::EMPLOYMENT, Affiliation::INVITED_POSITION, Affiliation::SERVICE => Position::class,
                Affiliation::EDUCATION, Affiliation::QUALIFICATION => Degree::class,
                Affiliation::DISTINCTION => Award::class,
                default => null,
            };
            if (!$class) {
                continue;
            }
            $key = substr(sha1(implode('|', [$affiliation->kind, $affiliation->organization, $affiliation->role, $affiliation->start])), 0, 40);
            $entry = null !== $scholar->getId() ? $this->entityManager->getRepository($class)->findOneBy(['scholar' => $scholar, 'origin' => $source, 'externalKey' => $key]) : null;
            if (!$entry) {
                /** @var CvEntry $entry */
                $entry = new $class($scholar);
                $entry->setOrigin($source)->setExternalKey($key)->setVisible(false);
                $this->entityManager->persist($entry);
                ++$summary->cv;
            }
            $entry->setTitle($affiliation->role ?: $affiliation->organization)
                ->setOrganization($affiliation->role ? $affiliation->organization : null)
                ->setCity($affiliation->city)
                ->setCountry($affiliation->country)
                ->setStart($affiliation->start)
                ->setEnd($affiliation->end)
                ->setUrl($affiliation->url);
            if ($entry instanceof Position && $affiliation->department) {
                $entry->setDepartment($affiliation->department);
            }
        }
    }
}
