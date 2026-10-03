<?php

namespace Base\Scholar\EventListener;

use Base\Event\SitemapEvent;
use Base\Scholar\Repository\PublicationRepository;
use Base\Scholar\Repository\ThemeRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * The publications' and the themes' own pages in /sitemap.xml: omnibase
 * lists the routes it can generate by itself (/publications, /cv...), a
 * publication or a theme is behind an id or a slug it cannot know.
 */
#[AsEventListener(event: SitemapEvent::BUILD)]
final class SitemapListener
{
    public function __construct(
        private readonly PublicationRepository $publications,
        private readonly ThemeRepository $themes,
    ) {
    }

    public function __invoke(SitemapEvent $event): void
    {
        $sitemap = $event->getSitemapper();
        foreach ($this->publications->findShown() as $publication) {
            $sitemap->register('scholar_publication', ['id' => $publication->getId(), 'slug' => $publication->getSlug()], $publication->getFetchedAt()?->format('c'));
        }
        foreach ($this->themes->findVisible() as $theme) {
            $sitemap->register('scholar_theme', ['slug' => $theme->getSlug()]);
        }
    }
}
