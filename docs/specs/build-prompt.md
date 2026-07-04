# Build Prompt — Modular Admin Panel (Laravel / Inertia / React)

> The governing specification for Atrium. Extended by `docs/specs/theming.md` (theming system).

## Role

Build a modular, extensible admin panel on top of the `nunomaduro/laravel-starter-kit-inertia-react` template. Follow the template's philosophy without exception: ultra-strict types, single-purpose action classes, "Cruddy by Design" controllers, PHPStan level 9 (max), Pest with 100% type and test coverage, Rector, Laravel Pint, strict TypeScript, and the kit's Vite Plus unified toolchain (OxLint + Oxfmt — no Prettier/ESLint).

## Prime directives

1. **Maximize use of maintained libraries; minimize custom code.** Before writing anything non-trivial, check whether an approved library (see Library Map) already solves it. Custom code is reserved for: domain logic inside Actions, the module contract, the shared data-table composition, the admin layout, and thin glue.
2. **Exception — modularity itself is hand-coded.** No module/package framework (no `nwidart/laravel-modules`, no `internachi/modular`, or similar). Modularity is achieved exclusively through the small Module Contract. Keep it minimal: registries + a base service provider + convention-based discovery. No dynamic module marketplace, no runtime enable/disable engine beyond what is specified.
3. **The server is the single source of truth.** No client-side data layer (no React Query, Redux, Zustand). Inertia props carry all data; Inertia partial reloads carry all updates. Table/query state lives in the URL. Validation rules live only in FormRequests (exposed via Laravel Precognition — never duplicated in zod/yup).
4. **Types cross the boundary by generation, never by hand.** All Inertia props are shaped by `spatie/laravel-data` DTOs; TypeScript types are generated via `spatie/laravel-typescript-transformer`. Hand-written interfaces for server data are forbidden.
5. **Authorization follows Laravel 13's native philosophy: the Gate and Policies are the single authorization system; libraries plug into it, never beside it.** spatie/laravel-permission v8 (^8.1) purely as the persistence layer for roles/permissions — all permissions register on the native Gate; every check happens through Policies, `can()`, and FormRequest `authorize()`. Consume declaratively with Laravel 13 attributes: `#[Middleware('role:admin')]` (Spatie aliases: role, permission, role_or_permission) at controller class level for the panel gate, and `#[Authorize]` attributes on methods invoking Policies — no imperative `Gate::authorize()` calls in method bodies. Rules: (a) application code checks permissions, never roles — roles are named permission bundles edited in the admin UI; the only role check is the coarse panel-entry gate; (b) super-admin via a single `Gate::before` hook; (c) the module PermissionRegistry is the code-level source of truth for which permissions exist and syncs idempotently to Spatie's tables — the database holds only assignments. The frontend renders `can` maps shipped as data and never contains authorization logic.
6. **Authentication is first-party only.** Keep the kit's session-based auth scaffolding (no Sanctum tokens). Laravel 13 first-party passkey support for admin accounts, plus optional 2FA. No third-party auth libraries.

## Stack (locked — versions verified July 2026)

- Laravel 13 (^13.0), Inertia v3, React 19, TypeScript 6 strict, Vite Plus, Tailwind CSS v4 (4.3.x). PHP floor: 8.5+ (starter kit requirement).
- UI: shadcn/ui over Radix primitives (vendored via `components.json`, kit convention).
- Tables: @tanstack/react-table pinned to stable v8 (^8.21) in fully manual mode (server-driven: manualSorting, manualFiltering, manualPagination; only core row model). NOT the v9 beta.
- Icons: lucide-react exclusively.
- Prefer Laravel 13 idioms where they reduce boilerplate: `#[Authorize]` / `#[Middleware]` PHP attributes, attribute-based model configuration.

## Library Map (approved; install as needed, do not substitute)

Backend: spatie/laravel-permission ^8.1, spatie/laravel-query-builder (index filtering/sorting), spatie/laravel-data + spatie/laravel-typescript-transformer (DTOs → TS), laravel/wayfinder (typed routes in TS), laravel-precognition-react-inertia (live validation from FormRequests), spatie/laravel-activitylog (audit), spatie/laravel-medialibrary (uploads), spatie/laravel-settings (settings pages), laravel/pennant (feature flags; module visibility is flagged), laravel/scout (search, driver configurable), laravel/pulse, laravel/horizon, opcodesio/log-viewer (mounted under admin middleware as "System"), maatwebsite/excel or spatie/simple-excel (exports), lab404/laravel-impersonate (impersonation), spatie/laravel-model-states (workflow states where needed), spatie/eloquent-sortable (manual ordering).

Frontend: @tanstack/react-table, sonner (toasts, fed by shared flash props), cmdk (command palette reading the nav registry), recharts (dashboard charts), tiptap (rich text where needed), dnd-kit (drag/drop, pairs with eloquent-sortable), react-dropzone (pairs with medialibrary), react-day-picker + date-fns (dates, shadcn calendar default), @tanstack/react-virtual (very long lists only), laravel-react-i18n (if i18n requested).

Do not add libraries outside this map without stating the reason and asking first.

## Module Contract (hand-coded — the only custom framework code allowed)

Purpose: each business domain (Users, Catalog, Orders, CMS, …) is a self-contained module. The admin shell knows no module by name; it renders whatever modules register.

