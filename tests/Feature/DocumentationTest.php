<?php

use App\Models\User;

test('guests can view the documentation index', function () {
    $response = $this->get(route('documentation.index'));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('Documentation/Show')
            ->has('sidebar')
            ->has('activeSlug')
            ->has('page')
    );
});

test('logged in users can still open the documentation', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('documentation.index'));

    $response->assertOk();
});

test('guests can view a documentation page without logging in', function () {
    $response = $this->get(route('documentation.show', ['slug' => 'readme']));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('Documentation/Show')
            ->where('activeSlug', 'readme')
            ->where('page.slug', 'readme')
            ->where('page.group', 'Getting Started')
            ->has('page.title')
            ->has('page.body')
            ->has('sidebar', 4)
    );
});

test('unknown documentation slug returns 404', function () {
    $this->get(route('documentation.show', ['slug' => 'halaman-tidak-ada']))
        ->assertNotFound();
});
