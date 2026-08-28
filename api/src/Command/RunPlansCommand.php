<?php

namespace App\Command;

use App\Service\MedicalPlanRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:plans:run',
    description: 'Engendre les échéances manquantes des soins récurrents',
)]
class RunPlansCommand extends Command
{
    public function __construct(private readonly MedicalPlanRunner $runner)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $created = $this->runner->runAll();

        foreach ($created as $event) {
            $io->text(sprintf(
                '  <info>%s</info> — %s, le %s',
                $event->getAnimal()->getName(),
                $event->getName(),
                $event->getDate()->format('d/m/Y'),
            ));
        }

        $io->success(sprintf('%d échéance(s) engendrée(s).', count($created)));

        // Le code de sortie est la seule chose que le cron sait lire :
        // 0 = tout va bien, tout le reste alerte la supervision.
        return Command::SUCCESS;
    }
}