Structure — every module lives in `app-modules/<Name>/` (composer PSR-4) and contains its own: `Providers/<Name>ServiceProvider`, `Http/Controllers`, `Http/Requests`, `Actions`, `Queries`, `Data` (DTOs), `Models`, `Policies`, `Database/Migrations`, `routes/admin.php`, and `resources/js/pages/...`.

Contract pieces (small; roughly this and no more):

1. **`ModuleServiceProvider` (abstract base).** Each module extends it. On boot: loads the module's migrations; registers the module's `routes/admin.php` inside the shared admin route group (prefix `admin`, name prefix `admin.`, middleware: auth, verified, admin gate); calls hooks: `navigation(NavRegistry $nav)`, `permissions(PermissionRegistry $perms)`, `widgets(WidgetRegistry $widgets)`. Module providers listed explicitly in `bootstrap/providers.php`.
2. **`NavRegistry`.** Sidebar items: label, route name, lucide icon name (string), required permission, group/section, sort order. Serialized into Inertia shared props by `HandleInertiaRequests`, filtered by the current user's permissions server-side. Sidebar and cmdk palette both render purely from this shared prop.
3. **`PermissionRegistry`.** Modules declare permission names (and default role assignments). `admin:sync-permissions` Artisan command syncs to spatie tables (idempotent; used in deploy and test setup).
4. **`WidgetRegistry`.** Dashboard widgets: key, permission, sort order, server-side resolver returning a DTO. Dashboard controller passes permitted widgets as Inertia deferred props; React maps widget keys to registered components (unknown keys render nothing).
5. **Frontend page discovery.** Inertia page resolver uses `import.meta.glob` over `resources/js/pages/**` and `app-modules/*/resources/js/pages/**`. Names namespaced: `<module>::<path>` (e.g. `users::index`). Vite config includes module directories.
6. **Feature-flag gating.** Every module has a Pennant feature (`module:<name>`), checked before exposing nav/routes/widgets, so a module can be dark-launched per role/user without code changes.

Explicit non-goals: no runtime plugin install/uninstall, no module dependency resolver, no config merging framework, no event-bus abstraction. Modules interact through ordinary Laravel events/contracts.

## Backend conventions (kit-aligned)

- Controllers contain only the seven CRUD verbs. Any non-CRUD operation (publish, suspend, impersonate, export) is its own invokable single-action controller.
- All writes go through `final readonly` Action classes with fully typed `handle()` signatures; Actions own transactions, activity logging, and event dispatch. Nothing else writes to the database.
- Every index page has a dedicated Query class wrapping `QueryBuilder::for(...)` with explicitly allowed filters/sorts, default sort, and a capped `per_page` (max 100). URL contract: `filter[search]`, `filter[<field>]` (CSV for multi), `sort` / `-sort`, `page`, `per_page`.
- Every resource has: Model, Policy, `StoreXRequest`/`UpdateXRequest` (Precognition-enabled, `authorize()` via Policy), `XData` DTO with a `can` ability map per row, controller methods ≤ ~5 lines each (authorize → query/action → render/redirect with flash).
- Flash messages (`success`/`error`) and the nav registry are shared via `HandleInertiaRequests`.

## Frontend conventions

- One `AdminLayout` (persistent layout pattern) with sidebar (from nav prop), breadcrumbs, `<Toaster/>` wired to flash props, and cmdk palette.
- One generic data-table composition: `DataTable`, `DataTableToolbar` (debounced search, faceted filters via Popover+Command, actions slot), `DataTableColumnHeader`, `DataTablePagination`, `DataTableRowActions` — all consuming a Laravel paginator DTO and a URL-state hook issuing Inertia partial reloads (`preserveState`, `preserveScroll`, `only: [prop]`, `replace`). New resources provide only a columns file and facet config.
- Forms use Inertia `useForm` via the Precognition wrapper + shadcn form primitives. Destructive actions always go through the shared AlertDialog confirm component.
- All routes referenced through Wayfinder-generated helpers; no hardcoded URL strings.

## Reference implementation & build order

1. Admin gate middleware, roles seeding, permission sync command.
2. Module contract (base provider + three registries + page discovery + Pennant gating) with ArchTest asserting every module provider extends the base and every Action is `final`.
3. `AdminLayout` + nav rendering + cmdk + flash toasts.
4. Shared data-table composition + URL-state hook.
5. **Users module end-to-end as the canonical reference**: full CRUD, Precognition forms, faceted index, per-row `can`, activity logging, export, tests.
6. Dashboard module (widget registry consumer, deferred props, recharts KPI cards).
7. System section: mount Pulse, Horizon, Log Viewer behind the admin gate; nav entries via a System module provider.

## Definition of done (every increment)

PHPStan level max passes; Rector and Pint clean; 100% type coverage; Pest suite green including: feature tests per controller (guest, forbidden, validation, happy path), unit tests per Action and Query, policy tests, architecture tests for the module contract rules. `tsc --noEmit` passes strict; OxLint/Oxfmt clean (no ESLint/Prettier); TS types regenerated and committed whenever a DTO changes. Verify each library's Laravel 13 / Inertia v3 / React 19 compatibility at install time and pin exact working versions. No TODOs, no commented-out code, no `any`, no suppressed errors.

## Working style

Work module by module, smallest shippable slice first. Before each slice, state in 3–5 sentences what will be built and which libraries cover which parts; flag anything requiring code outside the Library Map or the Module Contract and ask before writing it. Never introduce an abstraction until the second concrete consumer exists.

## Deployment

Docker for all deploys (see `docker/` and compose files). Git: commit locally; remote configured later.
