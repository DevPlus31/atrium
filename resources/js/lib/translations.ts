type Catalog = Record<string, string>;

/**
 * Every translation file — the shell's `lang/<locale>.json` and each
 * module's `app-modules/<Module>/lang/<locale>.json` — merged per locale, so
 * a module ships its own strings and leaves with them. The shell is applied
 * last: a project can override a module's wording in `lang/<locale>.json`.
 * Keyed `/lang/<locale>.json`, the shape laravel-react-i18n expects.
 */
const moduleFiles = import.meta.glob<Catalog>('/app-modules/*/lang/*.json', {
    eager: true,
    import: 'default',
});

const shellFiles = import.meta.glob<Catalog>('/lang/*.json', {
    eager: true,
    import: 'default',
});

const localeOf = (path: string): string =>
    path.slice(path.lastIndexOf('/') + 1, -'.json'.length);

export const translationFiles: Record<string, Catalog> = Object.entries({
    ...moduleFiles,
    ...shellFiles,
}).reduce<Record<string, Catalog>>((merged, [path, catalog]) => {
    const key = `/lang/${localeOf(path)}.json`;

    return { ...merged, [key]: { ...merged[key], ...catalog } };
}, {});
