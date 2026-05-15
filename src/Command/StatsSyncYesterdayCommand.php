<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\SuperAdmin\StatsSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:stats:sync-yesterday',
    description: 'Pulls yesterday stats from each enabled managed site and caches them in site_daily_stats. Run from cron once per day.',
)]
final class StatsSyncYesterdayCommand extends Command
{
    public function __construct(private readonly StatsSyncService $sync)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $results = $this->sync->syncYesterday();

        if ($results === []) {
            $io->note('No enabled managed sites.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($results as $domain => $r) {
            $rows[] = [$domain, $r['status'], $r['pageViews'], $r['errorReason'] ?? ''];
        }
        $io->table(['Site', 'Status', 'Page views', 'Error'], $rows);

        $allOk = !array_filter($results, fn(array $r) => $r['status'] !== 'ok');
        return $allOk ? Command::SUCCESS : Command::FAILURE;
    }
}
