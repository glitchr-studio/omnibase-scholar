<?php

namespace Base\Scholar\Service;

use Base\Scholar\Entity\Publication;
use Base\Scholar\Entity\Scholar;
use Omnischolar\Model\Contributor;
use Omnischolar\Model\WorkType;

/**
 * The scholar and their works as schema.org reads them: a Person (name,
 * title, affiliation, the pages that are theirs - ORCID, OpenAlex, HAL -
 * as sameAs, research interests as knowsAbout), a ScholarlyArticle, a
 * Book (its ISBNs, its publisher) or a Chapter for each publication.
 */
final class JsonLd
{
    /** @return array<string, mixed> */
    public function person(Scholar $scholar, ?string $url = null, ?string $image = null): array
    {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $scholar->getName(),
            'givenName' => $scholar->getGivenName(),
            'familyName' => $scholar->getFamilyName(),
            'honorificPrefix' => $scholar->getHonorific(),
            'jobTitle' => $scholar->getJobTitle(),
            'affiliation' => $scholar->getAffiliation() ? self::clean(['@type' => 'Organization', 'name' => $scholar->getAffiliation(), 'url' => $scholar->getAffiliationUrl()]) : null,
            'url' => $url,
            'image' => $image,
            'identifier' => $scholar->getOrcid() ? ['@type' => 'PropertyValue', 'propertyID' => 'ORCID', 'value' => $scholar->getOrcid()] : null,
            'sameAs' => $scholar->getIdentityUrls(),
            'knowsAbout' => $scholar->getKeywords(),
        ]);
    }

    /** @return array<string, mixed> */
    public function publication(Publication $publication, ?string $url = null, ?Scholar $scholar = null): array
    {
        $work = $publication->toWork();
        $type = match ($work->type) {
            WorkType::BOOK => 'Book',
            WorkType::CHAPTER => 'Chapter',
            WorkType::THESIS => 'Thesis',
            WorkType::REPORT => 'Report',
            WorkType::DATASET => 'Dataset',
            WorkType::SOFTWARE => 'SoftwareSourceCode',
            default => 'ScholarlyArticle',
        };
        $orcid = $scholar?->getOrcid();
        $authors = array_map(static function (Contributor $c) use ($scholar, $orcid) {
            $self = $scholar && ($c->orcid() && $c->orcid() === $orcid || $c->familyName() === $scholar->getFamilyName());

            return self::clean([
                '@type' => 'Person',
                'name' => $c->name,
                'sameAs' => $c->orcid() ? 'https://orcid.org/'.$c->orcid() : ($self && $orcid ? 'https://orcid.org/'.$orcid : null),
            ]);
        }, $work->authorsOnly() ?: $work->authors);

        $data = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $work->fullTitle(),
            'headline' => 'ScholarlyArticle' === $type ? mb_substr($work->fullTitle(), 0, 110) : null,
            'author' => $authors ?: null,
            'datePublished' => $work->date ?? ($work->year ? (string) $work->year : null),
            'abstract' => $work->abstract,
            'url' => $url,
            'sameAs' => array_values(array_filter([$work->doi() ? 'https://doi.org/'.$work->doi() : null, $publication->getLink()])),
            'identifier' => $work->doi() ? ['@type' => 'PropertyValue', 'propertyID' => 'DOI', 'value' => $work->doi()] : null,
            'isAccessibleForFree' => $publication->isOpenAccess() ?: null,
            'image' => $publication->getCoverUrl(),
            'keywords' => $work->keywords ? implode(', ', $work->keywords) : null,
            'inLanguage' => $work->language,
        ];
        if ('Book' === $type) {
            $data['isbn'] = $publication->getIsbns() ?: null;
            $data['publisher'] = $work->publisher ? ['@type' => 'Organization', 'name' => $work->publisher] : null;
            $data['bookEdition'] = $work->edition;
            $data['offers'] = $publication->getBuyUrl() ? ['@type' => 'Offer', 'url' => $publication->getBuyUrl()] : null;
        } elseif ($work->venue?->name) {
            $data['isPartOf'] = self::clean([
                '@type' => 'conference' === $work->venue->type ? 'Event' : ('book' === $work->venue->type ? 'Book' : 'Periodical'),
                'name' => $work->venue->name,
                'issn' => $work->venue->issn ?: null,
                'publisher' => $work->venue->publisher ? ['@type' => 'Organization', 'name' => $work->venue->publisher] : null,
            ]);
            $data['volumeNumber'] = $work->volume;
            $data['issueNumber'] = $work->issue;
            $data['pagination'] = $work->pages;
        }

        return self::clean($data);
    }

    /**
     * @param list<Publication> $publications
     *
     * @return array<string, mixed>
     */
    public function list(array $publications, string $name, ?Scholar $scholar = null): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $name,
            'numberOfItems' => \count($publications),
            'itemListElement' => array_map(fn (Publication $p, int $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'item' => array_diff_key($this->publication($p, null, $scholar), ['@context' => true])], $publications, array_keys($publications)),
        ];
    }

    /** @param array<string, mixed> $data */
    private static function clean(array $data): array
    {
        return array_filter($data, static fn ($v) => null !== $v && [] !== $v && '' !== $v);
    }
}
