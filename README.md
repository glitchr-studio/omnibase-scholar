# Scholar

A researcher's site for [omnibase](https://github.com/glitchr-studio/omnibase):
their publications read from the services that know them, through
[glitchr/omnischolar](https://github.com/glitchr-studio/omnischolar)
(OpenAlex, HAL, Crossref, ORCID, Open Library, arXiv, INSPIRE), validated
before they show, with what the site adds to each - hidden, pinned, in the
selection, a PDF, a link to buy it or to the publisher, a cover, a note,
themes - surviving every sync; the CV (positions, degrees, distinctions,
grants, courses, supervisions); research themes and projects; the pages and
their schema.org JSON-LD.

The bundle holds no talk and no service: a scholar's talks are
[omnibase/agenda](https://github.com/glitchr-studio/omnibase-agenda) `Event`s
(`agenda.jsonld_type: EducationEvent`), their services
[omnibase/consulting](https://github.com/glitchr-studio/omnibase-consulting)
`Offering`s.

```bash
composer require omnibase/scholar:dev-main glitchr/omnischolar omnischolar/openalex omnischolar/hal omnischolar/crossref
```

| | |
|---|---|
| `Entity\Scholar` | the person: name, title, affiliation, ORCID, the profiles works are read from (`openalex: A5108007452`, `hal: Keitaro Nakatani`), sameAs, portrait, biography |
| `Entity\Publication` | a `Work` of omnischolar kept whole (JSON) + columns to sort by + the site's overrides; `pending`, `published` or `rejected` |
| `Entity\Cv\*` | `Position`, `Degree`, `Award`, `Grant`, `Course`, `Supervision` |
| `Entity\Theme` | a research theme or a project, a page each |
| `scholar:sync` | every scholar's works, counts and CV read again (the cron container, weekly) |
| widget `scholar_pending` | "New publications to validate", with Validate / Reject |

Pages: `/publications` (filters; BibTeX, RIS, CSL-JSON of what is listed),
`/publications/{id}-{slug}`, `/books`, `/cv`, `/teaching`, `/research`,
`/research/{slug}`.

## Documentation

- [Installation and configuration](docs/installation.md)
- [Synchronisation and validation](docs/sync.md)
- [Pages, Twig functions and the host's look](docs/pages.md)
- [The back office](docs/admin.md)
- [Structured data and exports](docs/structured-data.md)

## Tests

`vendor/bin/phpunit` (or, inside a host application,
`php vendor/bin/phpunit -c vendor/omnibase/scholar/phpunit.xml.dist`): the
Work's storage and its keys, the sync laid over what the site has (a new work
waits, a known one follows its sources and keeps the site's overrides, a
rejected one stays rejected, one typed by hand is the site's), the reference
line, the exports, the JSON-LD.

License: LGPL-3.0-or-later.
