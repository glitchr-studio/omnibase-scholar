<?php

namespace Base\Scholar\Tests\Fixtures;

use Omnischolar\Model\Contributor;
use Omnischolar\Model\Identifier;
use Omnischolar\Model\Identifiers;
use Omnischolar\Model\Venue;
use Omnischolar\Model\Work;
use Omnischolar\Model\WorkType;

/** Works as the sources give them: a 2024 article (OpenAlex + HAL), a book with two editions. */
final class Works
{
    public static function acid(?int $citations = 12, array $sources = ['openalex', 'hal']): Work
    {
        return new Work(
            title: 'Acid-sensitive photoswitches',
            type: WorkType::ARTICLE,
            authors: [
                new Contributor('Lucas Chocron', 'Lucas', 'Chocron'),
                new Contributor('Keitaro Nakatani', 'Keitaro', 'Nakatani', Identifiers::of(Identifier::of('orcid', '0009-0005-1387-7295'))),
            ],
            year: 2024,
            date: '2024-09-25',
            venue: new Venue('Chemical Science', Venue::JOURNAL, ['2041-6520'], 'Royal Society of Chemistry'),
            volume: '15',
            issue: '41',
            pages: '17060-17071',
            abstract: 'Photoswitches that answer to acid.',
            openAccess: true,
            pdfUrl: 'https://hal.science/hal-04772417/document',
            citations: $citations,
            identifiers: Identifiers::of(Identifier::parse('10.1039/D4SC04973J'), Identifier::of('hal', 'hal-04772417')),
            source: 'openalex',
            sources: $sources,
        );
    }

    public static function seeds(): Work
    {
        $edition2016 = new Work(title: 'Planting the Seeds of Algebra, PreK-2', type: WorkType::BOOK, authors: [Contributor::fromName('Monica Neagoy')], year: 2016, publisher: 'Corwin', identifiers: Identifiers::of(Identifier::parse('9781452279701')), source: 'openlibrary', sources: ['openlibrary']);
        $edition2012 = new Work(title: 'Planting the Seeds of Algebra, PreK-2', type: WorkType::BOOK, authors: [Contributor::fromName('Monica Neagoy')], year: 2012, publisher: 'Corwin', identifiers: Identifiers::of(Identifier::parse('9781412996600')), source: 'openlibrary', sources: ['openlibrary']);

        return $edition2016->with(['editions' => [$edition2016, $edition2012], 'cover' => 'https://covers.openlibrary.org/b/id/1-L.jpg']);
    }
}
