<?php

namespace Goramax\NoctalysCli\Command\MakeCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LayoutCommand extends Command
{
    protected static $defaultName = 'make:layout';

    protected function configure(): void
    {
        $this
            ->setDescription('Generate a new layout file')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the layout');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $fileName = ucfirst($name) . '.php';
        $targetDir = getcwd() . '/src/Layouts';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $filePath = "$targetDir/$fileName";
        if (file_exists($filePath)) {
            $output->writeln("<comment>Layout $fileName already exists at $filePath</comment>");
            return Command::SUCCESS;
        }
        $template = "<?php\n\n// Layout: $name\n\n";
        file_put_contents($filePath, $template);
        $output->writeln("<info>Created layout file at $filePath</info>");
        return Command::SUCCESS;
    }
}
