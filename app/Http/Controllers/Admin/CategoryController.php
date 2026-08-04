<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(protected ImageService $imageService)
    {
    }

    public function index(Request $request): View
    {
        $query = Category::withCount('products')->orderBy('sort_order')->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', (bool)$request->get('status'));
        }

        $categories = $query->paginate(20)->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.categories.form', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCategory($request);

        if ($request->hasFile('upload_icon')) {
            try {
                $validated['icon'] = $this->imageService->store($request->file('upload_icon'), 'categories/icon');
            } catch (\RuntimeException $e) {
                return back()->withInput()->withErrors(['upload_icon' => $e->getMessage()]);
            }
        }

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(Category $category): View
    {
        $category->load('products:id,category_id,name,image_url,is_active');
        return view('admin.categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $this->validateCategory($request, $category);
        if ($request->boolean('remove_icon') && $category->icon) {
            $this->imageService->delete($category->icon);
            $validated['icon'] = null;
        }

        if ($request->hasFile('icon-input')) {
            try {
                $oldPath = $category->icon;
                $validated['icon'] = $this->imageService->store($request->file('icon-input'), 'categories/icon');
                if ($oldPath) {
                    $this->imageService->delete($oldPath);
                }
            } catch (\RuntimeException $e) {
                return back()->withInput()->withErrors(['upload_icon' => $e->getMessage()]);
            }
        }

        $category->update($validated);

        return redirect()->route('admin.categories.show', $category)
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->icon) {
            $this->imageService->delete($category->icon);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', "Category \"{$category->name}\" deleted.");
    }

    // -------------------------------------------------------------------------

    private function validateCategory(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'upload_icon' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:5120'],
            'remove_icon' => ['nullable', 'boolean'],
        ]);

        if (isset($data['scope_items'])) {
            $data['scope_items'] = array_values(array_filter($data['scope_items']));
        }
        $data['slug'] = str($data['name'])->slug();
        $data['parent_id'] = $request->filled('parent_id') ? $request->input('parent_id') : null;
        $data['is_active'] = (bool)($data['is_active'] ?? false);

        unset($data['upload_icon'], $data['remove_icon']);

        return $data;
    }

    public function duplicate(Category $category): RedirectResponse
    {
        $newCategory = $category->replicate();

        $newCategory->name = $category->name . ' Copy';

        $slug = str($newCategory->name)->slug();

        $originalSlug = $slug;
        $counter = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $newCategory->slug = $slug;


        if ($category->icon) {
            try {

                $newCategory->icon = $this->imageService->copy(
                    $category->icon,
                    'categories/icon'
                );

            } catch (\RuntimeException $e) {

                return back()
                    ->withErrors([
                        'icon' => $e->getMessage()
                    ]);

            }
        }


        $newCategory->save();


        return redirect()
            ->route('admin.categories.index')
            ->with('success','Category duplicated successfully.');
    }
}
