<?php

namespace App\Http\Controllers;

use App\Models\System\Installation;

class SynchController extends Controller
{
    public function index()
    {
        Installation::executeSchemaDatabase();

        return response('updating schema done', 200)
            ->header('Content-Type', 'text/plain');
    }
}
