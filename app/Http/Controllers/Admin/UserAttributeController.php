<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserAttribute;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserAttributeController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $perPage = in_array($request->integer('per_page'), [5, 10, 15, 25, 50, 100], true)
            ? $request->integer('per_page')
            : 5;

        $attributes = UserAttribute::withCount('values')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('label', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('label')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.master.user-attributes.index', compact('attributes', 'search', 'perPage'));
    }

    public function create()
    {
        return view('admin.master.user-attributes.create');
    }

    public function edit(UserAttribute $userAttribute)
    {
        return view('admin.master.user-attributes.edit', compact('userAttribute'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['options'] = $this->parseOptions($request);
        $data['is_active'] = $request->boolean('is_active');

        UserAttribute::create($data);

        return redirect()
            ->route('admin.user-attributes.index')
            ->with('success', __('ui.attribute_created'));
    }

    public function update(Request $request, UserAttribute $userAttribute)
    {
        $data = $this->validated($request, $userAttribute);

        $data['options'] = $this->parseOptions($request);
        $data['is_active'] = $request->boolean('is_active');

        $userAttribute->update($data);

        return redirect()
            ->route('admin.user-attributes.index')
            ->with('success', __('ui.attribute_updated'));
    }

    public function destroy(UserAttribute $userAttribute)
    {
        if ($userAttribute->values()->exists()) {
            return back()->with('error', __('ui.attribute_in_use'));
        }

        $userAttribute->delete();

        return redirect()
            ->route('admin.user-attributes.index')
            ->with('success', __('ui.attribute_deleted'));
    }

    private function validated(Request $request, ?UserAttribute $attribute = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('user_attributes', 'name')->ignore($attribute?->id)],
            'label' => 'required|string|max:255',
            'type' => 'required|in:text,date,textarea,select',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }

    private function parseOptions(Request $request): ?array
    {
        if ($request->type !== 'select' || ! $request->filled('options')) {
            return null;
        }

        return array_map('trim', explode(',', $request->options));
    }
}
