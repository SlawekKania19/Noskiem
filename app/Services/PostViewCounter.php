<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ---------------------------
// Licznik odsłon wpisów bloga (posts.views_count + post_views per dzień).
// Prosty i bez ciasteczek analitycznych - nie zastępuje Google Analytics, daje tylko
// orientację, co jest czytane. Nie liczymy:
// - wpisów nieopublikowanych (podgląd szkicu),
// - zalogowanych (redakcja/moderatorzy - nabijaliby odsłony przy pracy nad wpisem),
// - botów (po User-Agent),
// - ponownych wejść w tej samej sesji (odświeżenie strony to nie nowy czytelnik).
// ---------------------------
class PostViewCounter
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|embedly|preview|monitor|curl|wget|python|headless|lighthouse/i';

    public function record(Post $post, Request $request): void
    {
        if (! $post->isPublished() || $request->user() || $this->isBot($request)) {
            return;
        }

        $sessionKey = 'blog_viewed.'.$post->id;

        if ($request->hasSession()) {
            if ($request->session()->has($sessionKey)) {
                return;
            }

            $request->session()->put($sessionKey, true);
        }

        try {
            // ** Z pominięciem Eloquenta - bez ruszania updated_at wpisu
            DB::table('posts')->where('id', $post->id)->increment('views_count');

            DB::table('post_views')->upsert(
                [['post_id' => $post->id, 'date' => today()->toDateString(), 'views' => 1]],
                ['post_id', 'date'],
                ['views' => DB::raw('post_views.views + 1')],
            );
        } catch (\Throwable $e) {
            // Licznik nie może zepsuć czytania wpisu
            report($e);
        }
    }

    private function isBot(Request $request): bool
    {
        $userAgent = (string) $request->userAgent();

        return $userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent) === 1;
    }
}
