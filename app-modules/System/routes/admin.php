<?php

declare(strict_types=1);

/*
 * The System module registers no routes of its own. Pulse, Horizon, and the
 * Log Viewer each register their routes through their own service providers,
 * guarded by the middleware configured in config/pulse.php, config/horizon.php,
 * and config/log-viewer.php.
 */
