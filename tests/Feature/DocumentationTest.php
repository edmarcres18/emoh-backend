<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can access documentation page without authentication', function () {
    $response = $this->get('/documentation');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Documentation/Index'));
});

it('can access documentation with slug parameter', function () {
    $response = $this->get('/documentation/features');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->component('Documentation/Index')
            ->has('slug')
            ->where('slug', 'features')
    );
});

it('can access all documentation sections with slugs', function () {
    $slugs = ['overview', 'features', 'architecture', 'data-models', 'api-endpoints', 'vue-components', 'deployment', 'support'];

    foreach ($slugs as $slug) {
        $response = $this->get("/documentation/{$slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Documentation/Index')
                ->where('slug', $slug)
        );
    }
});

it('documentation page has correct title', function () {
    $response = $this->get('/documentation');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->has('title')
            ->where('title', 'EMOH Property Management - Documentation')
    );
});

it('documentation page is accessible to authenticated users', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/documentation');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Documentation/Index'));
});

it('returns 404 for invalid documentation slug', function () {
    $response = $this->get('/documentation/invalid-slug');

    $response->assertStatus(404);
});
