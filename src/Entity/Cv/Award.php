<?php

namespace Base\Scholar\Entity\Cv;

use Doctrine\ORM\Mapping as ORM;

/**
 * A distinction: a prize, a medal, a fellowship, an honorary title - given by whom
 * (`organization`), the year (`start`).
 */
#[ORM\Entity]
#[ORM\Table(name: 'scholar_award')]
#[ORM\Index(columns: ['visible', 'position'], name: 'scholar_award_shown_idx')]
class Award extends CvEntry
{
    protected function openEnded(): bool
    {
        return false;
    }
}
