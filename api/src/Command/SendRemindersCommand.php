<?php

namespace App\Command;

use App\Service\ReminderSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


#[AsCommand(
    name: 'app:reminders:send',
    description: 'Envoie les rappels arrivés à échéance, un digest par destinataire',
)]
class SendRemindersCommand extends Command
{
    public function __construct(private readonly ReminderSender $sender)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // L'instant est décidé ici, une fois, et traverse tout le traitement :
        // tous les rappels d'un même passage portent exactement le même `sentAt`,
        // et le plancher ne glisse pas pendant l'exécution.
        $report = $this->sender->sendDue(new \DateTimeImmutable());

        $io->text(sprintf(
            '%d rappel(s) envoyé(s) à %d destinataire(s).',
            $report['reminders'],
            $report['users'],
        ));

        if ($report['failures'] > 0) {
            // Sortie en échec pour que la supervision réagisse — mais après
            // avoir traité tout le monde : un destinataire injoignable ne doit
            // pas priver les autres de leur mail.
            $io->error(sprintf('%d destinataire(s) injoignable(s).', $report['failures']));

            return Command::FAILURE;
        }

        $io->success('Terminé.');

        return Command::SUCCESS;
    }
}
