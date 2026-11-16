<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $perPage = in_array($request->integer('per_page'), [5, 10, 15, 25, 50, 100], true)
            ? $request->integer('per_page')
            : 10;

        $departments = Department::withCount(['units', 'users'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $editing = $request->filled('edit') ? Department::find($request->integer('edit')) : null;

        return view('admin.master.departments.index', compact('departments', 'editing', 'search', 'perPage'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        Department::create($data);

        return back()->with('success', __('ui.department_created'));
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $department->update($data);

        return back()->with('success', __('ui.department_updated'));
    }

    public function destroy(Department $department)
    {
        if ($department->units()->exists() || $department->users()->exists()) {
            return back()->with('error', __('ui.department_in_use'));
        }

        $department->delete();

        return back()->with('success', __('ui.department_deleted'));
    }
}
