<?php

namespace Base\Scholar\Controller\Client;

use Base\Attributes\Attribute\Sitemap;
use Base\Scholar\Entity\Publication;
use Base\Scholar\Enum\ThemeKind;
use Base\Scholar\Repository\PublicationRepository;
use Base\Scholar\Repository\ScholarRepository;
use Base\Scholar\Repository\ThemeRepository;
use Base\Scholar\Service\Citations;
use Base\Scholar\Service\Curriculum;
use Base\Scholar\Service\JsonLd;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The scholar's pages: the publications (filtered by kind, year, theme and
 * words; each to cite or to take as BibTeX, RIS or CSL-JSON, the list as
 * well), one publication's page, the books with their covers, the CV,
 * the teaching (courses and supervisions), the research themes and
 * projects. Only what was validated and not hidden is shown.
 */
class ScholarController extends AbstractController
{
    private const EXPORTS = 'bib|ris|json';

    public function __construct(
        private readonly PublicationRepository $publications,
        private readonly ScholarRepository $scholars,
        private readonly ThemeRepository $themes,
        private readonly Curriculum $curriculum,
        private readonly Citations $citations,
        private readonly JsonLd $jsonLd,
        #[Autowire('%scholar.per_page%')] private readonly int $perPage = 100,
        #[Autowire('%scholar.jsonld%')] private readonly bool $withJsonLd = true,
    ) {
    }

    #[Sitemap(priority: 0.8, changefreq: 'weekly')]
    #[Route('/publications', name: 'scholar_publications', methods: ['GET'])]
    public function publications(Request $request): Response
    {
        $filters = $this->filters($request);
        $page = max(1, $request->query->getInt('page', 1));
        $total = $this->publications->countShown(...$filters);
        $list = $this->publications->findShown(...[...$filters, 'limit' => $this->perPage, 'offset' => ($page - 1) * $this->perPage]);
        $scholar = $this->scholars->findMain();

        return $this->render('@Scholar/client/publications.html.twig', [
            'scholar' => $scholar,
            'publications' => $list,
            'total' => $total,
            'page' => $page,
            'pages' => (int) max(1, ceil($total / $this->perPage)),
            'filters' => ['type' => $filters['types'][0] ?? null, 'year' => $filters['year'], 'theme' => $filters['theme'], 'q' => $filters['text']],
            'types' => $this->publications->countByType(),
            'years' => $this->publications->findYears(),
            'themes' => $this->themes->findVisible(),
            'jsonld' => $this->withJsonLd && $list ? $this->jsonLd->list($list, (string) ($scholar?->getName() ?? 'Publications'), $scholar) : null,
        ]);
    }

    /** The list, filtered as the page is, for a reference manager. */
    #[Route('/publications.{format}', name: 'scholar_publications_export', requirements: ['format' => self::EXPORTS], methods: ['GET'])]
    public function publicationsExport(Request $request, string $format): Response
    {
        $list = $this->publications->findShown(...$this->filters($request));

        return $this->download($this->citations->export(Citations::format($format), $list), 'publications.'.$format, Citations::format($format));
    }

    #[Route('/publications/{id}-{slug}', name: 'scholar_publication', requirements: ['id' => '\d+', 'slug' => '[a-z0-9\-]*'], methods: ['GET'])]
    public function publication(Request $request, int $id, string $slug = ''): Response
    {
        $publication = $this->find($id);
        if ($slug !== $publication->getSlug()) {
            return $this->redirectToRoute('scholar_publication', ['id' => $id, 'slug' => $publication->getSlug()], Response::HTTP_MOVED_PERMANENTLY);
        }
        $scholar = $this->scholars->findMain();

        return $this->render('@Scholar/client/publication.html.twig', [
            'scholar' => $scholar,
            'publication' => $publication,
            'reference' => $this->citations->reference($publication),
            'bibtex' => $this->citations->export('bibtex', $publication),
            'jsonld' => $this->withJsonLd ? $this->jsonLd->publication($publication, $this->generateUrl('scholar_publication', ['id' => $id, 'slug' => $slug], UrlGeneratorInterface::ABSOLUTE_URL), $scholar) : null,
        ]);
    }

