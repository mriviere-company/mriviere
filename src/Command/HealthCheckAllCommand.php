<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\SuperAdmin\HealthMonitor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:health:check-all',
    description: 'Pings every enabled managed site, persists a SiteHealthCheck and triggers alerts after 3 consecutive failures.',
)]
final class HealthCheckAllCommand extends Command
{
    public function __construct(private readonly HealthMonitor $monitor)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $summary = $this->monitor->checkAll();

        if ($summary === []) {
            $io->note('No enabled managed sites.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($summary as $domain => $row) {
            $rows[] = [
                $domain,
                $row['status'],
                $row['latencyMs'] !== null ? $row['latencyMs'] . ' ms' : '—',
                $row['alertSent'] ? 'yes' : '',
            ];
        }
        $io->table(['Site', 'Status', 'Latency', 'Alert'], $rows);

        $allOk = !array_filter($summary, fn(array $r) => $r['status'] !== 'ok');
        return $allOk ? Command::SUCCESS : Command::FAILURE;
    }
}
