# DDD Guide — Pragmatic Domain-Driven Design

> Extends `docs/specs/build-prompt.md`. Cross-links `docs/specs/theming.md`.
> Canonical reference module: **Catalog** (`app-modules/Catalog`). Richest example: **Shop**. Lightweight examples (vendor models): **Roles**, **Users**.

Atrium models each module as a **bounded context** and adds a small, enforced tactical-DDD layer on top of the existing Action pattern. This is **pragmatic Laravel-DDD**, not full hexagonal: the Eloquent model *is* the entity and the persistence mechanism. We add Value Objects, Domain Events, Domain Exceptions, and Repository interfaces — without rewriting the kit's Actions/Queries/Http conventions.

## When to use the domain layer

Use it for **anything with business rules**: invariants ("a system role keeps its name"), state machines ("pending → paid → shipped"), one-time transitions ("an invitation is accepted once, before it expires") or values that must always be valid (SKU, email, money). Plumbing without rules — settings screens, token management, dashboards, log viewers — stays as plain Actions over Eloquent or settings classes; do not add repositories for their own sake.

Two shapes, depending on who owns the model:

- **Our model → full aggregate.** Behaviour lives on the model (`Order::place()`, `->transitionTo()`, `Invitation::issue()`, `->accept()`, `->renew()`): it checks its invariants, throws a module `DomainException`, and records domain events. Actions load or create it, call the behaviour, save through the repository inside `DB::transaction`, then `flushDomainEvents()`. Side effects (emails, notifications) are listeners on those events. Examples: **Shop** (orders), **Catalog** (products), **Users** (invitations).
- **A vendor or shell model (Spatie `Role`, `App\Models\User`) → lightweight.** The rules go into a value object and the actions: `RoleName::isSystem()` decides what is protected, `UpdateRole`/`DeleteRole` throw `SystemRoleProtected`, and a repository owns the writes. Policies, FormRequests and DTOs ask the same value object, so the rule exists once. Examples: **Roles**, **Users** (the kernel's `Email`, `UserRepository`).

Rules that depend on *who acts* (you may only grant roles you hold; you cannot remove your own admin role; a token carries only your permissions) are authorization, not domain: they stay in policies and FormRequests.

## Layers

A module (`app-modules/<Name>/`) is organised into four layers. Existing folders keep their names; DDD adds `Domain/` and `Infrastructure/`.

| Layer | Location | Contents |
|---|---|---|
| **Domain** | `Domain/` | `ValueObjects/`, `Events/`, `Exceptions/`, `Repositories/` (interfaces). Framework-light, no HTTP/Inertia/DTO deps. |
| **Infrastructure** | `Infrastructure/` | `Models/` (Eloquent aggregates), `Repositories/` (`Eloquent*` implementations). |
| **Application** | `Actions/`, `Queries/`, `Data/` | Actions = command use-cases (own `DB::transaction`, `AuditLog::record()`, event flush). Queries = read use-cases (spatie/query-builder). Data = Inertia DTOs. |
| **Presentation** | `Http/`, `Policies/`, `routes/`, `resources/js/` | Controllers (7 CRUD verbs; non-CRUD ⇒ invokable), FormRequests, Policies, React pages. |

Shared kernel lives in `app/Domain/`: `Contracts/{DomainEvent,RecordsDomainEvents,ValueObject,Repository}`, `Concerns/InteractsWithDomainEvents`, `Exceptions/{DomainException,InvalidEmailException,InvalidMoneyException,LastAdministrator}`, integration events in `Events/` (create it with the first one), and the cross-context value objects `ValueObjects/Money` (Catalog, Shop) and `ValueObjects/Email` (the shell's accounts, Users, Shop). Put a value object in the kernel only when more than one bounded context needs it; modules still never import each other.

## Dependency rules (enforced by `tests/Unit/Architecture/ModuleBoundariesTest.php`)

- **Modules never import other modules.** Cross-context interaction goes through Laravel events/contracts, never direct `use Modules\Other\...`.
- **Domain purity.** Files under `Domain/` may not depend on `Inertia\`, `Illuminate\Http\`, `Illuminate\Routing\`, `Spatie\LaravelData\`, or their own module's `Http/`, `Actions/`, `Queries/`, `Data/`, or `Infrastructure\Repositories\`. Domain *may* reference the aggregate in `Infrastructure\Models` (the entity) and anything under `App\Domain`.
- **Value Objects are `final readonly`** and implement `App\Domain\Contracts\ValueObject`.
- **Every Domain repository interface has an `Eloquent*` implementation** in `Infrastructure/Repositories/`.

These tests iterate `app-modules/*`, so every present and future module is covered with no per-module edits.

## Value Objects

Immutable, `final readonly`, validate in the constructor, throw a module `DomainException` on invalid input. Examples: `Modules\Catalog\Domain\ValueObjects\Sku`, `Modules\Shop\Domain\ValueObjects\OrderNumber`, `Modules\Roles\Domain\ValueObjects\RoleName`, and the shared kernel's `App\Domain\ValueObjects\Money` and `App\Domain\ValueObjects\Email`.

**Scalar-DTO rule (critical):** Value Objects must **never** appear as properties on a `spatie/laravel-data` DTO. The TypeScript transformer only understands scalars; a VO property yields `any` or breaks `tsc`. DTOs expose scalars (`price_cents: int`, `currency: string`), and Actions construct VOs internally for validation/normalisation.

## Domain Events

1. The aggregate (Eloquent model) uses `App\Domain\Concerns\InteractsWithDomainEvents` and implements `App\Domain\Contracts\RecordsDomainEvents`.
2. Domain behaviour records events: `$this->recordThat(new ProductPublished($this->id, $this->sku))`.
3. The Action persists inside `DB::transaction`, then calls `$aggregate->flushDomainEvents()` **after the transaction returns** (i.e. after commit), so events never fire for rolled-back work.
4. Laravel's own events and mails sent from inside an action (`Registered`, a verification email) go through `DB::afterCommit(...)`: it waits for the *outermost* transaction, so they also hold when the action runs inside another one (e.g. `AcceptInvitation` → `CreateUser`).

Events implement `App\Domain\Contracts\DomainEvent` and carry scalars only. See `Modules\Catalog\Actions\PublishProduct` and `Modules\Catalog\Infrastructure\Models\Product::publish()`.

## Repositories

A Domain interface (`Domain/Repositories/<Agg>Repository`, extends `App\Domain\Contracts\Repository`) with an `Eloquent<Agg>Repository` implementation in `Infrastructure/Repositories/`. Bind them in the module provider's **`register()`** (the base `boot()` is `final`):

```php
public function register(): void
{
    $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
}
```

Repositories own **writes** (`save`, `delete`). Reads stay in Query classes and route-model binding (CQRS-lite) — do not add find/list methods to repositories unless a write-side consumer needs them. The one write-side read every changing aggregate gets is `lockForUpdate($aggregate)`: the action calls it first inside the transaction and checks the rule on the fresh, locked row, so two simultaneous requests cannot both pass it (`UpdateOrder`, `PublishProduct`, `AcceptInvitation`, `UpdateRole`).

## Domain exceptions at the edge

A broken rule throws a module `DomainException`. Policies and FormRequests normally stop the request before that (and give a friendly message); the exception is the guarantee when they could not — a stale page, a race, a console or queue caller. Nothing catches it in controllers: the shell's exception handler (`bootstrap/app.php`) sends people back with an error toast and API clients a 409 with the message, and does not report it as an error.

## Adding a module / aggregate

```bash
php artisan make:module Catalog          # scaffolds the bounded context + registers the provider
php artisan make:aggregate Catalog Product  # scaffolds the full CRUD DDD slice + tests (one aggregate per module)

php artisan migrate
php artisan admin:sync-permissions
php artisan typescript:transform
php artisan wayfinder:generate --with-form
```

`make:module` appends the provider to `bootstrap/providers.php` (it refuses to run when the module folder already exists); `tests/Unit/ArchTest.php` reads the module providers from that file, so nothing else needs editing. Generated tests go to the module's `tests/` folder. `make:aggregate` adds the strings its pages and controller translate to the module's `lang/en.json` (skipping those the shell catalogue already has) and copies them, still in English, into every other locale's catalogue (it prints how many to translate); `tests/Unit/TranslationKeysTest.php` fails when any translated string is missing. `module:remove` undoes `make:module`. Generated code and its tests pass every gate once the formatters have run (`composer lint`, as the generator prints): line wrapping depends on the names you choose.

Domain-specific pieces the generator cannot infer — Value Objects, Domain Events, and invariants like `publish()` — are added by hand, following the Catalog module and this guide.

## Definition of done

PHPStan max · Rector · Pint · Pest 100% line **and** type coverage · `tsc --noEmit` · OxLint/Oxfmt · `theme-lint` · arch tests (module contract + boundaries) · browser smoke test (`composer test` runs them; commands in the README's "Testing and quality gates"). Migrations are **up-only** (module migrations are measured for coverage; an un-exercised `down()` fails the gate).

## Downstream continuation

Build only modules. Run the generators, add domain behaviour by following Catalog. Adding or removing a module touches one shell file, auto-patched: `bootstrap/providers.php`. Everything else — code, pages, translations, tests — lives in the module folder. Pull shell/DDD improvements from upstream Atrium via `git merge` — the boundary tests keep every context isolated, so merges stay near conflict-free.
