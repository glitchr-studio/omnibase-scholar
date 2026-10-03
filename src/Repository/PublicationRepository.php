<?php

namespace Base\Scholar\Repository;

use Base\Scholar\Entity\Publication;
use Base\Scholar\Entity\Scholar;
use Base\Scholar\Entity\Theme;
use Base\Scholar\Enum\PublicationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Publication> */
class PublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    /** What the site shows: validated, not hidden. */
    public function shown(): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :published')->setParameter('published', PublicationStatus::PUBLISHED->value)
            ->andWhere('p.hidden = false');
    }

    /**
     * The list of the publications page: pinned first, then newest first;
     * filtered by kind, year, theme and words of the title, authors or venue.
     *
     * @param list<string> $types
     *
     * @return list<Publication>
     */
    public function findShown(array $types = [], ?int $year = null, ?Theme $theme = null, ?string $text = null, ?int $limit = null, int $offset = 0, array $excludeTypes = []): array
    {
        $qb = $this->filtered($types, $year, $theme, $text, $excludeTypes)
            ->orderBy('p.pinned', 'DESC')->addOrderBy('p.year', 'DESC')->addOrderBy('p.id', 'DESC')
            ->setFirstResult($offset);
        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /** @param list<string> $types */
    public function countShown(array $types = [], ?int $year = null, ?Theme $theme = null, ?string $text = null, array $excludeTypes = []): int
    {
        return (int) $this->filtered($types, $year, $theme, $text, $excludeTypes)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
    }

    /** @param list<string> $types */
    private function filtered(array $types, ?int $year, ?Theme $theme, ?string $text, array $excludeTypes = []): QueryBuilder
    {
        $qb = $this->shown();
        if ($types) {
            $qb->andWhere('p.type IN (:types)')->setParameter('types', $types);
        }
        if ($excludeTypes) {
            $qb->andWhere('p.type NOT IN (:excluded)')->setParameter('excluded', $excludeTypes);
        }
        if ($year) {
            $qb->andWhere('p.year = :year')->setParameter('year', $year);
        }
        if ($theme) {
            $qb->andWhere(':theme MEMBER OF p.themes')->setParameter('theme', $theme);
        }
        foreach (array_slice(preg_split('/\s+/u', trim((string) $text)) ?: [], 0, 6) as $i => $word) {
            if ('' !== $word) {
                $qb->andWhere("(p.title LIKE :w$i OR p.authors LIKE :w$i OR p.venue LIKE :w$i)")->setParameter("w$i", '%'.addcslashes($word, '%_').'%');
            }
        }

        return $qb;
    }

    /** @return list<Publication> the selection: the ones marked, else the pinned, else the latest */
    public function findSelected(int $limit = 5): array
    {
        $selected = $this->shown()->andWhere('p.selected = true')
            ->orderBy('p.year', 'DESC')->addOrderBy('p.id', 'DESC')->setMaxResults($limit)->getQuery()->getResult();

        return $selected ?: $this->findShown(limit: $limit);
    }

    public function findOneShown(int $id): ?Publication
    {
        return $this->shown()->andWhere('p.id = :id')->setParameter('id', $id)->getQuery()->getOneOrNullResult();
    }

    /** @return list<int> the years that have a publication, newest first */
    public function findYears(): array
    {
        return array_map('intval', array_column($this->shown()->select('DISTINCT p.year AS year')->andWhere('p.year IS NOT NULL')->orderBy('p.year', 'DESC')->getQuery()->getScalarResult(), 'year'));
    }

    /** @return array<string, int> type => count */
    public function countByType(): array
    {
        $counts = [];
        foreach ($this->shown()->select('p.type AS type, COUNT(p.id) AS n')->groupBy('p.type')->getQuery()->getScalarResult() as $row) {
            $counts[$row['type']] = (int) $row['n'];
        }
        arsort($counts);

        return $counts;
    }

    /** @return list<Publication> every publication of a scholar, whatever its status: what a sync matches against */
    public function findAllOf(Scholar $scholar): array
    {
        return $this->findBy(['scholar' => $scholar]);
    }

    /** @return list<Publication> the ones waiting for the scholar's eye, newest first */
    public function findPending(?int $limit = null): array
    {
        return $this->findBy(['status' => PublicationStatus::PENDING->value], ['year' => 'DESC', 'id' => 'DESC'], $limit);
    }

    public function countPending(): int
    {
        return $this->count(['status' => PublicationStatus::PENDING->value]);
    }
}
