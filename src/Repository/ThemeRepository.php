<?php

namespace Base\Scholar\Repository;

use Base\Scholar\Entity\Theme;
use Base\Scholar\Enum\ThemeKind;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Theme> */
class ThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Theme::class);
    }

    /** @return list<Theme> the visible ones, in their order; of one kind when asked */
    public function findVisible(?ThemeKind $kind = null): array
    {
        $criteria = ['visible' => true];
        if ($kind) {
            $criteria['kind'] = $kind->value;
        }

        return $this->findBy($criteria, ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function findOneVisible(string $slug): ?Theme
    {
        return $this->findOneBy(['slug' => $slug, 'visible' => true]);
    }
}
