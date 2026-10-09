# Atrium

[![tests](https://github.com/DevPlus31/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/DevPlus31/atrium/actions/workflows/tests.yml)

A modular admin panel and app template: Laravel 13 · Inertia v3 · React 19 · TypeScript · Tailwind v4 · shadcn/ui. A generic **shell** (auth, users, roles, settings, theming) hosts **modules**, one folder per business domain. Strict by construction: PHPStan max, 100% line and type coverage, architecture tests.

## Quick start

```bash
composer setup                   # install, .env, key, migrate, storage link, bun install
php artisan admin:create-user    # first verified admin (also syncs permissions)
composer run dev                 # server, queue, logs, Vite
```

PHP 8.5 runs natively or as `docker compose run --rm app <cmd>`; JS runs with Bun. Behind a load balancer or proxy in production, set `TRUSTED_PROXIES` (`.env.example` lists every setting).

## Reading the project (for people and AI agents)

Read in this order, and treat each one as the source of truth for its topic:

1. **This README**: how the pieces fit, and the rules.
2. **`CLAUDE.md` / `.ai/guidelines`**: coding conventions (Laravel Boost). Activate the skills in `.claude/skills` (`laravel-best-practices`, `wayfinder-development`, `fortify-development`) when working in those areas.
3. **`docs/specs/`**: `build-prompt.md` (architecture, definition of done), `ddd.md` (domain layer), `theming.md`.
4. **`docs/using-the-template.md`**: the downstream workflow and the "how do I add X" cookbook.
5. **The reference modules.** `app-modules/Catalog` is the canonical example; copy its shapes. `Shop` shows events, listeners, notifications, settings and an API.

Where things live:

| Path | What |
|---|---|
| `app/Modules/` | The module contract: base provider, registries, `IndexQuery`, shared DTOs |
| `app/` (rest) | The shell: auth, account settings, notifications, search, shared settings |
| `app-modules/<Name>/` | One module: code, pages, translations and tests, all in one folder |
| `resources/js/` | Shell frontend: layouts, data table, UI kit, hooks |
| `tests/` | Shell tests, architecture tests, fixtures |

Before inventing anything, look for the sibling that already does it. Consistency beats novelty here.

## Building a module

```bash
php artisan make:module Billing                # folder, provider, routes, test; registers the provider
php artisan make:aggregate Billing Invoice     # model, migration, repository, actions, requests, policy,
                                               # query, DTO, controller, pages, lang (every locale), tests
php artisan migrate && php artisan admin:sync-permissions
php artisan typescript:transform && php artisan wayfinder:generate --with-form
composer lint && bun run lint                  # formats the generated code
```

**Request flow (every module, every feature):**

```
route → controller → FormRequest → Action → Data DTO → Inertia page
```

- **Controllers** are `final readonly`, use only the seven CRUD verbs, authorize with `#[Authorize]`, and stay a few lines long. Anything else gets an **invokable single-action controller** (`ExportUsersController`, `PublishProductController`).
- **Actions** (`Actions/`, `final readonly`, one `handle()` method) own every write: transactions, activity log, events. Compose actions rather than duplicating them. Every action has a unit test.
- **FormRequests** hold all validation, and Precognition surfaces it live. Requests expose typed getters (`$request->roles()`).
- **Queries** (`Queries/`, extending `IndexQuery`) build index pages. URL contract: `filter[search]`, `filter[<field>]`, `sort`/`-sort`, `page`, `per_page` ≤ 100.
- **Data** (spatie/laravel-data) DTOs are the only shapes sent to the frontend, each row carrying a `can` map. TypeScript types are **generated**, never hand-written.
- **Frontend:** `useForm(store(), data)` (a Wayfinder route) or `<Form {...store.form()}>`, Wayfinder routes everywhere (`@/routes/...`, no hard-coded URLs), toasts through `Inertia::flash('toast', …)`, deferred props with skeletons, `useBreadcrumbs()`. Semantic theme tokens only; `bun run lint:theme` fails on raw colours and on left/right utilities (use `ms-`/`me-`/`start-`/`end-`).
- **Domain**: when the feature has rules, model them in the domain layer (next section).

**The module provider** extends `App\Modules\ModuleServiceProvider` and fills in only the hooks it needs:

```php
final class BillingServiceProvider extends ModuleServiceProvider
{
    protected function name(): string { return 'billing'; }

    protected function navigation(NavRegistry $nav): void
    {
        $nav->add(module: $this->name(), label: 'Invoices', routeName: 'admin.invoices.index',
            icon: 'receipt', permission: 'invoices.view', group: 'Billing', sort: 10);
        // area: Area::Member (member home) or Area::Settings (an account settings tab)
    }

    protected function permissions(PermissionRegistry $permissions): void
    {
        $permissions->declare('invoices.view', roles: ['admin']);
    }

    protected function widgets(WidgetRegistry $widgets): void { /* dashboard cards */ }
    protected function search(SearchRegistry $search): void { /* command palette */ }
}
```

The base `boot()` is `final` and wires everything else:
- **Admin routes:** `routes/admin.php` is served under `/admin` (names `admin.`) behind `auth`, `verified`, `can:access-panel` and the module switch.
- **Optional routes:** `routes/web.php` for member or guest pages, and `routes/api.php`, served under `/api/v1` with a Sanctum token.
- **Also loaded:** migrations, `lang/*.json`, the module's pages (`<module>::<page>`; pages under `pages/public/` render without the admin layout), and a Pennant flag `module:<name>`.
- **Plain Laravel for the rest:** listeners in `Listeners/` (event discovery), policies in `Policies/` (policy discovery), bindings and commands in `register()`.

**Boundaries.** Modules never import each other, and the shell never imports a module (architecture tests enforce both). Modules talk through events; an event another module may handle is an integration event in the shared kernel (create `app/Domain/Events` when you need the first one). `php artisan module:remove <Name>` must leave a green suite. `MODULES_DISABLED=billing` turns a module off without removing it.

## Domain-Driven Design (for anything with rules)

Each module is a bounded context. Whatever has **business rules** (invariants, a lifecycle, one-time steps, values that must be valid) goes through the domain layer; plain CRUD and settings screens don't need it. The full guide is `docs/specs/ddd.md`.

| Piece | Where | Example |
|---|---|---|
| **Aggregate**: our Eloquent model with behaviour that guards its rules | `Infrastructure/Models` | `Order::place()`, `->transitionTo()`; `Invitation::issue()`, `->accept()`, `->renew()` |
| **Value object**: `final readonly`, validates itself | `Domain/ValueObjects` (or `app/Domain` if shared) | `Sku`, `OrderNumber`, `Email`, `RoleName`, `Money` |
| **Domain exception**: a broken rule | `Domain/Exceptions` | `InvalidOrderTransition`, `InvitationNotPending`, `SystemRoleProtected` |
| **Domain event**: recorded by the aggregate, carries scalars | `Domain/Events` | `OrderStatusChanged`, `InvitationAccepted` |
| **Repository**: an interface, plus an `Eloquent*` implementation bound in `register()`, that handles writes only | `Domain/Repositories`, `Infrastructure/Repositories` | `OrderRepository`, `InvitationRepository` |
| **Listener**: side effects of an event (emails, notifications) | `Listeners/` (auto-discovered) | `NotifyCustomerOfOrderStatus`, `SendInvitationEmail` |

The flow inside an action:
1. Lock the aggregate or create it.
2. Call its behaviour, which throws if a rule is broken.
3. Save it through the repository inside `DB::transaction`.
4. After commit, call `flushDomainEvents()`; listeners do the rest.

Models we don't own (Spatie `Role`, the shell's `User`) use the **lightweight** shape: the rule lives in a value object (`RoleName::isSystem()`), the action enforces it, and policies, requests and DTOs ask the same object. Rules about *who acts* ("grant only roles you hold") are authorization and stay in policies and requests. `ModuleBoundariesTest` keeps `Domain/` free of HTTP, Inertia and DTO code.

## Permissions

All authorization goes through Laravel's **Gate**. Spatie Permission only stores roles and assignments.

1. **Declare** permissions in your provider's `permissions()`. Names must be namespaced (`invoices.view`, `invoices.update`); bare names like `update` are rejected because they would shadow policy methods.
2. **Sync** them with `php artisan admin:sync-permissions`. It's idempotent, gives the declared default roles their permissions, and prunes permissions no module declares.
3. **Check permissions, never roles:**
   - in policies: `return $user->can('invoices.update') && $invoice->isDraft();`
   - in controllers: `#[Authorize('update', 'invoice')]`, never `Gate::authorize()` inside method bodies;
   - in routes: `can:` middleware;
   - in requests: `authorize()`;
   - anywhere else: `$user->can()`.
4. **Ship decisions to the UI** as `can` maps in the DTOs. The frontend never decides access itself.

How access is decided:
- **Panel entry** is the single gate `access-panel` (`User::PANEL_ABILITY`, by default "has the `admin` role"). Redefine that one gate to change who gets into the admin area. Everyone else lands on the member home.
- **The super-admin role** holds every *declared* permission, but policies still apply: "paid orders are kept" binds super-admins too.
- **Granting is capped.** Admins may grant only roles and permissions they hold themselves (so an admin may make another admin, never a super-admin). Only a super-admin may edit or impersonate super-admins, or edit the `admin`/`super-admin` roles, which can never be renamed or deleted.
- **API tokens** carry a subset of the user's permissions. A permission the token lacks is denied, even for a super-admin.

## Shell building blocks

| Need | Use | Example to copy |
|---|---|---|
| Admin menu item, member page, account tab | `navigation()` with `Area::Admin` / `Member` / `Settings` | `Dashboard`, `Api` providers |
| Dashboard card | `widgets()` + `resources/js/widgets/<key>.tsx` | `Users` widgets |
| Command-palette search | `search()` + invokable `Search/<Name>Search` | `Shop/Search/OrdersSearch` |
| Table with bulk actions | `useRowSelection`, `DataTableBulkActions`, `useBulkDelete`; server side `ValidatesBulkSelection` | `Catalog` delete-products |
| Notify a user (bell + email) | extend `AppNotification`, send from a listener | `Shop/Listeners/NotifyCustomerOfOrderStatus` |
| Admin-editable settings | a spatie settings class + `SettingsMigration` + page in the `Settings` menu group | `Shop/Settings/ShopSettings` |
| File uploads | `HasMedia` on the model, `ImageInput` component, `File::image()` rule; files go to `MEDIA_DISK` (`public` or `s3`) | `User` avatar |
| API endpoints | module `routes/api.php`, controllers in `Http/Controllers/Api/V1`, resources in `Http/Resources/V1` | `Shop` orders API |
| Pages for people without an account | `resources/js/pages/public/` + `routes/web.php` | `Users` accept-invitation |

Already in the shell:
- **Accounts:** auth (Fortify, 2FA, passkeys), invitations, impersonation, sessions, avatars, timezone.
- **Communication:** notifications, branded emails, a site-wide announcement banner.
- **Admin tooling:** audit log, Pulse, Horizon and Log Viewer.
- **Platform:** themes (presets, light/dark, layout variants, RTL; see `THEMING.md`), plus security headers and CSP.

## Styling and layout

Every colour, font, radius and size is a CSS variable (a **token**), and one file decides the shell's structure. Restyle by changing token values; relayout by changing the layout config. Never edit pages or module components to change the look. `bun run lint:theme` fails on raw colours (`bg-white`, `#fff`, `oklch(…)` in a class or style) and on physical direction classes (`ml-`, `pr-`, `left-`; use `ms-`, `pe-`, `start-`). Full contract: `THEMING.md`.

| To change | Edit | Notes |
|---|---|---|
| Colours | the `:root` (light) and `.dark` blocks in `resources/css/app.css` | Shadcn names: `--background`, `--primary`, `--muted`, `--sidebar-*`, `--chart-1…5`. Keep WCAG AA on text/background and `primary-foreground`/`primary` |
| Page background | `--background`, **and** the matching `background-color` in `resources/views/app.blade.php` | That inline style paints before the CSS loads; `FirstPaintBackgroundTest` fails when they differ |
| Fonts | `--font-body`, `--font-display`, `--font-mono` in `app.css`, and the font `<link>` in `app.blade.php` | `font-sans` follows `--font-body` |
| Corners, row spacing, sizes | `--radius`, `--density` (table rows), `--header-height`, `--content-max-width` (boxed width), `--sidebar-width`, `--sidebar-width-icon` | Presets may override these too |
| One component's look | `resources/js/components/ui/*` (vendored shadcn) | A shell change: every module gets it. Use tokens, never colours |
| Logo, favicons, auth artwork, error pages, emails | see "Branding a project" in `THEMING.md` | `rg -n "@branding"` lists every spot |

**Adding a theme preset** (users pick it in the header menu or the command palette):
1. Copy `resources/css/themes/ember.css` to `themes/<name>.css`. Keep only the overridden tokens, in a `:root[data-theme='<name>']` block and a `.dark` companion block.
2. `@import` it in `resources/css/app.css`.
3. Add a `case` to `App\Enums\ThemePreset`, then run `php artisan typescript:transform`.
4. Add the option to `themePresetOptions` in `resources/js/components/admin/theme-options.ts`, and its label to `lang/*.json`.
5. If it changes `--background`, add an `html[data-theme='<name>']` (or `html.dark[data-theme='<name>']`) rule to the first-paint style in `app.blade.php`.

**Layout.** The options below are saved per user (topbar settings menu, command palette):

| Option | Values | Default |
|---|---|---|
| `nav_placement` | `sidebar-left` · `sidebar-right` · `topbar` | `sidebar-left` |
| `sidebar_variant` | `sidebar` · `floating` · `inset` | `sidebar` |
| `sidebar_collapsible` | `offcanvas` · `icon` · `none` | `icon` |
| `content_width` | `fluid` · `boxed` | `fluid` |
| `header` | `sticky` · `static` | `sticky` |
| `direction` | `ltr` · `rtl` | `ltr` |

- **Defaults for everyone:** `ResolveUserPreferences::DEFAULT_LAYOUT`; the default preset and appearance are the fallbacks in its `handle()`. A user's own choice still wins.
- **Changing how a variant renders:** `resources/js/layouts/admin-layout.tsx` is the only file that reads the layout config. The pieces it assembles are `components/admin/admin-sidebar.tsx`, `admin-topbar.tsx` and `admin-header.tsx`. Menu items come from module `navigation()` hooks, never from the layout.
- **Adding an option or value:**
  1. Add the case to its enum in `app/Enums`.
  2. If it is a new option, add it to `LayoutConfigData` and `DEFAULT_LAYOUT`.
  3. Run `php artisan typescript:transform`.
  4. Add the branch in `admin-layout.tsx`.
  5. Add the menu entry in `theme-options.ts`, plus its translations.
  6. Free-form or drag-and-drop layouts are out of scope by design.
- **Other layouts:** account settings pages sit inside `AdminLayout` with `layouts/settings/layout.tsx` (its tab list adds the module-registered tabs). Sign-in pages and public module pages (`pages/public/`) use `layouts/auth-layout.tsx`. All of them use the same tokens, so restyling reaches them too.
- **Module pages** render into the content area and never read layout config, position themselves against the shell, or add global CSS. That is what lets every module work in every layout and preset.

After a change, check light and dark, each preset, `rtl`, and the `topbar` layout. `tests/Browser/ThemeMatrixTest.php` covers the theme and layout combinations. Then run the quality gates below.

## Translations

- **Keys:** flat JSON with English keys, used by both `__()` in PHP and `t()` in React. The shell's strings live in `lang/<locale>.json`; a module's in `app-modules/<Name>/lang/<locale>.json`.
- **Languages:** English and French ship. `TranslationKeysTest` fails on any missing key, empty line or dropped `:placeholder`.
- **Adding a locale:** copy every `en.json`, add `lang/<code>/*.php` (start from the French ones), and register it in `config/app.php` → `available_locales`.
- **Formatting:** dates, numbers and money go through `useFormatters()`, in the user's locale and timezone.

## Quality gates (all must pass)

```bash
vendor/bin/pint --dirty --format agent && vendor/bin/rector && vendor/bin/phpstan
bun run test:types && bun run test:lint
bun run build                                                                # browser tests need the production build
XDEBUG_MODE=coverage vendor/bin/pest --parallel --coverage --exactly=100.0   # incl. browser tests
vendor/bin/pest --type-coverage --min=100
```

- **Where tests live:** module tests sit in `app-modules/<Name>/tests/{Feature,Unit,Browser}`.
- **Coverage:** every controller gets guest, forbidden, validation and happy-path tests, and every action gets a unit test.
- **Browser tests** run against the production build: stop `composer run dev` first.
- **Test database:** tests use in-memory SQLite. **Never** run `migrate:fresh` or `--env=testing` database commands against the dev database.

## Rules that fail review

- Writes outside actions; controller methods beyond the CRUD verbs; `Gate::authorize()` in method bodies; role checks.
- Hand-written TypeScript types for server data; hard-coded URLs; client-side data stores.
- Raw colours, palette classes or physical direction utilities in frontend code.
- A module importing another module, or the shell importing a module.
- New dependencies outside the approved list in `docs/specs/build-prompt.md`.

## Using Atrium as a template

1. Create a project from this repo, set up `.env`, then follow the quick start.
2. Build **only modules**. Leave the shell (`app/Modules`, layouts, data table, theming, core modules) untouched so upstream merges stay clean.
3. Pull improvements with `git merge atrium/main`. Make shell changes here first, then merge them into projects.
4. Drop what you don't need: `php artisan module:remove Shop` (also `Catalog` and `Api`).

**Multi-tenancy is deliberately left to each project.** Atrium is single-tenant. Decide on tenancy before the first module.

## License

MIT. Built on [nunomaduro/laravel-starter-kit-inertia-react](https://github.com/nunomaduro/laravel-starter-kit-inertia-react).
