<?php

namespace Base\Scholar\Tests\Entity;

use Base\Scholar\Entity\Cv\Supervision;
use Base\Scholar\Entity\Publication;
use Base\Scholar\Entity\Theme;
use Base\Scholar\Enum\PublicationStatus;
use Base\Scholar\Enum\SupervisionLevel;
use Base\Scholar\Enum\ThemeKind;
use Doctrine\ORM\Mapping as ORM;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A publication's status, a theme's kind and a supervision's level are PHP
 * enums on their string columns (Doctrine's enumType): the record holds the
 * case, a setter still takes the stored word, and a case names itself for
 * the back office's select.
 */
final class EnumFieldsTest extends TestCase
{
    public function testAPublicationHoldsItsStatus(): void
    {
        $publication = new Publication();
        $this->assertSame(PublicationStatus::PENDING, $publication->getStatus());
        $this->assertTrue($publication->isPending());

        $this->assertSame(PublicationStatus::PUBLISHED, $publication->approve()->getStatus());
        $this->assertTrue($publication->isPublished());
        $this->assertSame(PublicationStatus::REJECTED, $publication->setStatus('rejected')->getStatus(), 'the stored word is read as its case');
        $this->assertSame(PublicationStatus::PENDING, $publication->setStatus('nonsense')->getStatus(), 'a word that names no case: pending');
    }

    public function testAThemeHoldsItsKind(): void
    {
        $this->assertSame(ThemeKind::THEME, (new Theme('Photochromism'))->getKind());
        $project = new Theme('ANR', ThemeKind::PROJECT);
        $this->assertTrue($project->isProject());
        $this->assertSame(ThemeKind::THEME, $project->setKind('theme')->getKind());
    }

    public function testASupervisionHoldsItsLevel(): void
    {
        $supervision = new Supervision();
        $this->assertSame(SupervisionLevel::PHD, $supervision->getLevel());
        $this->assertSame(SupervisionLevel::MASTER, $supervision->setLevel('master')->getLevel());
    }

    public function testTheColumnsAreStringsOfTheSameLengthMappedToTheEnum(): void
    {
        foreach ([[Publication::class, 'status', PublicationStatus::class], [Theme::class, 'kind', ThemeKind::class], [Supervision::class, 'level', SupervisionLevel::class]] as [$class, $property, $enum]) {
            $column = (new \ReflectionProperty($class, $property))->getAttributes(ORM\Column::class)[0]->newInstance();
            $this->assertSame('string', $column->type, "$class::$property");
            $this->assertSame(16, $column->length, "$class::$property: the column it always had");
            $this->assertSame($enum, $column->enumType, "$class::$property");
        }
    }

    public function testACaseNamesItselfInTheScholarDomain(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())->method('trans')->with('publication.status.pending', [], 'scholar', 'fr')->willReturn('À valider');

        $this->assertInstanceOf(TranslatableInterface::class, PublicationStatus::PENDING);
        $this->assertSame('À valider', PublicationStatus::PENDING->trans($translator, 'fr'));
        $this->assertInstanceOf(TranslatableInterface::class, ThemeKind::PROJECT);
        $this->assertInstanceOf(TranslatableInterface::class, SupervisionLevel::POSTDOC);
    }
}
