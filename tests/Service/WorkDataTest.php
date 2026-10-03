<?php

namespace Base\Scholar\Tests\Service;

use Base\Scholar\Service\WorkData;
use Base\Scholar\Tests\Fixtures\Works;
use PHPUnit\Framework\TestCase;

final class WorkDataTest extends TestCase
{
    public function testAWorkComesBackWhole(): void
    {
        $work = Works::acid();
        $back = WorkData::fromArray(json_decode(json_encode(WorkData::toArray($work)), true));

        $this->assertEquals($work, $back);
        $this->assertSame('0009-0005-1387-7295', $back->authors[1]->orcid());
        $this->assertSame('Chemical Science', $back->venue->name);
    }

    public function testABooksEditionsComeBackAndNameIt(): void
    {
        $book = WorkData::fromArray(WorkData::toArray(Works::seeds()));

        $this->assertCount(2, $book->editions);
        $this->assertSame(['isbn:9781452279701', 'isbn:9781412996600'], WorkData::keys($book));
        $this->assertSame('planting the seeds of algebra prek 2|2016', WorkData::fingerprint($book));
    }

    public function testTheAuthorsLine(): void
    {
        $this->assertSame('Lucas Chocron, Keitaro Nakatani', WorkData::authorsLine(Works::acid()));
        $this->assertSame('Lucas Chocron et al.', WorkData::authorsLine(Works::acid(), 1));
    }
}
