<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ApiDocumentationController extends Controller
{
    public function index()
    {
        return view('admin.api-docs.index', [
            'baseUrl' => url('/api/mobile'),
        ]);
    }
}
