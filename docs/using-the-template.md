# Using Atrium as a Template

Atrium is the upstream shell; your project lives entirely in **modules** under
`app-modules/<Name>/`. This guide is the downstream workflow. See
[`docs/specs/ddd.md`](specs/ddd.md) for the DDD contract and
[`docs/specs/build-prompt.md`](specs/build-prompt.md) for the module contract.

> All PHP runs through Docker (`docker compose run --rm app <cmd>`); JS runs
> natively via `bun`. Adjust to your own environment if you are not using the
> bundled Docker setup.

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
docker compose run --rm --no-deps app php artisan admin:create-user   # first verified admin
bun install
docker compose up          # serves the app on :8000
bun run dev                # HMR
```

## 2. Build a domain — the core loop

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
create / edit pages + columns; and a controller feature test that covers 100%
of the generated backend. It also wires the module provider (repository
binding, nav item, and `view/create/update/delete` permissions).

Run the printed follow-ups:

```bash
docker compose run --rm --no-deps app php artisan migrate
docker compose run --rm --no-deps app php artisan admin:sync-permissions
docker compose run --rm --no-deps app php artisan typescript:transform
docker compose run --rm --no-deps app php artisan wayfinder:generate --with-form
bun run lint
```

You now have a working, authorized, fully-tested admin screen with no
hand-written boilerplate. The generated aggregate ships with a `name` and
`description` field — replace those with your own.

## 3. Add the domain-specific parts by hand

The generator cannot infer your invariants — follow the **Catalog** module
(`app-modules/Catalog`) as the worked reference:

| To add… | Follow Catalog's… | Rule |
|---|---|---|
| A validated value (money, SKU, email) | `Domain/ValueObjects/{Sku,Money}` | `final readonly`, validate in the constructor, throw a module `DomainException`. **Never put a value object in a Data DTO** — DTOs stay scalar (keeps TypeScript generation working). |
| A "something happened" event | `Domain/Events/ProductPublished` + `Product::publish()` | The model records via `recordThat(...)`; the Action calls `flushDomainEvents()` after commit. |
| An invariant (can't publish twice) | `ProductAlreadyPublished` + `PublishProduct` | Throw a `DomainException`; catch it at the controller boundary for a user-facing flash. |
| A non-CRUD action (publish, export) | `PublishProductController` (invokable) | Controllers keep only the seven CRUD verbs; everything else is its own invokable controller. |

## 4. Verify — the gates are the acceptance bar

```bash
docker compose run --rm --no-deps app vendor/bin/pint --dirty --format agent
docker compose run --rm --no-deps app vendor/bin/rector
docker compose run --rm --no-deps app vendor/bin/phpstan
docker compose run --rm app php artisan test --compact
bun run build && bun run test:types && bun run test:lint
```

## 5. Pull upstream improvements

Because you only ever touch your own modules, shell upgrades merge
near-conflict-free:

```bash
git fetch atrium && git merge atrium/main
```

## The one rule that keeps this working

**Build only under `app-modules/<Yours>/`. Never edit shell code** — `app/`,
`app/Domain/`, `resources/js/components/data-table/`, `resources/js/layouts/`,
the theming CSS, or the generic modules. The `ModuleBoundariesTest` enforces
this: modules cannot import one another, so contexts stay isolated and merges
stay clean. Improve the shell *upstream* in Atrium, then merge it down —
never the other way around.
