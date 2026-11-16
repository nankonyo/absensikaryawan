<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $perPage = in_array($request->integer('per_page'), [5, 10, 15, 25, 50, 100], true)
            ? $request->integer('per_page')
            : 10;

        $leaveTypes = LeaveType::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('category')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $editing = $request->filled('edit') ? LeaveType::find($request->integer('edit')) : null;

        return view('admin.master.leave-types.index', compact('leaveTypes', 'editing', 'search', 'perPage'));
    }

    public function store(Request $request)
    {
        LeaveType::create($this->validated($request));

        return back()->with('success', __('ui.leave_type_created'));
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $leaveType->update($this->validated($request));

        return back()->with('success', __('ui.leave_type_updated'));
    }

    public function destroy(LeaveType $leaveType)
    {
        if ($leaveType->leaves()->exists()) {
            return back()->with('error', __('ui.leave_type_in_use'));
        }

        $leaveType->delete();

        return back()->with('success', __('ui.leave_type_deleted'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:sakit,izin,cuti',
        ]);

        $data['requires_attachment'] = $request->boolean('requires_attachment');
        $data['requires_reason'] = $request->boolean('requires_reason');
        $data['deducts_quota'] = $request->boolean('deducts_quota');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
