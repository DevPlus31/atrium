/**
 * Atrium theme lint gate (docs/specs/theming.md "The one hard rule").
 *
 * Scans frontend sources for raw Tailwind palette classes, color literals,
 * and physical (non-logical) direction utilities. Run with:
 *
 *     bun scripts/theme-lint.ts
 *
 * Rules:
 *   - raw-palette:  bg-white, text-slate-500, ring-blue-500/40, ...
 *   - color-literal: #hex, rgb()/rgba(), hsl()/hsla(), oklch()/oklab() in
 *     className strings or style objects.
 *   - physical-direction: ml-, mr-, pl-, pr-, left-, right-, text-left,
 *     text-right, rounded-l..., rounded-r... class tokens (use the logical
 *     ms-, me-, ps-, pe-, start-, end-, text-start, text-end, rounded-s...,
 *     rounded-e... utilities instead).
 *
 * Scope:
 *   - resources/js/{layouts,components,pages,hooks} (excluding components/ui):
 *     all three rules.
 *   - resources/js/components/ui: raw-palette + color-literal only. Vendored
 *     shadcn primitives keep physical positioning because floating UI
 *     (popovers, submenus, sheets) is genuinely directional; Radix flips it
 *     through the DirectionProvider, not through logical CSS properties.
 *   - app-modules/[star]/resources/js: all three rules.
 *
 * Allowlist: a `// theme-lint-allow-next-line <rule>` comment on the line
 * above a genuinely-directional (or otherwise justified) usage silences that
 * rule for the next line, e.g.:
 *
 *     // theme-lint-allow-next-line physical-direction
 *     <ChevronRight className="ml-auto" />
 */
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, relative, sep } from 'node:path';

type RuleName = 'raw-palette' | 'color-literal' | 'physical-direction';

type Rule = {
    name: RuleName;
    pattern: RegExp;
    message: string;
};

type Violation = {
    file: string;
    line: number;
    rule: RuleName;
    match: string;
    message: string;
};

const PALETTE_COLORS = [
    'slate',
    'gray',
    'zinc',
    'neutral',
    'stone',
    'red',
    'orange',
    'amber',
    'yellow',
    'lime',
    'green',
    'emerald',
    'teal',
    'cyan',
    'sky',
    'blue',
    'indigo',
    'violet',
    'purple',
    'fuchsia',
    'pink',
    'rose',
].join('|');

const rules: Rule[] = [
    {
        name: 'raw-palette',
        pattern: new RegExp(
            String.raw`(?<=^|[\s'"\`:!])(?:bg|text|border|ring|fill|stroke|outline|divide|decoration|caret|accent|shadow|from|via|to)-(?:(?:${PALETTE_COLORS})-\d{2,3}|white|black)(?:/\d{1,3})?(?![\w-])`,
            'g',
        ),
        message: 'raw Tailwind palette class — use a semantic token utility',
    },
    {
        name: 'color-literal',
        pattern:
            /#(?:[0-9a-fA-F]{8}|[0-9a-fA-F]{6}|[0-9a-fA-F]{3,4})(?![0-9a-zA-Z-])|(?:\brgba?|\bhsla?|\boklch|\boklab)\(/g,
        message: 'color literal — use a semantic token (var(--token))',
    },
    {
        name: 'physical-direction',
        pattern:
            /(?<=^|[\s'"`:!])-?(?:(?:m|p)(?:l|r)-[\w.[\]()/-]+|(?:left|right)-[\w.[\]()/-]+|text-left|text-right|rounded-(?:l|r|tl|tr|bl|br)(?:-[\w.[\]()/-]+)?)(?![\w-])/g,
        message:
            'physical direction utility — use logical (ms-/me-/ps-/pe-/start-/end-/text-start/text-end/rounded-s*/rounded-e*)',
    },
];

const root = process.cwd();
const violations: Violation[] = [];

function listFiles(dir: string): string[] {
    let entries: string[];

    try {
        entries = readdirSync(dir);
    } catch {
        return [];
    }

    const files: string[] = [];

    for (const entry of entries) {
        const path = join(dir, entry);
        const stats = statSync(path);

        if (stats.isDirectory()) {
            files.push(...listFiles(path));
        } else if (/\.(?:ts|tsx|js|jsx)$/.test(entry)) {
            files.push(path);
        }
    }

    return files;
}

function isAllowed(lines: string[], index: number, rule: RuleName): boolean {
    const previous = index > 0 ? lines[index - 1] : undefined;

    if (previous === undefined) {
        return false;
    }

    // Works in `//` and `{/* ... */}` comments alike.
    const match = previous.match(/theme-lint-allow-next-line\s+([\w-]+)/);

    return match !== null && match[1] === rule;
}

function scanFile(file: string, activeRules: Rule[]): void {
    const content = readFileSync(file, 'utf8');
    const lines = content.split(/\r?\n/);

    lines.forEach((line, index) => {
        for (const rule of activeRules) {
            rule.pattern.lastIndex = 0;

            let match = rule.pattern.exec(line);

            while (match !== null) {
                if (!isAllowed(lines, index, rule.name)) {
                    violations.push({
                        file: relative(root, file).split(sep).join('/'),
                        line: index + 1,
                        rule: rule.name,
                        match: match[0],
                        message: rule.message,
                    });
                }

                match = rule.pattern.exec(line);
            }
        }
    });
}

const allRules = rules;
const colorRulesOnly = rules.filter(
    (rule) => rule.name !== 'physical-direction',
);

// resources/js/{layouts,components,pages,hooks} minus components/ui.
for (const scope of ['layouts', 'components', 'pages', 'hooks']) {
    for (const file of listFiles(join(root, 'resources', 'js', scope))) {
        const normalized = relative(root, file).split(sep).join('/');

        if (normalized.startsWith('resources/js/components/ui/')) {
            continue;
        }

        scanFile(file, allRules);
    }
}

// components/ui: color rules only (vendored primitives keep physical CSS).
for (const file of listFiles(join(root, 'resources', 'js', 'components', 'ui'))) {
    scanFile(file, colorRulesOnly);
}

// app-modules/*/resources/js: full rule set.
try {
    for (const moduleName of readdirSync(join(root, 'app-modules'))) {
        const moduleJs = join(root, 'app-modules', moduleName, 'resources', 'js');

        for (const file of listFiles(moduleJs)) {
            scanFile(file, allRules);
        }
    }
} catch {
    // no app-modules directory
}

if (violations.length > 0) {
    for (const violation of violations) {
        console.error(
            `${violation.file}:${violation.line}  [${violation.rule}]  "${violation.match}" — ${violation.message}`,
        );
    }

    console.error(`\ntheme-lint: ${violations.length} violation(s) found.`);
    process.exit(1);
}

console.log('theme-lint: no violations.');
