<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Authorization is a controller responsibility in this application: every
    // user-owned resource is checked against a policy before it is returned.
    use AuthorizesRequests;
}
