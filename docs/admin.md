# The back office (omnibase/admin)

CRUD screens, under `/admin/scholars/...`:

| Screen | Controller | |
|---|---|---|
| Identity and sources | `Crud\Scholar\ScholarCrudController` | the person; *Sync now* on each |
| Publications | `Crud\Scholar\PublicationCrudController` | Validate / Reject; the sources' fields read only, the site's editable; *New* types one by hand |
| Themes and projects | `Crud\Scholar\ThemeCrudController` | |
| CV | `PositionCrudController`, `DegreeCrudController`, `AwardCrudController`, `GrantCrudController`, `CourseCrudController`, `SupervisionCrudController` | a line read from ORCID waits hidden |

## Who writes

These screens are the site's administrator's (`ROLE_ADMIN`: the researcher
whose site it is), not the super-admin's only - each controller carries
omnibase/admin's `#[OpenToAdmins]`: creating, editing and deleting, and the
screens' own buttons (`approve`, `reject` on a publication, `sync` on the
scholar). An application's CRUD extending one of them is opened too, and may
close an action again in its `configureActions()`
(`->setPermission(Action::DELETE, 'ROLE_SUPERADMIN')`). The attribute is
omnibase/admin's from 7474f85.

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

A publication's status, a theme's kind and a supervision's level are PHP
enums (`PublicationStatus`, `ThemeKind`, `SupervisionLevel`), mapped with
Doctrine's `enumType:` on their string columns; in the back office each is a
select of its cases (`SelectField::new('status')`), which name themselves in
the `scholar` domain (`TranslatableInterface`). (They were kept as strings,
with `statusValue` / `kindValue` / `levelValue` accessors for the selects,
until omnibase could put an enum back into an entity's previous state and
build a select from one: same columns, no migration.)
