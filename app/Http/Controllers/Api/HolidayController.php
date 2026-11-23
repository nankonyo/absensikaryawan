<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m-d', $request->query('month').'-01')->startOfMonth()
            : now()->startOfMonth();

        $holidays = Holiday::query()
            ->active()
            ->whereBetween('date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->orderBy('date')
            ->get()
            ->map(fn (Holiday $holiday) => [
                'id' => $holiday->id,
                'date' => $holiday->date->toDateString(),
                'name' => $holiday->name,
                'type' => $holiday->type,
                'type_label' => $holiday->typeLabel(),
                'is_active' => $holiday->is_active,
            ]);

        return response()->json([
            'month' => $month->format('Y-m'),
            'month_label' => $month->translatedFormat('F Y'),
            'holidays' => $holidays,
        ]);
    }
}
