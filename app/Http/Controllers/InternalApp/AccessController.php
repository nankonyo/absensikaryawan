<?php

namespace App\Http\Controllers\InternalApp;

use App\Models\System\InternalAppInstallation;
use App\Support\Feature;
use Illuminate\Routing\Controller;

class AccessController extends Controller
{
    public function __construct()
    {
        InternalAppInstallation::ensureForRst();
    }

    public function index()
    {
        abort_unless($this->isAdmin(), 403);
        $features = Feature::groups();

        return view('internal-app.access.index', compact('features'));
    }

    protected function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
