<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationRequest;
use App\Models\Locations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $sortBy = $request->get('sort', 'latest');
        
        $locations = Locations::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
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
            ->when($sortBy === 'code_asc', function ($query) {
                $query->orderBy('code', 'asc');
            })
            ->when($sortBy === 'code_desc', function ($query) {
                $query->orderBy('code', 'desc');
            })
            ->paginate(10)
            ->withQueryString();

        // Ensure the sort filter is always passed to the frontend, even if not in request
        $filters = $request->only(['search', 'sort']);
        if (!isset($filters['sort'])) {
            $filters['sort'] = 'latest';
        }

        return Inertia::render('Locations/Index', [
            'locations' => $locations,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Locations/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LocationRequest $request): RedirectResponse
    {
        try {
            Locations::create($request->validated());

            return redirect()->route('locations.index')
                ->with('success', 'Location created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create location. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Locations $location): Response
    {
        return Inertia::render('Locations/Show', [
            'location' => $location,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Locations $location): Response
    {
        return Inertia::render('Locations/Edit', [
            'location' => $location,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LocationRequest $request, Locations $location): RedirectResponse
    {
        try {
            $location->update($request->validated());

            return redirect()->route('locations.index')
                ->with('success', 'Location updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update location. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Locations $location): RedirectResponse
    {
        try {
            $location->delete();

            return redirect()->route('locations.index')
                ->with('success', 'Location deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete location. Please try again.');
        }
    }
}
