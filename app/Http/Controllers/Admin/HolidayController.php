<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $month = (string) $request->query('month', now()->format('Y-m'));
        $monthStart = $this->monthStart($month);

        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $holidays = Holiday::query()
            ->whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->orderBy('date')
            ->get()
            ->keyBy(fn (Holiday $holiday) => $holiday->date->toDateString());

        $days = [];
        for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay()) {
            $days[] = $day->copy();
        }

        $monthHolidays = $holidays->filter(
            fn (Holiday $holiday) => $holiday->date->format('Y-m') === $monthStart->format('Y-m')
        );

        $editing = $request->filled('edit') ? Holiday::find($request->integer('edit')) : null;

        $formDate = $editing?->date?->toDateString();
        $requestedDate = (string) $request->query('date');
        if (! $formDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate)) {
            $formDate = $requestedDate;
        }

        return view('admin.master.holidays.index', [
            'monthStart' => $monthStart,
            'prevMonth' => $monthStart->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $monthStart->copy()->addMonth()->format('Y-m'),
            'days' => $days,
            'holidays' => $holidays,
            'monthHolidays' => $monthHolidays,
            'editing' => $editing,
            'formDate' => $formDate,
        ]);
    }

    public function store(Request $request)
    {
        Holiday::create($this->validated($request));

        return back()->with('success', __('ui.holiday_created'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        $holiday->update($this->validated($request, $holiday));

        return redirect()
            ->route('admin.holidays.index', ['month' => $holiday->date->format('Y-m')])
            ->with('success', __('ui.holiday_updated'));
    }

    public function destroy(Holiday $holiday)
    {
        $month = $holiday->date->format('Y-m');
        $holiday->delete();

        return redirect()
            ->route('admin.holidays.index', ['month' => $month])
            ->with('success', __('ui.holiday_deleted'));
    }

    private function validated(Request $request, ?Holiday $holiday = null): array
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(Holiday::TYPES)],
        ]);

        $date = Carbon::parse($data['date'])->toDateString();

        $duplicate = Holiday::query()
            ->whereDate('date', $date)
            ->when($holiday, fn ($query) => $query->whereKeyNot($holiday->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'date' => __('validation.unique', ['attribute' => __('ui.holiday_date')]),
            ]);
        }

        $data['date'] = $date;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function monthStart(string $month): Carbon
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();

            if ($start->format('Y-m') === $month) {
                return $start;
            }
        }

        return now()->startOfMonth();
    }
}
