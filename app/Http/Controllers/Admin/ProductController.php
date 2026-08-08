<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(protected ImageService $imageService) {}

    public function index(Request $request): View
    {
        $request->validate([
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'frequency' => ['nullable', 'in:once_off,monthly,quarterly,annually'],
            'status' => ['nullable', 'boolean'],
            'quote_default' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $categories = \App\Models\Category::active()
            ->orderBy('name')
            ->get();

        $query = Product::with('category')
            ->orderBy('sort_order')
            ->orderBy('name');

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');

            $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', "%{$search}%");
                    });
            });
        });

        $query->when($request->filled('category'), function ($q) use ($request) {
            $q->whereHas('category', function ($categoryQuery) use ($request) {
                $categoryQuery->where('slug', $request->input('category'));
            });
        });

        $query->when($request->filled('frequency'), function ($q) use ($request) {
            $frequency = $request->input('frequency');

            if ($frequency === 'once_off') {
                $q->where(function ($query) {
                    $query->whereNull('frequency')
                        ->orWhereNotIn('frequency', [
                            'monthly',
                            'quarterly',
                            'annually',
                        ]);
                });
            } else {
                $q->where('frequency', $frequency);
            }
        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('is_active', $request->boolean('status'));
        });
        $query->when($request->filled('quote_default'), function ($q) use ($request) {
            $q->where('quote_default', $request->boolean('quote_default'));
        });

        $products = $query->paginate(20)->withQueryString();

        return view('admin.products.index', compact('products', 'categories'));
    }
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.form', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);

        if ($request->hasFile('logo')) {
            try {
                $validated['image_url'] = $this->imageService->store($request->file('logo'));
            } catch (\RuntimeException $e) {
                return back()->withInput()->withErrors(['logo' => $e->getMessage()]);
            }
        }

        Product::create($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product): View
    {
        $product->load('category');
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request, $product);

        if ($request->boolean('remove_logo') && $product->image_url) {
            $this->imageService->delete($product->image_url);
            $validated['image_url'] = null;
        }

        if ($request->hasFile('logo')) {
            try {
                $oldPath = $product->image_url;
                $validated['image_url'] = $this->imageService->store($request->file('logo'));
                if ($oldPath) {
                    $this->imageService->delete($oldPath);
                }
            } catch (\RuntimeException $e) {
                return back()->withInput()->withErrors(['logo' => $e->getMessage()]);
            }
        }

        $product->update($validated);

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image_url) {
            $this->imageService->delete($product->image_url);
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', "Product \"{$product->name}\" deleted.");
    }

    // -------------------------------------------------------------------------

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id'       => ['required', 'exists:categories,id'],
            'name'              => ['required', 'string', 'max:255'],
            'short_name'        => ['nullable', 'string', 'max:100'],
            'description'       => ['nullable', 'string'],
            'scope_items'       => ['nullable', 'array'],
            'scope_items.*'     => ['nullable', 'string', 'max:255'],
            'key_scope_keyword' => ['nullable', 'string', 'max:100'],
            'price_type'        => ['required', 'in:fixed,dropdown,hourly'],
            'fixed_price'       => ['nullable', 'numeric', 'min:0'],
            'price_min'         => ['nullable', 'numeric', 'min:0'],
            'price_max'         => ['nullable', 'numeric', 'min:0', 'gte:price_min'],
            'price_increment'   => ['nullable', 'numeric', 'min:0.01'],
            'hourly_rate'       => ['nullable', 'numeric', 'min:0'],
            'setup_fee'         => ['nullable', 'numeric', 'min:0'],
            'frequency'         => ['nullable', 'in:once_off,monthly,quarterly,yearly'],
            'notes'             => ['nullable', 'string'],
            'is_active'         => ['nullable', 'boolean'],
            'quote_default'         => ['nullable', 'boolean'],
            'sort_order'        => ['nullable', 'integer', 'min:0'],
            // Accept raw uploads up to 5 MB — service compresses to ≤50 KB
            'logo'              => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:5120'],
            'remove_logo'       => ['nullable', 'boolean'],
        ]);

        if (isset($data['scope_items'])) {
            $data['scope_items'] = array_values(array_filter($data['scope_items']));
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        unset($data['logo'], $data['remove_logo']);

        return $data;
    }
}
