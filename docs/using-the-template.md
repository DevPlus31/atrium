# Using Atrium as a Template

Atrium is the upstream shell; your project lives entirely in **modules** under
`app-modules/<Name>/`. This guide is the downstream workflow. See
[`docs/specs/ddd.md`](specs/ddd.md) for the DDD contract and
[`docs/specs/build-prompt.md`](specs/build-prompt.md) for the module contract.

> The PHP commands below use the bundled Docker setup
> (`docker compose run --rm app <cmd>`); with PHP 8.5 installed locally, run
> them directly (`php artisan …`). JS runs natively via `bun`.

## 1. Start the project

Use GitHub's **"Use this template"** (or clone), then wire the upstream so you
can pull shell/DDD improvements later:

```bash
git remote add atrium git@github.com:DevPlus31/atrium.git
git fetch atrium
```

First-run setup:

```bash
docker compose run --rm app composer install
cp .env.example .env
docker compose run --rm --no-deps app php artisan key:generate
docker compose run --rm --no-deps app php artisan migrate
docker compose run --rm --no-deps app php artisan storage:link   # serves uploads from the public disk
docker compose run --rm --no-deps app php artisan admin:create-user   # first verified admin
bun install
docker compose up          # serves the app on :8000
bun run dev                # HMR
```

## 2. Drop the example modules you don't need

`Catalog` and `Shop` exist to show the patterns, and `Api` (personal access
tokens) is optional; the core modules (`Users`, `Roles`, `Audit`, `Dashboard`,
`Settings`, `System`) are the admin panel itself. Remove one — its code,
pages, translations and tests all live in its folder:

```bash
docker compose run --rm --no-deps app php artisan module:remove Shop
docker compose run --rm --no-deps app php artisan admin:sync-permissions   # prunes its permissions
docker compose run --rm --no-deps app php artisan typescript:transform
docker compose run --rm --no-deps app php artisan wayfinder:generate --with-form
bun run build
```

The command lists the tables the module created; drop them with a migration
of your own when you no longer need the data. To hide a module without
removing it, set `MODULES_DISABLED=shop` in `.env`.

## 3. Build a domain — the core loop

A **bounded context = one module**; a business entity = an **aggregate**.

```bash
# One command scaffolds the bounded context + registers the provider.
docker compose run --rm --no-deps app php artisan make:module Shop

# One command scaffolds the full CRUD DDD slice for an aggregate + wires the provider.
docker compose run --rm --no-deps app php artisan make:aggregate Shop Order
```

`make:aggregate` generates, gates-green: the Domain repository contract; the
Eloquent model, migration, factory, and repository implementation; Create /
Update / Delete actions; an index Query; a scalar DTO; the controller, form
requests, and policy; the `routes/admin.php` resource route; React index /
create / edit pages + columns; a controller feature test and unit tests for the
three actions, together covering 100% of the generated backend; and the
module's `lang/en.json` plus one catalogue per other locale (`fr.json`, in
English until translated — the command says how many strings). It also wires
the module provider (repository binding, nav item, and
`view/create/update/delete` permissions).

It scaffolds **one aggregate per module**: the routes file, the pages and the
provider wiring markers are per module, so a second `make:aggregate` on the
same module is refused rather than overwriting the first. Add further
aggregates to a module by hand, or give them their own module.

Run the printed follow-ups:

```bash
docker compose run --rm --no-deps app php artisan migrate
docker compose run --rm --no-deps app php artisan admin:sync-permissions
docker compose run --rm --no-deps app php artisan typescript:transform
docker compose run --rm --no-deps app php artisan wayfinder:generate --with-form
composer lint && bun run lint
```

You now have a working, authorized, fully-tested admin screen with no
hand-written boilerplate. The generated aggregate ships with a `name` and
`description` field — replace those with your own.

## 4. Add the domain-specific parts by hand

The generator cannot infer your invariants — follow the **Catalog** module
(`app-modules/Catalog`) as the worked reference:

