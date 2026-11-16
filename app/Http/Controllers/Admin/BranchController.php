<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $perPage = in_array($request->integer('per_page'), [5, 10, 15, 25, 50, 100], true)
            ? $request->integer('per_page')
            : 5;

        $branches = Branch::withCount('users')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.master.branches.index', compact('branches', 'search', 'perPage'));
    }

    public function create()
    {
        return view('admin.master.branches.create');
    }

    public function edit(Branch $branch)
    {
        return view('admin.master.branches.edit', compact('branch'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        Branch::create($data);

        return redirect()
            ->route('admin.branches.index')
            ->with('success', __('ui.branch_created'));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $branch->update($data);

        return redirect()
            ->route('admin.branches.index')
            ->with('success', __('ui.branch_updated'));
    }

    public function destroy(Branch $branch)
    {
        if ($branch->users()->exists()) {
            return back()->with('error', __('ui.branch_in_use'));
        }

        $branch->delete();

        return redirect()
            ->route('admin.branches.index')
            ->with('success', __('ui.branch_deleted'));
    }
}
