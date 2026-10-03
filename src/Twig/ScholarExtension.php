<?php

namespace Base\Scholar\Twig;

use Base\Scholar\Entity\Publication;
use Base\Scholar\Entity\Scholar;
use Base\Scholar\Enum\ThemeKind;
use Base\Scholar\Repository\PublicationRepository;
use Base\Scholar\Repository\ScholarRepository;
use Base\Scholar\Repository\ThemeRepository;
use Base\Scholar\Service\Citations;
use Base\Scholar\Service\Curriculum;
use Base\Scholar\Service\JsonLd;
use Omnischolar\Model\WorkType;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * What a host's own pages ask the scholar bundle: the person (a home
 * page's portrait and title, their JSON-LD), the selection or the latest
 * publications, the books, the themes, a CV section, a publication's
 * reference line, a work type's name.
 */
final class ScholarExtension extends AbstractExtension
{
    private ?Scholar $scholar = null;
    private bool $looked = false;

    public function __construct(
        private readonly ScholarRepository $scholars,
        private readonly PublicationRepository $publications,
        private readonly ThemeRepository $themes,
        private readonly Curriculum $curriculum,
        private readonly Citations $citations,
        private readonly JsonLd $jsonLd,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('scholar', fn (): ?Scholar => $this->scholar()),
            new TwigFunction('scholar_selected', fn (int $limit = 5): array => $this->safe(fn () => $this->publications->findSelected($limit))),
            new TwigFunction('scholar_latest', fn (int $limit = 5, array $types = []): array => $this->safe(fn () => $this->publications->findShown($types, limit: $limit))),
            new TwigFunction('scholar_books', fn (?int $limit = null): array => $this->safe(fn () => $this->publications->findShown(['book'], limit: $limit))),
            new TwigFunction('scholar_count', fn (array $types = []): int => (int) $this->safe(fn () => $this->publications->countShown($types), 0)),
            new TwigFunction('scholar_themes', fn (?string $kind = null): array => $this->safe(fn () => $this->themes->findVisible($kind ? ThemeKind::tryFrom($kind) : null))),
            new TwigFunction('scholar_cv', fn (?array $sections = null): array => $this->curriculum->sections($sections)),
            new TwigFunction('scholar_reference', fn (Publication $publication): string => $this->citations->reference($publication)),
            new TwigFunction('scholar_person_jsonld', fn (?string $url = null, ?string $image = null): ?array => ($s = $this->scholar()) ? $this->jsonLd->person($s, $url, $image) : null),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('scholar_type_label', fn (string|WorkType|null $type, int $count = 1): string => $this->translator->trans('publication.type.'.(($type instanceof WorkType ? $type->value : $type) ?: 'other'), ['count' => $count], 'scholar')),
        ];
    }

    private function scholar(): ?Scholar
    {
        if (!$this->looked) {
            $this->looked = true;
            $this->scholar = $this->safe(fn () => $this->scholars->findMain(), null);
        }

        return $this->scholar;
    }

    /** A page still renders before the tables are migrated. */
    private function safe(callable $read, mixed $default = []): mixed
    {
        try {
            return $read();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $default;
        }
    }
}
