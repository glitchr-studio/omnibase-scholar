<?php

namespace Base\Scholar\Admin\Widget;

use Base\Admin\Config\Menu\MenuItem;
use Base\Admin\Widget\DashboardWidgetTypeInterface;
use Base\Scholar\Repository\PublicationRepository;
use Base\Scholar\Repository\ScholarRepository;
use Base\Scholar\Service\Curriculum;

/**
 * The dashboard's "New publications to validate": what the last syncs
 * found that is not on the site yet - each with its "Validate" and
 * "Reject" -, the CV lines read and still hidden, and when the sources
 * were last read. `yield MenuItem::block('scholar_pending', ...)` in the
 * dashboard's configureWidgetItems() places it.
 */
final class PendingPublicationsWidgetType implements DashboardWidgetTypeInterface
{
    public function __construct(
        private readonly PublicationRepository $publications,
        private readonly ScholarRepository $scholars,
        private readonly Curriculum $curriculum,
    ) {
    }

    public static function getName(): string
    {
        return 'scholar_pending';
    }

    public function getTemplate(): string
    {
        return '@Scholar/admin/widget/pending.html.twig';
    }

    public function getTemplateVars(MenuItem $widget): array
    {
        return [
            'count' => $this->publications->countPending(),
            'publications' => $this->publications->findPending(8),
            'hidden_cv' => $this->curriculum->countHidden(),
            'scholar' => $this->scholars->findMain(),
        ];
    }
}
