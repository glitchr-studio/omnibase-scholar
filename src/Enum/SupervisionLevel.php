<?php

namespace Base\Scholar\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/** Who was supervised: a doctoral student, a master's student, a post-doc, an intern. */
enum SupervisionLevel: string implements TranslatableInterface
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

    /** Its name in the `scholar` domain: what a select of the back office shows. */
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->label(), [], 'scholar', $locale);
    }
}
