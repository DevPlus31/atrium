# Atrium

Modular, extensible admin panel. Laravel 13 · Inertia v3 · React 19 · TypeScript (strict) · Tailwind v4 · shadcn/ui, built on `nunomaduro/laravel-starter-kit-inertia-react`. Ultra-strict by construction: PHPStan level max, 100% line + type coverage, Rector, Pint, OxLint/Oxfmt, and a custom theme lint — all enforced as gates.

**Governing specs (read before any nontrivial change):** `docs/specs/build-prompt.md` (architecture, module contract, definition of done) and `docs/specs/theming.md` (token-only theming, layout variants, RTL). `THEMING.md` documents the token and layout contracts.

## Environment (this machine)

- **PHP runs only through Docker**: `docker compose run --rm app <cmd>` (add `--no-deps` when redis isn't needed). No local PHP.
- **JS runs natively via Bun**: `bun run build|dev|lint|test:lint|test:types`.
- First run: `composer setup` equivalent via Docker, then `php artisan migrate`, then **`php artisan admin:create-user`** — registration is disabled; this command creates the first verified admin and syncs permissions.
- Dev: `docker compose up` serves the app on :8000; `bun run dev` for HMR.
- Production image: `docker/prod` (FrankenPHP).

## Architecture

**Module contract** — the only hand-written framework code. Each business domain is a self-contained module under `app-modules/<Name>/` (PSR-4 `Modules\`), with its own Providers, Http (Controllers/Requests), Actions, Queries, Data (DTOs), Policies, Database/Migrations, `routes/admin.php`, and `resources/js/pages`. The shell knows no module by name.

- `App\Modules\ModuleServiceProvider` (abstract): a module implements `name()` and hook methods. Its final `boot()` loads migrations, registers `routes/admin.php` under prefix `admin.` + middleware `['web','auth','verified','role:admin', EnsureModuleIsEnabled:<name>]`, defines the Pennant flag `module:<name>`, and calls the hooks.
- `NavRegistry` — sidebar/topbar/cmdk items (label, route, lucide icon name, permission, group, sort). Serialized into shared Inertia props, permission-filtered server-side.
- `PermissionRegistry` — modules declare permissions + default role assignments; `php artisan admin:sync-permissions` syncs idempotently to Spatie tables (prunes undeclared permissions; DB holds only assignments).
- `WidgetRegistry` — dashboard widgets (key, permission, sort, DTO resolver) delivered as deferred Inertia props; React maps keys to components, unknown keys render nothing.
- Page discovery: Inertia resolves `<module>::<path>` (e.g. `roles::index`) by globbing `app-modules/*/resources/js/pages/**`.
- Modules registered explicitly in `bootstrap/providers.php`. Arch tests enforce the contract (providers extend the base, Actions final+readonly, no `App\Http\Controllers` usage from app code).

**Request flow (canonical, see the Users module):** route → controller (`final readonly`, only CRUD verbs, `#[Authorize]` attributes, bodies ≤ ~5 lines) → Query class for indexes (spatie/laravel-query-builder; URL contract `filter[search]`, `filter[<field>]` CSV, `sort`/`-sort`, `page`, `per_page` ≤ 100) or `final readonly` Action for writes (owns `DB::transaction`, activity logging, events) → `spatie/laravel-data` DTO with a per-row `can` ability map → Inertia page. Non-CRUD operations are invokable single-action controllers (export, impersonate).

**Authorization:** spatie/laravel-permission is persistence only; everything checks the native Gate/Policies with **permissions, never roles**. The only role checks: the `role:admin` panel-entry gate and the `super-admin` `Gate::before` bypass. Roles are permission bundles edited in the Roles UI. The frontend never authorizes — it renders the server-sent `can` maps.

**Types cross the boundary by generation:** `php artisan typescript:transform` → `resources/js/types/generated.d.ts` (`Modules.<Name>.Data.*`); Wayfinder generates typed route/action helpers (`@/routes/...`, `@/actions/...`) via the Vite plugin or `php artisan wayfinder:generate --with-form`. Hand-written interfaces for server data are forbidden. Validation lives only in FormRequests, surfaced live via Precognition.

**Theming (see THEMING.md):** semantic design tokens only — raw palette classes, color literals, and physical direction utilities (`ml-`, `left-`…) fail `bun run lint:theme`. Presets: default / ember / contrast, light + dark, per-user appearance, plus an enumerated layout variant system (nav placement, sidebar variants, boxed/fluid, sticky/static header, LTR/RTL) interpreted solely by `AdminLayout`. First paint is stamped server-side (no theme flash). A browser test matrix screenshots every preset × appearance and layout variant.

## Modules & features

| Area | What exists |
|---|---|
| Users | CRUD, faceted index, role assignment (self-lockout guard), CSV export, impersonation (banner shell-wide, activity-logged) |
| Roles | CRUD for permission bundles; grouped permission checkboxes; `admin`/`super-admin` are protected system roles |
| Dashboard | Widget registry consumer, deferred props, Recharts KPI/chart widgets |
| System | Pulse, Horizon, Log Viewer mounted behind admin permissions (`system.*`) |
| Auth | Session (Fortify), 2FA (TOTP + recovery), **passkeys** (first-party `laravel/passkeys`; settings page + passwordless login), email verification, password confirm |
| Bootstrap | `php artisan admin:create-user`, `php artisan admin:sync-permissions` |

## Gates (all must pass; run before finalizing any change)

```bash
docker compose run --rm --no-deps app vendor/bin/pint --dirty --format agent
docker compose run --rm --no-deps app vendor/bin/rector
docker compose run --rm --no-deps app vendor/bin/phpstan
docker compose run --rm app php artisan test --compact          # full suite incl. browser tests
docker compose run --rm --no-deps app vendor/bin/pest --type-coverage --min=100
docker compose run --rm --no-deps -e XDEBUG_MODE=coverage app vendor/bin/pest --parallel --coverage --exactly=100.0
bun run build && bun run test:types && bun run test:lint        # build, tsc, oxfmt/oxlint + theme-lint
```

Tests live in root `tests/` mirroring module namespaces (`tests/Feature/Modules/<Name>/…`, `tests/Unit/Modules/<Name>/…`, `tests/Browser/…`). Feature tests per controller cover guest/forbidden/validation/happy paths; browser tests assert no JS errors across the theme × layout matrix.

## Rules that fail review

- Color literals or raw palette classes anywhere in admin/module frontend code; physical direction utilities (use `ms-`/`me-`/`ps-`/`pe-`/`start-`/`end-`).
- Role checks in application logic (permissions only), imperative `Gate::authorize()` in controller bodies (use `#[Authorize]`).
- Hand-written TS types for server data; hardcoded URLs (use Wayfinder); client-side data layers (server is the single source of truth).
- Writes outside Actions; controllers beyond the seven CRUD verbs; new libraries outside the approved map in `docs/specs/build-prompt.md`.
- Modules reading layout config, overriding tokens, or injecting global CSS.

Deliberately deferred (designed-for, not built): DB-driven white-label theming via spatie/laravel-settings, per-user dashboard widget reordering (dnd-kit).
