<?php

namespace Base\Scholar\Tests\Service;

use Base\Scholar\Entity\Publication;
use Base\Scholar\Entity\Scholar;
use Base\Scholar\Enum\PublicationStatus;
use Base\Scholar\Repository\PublicationRepository;
use Base\Scholar\Service\Synchronizer;
use Base\Scholar\Tests\Fixtures\Works;
use Doctrine\ORM\EntityManagerInterface;
use Omnischolar\Model\Identifier;
use Omnischolar\Model\Identifiers;
use Omnischolar\Model\Work;
use Omnischolar\Model\WorkType;
use PHPUnit\Framework\TestCase;

/**
 * A sync laid over what the site has: new works wait, known ones follow
 * their sources, and what the site added survives.
 */
final class SynchronizerTest extends TestCase
{
    /** @var list<Publication> */
    private array $stored = [];

    private function synchronizer(): Synchronizer
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $o) { if ($o instanceof Publication) { $this->stored[] = $o; } });
        $repository = $this->createStub(PublicationRepository::class);
        $repository->method('findAllOf')->willReturnCallback(fn () => $this->stored);

        return new Synchronizer($em, $repository);
    }

    private function scholar(): Scholar
    {
        $scholar = new Scholar('Keitaro Nakatani');
        (new \ReflectionProperty(Scholar::class, 'id'))->setValue($scholar, 1);

        return $scholar;
    }

    public function testANewWorkWaitsToBeValidated(): void
    {
        $summary = $this->synchronizer()->merge($this->scholar(), [Works::acid()]);

        $this->assertSame(1, $summary->created);
        $this->assertCount(1, $this->stored);
        $this->assertSame(PublicationStatus::PENDING, $this->stored[0]->getStatus());
        $this->assertFalse($this->stored[0]->isShown());
        $this->assertSame('10.1039/d4sc04973j', $this->stored[0]->getDoi());
        $this->assertSame('Chemical Science', $this->stored[0]->getVenue());
    }

    public function testTheOverridesSurviveTheNextSync(): void
    {
        $sync = $this->synchronizer();
        $scholar = $this->scholar();
        $sync->merge($scholar, [Works::acid(citations: 12)]);
        $publication = $this->stored[0];
        $publication->approve()->setPinned(true)->setSelected(true)->setNote('Cover of the issue.')
            ->setPdfUrl('https://example.org/acid.pdf')->setBuyUrl('https://example.org/buy');

        // A week later: more citations, the record now also from Crossref.
        $summary = $sync->merge($scholar, [Works::acid(citations: 20, sources: ['crossref', 'openalex', 'hal'])]);

        $this->assertSame(0, $summary->created);
        $this->assertSame(1, $summary->updated);
        $this->assertCount(1, $this->stored, 'found again, not doubled');
        $this->assertSame(20, $publication->getCitations());
        $this->assertSame(['crossref', 'openalex', 'hal'], $publication->getSources());
        $this->assertTrue($publication->isPublished());
        $this->assertTrue($publication->isPinned());
        $this->assertTrue($publication->isSelected());
        $this->assertSame('Cover of the issue.', $publication->getNote());
        $this->assertSame('https://example.org/acid.pdf', $publication->getFullTextUrl());
        $this->assertSame('https://example.org/acid.pdf', $publication->toWork()->pdfUrl);
    }

    public function testFoundAgainByAnyIdentifierEverSeen(): void
    {
        $sync = $this->synchronizer();
        $scholar = $this->scholar();
        // First only HAL knew it (no DOI yet)...
        $sync->merge($scholar, [new Work(title: 'Acid-sensitive photoswitches', type: WorkType::ARTICLE, year: 2024, identifiers: Identifiers::of(Identifier::of('hal', 'hal-04772417')), sources: ['hal'])]);
        // ...then the published record, with its DOI and the same HAL id.
        $summary = $sync->merge($scholar, [Works::acid()]);

        $this->assertSame(1, $summary->updated);
        $this->assertCount(1, $this->stored);
        $this->assertContains('doi:10.1039/d4sc04973j', $this->stored[0]->getMatchKeys());
    }

    public function testARejectedWorkStaysRejectedAndAManualOneIsTheSites(): void
    {
        $sync = $this->synchronizer();
        $scholar = $this->scholar();
        $sync->merge($scholar, [Works::acid()]);
        $this->stored[0]->reject();
        $manual = (new Publication($scholar))->setManual(true)->setTitle('Planting the Seeds of Algebra, PreK-2')->setType('book')->setYear(2016)->setVenue('Corwin')->approve();
        $this->stored[] = $manual;

        $summary = $sync->merge($scholar, [Works::acid(), Works::seeds()]);

        $this->assertSame(1, $summary->ignored);
        $this->assertSame(PublicationStatus::REJECTED, $this->stored[0]->getStatus());
        $this->assertCount(2, $this->stored, 'the book typed by hand is found by its title and year');
        $this->assertSame('Corwin', $manual->getVenue());
        $this->assertNull($manual->getWork(), 'a manual record keeps its own columns');
        $this->assertContains('isbn:9781412996600', $manual->getMatchKeys());
    }

    public function testAnAutoApprovingScholarsWorksGoOnlineAtOnce(): void
    {
        $scholar = $this->scholar()->setAutoApprove(true);
        $this->synchronizer()->merge($scholar, [Works::seeds()]);

        $this->assertTrue($this->stored[0]->isShown());
        $this->assertTrue($this->stored[0]->isBook());
        $this->assertCount(2, $this->stored[0]->getEditions());
        $this->assertSame(['9781452279701', '9781412996600'], $this->stored[0]->getIsbns());
        $this->assertSame('https://covers.openlibrary.org/b/id/1-L.jpg', $this->stored[0]->getCoverUrl());
    }
}
