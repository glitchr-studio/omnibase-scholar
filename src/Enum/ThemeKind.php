<?php

namespace Base\Scholar\Enum;

/** A line of research (a theme), or a project with its dates and funding. */
enum ThemeKind: string
{
    case THEME = 'theme';
    case PROJECT = 'project';

    public function label(): string
    {
        return 'theme.kind.'.$this->value;
    }
}