    #[Route('/publications/{id}.{format}', name: 'scholar_publication_export', requirements: ['id' => '\d+', 'format' => self::EXPORTS], methods: ['GET'])]
    public function publicationExport(int $id, string $format): Response
    {
        $publication = $this->find($id);

        return $this->download($this->citations->export(Citations::format($format), $publication), $publication->getSlug().'.'.$format, Citations::format($format));
    }

    #[Sitemap(priority: 0.7, changefreq: 'monthly')]
    #[Route('/books', name: 'scholar_books', methods: ['GET'])]
    public function books(): Response
    {
        $books = $this->publications->findShown(['book']);
        $scholar = $this->scholars->findMain();

        return $this->render('@Scholar/client/books.html.twig', [
            'scholar' => $scholar,
            'books' => $books,
            'jsonld' => $this->withJsonLd && $books ? $this->jsonLd->list($books, (string) ($scholar?->getName() ?? 'Books'), $scholar) : null,
        ]);
    }

    #[Sitemap(priority: 0.7, changefreq: 'monthly')]
    #[Route('/cv', name: 'scholar_cv', methods: ['GET'])]
    public function cv(Request $request): Response
    {
        $scholar = $this->scholars->findMain();

        return $this->render('@Scholar/client/cv.html.twig', [
            'scholar' => $scholar,
            'sections' => $this->curriculum->sections(),
            'selected' => $this->publications->findSelected(8),
            'jsonld' => $this->withJsonLd && $scholar ? $this->jsonLd->person($scholar, $this->generateUrl('scholar_cv', [], UrlGeneratorInterface::ABSOLUTE_URL), $scholar->getPortraitUrl() ? $request->getSchemeAndHttpHost().$scholar->getPortraitUrl() : null) : null,
        ]);
    }

    #[Sitemap(priority: 0.6, changefreq: 'monthly')]
    #[Route('/teaching', name: 'scholar_teaching', methods: ['GET'])]
    public function teaching(): Response
    {
        return $this->render('@Scholar/client/teaching.html.twig', [
            'scholar' => $this->scholars->findMain(),
            'sections' => $this->curriculum->sections(['courses', 'supervisions']),
        ]);
    }

    #[Sitemap(priority: 0.7, changefreq: 'monthly')]
    #[Route('/research', name: 'scholar_research', methods: ['GET'])]
    public function research(): Response
    {
        return $this->render('@Scholar/client/research.html.twig', [
            'scholar' => $this->scholars->findMain(),
            'themes' => $this->themes->findVisible(ThemeKind::THEME),
            'projects' => $this->themes->findVisible(ThemeKind::PROJECT),
        ]);
    }

    #[Route('/research/{slug}', name: 'scholar_theme', requirements: ['slug' => '[a-z0-9\-]+'], methods: ['GET'])]
    public function theme(string $slug): Response
    {
        $theme = $this->themes->findOneVisible($slug) ?? throw $this->createNotFoundException(sprintf('No theme "%s".', $slug));

        return $this->render('@Scholar/client/theme.html.twig', [
            'scholar' => $this->scholars->findMain(),
            'theme' => $theme,
            'publications' => $this->publications->findShown(theme: $theme),
        ]);
    }

    private function find(int $id): Publication
    {
        return $this->publications->findOneShown($id) ?? throw $this->createNotFoundException(sprintf('No publication "%d".', $id));
    }

    /** @return array{types: list<string>, year: ?int, theme: ?\Base\Scholar\Entity\Theme, text: ?string} */
    private function filters(Request $request): array
    {
        $type = preg_replace('/[^a-z_]/', '', $request->query->getString('type'));
        $theme = $request->query->getString('theme');

        return [
            'types' => $type ? [$type] : [],
            'year' => $request->query->getInt('year') ?: null,
            'theme' => '' !== $theme ? $this->themes->findOneVisible($theme) : null,
            'text' => mb_substr(trim($request->query->getString('q')), 0, 120) ?: null,
        ];
    }

    /** A text file to take away: BibTeX, RIS or CSL-JSON. */
    private function download(string $content, string $fileName, string $format): Response
    {
        $response = new Response($content, Response::HTTP_OK, ['Content-Type' => Citations::contentType($format)]);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName));
        $response->headers->set('X-Robots-Tag', 'noindex');

        return $response;
    }
}
