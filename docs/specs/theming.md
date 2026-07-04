# Build Prompt — Atrium Theming System (focused)

> Extension to the main Atrium build prompt (`docs/specs/build-prompt.md`); all its directives (strictness, library map, module contract, no custom code where a library exists) still apply.

## Scope

This prompt governs everything visual-theming related in the Atrium admin panel (Laravel 13 / Inertia v3 / React 19 / Tailwind v4 / shadcn/ui / Radix / lucide-react / Recharts).

## Goal

The panel must be fully themable in two composable dimensions: (1) visual theming — every surface re-skins by swapping design-token values; (2) layout theming — the shell's structure (navigation placement, sidebar behavior, content width, direction) is selected from an enumerated set of layout variants via configuration, with zero module changes in either case. Ship light/dark/system per-user appearance, named preset themes, and the layout variant system now; design so database-driven runtime theming (white-label) can be added later as a Settings-module feature without refactoring.

## The one hard rule (enforced everywhere, forever)

Semantic design tokens only. Raw palette utilities are forbidden in all admin and module components.

- Allowed: token-based utilities — background, foreground, primary, primary-foreground, secondary, muted, muted-foreground, accent, destructive, border, input, ring, card, popover, chart-1…chart-5, sidebar and its sub-tokens, radius (e.g. bg-background, text-muted-foreground, border-border, bg-primary, rounded via the standard rounded utilities).
- Forbidden: any raw Tailwind palette class (bg-white, bg-slate-100, text-blue-600, border-gray-200, hex/rgb/oklch literals in className or inline styles), and any color literal inside module code.
- If a needed role has no token, add a new semantic token to the theme contract — never inline a color. Adding a token is a shell-level change reviewed like an API change.
- Enforce mechanically: add an OxLint rule / custom lint check (or a CI grep gate) that fails the build on raw palette classes and color literals under the admin and module frontend directories. Add this check to the Definition of Done.

## Theme contract (the token set)

- Base the contract exactly on shadcn/ui's CSS-variable convention (the tokens listed above), defined via Tailwind v4 @theme / CSS variables — no tailwind.config theming, no CSS-in-JS, no styled-components, no second theming system.
- Structural tokens are part of the contract, not just colors: radius, font families (display/body/mono), and a density/spacing scale variable consumed by the shared data-table for row padding.
- A theme = one complete set of values for the contract, scoped to a class or data attribute on the root html element. Dark mode is the same mechanism (class strategy), not a separate system.
- Charts: Recharts components must consume the chart-1…chart-5 (and grid/axis via muted/border) tokens. No hex values in chart configs. Verify every dashboard widget renders correctly in dark mode and in every preset.
- Icons: lucide-react inherits currentColor — never pass color props with literals.

## Required features (build now)

- Appearance: light / dark / system, per user. Persisted (cookie readable server-side so the initial Inertia page render carries the correct class — no flash of wrong theme; mirror to a user preference column when authenticated). A small appearance toggle in the AdminLayout topbar and an entry in the cmdk palette. Respect prefers-color-scheme for "system" and prefers-reduced-motion globally.
- Preset themes. Ship at least: default, one brand variant, and one high-contrast (accessibility) preset — each as a small CSS block of token overrides (light + dark values per preset). Preset selection is a user preference, persisted like appearance. Generate palettes with a shadcn theme generator (e.g. tweakcn) or Radix Colors; every preset must pass WCAG AA contrast for foreground-on-background and primary-foreground-on-primary pairs.
- Theme provider, minimal. One small React context/hook exposing current appearance + preset and setters; it only toggles the root class/attribute and persists. No re-render-the-tree theming logic, no runtime style computation. Server shares the persisted choice via Inertia shared props.

## Layout theming (structural variants)

Principle: layout themability is an enumerated set of supported variants selected by configuration — never free-form layout. Free-form or drag-and-drop shell builders are explicitly out of scope.

