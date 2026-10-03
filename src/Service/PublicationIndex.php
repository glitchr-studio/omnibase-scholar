<?php

namespace Base\Scholar\Service;

use Base\Scholar\Entity\Publication;
use Omnischolar\Model\Work;

/**
 * The scholar's publications, findable by what names a work: any
 * identifier ever seen for it (a DOI, a HAL id, an ISBN of any edition),
 * else its title and year - unless both carry a DOI and they differ (two
 * versions of a dataset, an erratum).
 */
final class PublicationIndex
{
    /** @var array<string, Publication> */
    private array $byKey = [];

    /** @var array<string, Publication> */
    private array $byFingerprint = [];

    /** @param iterable<Publication> $publications */
    public function __construct(iterable $publications = [])
    {
        foreach ($publications as $publication) {
            $this->add($publication);
        }
    }

    public function add(Publication $publication): void
    {
        foreach ($publication->getMatchKeys() as $key) {
            $this->byKey[$key] ??= $publication;
        }
        if ($fingerprint = $publication->getFingerprint()) {
            $this->byFingerprint[$fingerprint] ??= $publication;
        }
    }

    public function find(Work $work): ?Publication
    {
        foreach (WorkData::keys($work) as $key) {
            if (isset($this->byKey[$key])) {
                return $this->byKey[$key];
            }
        }

        $candidate = $this->byFingerprint[WorkData::fingerprint($work)] ?? null;
        if ($candidate && $candidate->getDoi() && $work->doi() && $candidate->getDoi() !== $work->doi()) {
            return null;
        }

        return $candidate;
    }
}
