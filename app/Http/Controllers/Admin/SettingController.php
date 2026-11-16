<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\OfficeLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        $office = OfficeLocation::where('is_active', true)->first();

        return view('admin.settings.index', compact('settings', 'office'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'work_start_time' => 'required|date_format:H:i',
            'work_end_time' => 'required|date_format:H:i',
            'late_tolerance' => 'required|integer|min:0',
            'annual_leave_quota' => 'required|integer|min:1|max:365',
            'timezone' => 'required|timezone',
            'face_recognition_enabled' => 'required|boolean',
            'face_recognition_threshold' => 'required|integer|min:0|max:100',
            'company_name' => 'required|string|max:255',
            'company_address' => 'required|string|max:500',
            'company_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:1',
        ]);

        $office = OfficeLocation::where('is_active', true)->first()
            ?? new OfficeLocation(['name' => $request->company_name, 'is_active' => true]);

        $office->fill([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius' => $request->radius,
        ])->save();

        foreach ($request->except(['_token', 'latitude', 'longitude', 'radius', 'company_logo']) as $key => $value) {
            $type = match($key) {
                'face_recognition_enabled' => 'boolean',
                'late_tolerance', 'face_recognition_threshold', 'annual_leave_quota' => 'integer',
                default => 'string',
            };

            Setting::set($key, $value, $type);
        }

        if ($request->hasFile('company_logo')) {
            $oldLogo = Setting::get('company_logo');
            $path = $request->file('company_logo')->store('logos', 'public');

            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }

            Setting::set('company_logo', $path);
        }

        \App\Models\AuditLog::log('settings_updated', 'Setting', null);

        return redirect()
            ->route('admin.settings.index')
            ->with('success', __('ui.settings_updated'));
    }
}
