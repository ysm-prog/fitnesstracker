<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The first-party client is a same-site browser application, so its
     * requests carry an Origin that Sanctum recognises as stateful and are
     * served with a session. Tests exercise that same path; anything relying
     * on a bearer token instead sets its own Authorization header.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Origin', 'http://localhost');
    }
}
