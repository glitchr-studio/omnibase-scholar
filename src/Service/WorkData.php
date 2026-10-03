<?php

namespace Base\Scholar\Service;

use Omnischolar\Merger;
use Omnischolar\Model\Contributor;
use Omnischolar\Model\Identifiers;
use Omnischolar\Model\Venue;
use Omnischolar\Model\Work;
use Omnischolar\Model\WorkType;

/**
 * A glitchr/omnischolar Work as the database keeps it - plain arrays in a
 * JSON column - and back, every field included (authors with their
 * identifiers, the venue, a book's editions). What makes two records the
 * same work for the site (keys(), fingerprint()) is here too.
 */
final class WorkData
{
    /** @return array<string, mixed> */
    public static function toArray(Work $work): array
    {
        return array_filter([
            'title' => $work->title,
            'subtitle' => $work->subtitle,
            'type' => $work->type->value,
            'authors' => array_map(static fn (Contributor $c) => array_filter([
                'name' => $c->name,
                'given' => $c->given,
                'family' => $c->family,
                'identifiers' => $c->identifiers->toArray(),
                'role' => Contributor::AUTHOR !== $c->role ? $c->role : null,
                'affiliations' => $c->affiliations,
            ], static fn ($v) => null !== $v && [] !== $v), $work->authors),
            'year' => $work->year,
            'date' => $work->date,
            'venue' => $work->venue ? array_filter([
                'name' => $work->venue->name,
                'type' => $work->venue->type,
                'issn' => $work->venue->issn,
                'publisher' => $work->venue->publisher,
                'abbreviation' => $work->venue->abbreviation,
                'identifiers' => $work->venue->identifiers->toArray(),
                'url' => $work->venue->url,
            ], static fn ($v) => null !== $v && [] !== $v) : null,
            'publisher' => $work->publisher,
            'volume' => $work->volume,
            'issue' => $work->issue,
            'pages' => $work->pages,
            'edition' => $work->edition,
            'abstract' => $work->abstract,
            'language' => $work->language,
            'openAccess' => $work->openAccess,
            'url' => $work->url,
            'pdfUrl' => $work->pdfUrl,
            'license' => $work->license,
            'cover' => $work->cover,
            'citations' => $work->citations,
            'keywords' => $work->keywords,
            'domains' => $work->domains,
            'identifiers' => $work->identifiers->toArray(),
            'source' => $work->source,
            'sources' => $work->sources,
            'editions' => array_map([self::class, 'toArray'], $work->editions),
            'note' => $work->note,
        ], static fn ($v) => null !== $v && [] !== $v && '' !== $v);
    }

    /** @param array<string, mixed> $data what toArray() gave */
    public static function fromArray(array $data): Work
    {
        $venue = $data['venue'] ?? null;

        return new Work(
            title: (string) ($data['title'] ?? ''),
            type: WorkType::tryFrom((string) ($data['type'] ?? '')) ?? WorkType::OTHER,
            authors: array_map(static fn (array $c) => new Contributor(
                (string) ($c['name'] ?? ''),
                $c['given'] ?? null,
                $c['family'] ?? null,
                Identifiers::fromArray($c['identifiers'] ?? []),
                $c['role'] ?? Contributor::AUTHOR,
                $c['affiliations'] ?? [],
            ), $data['authors'] ?? []),
            year: isset($data['year']) ? (int) $data['year'] : null,
            date: $data['date'] ?? null,
            subtitle: $data['subtitle'] ?? null,
            venue: \is_array($venue) ? new Venue(
                (string) ($venue['name'] ?? ''),
                $venue['type'] ?? Venue::JOURNAL,
                $venue['issn'] ?? [],
                $venue['publisher'] ?? null,
                $venue['abbreviation'] ?? null,
                Identifiers::fromArray($venue['identifiers'] ?? []),
                $venue['url'] ?? null,
            ) : null,
            publisher: $data['publisher'] ?? null,
            volume: $data['volume'] ?? null,
            issue: $data['issue'] ?? null,
            pages: $data['pages'] ?? null,
            edition: $data['edition'] ?? null,
            abstract: $data['abstract'] ?? null,
            language: $data['language'] ?? null,
            openAccess: $data['openAccess'] ?? null,
            url: $data['url'] ?? null,
            pdfUrl: $data['pdfUrl'] ?? null,
            license: $data['license'] ?? null,
            cover: $data['cover'] ?? null,
            citations: isset($data['citations']) ? (int) $data['citations'] : null,
            keywords: $data['keywords'] ?? [],
            domains: $data['domains'] ?? [],
            identifiers: Identifiers::fromArray($data['identifiers'] ?? []),
            source: $data['source'] ?? null,
            sources: $data['sources'] ?? [],
            editions: array_map([self::class, 'fromArray'], $data['editions'] ?? []),
            note: $data['note'] ?? null,
        );
    }

    /**
     * What names the work for good: its identifiers ("doi:10.1039/d4sc04973j",
     * "hal:hal-04772417", "isbn:9781412996600"...), its editions' included.
     *
     * @return list<string>
     */
    public static function keys(Work $work): array
    {
        $keys = $work->identifiers->keys();
        foreach ($work->editions as $edition) {
            array_push($keys, ...$edition->identifiers->keys());
        }

        return array_values(array_unique($keys));
    }

    /** Its title without accents nor punctuation, and its year: the last resort to tell it again. */
    public static function fingerprint(Work $work): string
    {
        return Merger::fingerprint(Merger::mainTitle($work)).'|'.($work->year ?? '');
    }

    /** "Chocron, L.; Nakatani, K. et al.": the authors as a list prints them. */
    public static function authorsLine(Work $work, int $max = 8): string
    {
        $names = array_map(static fn (Contributor $c) => $c->name, $work->authorsOnly() ?: $work->authors);
        if (\count($names) > $max) {
            return implode(', ', \array_slice($names, 0, $max)).' et al.';
        }

        return implode(', ', $names);
    }
}
