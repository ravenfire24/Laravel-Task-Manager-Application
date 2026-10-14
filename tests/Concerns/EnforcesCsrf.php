<?php

namespace Tests\Concerns;

trait EnforcesCsrf
{
    protected function enableCsrfProtection(): string
    {
        // Laravel bypasses CSRF while running unit tests. Change only this app instance.
        $environment = $this->app['env'];
        $this->app['env'] = 'local';
        $this->beforeApplicationDestroyed(function () use ($environment): void {
            $this->app['env'] = $environment;
        });

        $token = 'integration-test-session-token';
        $this->withSession(['_token' => $token])->withHeader('X-CSRF-TOKEN', $token);

        return $token;
    }
}
