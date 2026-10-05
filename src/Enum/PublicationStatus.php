<?php

namespace Base\Scholar\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Where a publication stands: read by scholar:sync and waiting for the
 * scholar's eye (pending), on the site (published), or set aside for good
 * (rejected: a homonym's work, a duplicate the sources could not tell
 * apart) - a rejected work found again by the next sync stays rejected.
 */
enum PublicationStatus: string implements TranslatableInterface
{
    case PENDING = 'pending';
    case PUBLISHED = 'published';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return 'publication.status.'.$this->value;
    }

    /** Its name in the `scholar` domain: what a select of the back office shows. */
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->label(), [], 'scholar', $locale);
    }
}