The layout contract — a typed config object of enumerated options, persisted like the theme preference and shared via Inertia props:

- nav_placement: sidebar-left | sidebar-right | topbar
- sidebar_variant: sidebar | floating | inset (shadcn Sidebar variant prop)
- sidebar_collapsible: offcanvas | icon | none (shadcn Sidebar collapsible prop)
- content_width: fluid | boxed
- header: sticky | static
- direction: ltr | rtl
- density stays a token (see theme contract), not a layout option

Implementation rules:

- Use the shadcn/ui Sidebar primitive and its SidebarProvider as-is for all sidebar variants — side, variant, collapsible, mobile behavior, and collapse state are its props/features; do not rebuild them. Compose only the topbar variant yourself from existing shadcn primitives (NavigationMenu, DropdownMenu).
- AdminLayout is the single interpreter of the layout config. No other component, page, or module may branch on layout config. Modules render into the content region and must be completely layout-agnostic.
- The NavRegistry stays the single source of navigation data; the shell renders it as a sidebar tree or topbar menus depending on nav_placement. Breadcrumbs and the cmdk palette are layout-independent.
- Structural dimensions are tokens in the theme contract (sidebar width — via shadcn's sidebar width variables, header height, boxed content max-width), so visual presets may adjust dimensions while layout config selects structure. Any theme must compose with any layout variant.
- RTL discipline from day one: use logical properties/utilities only (ms-/me-, ps-/pe-, start/end) in all admin and module code — physical ml-/mr-/pl-/pr- and left/right utilities are forbidden except where genuinely directional (e.g. chevrons that must mirror get handled via dir-aware styling). Add this to the same lint/CI gate as the token rule. Set dir on the html element from the layout config; verify Radix components receive direction correctly (DirectionProvider).
- Persistence and flash-prevention follow the appearance mechanism: config readable server-side, applied on first paint, toggles available in the topbar settings menu and cmdk palette (at minimum: collapse sidebar, switch nav placement).

Designed-for, not built now: per-user dashboard widget arrangement (reorder via dnd-kit, order persisted per user through the WidgetRegistry's ordered data). Do not build it yet; just keep widget order data-driven so it slots in later.

Later, a Settings-module page (spatie/laravel-settings) will store token values in the database and inject them as an inline CSS-variable block in the root layout, overriding the active preset. Per-tenant capable. Obligation now: keep the token contract the single interface (so DB values can override it 1:1), keep preset CSS structured as pure token-value blocks, and do not couple any component to a specific preset. Do NOT build the settings UI, color pickers, or DB storage yet.

## Module contract addition

Theming — visual and layout — is a shell concern. Modules consume tokens and render into the content region; they never define colors, fonts, radii, shadows, or position themselves relative to the shell, and never read layout config. A module that ships its own palette, overrides root tokens, injects global CSS, or branches on layout fails review. This guarantees every module is automatically compatible with every current and future theme and layout variant. Add an architecture test / lint scope covering app-modules frontend code with the same token-only and logical-properties-only rules.

## Definition of done (theming)

- Token-only + logical-properties-only lint/CI gate passing on all admin + module frontend code.
- Every page, the shared data-table, all dialogs/popovers/dropdowns, toasts, the navigation shell, the cmdk palette, and every dashboard chart verified in: light, dark, and each preset (screenshot pass at minimum; browser tests where the kit's Pest browser testing is configured).
- Layout matrix verified: each nav_placement × collapsible mode, boxed and fluid content, sticky and static header, and RTL — using the Users module index and one form page as the reference surfaces. Every theme preset must render correctly in every layout variant.
- No flash of incorrect theme, direction, or layout on first paint or after Inertia navigation.
- High-contrast preset passes WCAG AA on the required pairs; visible keyboard focus ring (ring token) in all themes and layout variants.
- Docs: a short THEMING.md listing the token contract, the layout contract, how to add a preset (copy a token block, adjust values, register it), and the rule that new semantic tokens or layout options are shell-level API changes.
