<?php

namespace App\Console\Commands;

use App\Services\AgentNotifier;
use Illuminate\Console\Command;

// ---------------------------
// Prośby o pomoc do Agentów noskiem - nowe ogłoszenia "Zaginął" w ich obszarach
// działania, jeden zbiorczy mail na agenta. Uruchamiane co godzinę (routes/console.php).
// ---------------------------

class NotifyAgents extends Command
{
    protected $signature = 'agents:notify';

    protected $description = 'Wysyła agentom prośby o pomoc w poszukiwaniach (nowe ogłoszenia w ich obszarach)';

    public function handle(AgentNotifier $notifier): int
    {
        $out = $notifier->run();

        $this->info("Ogłoszenia: {$out['listings']}, wysłane maile: {$out['mails']}");

        return self::SUCCESS;
    }
}
