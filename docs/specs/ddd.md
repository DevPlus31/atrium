# DDD Guide — Pragmatic Domain-Driven Design

> Extends `docs/specs/build-prompt.md`. Cross-links `docs/specs/theming.md`.
> Canonical reference module: **Catalog** (`app-modules/Catalog`). Lightweight example: **Users**.

Atrium models each module as a **bounded context** and adds a small, enforced tactical-DDD layer on top of the existing Action pattern. This is **pragmatic Laravel-DDD**, not full hexagonal: the Eloquent model *is* the entity and the persistence mechanism. We add Value Objects, Domain Events, Domain Exceptions, and Repository interfaces — without rewriting the kit's Actions/Queries/Http conventions.

## Layers

A module (`app-modules/<Name>/`) is organised into four layers. Existing folders keep their names; DDD adds `Domain/` and `Infrastructure/`.

| Layer | Location | Contents |
|---|---|---|
| **Domain** | `Domain/` | `ValueObjects/`, `Events/`, `Exceptions/`, `Repositories/` (interfaces). Framework-light, no HTTP/Inertia/DTO deps. |
| **Infrastructure** | `Infrastructure/` | `Models/` (Eloquent aggregates), `Repositories/` (`Eloquent*` implementations). |
| **Application** | `Actions/`, `Queries/`, `Data/` | Actions = command use-cases (own `DB::transaction`, activity log, event flush). Queries = read use-cases (spatie/query-builder). Data = Inertia DTOs. |
| **Presentation** | `Http/`, `Policies/`, `routes/`, `resources/js/` | Controllers (7 CRUD verbs; non-CRUD ⇒ invokable), FormRequests, Policies, React pages. |

Shared kernel lives in `app/Domain/`: `Contracts/{DomainEvent,RecordsDomainEvents,ValueObject,Repository}`, `Concerns/InteractsWithDomainEvents`, `Exceptions/DomainException`.

## Dependency rules (enforced by `tests/Unit/Architecture/ModuleBoundariesTest.php`)

- **Modules never import other modules.** Cross-context interaction goes through Laravel events/contracts, never direct `use Modules\Other\...`.
- **Domain purity.** Files under `Domain/` may not depend on `Inertia\`, `Illuminate\Http\`, `Illuminate\Routing\`, `Spatie\LaravelData\`, or their own module's `Http/`, `Actions/`, `Queries/`, `Data/`, or `Infrastructure\Repositories\`. Domain *may* reference the aggregate in `Infrastructure\Models` (the entity) and anything under `App\Domain`.
- **Value Objects are `final readonly`** and implement `App\Domain\Contracts\ValueObject`.
- **Every Domain repository interface has an `Eloquent*` implementation** in `Infrastructure/Repositories/`.

These tests iterate `app-modules/*`, so every present and future module is covered with no per-module edits.

## Value Objects

Immutable, `final readonly`, validate in the constructor, throw a module `DomainException` on invalid input. Examples: `Modules\Catalog\Domain\ValueObjects\{Sku,Money}`, `Modules\Users\Domain\ValueObjects\Email`.

**Scalar-DTO rule (critical):** Value Objects must **never** appear as properties on a `spatie/laravel-data` DTO. The TypeScript transformer only understands scalars; a VO property yields `any` or breaks `tsc`. DTOs expose scalars (`price_cents: int`, `currency: string`), and Actions construct VOs internally for validation/normalisation.

## Domain Events

1. The aggregate (Eloquent model) uses `App\Domain\Concerns\InteractsWithDomainEvents` and implements `App\Domain\Contracts\RecordsDomainEvents`.
2. Domain behaviour records events: `$this->recordThat(new ProductPublished($this->id, $this->sku))`.
3. The Action persists inside `DB::transaction`, then calls `$aggregate->flushDomainEvents()` **after the transaction returns** (i.e. after commit), so events never fire for rolled-back work.

Events implement `App\Domain\Contracts\DomainEvent` and carry scalars only. See `Modules\Catalog\Actions\PublishProduct` and `Modules\Catalog\Infrastructure\Models\Product::publish()`.

## Repositories

A Domain interface (`Domain/Repositories/<Agg>Repository`, extends `App\Domain\Contracts\Repository`) with an `Eloquent<Agg>Repository` implementation in `Infrastructure/Repositories/`. Bind them in the module provider's **`register()`** (the base `boot()` is `final`):

```php
public function register(): void
{
    Gate::policy(Product::class, ProductPolicy::class);
    $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
}
```

Repositories own **writes** (`save`, `delete`). Reads stay in Query classes and route-model binding (CQRS-lite) — do not add find/list methods to repositories unless a write-side consumer needs them.

## Adding a module / aggregate

```bash
php artisan make:module Catalog          # scaffolds the bounded context + registers the provider
php artisan make:aggregate Catalog Product  # scaffolds the full CRUD DDD slice + tests

php artisan migrate
php artisan admin:sync-permissions
php artisan typescript:transform
php artisan wayfinder:generate --with-form
```

`make:module` appends the provider to `bootstrap/providers.php` and the `$moduleProviders` array in `tests/Unit/ArchTest.php` (both idempotent). Generated code and its tests ship gates-green.

Domain-specific pieces the generator cannot infer — Value Objects, Domain Events, and invariants like `publish()` — are added by hand, following the Catalog module and this guide.

## Definition of done

PHPStan max · Rector · Pint · Pest 100% line **and** type coverage · `tsc --noEmit` · OxLint/Oxfmt · `theme-lint` · arch tests (module contract + boundaries) · browser smoke test. Migrations are **up-only** (module migrations are measured for coverage; an un-exercised `down()` fails the gate).

## Downstream continuation

Build only modules. Run the generators, add domain behaviour by following Catalog. Adding a module touches exactly two shell files (both auto-patched). Pull shell/DDD improvements from upstream Atrium via `git merge` — the boundary tests keep every context isolated, so merges stay near conflict-free.
