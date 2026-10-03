<?php

namespace Base\Scholar\Tests\Service;

use Base\Scholar\Entity\Publication;
use Base\Scholar\Entity\Scholar;
use Base\Scholar\Service\Citations;
use Base\Scholar\Service\JsonLd;
use Base\Scholar\Tests\Fixtures\Works;
use PHPUnit\Framework\TestCase;

final class CitationsTest extends TestCase
{
    public function testTheReferenceLine(): void
    {
        $publication = new Publication(new Scholar('Keitaro Nakatani'), Works::acid());

        $this->assertSame('Chocron, L., Nakatani, K. (2024). Acid-sensitive photoswitches. Chemical Science, 15(41), 17060–17071. https://doi.org/10.1039/d4sc04973j', (new Citations())->reference($publication));
    }

    public function testTheExportsCarryTheSitesOverrides(): void
    {
        $publication = (new Publication(new Scholar('Keitaro Nakatani'), Works::acid()))->setPublisherUrl('https://pubs.rsc.org/en/content/articlelanding/2024/sc/d4sc04973j');
        $citations = new Citations();

        $bibtex = $citations->export('bibtex', $publication);
        $this->assertStringStartsWith('@article{chocron2024acid,', $bibtex);
        $this->assertStringContainsString('doi = {10.1039/d4sc04973j}', $bibtex);
        $this->assertStringContainsString('pubs.rsc.org', $bibtex);
        $this->assertStringContainsString("TY  - JOUR\r\n", $citations->export('ris', [$publication]));
        $this->assertSame('bibtex', Citations::format('bib'));
        $this->assertNull(Citations::format('pdf'));
    }

    public function testTheJsonLd(): void
    {
        $scholar = (new Scholar('Keitaro Nakatani'))->setFamilyName('Nakatani')->setOrcid('https://orcid.org/0009-0005-1387-7295')
            ->setProfilesText("openalex: A5108007452\nhal: keitaro-nakatani\nhal: Keitaro Nakatani")->setAffiliation('ENS Paris-Saclay');
        $jsonLd = new JsonLd();

        $person = $jsonLd->person($scholar, 'https://example.org/cv');
        $this->assertSame('Person', $person['@type']);
        $this->assertSame(['https://orcid.org/0009-0005-1387-7295', 'https://openalex.org/A5108007452', 'https://cv.hal.science/keitaro-nakatani'], $person['sameAs']);
        $this->assertSame(['openalex' => ['A5108007452'], 'hal' => ['keitaro-nakatani', 'Keitaro Nakatani']], $scholar->getProfiles());

        $article = $jsonLd->publication(new Publication($scholar, Works::acid()), null, $scholar);
        $this->assertSame('ScholarlyArticle', $article['@type']);
        $this->assertSame('Periodical', $article['isPartOf']['@type']);
        $this->assertSame('https://orcid.org/0009-0005-1387-7295', $article['author'][1]['sameAs']);

        $book = $jsonLd->publication(new Publication($scholar, Works::seeds()));
        $this->assertSame('Book', $book['@type']);
        $this->assertSame(['9781452279701', '9781412996600'], $book['isbn']);
    }
}
