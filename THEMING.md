# Atrium Theming

How the admin panel re-skins and re-shapes itself. Governed by
`docs/specs/theming.md`; this file is the working reference.

## The one hard rule

**Semantic design tokens only.** No raw Tailwind palette classes
(`bg-white`, `text-blue-600`), no color literals (hex/rgb/oklch) in
`className` or styles, and no physical direction utilities (`ml-`, `pl-`,
`left-`, `text-left`, `rounded-l*`, `border-l*`) — use logical ones (`ms-`,
`ps-`, `start-`, `text-start`, `rounded-s*`, `border-s*`). Enforced by `bun run lint:theme`
(part of `bun run lint` / `bun run test:lint`, so it runs in every
`composer test` gate)
over `resources/js/{layouts,components,pages,hooks}` and every
`app-modules/*/resources/js` (color rules also cover `resources/js/lib` and
the `app.tsx`/`ssr.tsx` entry points; the vendored shadcn components in
`resources/js/components/ui` get the color rules only). Structural sizes come from tokens too:
`--header-height` and `--content-max-width` (boxed layout). A genuinely-directional exception is silenced
with `// theme-lint-allow-next-line <rule>` on the line above.

## Token contract

Defined in `resources/css/app.css` (Tailwind v4 `@theme` + CSS variables on
`:root` / `.dark`), following the shadcn/ui convention:

- **Color roles**: `background`, `foreground`, `card(-foreground)`,
  `popover(-foreground)`, `primary(-foreground)`, `secondary(-foreground)`,
  `muted(-foreground)`, `accent(-foreground)`, `destructive(-foreground)`,
  `success(-foreground)`, `overlay`, `border`, `input`, `ring`,
  `chart-1`…`chart-5`, and the `sidebar-*` set.
- **Structural tokens**: `--radius`, font families (`--font-display`,
  `--font-body`, `--font-mono`), and `--density` (row padding, consumed by
  the shared data-table).

Charts consume `var(--chart-*)` (grid/axes via `border`/`muted-foreground`);
lucide icons inherit `currentColor`. If a needed role has no token, **add a
token to the contract** — never inline a color. New tokens (and new layout
options) are shell-level API changes and are reviewed like one.

## Appearance and presets

- Appearance: `light` / `dark` / `system` per user. Dark mode is the `.dark`
  class on `<html>` (class strategy — same mechanism as presets, not a
  second system).
- Presets ship as pure token-override blocks in `resources/css/themes/`
  (`ember.css`, `contrast.css`), scoped to `:root[data-theme='<name>']` with
  a `.dark` companion block, and registered as:
  1. a `case` in `App\Enums\ThemePreset`;
  2. an `@import` in `resources/css/app.css`;
  3. an entry in `themePresetOptions`
     (`resources/js/components/admin/theme-options.ts`).
  That is the whole recipe for **adding a preset**: copy an existing block,
  adjust values (keep WCAG AA on `foreground`/`background` and
  `primary-foreground`/`primary`), register in those three places.
- Persistence: cookies (`appearance`, `theme`, `layout`; js-readable,
  unencrypted) for guests and first paint; mirrored to `users.appearance` /
  `users.theme` / `users.layout` when authenticated via
  `PATCH settings/preferences`. `HandleAppearance` stamps `<html>`
  server-side (class, `data-theme`, `dir`) so there is no flash of the wrong
  theme, and `HandleInertiaRequests` shares the resolved values as props.

## Layout contract

`App\Modules\Data\LayoutConfigData` (generated to TS, shared as the `layout`
prop) — enumerated options only, no free-form layout:

| Option | Values |
| --- | --- |
| `nav_placement` | `sidebar-left` · `sidebar-right` · `topbar` |
| `sidebar_variant` | `sidebar` · `floating` · `inset` |
| `sidebar_collapsible` | `offcanvas` · `icon` · `none` |
| `content_width` | `fluid` · `boxed` |
| `header` | `sticky` · `static` |
| `direction` | `ltr` · `rtl` |

`AdminLayout` is the **single interpreter** of this config (sidebar variants
via the shadcn Sidebar primitive as-is; the topbar variant composed from
shadcn primitives; Radix `DirectionProvider` wired from `direction`). No
page, component, or module may branch on layout config — modules render
into the content region and are layout-agnostic.

## Client API

`useThemePreference()` (`resources/js/hooks/use-appearance.tsx`) exposes
`appearance`, `theme`, `layout` and their setters; setters stamp the root
element immediately, write cookies, and persist server-side when
authenticated. Toggles live in the shell header (`ThemeSettingsMenu`) and
the command palette (appearance, preset, nav placement, sidebar collapse).

## Module rules

Modules consume tokens; they never define colors/fonts/radii/shadows,
override root tokens, inject global CSS, read layout config, or position
themselves relative to the shell. The theme-lint gate covers module frontend
code with the same rules.

## Later (designed-for, not built)

Database-driven white-label theming will inject token values as an inline
CSS-variable block overriding the active preset (spatie/laravel-settings).
Keep presets as pure token-value blocks and never couple a component to a
preset name so this stays a drop-in.

## Branding a project

Everything brand-specific is tagged `@branding`; `rg -n "@branding"` lists it.
To make Atrium yours:

| What | Where |
|---|---|
| Product name (sidebar, tab title, auth and error pages, mail) | `APP_NAME` in `.env` — no code change |
| Logo mark | `resources/js/components/app-logo-icon.tsx` (keep `currentColor`) |
| Favicons | `public/favicon.svg`, `public/favicon.ico`, `public/apple-touch-icon.png` |
| Colours and fonts | Tokens in `resources/css/app.css` (or a preset in `resources/css/themes/`); web fonts in `resources/views/app.blade.php`, whose first-paint background must match `--background` |
| Auth pages pitch and artwork | `resources/js/layouts/auth-layout.tsx` (tagline, highlights) and `resources/js/components/auth/atrium-hall-art.tsx` |
| Error pages (403, 404, 429, 500, 503) | `resources/js/pages/error.tsx` (copy); rendered by `AppServiceProvider::renderErrorPage()` |
| Emails | Already branded: the logo from Settings → General (or `APP_NAME`), the support address in the footer, light and dark. `resources/views/vendor/mail/html/{layout,header,message}.blade.php` and `text/message.blade.php` override Laravel's; everything else falls back to its defaults. Colours: the dark-mode block in `layout.blade.php`, mirroring your dark tokens (mail apps need hex) |

Brand copy lives in `lang/<locale>.json` like every other string, so it is
translated the same way.
