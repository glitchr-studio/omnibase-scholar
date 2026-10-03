# The back office (omnibase/admin)

CRUD screens, under `/admin/scholars/...`:

| Screen | Controller | |
|---|---|---|
| Identity and sources | `Crud\Scholar\ScholarCrudController` | the person; *Sync now* on each |
| Publications | `Crud\Scholar\PublicationCrudController` | Validate / Reject; the sources' fields read only, the site's editable; *New* types one by hand |
| Themes and projects | `Crud\Scholar\ThemeCrudController` | |
| CV | `PositionCrudController`, `DegreeCrudController`, `AwardCrudController`, `GrantCrudController`, `CourseCrudController`, `SupervisionCrudController` | a line read from ORCID waits hidden |

```php
// DashboardController
yield MenuItem::block('scholar_pending', 'Publications à valider', 'fa-solid fa-book-open')->setSize(4);
yield MenuItem::linkToCrud(\Base\Scholar\Entity\Publication::class, 'Publications', 'fa-solid fa-book-open');
yield MenuItem::linkToCrud(\Base\Scholar\Entity\Scholar::class, 'Identité et sources', 'fa-solid fa-id-card');
yield MenuItem::linkToCrud(\Base\Scholar\Entity\Cv\Position::class, 'CV · Postes', 'fa-solid fa-briefcase');
```

The widget's buttons post to `admin_crud_scholars_publications_approve` and
`_reject` with the `admin-action-approve` / `admin-action-reject` tokens
(omnibase/admin's `#[AdminAction]`), and come back to where they were.

The status, a theme's kind are kept as strings (`statusValue`, `kindValue`
for the selects): omnibase's Uploader rebuilds an entity's previous state from
raw values and cannot give an enum back.
