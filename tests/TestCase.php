<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Switching users mid-test is a fresh sign-in: forget the previous
     * user's password hash, or AuthenticateSession would sign the new one
     * out (a real sign-in or impersonation re-stores it).
     */
    public function actingAs(Authenticatable $user, $guard = null): static
    {
        if ($this->app?->bound('session.store')) {
            $this->app->make(Session::class)->forget('password_hash_'.($guard ?? config('auth.defaults.guard')));
        }

        return parent::actingAs($user, $guard);
    }
}
