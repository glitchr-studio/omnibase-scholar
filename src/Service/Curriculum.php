<?php

namespace Base\Scholar\Service;

use Base\Scholar\Entity\Cv\Award;
use Base\Scholar\Entity\Cv\Course;
use Base\Scholar\Entity\Cv\CvEntry;
use Base\Scholar\Entity\Cv\Degree;
use Base\Scholar\Entity\Cv\Grant;
use Base\Scholar\Entity\Cv\Position;
use Base\Scholar\Entity\Cv\Supervision;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The CV's sections, each line checked (visible) and in order: the
 * position set by hand first, then the latest - a current one before
 * those that ended.
 */
final class Curriculum
{
    /** Section => entity, in the order a CV reads. */
    public const SECTIONS = [
        'positions' => Position::class,
        'degrees' => Degree::class,
        'awards' => Award::class,
        'grants' => Grant::class,
        'courses' => Course::class,
        'supervisions' => Supervision::class,
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param list<string>|null $sections the ones wanted (SECTIONS' keys)
     *
     * @return array<string, list<CvEntry>> the sections that have a line
     */
    public function sections(?array $sections = null): array
    {
        $out = [];
        foreach (self::SECTIONS as $section => $class) {
            if (null !== $sections && !\in_array($section, $sections, true)) {
                continue;
            }
            if ($entries = $this->entries($class)) {
                $out[$section] = $entries;
            }
        }

        return $out;
    }

    /**
     * @param class-string<CvEntry> $class
     *
     * @return list<CvEntry>
     */
    public function entries(string $class): array
    {
        try {
            $entries = $this->entityManager->getRepository($class)->findBy(['visible' => true]);
        } catch (\Throwable) {
            return []; // its table not migrated yet
        }
        usort($entries, static fn (CvEntry $a, CvEntry $b) => [$a->getPosition(), $b->isCurrent(), $b->getStart() ?? $b->getEnd() ?? '', $a->getId()]
            <=> [$b->getPosition(), $a->isCurrent(), $a->getStart() ?? $a->getEnd() ?? '', $b->getId()]);

        return $entries;
    }

    /** How many lines of the CV wait hidden (read from ORCID, not checked yet). */
    public function countHidden(): int
    {
        $count = 0;
        foreach (self::SECTIONS as $class) {
            try {
                $count += $this->entityManager->getRepository($class)->count(['visible' => false]);
            } catch (\Throwable) {
            }
        }

        return $count;
    }
}
