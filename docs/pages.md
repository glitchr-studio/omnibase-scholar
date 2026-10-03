# Pages, Twig functions and the host's look

| Route | Path | |
|---|---|---|
| `scholar_publications` | `/publications` | pinned first, then by year; `?type=article&year=2024&theme=<slug>&q=words`, `?page=` |
| `scholar_publications_export` | `/publications.{bib,ris,json}` | what the page lists, as a file |
| `scholar_publication` | `/publications/{id}-{slug}` | the record, its abstract, its editions, the reference to copy, its BibTeX |
| `scholar_publication_export` | `/publications/{id}.{bib,ris,json}` | |
| `scholar_books` | `/books` | the covers (the one uploaded, else Open Library's, else the title set as a cover) |
| `scholar_cv` | `/cv` | the person, the biography, the CV's sections, the selection |
| `scholar_teaching` | `/teaching` | courses and supervisions |
| `scholar_research` | `/research` | themes and projects |
| `scholar_theme` | `/research/{slug}` | a theme's text and its publications |

Only validated, not hidden publications are shown. The publication and theme
pages are in `/sitemap.xml` (`EventListener\SitemapListener`).

## What is not given yet

A portrait, a biography, a CV, research themes that nobody gave are never
filled with an invented text: `@Scholar/client/_draft.html.twig` marks the
place as a draft (`scholar-draft`, its tag *Draft*).

```twig
{% include '@Scholar/client/_draft.html.twig' with {kind: 'biography', message: 'The biography is being written.'} %}
```

## Twig, for the host's own pages

| | |
|---|---|
| `scholar()` | the site's scholar (the first one) |
| `scholar_selected(n)` | the selection, else the latest |
| `scholar_latest(n, types)` | the latest shown |
| `scholar_books(n)`, `scholar_count(types)`, `scholar_themes(kind)` | |
| `scholar_cv(sections)` | `{positions: [...], degrees: [...]}` |
| `scholar_reference(publication)` | "Chocron, L., Nakatani, K. (2024). …" |
| `scholar_person_jsonld(url, image)` | the Person, for a home page |
| `type\|scholar_type_label(count)` | "Article", "Articles" in the reader's language |

`{% include '@Scholar/client/_publication.html.twig' %}` renders a
publication as the list does; `_theme_card.html.twig` a theme.

## The look

The templates extend `layout1.html.twig` and fill `title`, `description`,
`stylesheets` and `content`. `public/css/scholar.css` follows the host's
custom properties: `--scholar-accent`, `--scholar-on-accent`,
`--scholar-ink`, `--scholar-soft`, `--scholar-line`, `--scholar-surface`,
`--scholar-font-display`, `--scholar-measure`. Templates may be overridden
in `templates/bundles/ScholarBundle/`.
