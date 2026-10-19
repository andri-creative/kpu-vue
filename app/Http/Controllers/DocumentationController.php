<?php

namespace App\Http\Controllers;

use App\Support\Documentation;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    public function index(): Response
    {
        $pages = Documentation::pages();
        $activeSlug = array_key_first($pages);

        return Inertia::render('Documentation/Show', [
            'sidebar' => Documentation::navigation(),
            'activeSlug' => $activeSlug,
            'page' => $activeSlug !== null ? $pages[$activeSlug] : null,
        ]);
    }

    public function show(string $slug): Response
    {
        $page = Documentation::pages()[$slug] ?? null;

        abort_if($page === null, 404);

        return Inertia::render('Documentation/Show', [
            'sidebar' => Documentation::navigation(),
            'activeSlug' => $slug,
            'page' => $page,
        ]);
    }
}
