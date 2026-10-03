<?php

namespace Base\Scholar\Enum;

/** Who was supervised: a doctoral student, a master's student, a post-doc, an intern. */
enum SupervisionLevel: string
{
    case PHD = 'phd';
    case MASTER = 'master';
    case POSTDOC = 'postdoc';
    case INTERN = 'intern';
    case OTHER = 'other';

    public function label(): string
    {
        return 'cv.supervision.level.'.$this->value;
    }
}
