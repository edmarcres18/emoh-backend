<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $sortBy = $request->get('sort', 'latest');
        
        $categories = Category::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($sortBy === 'latest', function ($query) {
                $query->orderBy('created_at', 'desc');
            })
            ->when($sortBy === 'oldest', function ($query) {
                $query->orderBy('created_at', 'asc');
            })
            ->when($sortBy === 'name_asc', function ($query) {
                $query->orderBy('name', 'asc');
            })
            ->when($sortBy === 'name_desc', function ($query) {
                $query->orderBy('name', 'desc');
            })
            ->paginate(10)
            ->withQueryString();

        // Ensure the sort filter is always passed to the frontend, even if not in request
        $filters = $request->only(['search', 'sort']);
        if (!isset($filters['sort'])) {
            $filters['sort'] = 'latest';
        }

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Categories/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request): RedirectResponse
    {
        try {
            Category::create($request->validated());

            return redirect()->route('categories.index')
                ->with('success', 'Category created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create category. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): Response
    {
        return Inertia::render('Categories/Show', [
            'category' => $category,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category): Response
    {
        return Inertia::render('Categories/Edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        try {
            $category->update($request->validated());

            return redirect()->route('categories.index')
                ->with('success', 'Category updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update category. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): RedirectResponse
    {
        try {
            $category->delete();

            return redirect()->route('categories.index')
                ->with('success', 'Category deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete category. Please try again.');
        }
    }
}
