<?php

namespace App\Http\Controllers;

use App\Models\Page;

// ---------------------------
// Kontroler statycznych podstron (np. /cookies) — treść zarządzana z panelu Filament
// ---------------------------

class PageController extends Controller
{
    // Wyświetla stronę po jej "slug" (route-model-binding, patrz Page::getRouteKeyName)
    public function show(Page $page)
    {
        return view('pages.show', compact('page'));
    }
}
