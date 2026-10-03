# Structured data and exports

## JSON-LD (`Service\JsonLd`, `scholar.jsonld`)

| Page | schema.org |
|---|---|
| `/cv`, and a host's home page through `scholar_person_jsonld()` | `Person`: name, given and family names, honorific, jobTitle, affiliation, ORCID as `identifier`, `sameAs` (ORCID, OpenAlex, HAL's CV, the pages typed), `knowsAbout` |
| a publication | `ScholarlyArticle` (a journal's `Periodical`, a conference's `Event`, a book's as `isPartOf`), `Book` (ISBNs, publisher, edition, an `Offer` to buy it), `Chapter`, `Thesis`, `Report`, `Dataset`, `SoftwareSourceCode`; the authors, the scholar's with his ORCID |
| `/publications`, `/books` | an `ItemList` of them |

## Citing

`Service\Citations::reference()` writes the line to copy (authors with
initials, year, title, journal, volume(issue), pages, DOI - or ISBN).
`export('bibtex'|'ris'|'csl-json', $publications)` writes the files through
glitchr/omnischolar's `Export`, from what the site shows: a PDF or a
publisher's page given on the site goes into the file.
