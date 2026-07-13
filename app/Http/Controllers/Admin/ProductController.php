<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::with('category')->orderBy('sort_order')->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', (bool) $request->get('status'));
        }

        if ($request->filled('price_type')) {
            $query->where('price_type', $request->get('price_type'));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->get('category'));
        }

        $products = $query->paginate(20)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.form', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);

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

        $product->update($validated);

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', "Product {$product->name} deleted.");
    }

    // -------------------------------------------------------------------------

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $rules = [
            'category_id'     => ['nullable', 'exists:categories,id'],
            'name'            => ['required', 'string', 'max:255'],
            'short_name'      => ['nullable', 'string', 'max:100'],
            'description'     => ['nullable', 'string'],
            'scope_items'     => ['nullable', 'array'],
            'scope_items.*'   => ['nullable', 'string', 'max:255'],
            'key_scope_keyword' => ['nullable', 'string', 'max:100'],
            'price_type'      => ['required', 'in:fixed,dropdown,hourly'],
            'fixed_price'     => ['nullable', 'numeric', 'min:0'],
            'price_min'       => ['nullable', 'numeric', 'min:0'],
            'price_max'       => ['nullable', 'numeric', 'min:0', 'gte:price_min'],
            'price_increment' => ['nullable', 'numeric', 'min:0.01'],
            'hourly_rate'     => ['nullable', 'numeric', 'min:0'],
            'frequency'       => ['nullable', 'in:once_off,monthly,quarterly,yearly'],
            'image_url'       => ['nullable', 'url', 'max:2048'],
            'notes'           => ['nullable', 'string'],
            'is_active'       => ['nullable', 'boolean'],
            'sort_order'      => ['nullable', 'integer', 'min:0'],
        ];

        $data = $request->validate($rules);

        // Strip blank scope items
        if (isset($data['scope_items'])) {
            $data['scope_items'] = array_values(array_filter($data['scope_items']));
        }

        // Coerce is_active to boolean
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}
