<?php

namespace App\Command;

use App\Service\ReminderScheduler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Point d'entrée CLI de ReminderScheduler, destiné à être appelé quotidiennement,
 * après app:plans:run — les échéances engendrées cette nuit portent déjà leurs
 * rappels, mais l'ordre inverse ferait attendre un jour à celles créées ensuite.
 *
 * Comme sa jumelle, elle ne contient aucune logique métier : rejouable à volonté,
 * elle ne fait rien quand tout est en règle.
 */
#[AsCommand(
    name: 'app:reminders:sync',
    description: 'Remet les rappels en accord avec la date de leur échéance',
)]
class SyncRemindersCommand extends Command
{
    public function __construct(private readonly ReminderScheduler $scheduler)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $report = $this->scheduler->syncAll();

        $io->success(sprintf(
            '%d échéance(s) examinée(s) : %d rappel(s) créé(s), %d supprimé(s).',
            $report['events'],
            $report['created'],
            $report['removed'],
        ));

        // Le code de sortie est la seule chose que le cron sait lire :
        // 0 = tout va bien, tout le reste alerte la supervision.
        return Command::SUCCESS;
    }
}
