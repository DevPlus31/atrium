<?php

declare(strict_types=1);

/*
 * Only what differs from Laravel Boost's defaults (merged over them): Copilot
 * keeps its instructions under .github, so Boost never recreates AGENTS.md
 * (only Claude and GitHub tooling live in this repository).
 */
return [
    'agents' => [
        'copilot' => [
            'guidelines_path' => '.github/copilot-instructions.md',
        ],
    ],
];
