# Synchronisation and validation

`bin/console scholar:sync` (`--scholar=<id>` for one) reads, for every
scholar, every profile's works - every page, through omnischolar's
`Collector`, merged by its `Merger` (same DOI, ISBN, arXiv id, HAL id, then
title and year) - and lays them over the publications the site has:

| The work | What happens |
|---|---|
| unknown to the site | a new `Publication`, **pending**: nothing shows before it is validated (unless the scholar has *Publish without validation*) |
| known (any identifier ever seen for it, else its title and year) | its sources' record and columns refreshed; nothing the site added moves |
| rejected once | refreshed, still rejected: a homonym's work never comes back |
| typed by hand (`manual`) | found, its identifiers remembered; its columns are the site's and stay |
| no longer given by the sources | left as it is |

A source that is down or rate limited is named in the summary
(`incomplete`) and the others still answer; nothing is taken for "gone".

The same run reads the counts of the metrics source's profile
(`scholar.metrics_source`: works, citations, h-index, i10-index, as OpenAlex
computes them) and the CV source's lines (`scholar.cv_source`: ORCID's
employments, education, distinctions - each once, by a key of its own;
a new line waits hidden).

What the run did is kept on the scholar (`lastSyncAt`, `lastSync`) and shown
in the widget.

## Calling it yourself

```php
$summary = $synchronizer->sync($scholar);           // read the sources, then merge, flush
$summary = $synchronizer->merge($scholar, $works);  // lay Works over the site's (persisted, not flushed)
$summary->created; $summary->updated; $summary->incomplete;
```

`merge()` is what a test or an import of a file calls.

## Validating

The dashboard's `scholar_pending` widget lists the newest pending works with
two buttons; the list *Publications* filters on the status and has the same
actions on each row and record. `Publication::approve()`, `reject()`.
