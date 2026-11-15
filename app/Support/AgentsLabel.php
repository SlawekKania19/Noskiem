<?php

namespace App\Support;

// ---------------------------
// Tekst licznika "X agentów już szuka tego zwierzaka" z polską odmianą:
// 1 agent szuka / 2-4 agenci szukają / 5+ agentów szuka (12-14 jak 5+).
// ---------------------------
class AgentsLabel
{
    public static function searching(int $count, bool $short = false): string
    {
        $few = $count % 10 >= 2 && $count % 10 <= 4 && ($count % 100 < 12 || $count % 100 > 14);

        $text = match (true) {
            $count === 1 => '1 agent już szuka',
            $few => "{$count} agenci już szukają",
            default => "{$count} agentów już szuka",
        };

        return $short ? $text : $text.' tego zwierzaka';
    }
}
