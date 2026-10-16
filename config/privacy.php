<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Privacy (GDPR, CCPA/CPRA and the other US state privacy laws)
|--------------------------------------------------------------------------
|
| The legal documents themselves are Markdown files per language in
| resources/legal/<locale>/{terms,privacy,cookies}.md: have them reviewed
| before going live. README "Privacy" explains every piece.
|
*/

return [

    /*
     * The version of the Terms of Service (and the Privacy Policy notice that
     * goes with them) users must accept. Sign-up records it; change it when
     * the terms change and everyone is asked to accept again at their next
     * visit. Null turns the acceptance step off.
     */
    'terms_version' => '2026-10-16',

    'consent' => [

        /*
         * The optional cookie and tracking categories this app uses, from
         * App\Enums\ConsentCategory. Strictly necessary cookies (session,
         * CSRF, the visitor's own display choices) never need consent and are
         * not listed. With none listed, no consent banner shows.
         */
        'categories' => ['analytics', 'marketing'],

        /*
         * The version of the cookie policy a choice was made against. Change
         * it when the categories or their purposes change, and every visitor
         * is asked again.
         */
        'version' => '2026-10-16',

        /*
         * How long a choice lasts before the visitor is asked again. European
         * regulators expect consent to be renewed at least every 13 months.
         */
        'lifetime_days' => 365,
    ],

    'retention' => [

        /*
         * Read notifications are deleted after this many days.
         */
        'read_notifications_days' => 90,

        /*
         * Accounts that signed up, never verified their email address and
         * were never given a role are deleted after this many days. Null keeps
         * them.
         */
        'abandoned_signups_days' => 30,

        /*
         * Consent records prove what each visitor agreed to; they are kept
         * this long (CCPA asks for 24 months of request records).
         */
        'consent_records_days' => 730,
    ],

];
