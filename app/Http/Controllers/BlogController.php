<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\Post;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

// ---------------------------
// Blog — publiczna lista i pojedyncze wpisy. Widoczne tylko wpisy opublikowane
// (scope Post::published). Treść wpisów zarządzana z panelu (PostResource).
// ---------------------------

class BlogController extends Controller
{
    // Lista opublikowanych wpisów; opcjonalny filtr ?kategoria=slug
    public function index(Request $request)
    {
        $activeCategory = $request->filled('kategoria')
            ? BlogCategory::where('slug', $request->query('kategoria'))->first()
            : null;

        return view('blog.index', [
            'posts' => $this->publishedPosts($activeCategory)->paginate(9)->withQueryString(),
            'categories' => $this->activeCategories(),
            'activeCategory' => $activeCategory,
        ]);
    }

    // Ta sama lista z ładnego adresu /blog/kategoria/{slug}
    public function category(BlogCategory $category)
    {
        return view('blog.index', [
            'posts' => $this->publishedPosts($category)->paginate(9)->withQueryString(),
            'categories' => $this->activeCategories(),
            'activeCategory' => $category,
        ]);
    }

    // Kanał RSS — ostatnie opublikowane wpisy
    public function feed()
    {
        $posts = Post::published()
            ->with(['author', 'category'])
            ->latest('published_at')
            ->take(20)
            ->get();

        return response()
            ->view('blog.feed', ['posts' => $posts])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    // Pojedynczy wpis
    public function show(Post $post)
    {
        // ** Wpis nieopublikowany (szkic/zaplanowany) widzi tylko admin albo jego autor
        if (! $post->isPublished()) {
            $user = auth()->user();
            abort_unless($user && ($user->is_admin || $post->user_id === $user->id), 404);
        }

        $post->load(['author', 'category']);

        // ** Kilka innych opublikowanych wpisów z tej samej kategorii — blok „Zobacz też"
        $related = $post->blog_category_id
            ? Post::published()
                ->where('blog_category_id', $post->blog_category_id)
                ->whereKeyNot($post->id)
                ->with('category')
                ->latest('published_at')
                ->take(3)
                ->get()
            : collect();

        return view('blog.show', compact('post', 'related'));
    }

    // ** Zapytanie bazowe listy — opublikowane, z autorem i kategorią, od najnowszych
    private function publishedPosts(?BlogCategory $category): Builder
    {
        return Post::published()
            ->with(['author', 'category'])
            ->when($category, fn (Builder $q) => $q->where('blog_category_id', $category->id))
            ->latest('published_at');
    }

    // ** Kategorie do paska filtrów — tylko te z co najmniej jednym opublikowanym wpisem
    private function activeCategories()
    {
        return BlogCategory::query()
            ->whereHas('posts', fn (Builder $q) => $q->published())
            ->orderBy('sort')
            ->get();
    }
}
