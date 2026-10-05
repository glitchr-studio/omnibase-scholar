<?php

namespace Base\Scholar\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/** A line of research (a theme), or a project with its dates and funding. */
enum ThemeKind: string implements TranslatableInterface
{
    case THEME = 'theme';
    case PROJECT = 'project';

    public function label(): string
    {
        return 'theme.kind.'.$this->value;
    }

    /** Its name in the `scholar` domain: what a select of the back office shows. */
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->label(), [], 'scholar', $locale);
    }
}
