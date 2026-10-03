# Installation and configuration

```bash
composer require omnibase/scholar:dev-main glitchr/omnischolar omnischolar/openalex omnischolar/hal omnischolar/crossref omnischolar/orcid
```

```php
// config/bundles.php
Omnischolar\Bridge\Symfony\OmnischolarBundle::class => ['all' => true],
Base\Scholar\ScholarBundle::class => ['all' => true],
```

```yaml
# config/routes.yaml
scholar_controller:
    resource: "@ScholarBundle/src/Controller/Client"
    type: attribute
    prefix: /
```

## The sources (glitchr/omnischolar)

The names a scholar's profiles use are the configured sources:

```yaml
# config/packages/omnischolar.yaml
omnischolar:
    sources:
        openalex: { factory: openalex, options: { mailto: '%env(OMNISCHOLAR_MAILTO)%' } }
        hal: { factory: hal }
        crossref: { factory: crossref, options: { mailto: '%env(OMNISCHOLAR_MAILTO)%' } }
        orcid: { factory: orcid }
        openlibrary: { factory: openlibrary, options: { mailto: '%env(OMNISCHOLAR_MAILTO)%' } }
    merger:
        preferences: { abstract: [hal, openalex], pdfUrl: [hal, openalex] }
```

## The bundle (every key optional)

```yaml
# config/packages/scholar.yaml
scholar:
    max_per_source: 2000          # works read from one source for one scholar
    metrics_source: openalex      # whose profile gives the counts (h-index...); ~ for none
    cv_source: orcid              # where the CV lines are read; ~: typed only
    per_page: 100                 # publications per page
    jsonld: true                  # Person, ScholarlyArticle, Book on the pages
    abstracts_in_exports: false
```

Then the tables (`scholar_scholar`, `scholar_publication`,
`scholar_publication_theme`, `scholar_theme`, `scholar_position`,
`scholar_degree`, `scholar_award`, `scholar_grant`, `scholar_course`,
`scholar_supervision`):

```bash
bin/console doctrine:migrations:diff && bin/console doctrine:migrations:migrate
bin/console assets:install          # public/css/scholar.css
```

## The scholar

In the back office (*Identity and sources*), or in the fixtures:

```php
$scholar = (new Scholar('Keitaro Nakatani'))
    ->setOrcid('0009-0005-1387-7295')
    ->setProfiles([
        'openalex' => ['A5108007452'],
        'hal' => ['Keitaro Nakatani'],      // by the name he signs: by ORCID HAL finds only the deposits that carry it
        'crossref' => ['Keitaro Nakatani'],
    ]);
```

A profile names a configured source and one or several authors there: an
OpenAlex id, an ORCID, an idHAL, a name - what that source's `works()` reads
(glitchr/omnischolar's `docs/sources.md`).

## The cron container

```cron
30 2 * * 1  php /srv/app/bin/console scholar:sync >> /srv/app/var/log/cron.scholar-sync.log 2>&1
```

No Symfony Scheduler: the sites' cron container runs it.
