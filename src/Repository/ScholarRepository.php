<?php

namespace Base\Scholar\Repository;

use Base\Scholar\Entity\Scholar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Scholar> */
class ScholarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Scholar::class);
    }

    /** The site's scholar: the first one typed (a site is usually one person's). */
    public function findMain(): ?Scholar
    {
        return $this->findOneBy([], ['id' => 'ASC']);
    }
}