| To add… | Follow Catalog's… | Rule |
|---|---|---|
| A validated value (money, SKU, email) | `Domain/ValueObjects/` (e.g. Catalog's `Sku`; shared ones such as `Money` live in `app/Domain/ValueObjects`) | `final readonly`, validate in the constructor, throw a module `DomainException`. **Never put a value object in a Data DTO** — DTOs stay scalar (keeps TypeScript generation working). |
| A "something happened" event | `Domain/Events/ProductPublished` + `Product::publish()` | The model records via `recordThat(...)`; the Action calls `flushDomainEvents()` after commit. |
| An invariant (can't publish twice) | `Product::publish()` throwing `ProductAlreadyPublished`, called by `PublishProduct` | The rule lives on the model: it throws a module `DomainException`. The action locks the row first (`$repository->lockForUpdate()`) so two requests can't both pass the check. Policies keep the UI from offering the action; if a stale page still triggers it, the shell turns the exception into an error toast (a 409 for API clients) — no try/catch in controllers. |
| A non-CRUD action (publish, export) | `PublishProductController` (invokable) | Controllers keep only the seven CRUD verbs; everything else is its own invokable controller. |

## 5. Hook into the rest of the app

Everything a module contributes is declared in its provider; nothing in the
shell or in another module is edited:

| To… | Declare in your provider |
|---|---|
| Add an admin menu item, a member-area page, or a tab in every user's account settings | `navigation()`: `$nav->add(..., area: Area::Admin` / `Area::Member` / `Area::Settings)` |
| Add permissions (and grant them to roles by default) | `permissions()`: `$permissions->declare('orders.view', roles: ['admin'])` |
| Add a card to the admin dashboard or the member home | `widgets()`: `$widgets->declare(..., area: …)`; the resolver gets the viewer and may return null to hide the card; the component is `resources/js/widgets/<key>.tsx` |
| React to something another part of the app did | Nothing to declare: put a listener in your module's `Listeners/` folder and type-hint the event in `handle()` (Laravel event discovery), e.g. `handle(Registered $event)` for sign-ups. To let other modules react to *your* domain, publish an integration event in the shared kernel (`app/Domain/Events`, created with the first one) upstream |
| Authorize your models | Nothing to declare: Laravel's policy discovery finds `Modules\<Name>\Policies\<Model>Policy` |
| Bind interfaces, policies for models outside your module, console commands | `register()` |
| Ship translated strings | `lang/en.json` in the module, plus one file per other locale (`fr.json`); the generator writes them all, the other locales in English until translated |
| Let a model own files (photos, attachments) | `implements HasMedia` + `use InteractsWithMedia` and declare collections/conversions on the model (see `User`); the `ImageInput` component uploads, `File::image()` validates |
| Add a page for people without an account (signed link, public form) | Put it under `resources/js/pages/public/` and route it from `routes/web.php`: it renders without the admin shell and wraps itself in `AuthLayout` (see Users' `public/accept-invitation`) |
| Expose a module over the API | `routes/api.php` in the module (served under `/api/v1`, token-authenticated) with controllers in `Http/Controllers/Api/V1` and Eloquent resources in `Http/Resources/V1`; authorize with `#[Authorize]` as usual — tokens narrow permissions (see Shop's orders API) |
| Let admins act on many rows at once | `useRowSelection` + `<DataTableBulkActions>` on the index page; a `ValidatesBulkSelection` request, an invokable controller and an action composing the single-row one (see Catalog's `DeleteProductsController`) |
| Make a module's records findable from the command palette | `search()`: `$search->add(module: $this->name(), label: '…', searcher: <Name>Search::class, permission: '…')` with an invokable `Search/<Name>Search` returning `SearchResultData` (see Shop's `OrdersSearch`) |
| Tell a user something happened (bell + email) | A `Notifications/<Name>Notification` extending `AppNotification` with `message()`, sent from a listener in `Listeners/` (see Shop's `NotifyCustomerOfOrderStatus`) |
| Give admins editable settings | A `Settings/<Name>Settings` class (spatie/laravel-settings), a `SettingsMigration` in `Database/Migrations` with the defaults, a page, and a menu item in the `Settings` group (see Shop's default currency) |

Modules never import each other (`ModuleBoundariesTest`). When two modules
must share a concept, put it in the shared kernel (`app/Domain`) upstream.

## 6. Verify — the gates are the acceptance bar

```bash
docker compose run --rm --no-deps app vendor/bin/pint --dirty --format agent
docker compose run --rm --no-deps app vendor/bin/rector
docker compose run --rm --no-deps app vendor/bin/phpstan
bun run test:types && bun run test:lint
bun run build        # browser tests run against the production build (stop `bun run dev` first)
docker compose run --rm app sh -c 'XDEBUG_MODE=coverage vendor/bin/pest --parallel --coverage --exactly=100.0'
docker compose run --rm app vendor/bin/pest --type-coverage --min=100
```

## 7. Pull upstream improvements

Because you only ever touch your own modules, shell upgrades merge
near-conflict-free:

```bash
git fetch atrium && git merge atrium/main
```

## The one rule that keeps this working

**Build only under `app-modules/<Yours>/`. Never edit shell code** — `app/`,
`app/Domain/`, `resources/js/components/data-table/`, `resources/js/layouts/`,
the theming CSS, or the generic modules. That part is a convention; what the
`ModuleBoundariesTest` enforces is that modules never import one another (and
the shell never imports a module), so contexts stay isolated and merges stay
clean. Extension points never require editing a generic module (see
§5): menus, settings tabs, permissions, widgets, listeners and translations are
all declared or shipped by your module. Improve the shell *upstream* in Atrium, then merge it down —
never the other way around.
