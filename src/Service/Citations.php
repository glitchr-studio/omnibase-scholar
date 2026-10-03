<?php

namespace Base\Scholar\Service;

use Base\Scholar\Entity\Publication;
use Omnischolar\Export;
use Omnischolar\Model\Contributor;
use Omnischolar\Model\Work;
use Omnischolar\Model\WorkType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * How a publication is cited: a reference line to copy (authors, year,
 * title, where, volume, pages, DOI - the order the sciences read), and the
 * files a reference manager takes - BibTeX, RIS, CSL-JSON - written by
 * glitchr/omnischolar's Export from what the site shows (its overrides
 * included).
 */
final class Citations
{
    private readonly Export $export;

    public function __construct(#[Autowire('%scholar.abstracts_in_exports%')] bool $abstracts = false)
    {
        $this->export = new Export($abstracts);
    }

    /** "Chocron, L., Nakatani, K. et al. (2024). Acid-sensitive photoswitches. Chemical Science, 15(41), 17060–17071. https://doi.org/10.1039/d4sc04973j" */
    public function reference(Publication $publication): string
    {
        $work = $publication->toWork();
        $authors = array_map(static fn (Contributor $c) => $c->given ? $c->familyName().', '.self::initials($c->given) : $c->name, $work->authorsOnly() ?: $work->authors);
        $names = \count($authors) > 6 ? implode(', ', \array_slice($authors, 0, 6)).' et al.' : implode(', ', $authors);

        $where = [];
        if (WorkType::BOOK === $work->type) {
            $where[] = $work->publisher ?? $publication->getVenue();
        } elseif ($work->venue?->name) {
            $where[] = $work->venue->name.($work->volume ? ', '.$work->volume.($work->issue ? '('.$work->issue.')' : '') : '').($work->pages ? ', '.str_replace('-', '–', $work->pages) : '');
        } elseif ($publication->getVenue()) {
            $where[] = $publication->getVenue();
        }

        $line = trim(($names ? $names.' ' : '').'('.($work->year ?? 'n.d.').'). '.rtrim($work->fullTitle(), '.').'.');
        foreach (array_filter($where) as $part) {
            $line .= ' '.rtrim((string) $part, '.').'.';
        }
        if ($doi = $work->doi()) {
            $line .= ' https://doi.org/'.$doi;
        } elseif ($isbn = $work->isbns()[0] ?? null) {
            $line .= ' ISBN '.$isbn;
        }

        return $line;
    }

    /**
     * @param Publication|iterable<Publication> $publications
     * @param string                            $format       bibtex, ris, csl-json
     */
    public function export(string $format, Publication|iterable $publications): string
    {
        $works = array_map(static fn (Publication $p): Work => $p->toWork(), $publications instanceof Publication ? [$publications] : [...$publications]);

        return $this->export->format($format, $works);
    }

    public static function contentType(string $format): string
    {
        return Export::contentType($format);
    }

    /** "bib" for BibTeX, "ris", "json" for CSL-JSON: the file's extension. */
    public static function extension(string $format): string
    {
        return match ($format) {
            Export::BIBTEX => 'bib',
            Export::RIS => 'ris',
            default => 'json',
        };
    }

    /** "bib" => bibtex: a file's extension to its format, null if none. */
    public static function format(string $extension): ?string
    {
        return match ($extension) {
            'bib', 'bibtex' => Export::BIBTEX,
            'ris' => Export::RIS,
            'json', 'csl' => Export::CSL_JSON,
            default => null,
        };
    }

    private static function initials(string $given): string
    {
        return implode(' ', array_map(static fn (string $part) => implode('-', array_map(static fn (string $p) => mb_substr($p, 0, 1).'.', explode('-', $part))), preg_split('/\s+/u', trim($given)) ?: []));
    }
}
