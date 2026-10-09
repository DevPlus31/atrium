<?php

declare(strict_types=1);

/**
 * The root view paints the page background before the stylesheet loads; a
 * colour that differs from the active preset's flashes on every visit.
 */
it('paints each preset’s background before the stylesheet loads', function (): void {
    $view = (string) file_get_contents(resource_path('views/app.blade.php'));
    $expected = [];

    preg_match_all('/^(:root|\.dark)\s*\{[^}]*?--background:\s*([^;]+);/ms', (string) file_get_contents(resource_path('css/app.css')), $base, PREG_SET_ORDER);

    foreach ($base as [, $selector, $colour]) {
        $expected[$selector === ':root' ? 'html' : 'html.dark'] = $colour;
    }

    foreach (glob(resource_path('css/themes/*.css')) ?: [] as $theme) {
        preg_match_all("/^:root\\[data-theme='([a-z-]+)'\\](\\.dark)?\\s*\\{[^}]*?--background:\\s*([^;]+);/ms", (string) file_get_contents($theme), $blocks, PREG_SET_ORDER);

        foreach ($blocks as [, $name, $dark, $colour]) {
            $selector = ($dark === '' ? 'html' : 'html.dark')."[data-theme='".$name."']";
            $inherited = $expected[$dark === '' ? 'html' : 'html.dark'] ?? null;

            if ($colour !== $inherited) {
                $expected[$selector] = $colour;
            }
        }
    }

    expect($expected)->toHaveKeys(['html', 'html.dark']);

    foreach ($expected as $selector => $colour) {
        expect($view)->toMatch('/'.preg_quote($selector, '/').'\s*\{\s*background-color:\s*'.preg_quote($colour, '/').';/');
    }
});
