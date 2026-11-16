<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $perPage = in_array($request->integer('per_page'), [5, 10, 15, 25, 50, 100], true)
            ? $request->integer('per_page')
            : 10;

        $units = Unit::with('department')
            ->withCount('users')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();
        $editing = $request->filled('edit') ? Unit::find($request->integer('edit')) : null;

        return view('admin.master.units.index', compact('units', 'departments', 'editing', 'search', 'perPage'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        Unit::create($data);

        return back()->with('success', __('ui.unit_created'));
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $unit->update($data);

        return back()->with('success', __('ui.unit_updated'));
    }

    public function destroy(Unit $unit)
    {
        if ($unit->users()->exists()) {
            return back()->with('error', __('ui.unit_in_use'));
        }

        $unit->delete();

        return back()->with('success', __('ui.unit_deleted'));
    }
}
