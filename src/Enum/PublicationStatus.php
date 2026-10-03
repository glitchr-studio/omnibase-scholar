<?php

namespace Base\Scholar\Enum;

/**
 * Where a publication stands: read by scholar:sync and waiting for the
 * scholar's eye (pending), on the site (published), or set aside for good
 * (rejected: a homonym's work, a duplicate the sources could not tell
 * apart) - a rejected work found again by the next sync stays rejected.
 */
enum PublicationStatus: string
{
    case PENDING = 'pending';
    case PUBLISHED = 'published';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return 'publication.status.'.$this->value;
    }
}
