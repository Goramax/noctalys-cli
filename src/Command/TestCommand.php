<?php

namespace Goramax\NoctalysCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    protected static $defaultName = 'test';
    protected static $defaultDescription = 'Affiche le texte NOCTALYS';

    protected function configure(): void
    {
        $this->setName('test')
             ->setDescription('Affiche le texte NOCTALYS');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('NOCTALYS');
        
        return Command::SUCCESS;
    }
}