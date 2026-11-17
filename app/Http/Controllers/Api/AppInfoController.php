<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class AppInfoController extends Controller
{
    public function index()
    {
        $logo = Setting::get('company_logo');

        return response()->json([
            'app_name' => Setting::get('app_name', config('app.name')),
            'company_name' => Setting::get('company_name', config('app.name')),
            'logo_url' => $logo ? Storage::disk('public')->url($logo) : null,
            'play_store_url' => Setting::get('play_store_url'),
            'app_store_url' => Setting::get('app_store_url'),
        ]);
    }
}
