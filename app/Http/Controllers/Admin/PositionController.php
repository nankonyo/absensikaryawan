<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Position;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $perPage = in_array($request->integer('per_page'), [5, 10, 15, 25, 50, 100], true)
            ? $request->integer('per_page')
            : 10;

        $positions = Position::withCount('users')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('level')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $editing = $request->filled('edit') ? Position::find($request->integer('edit')) : null;
        $departments = Department::orderBy('name')->get();
        $units = Unit::with('department')->orderBy('name')->get();

        return view('admin.master.positions.index', compact('positions', 'editing', 'departments', 'units', 'search', 'perPage'));
    }

    public function store(Request $request)
    {
        Position::create($this->validated($request));

        return back()->with('success', __('ui.position_created'));
    }

    public function update(Request $request, Position $position)
    {
        $position->update($this->validated($request));

        return back()->with('success', __('ui.position_updated'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|integer|min:1|max:5',
            'department_id' => 'nullable|exists:departments,id',
            'unit_id' => ['nullable', Rule::exists('units', 'id')
                ->where('department_id', $request->department_id)],
            'description' => 'nullable|string|max:500',
        ]);
    }

    public function destroy(Position $position)
    {
        if ($position->users()->exists()) {
            return back()->with('error', __('ui.position_in_use'));
        }

        $position->delete();

        return back()->with('success', __('ui.position_deleted'));
    }
}
