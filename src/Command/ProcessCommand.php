<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\MessageProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'app:process',
    description: 'Processes a list of JSON messages and classifies them into inspections and failure reports.'
)]
class ProcessCommand extends Command
{
    public function __construct(
        private MessageProcessor $processor
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source-file', InputArgument::REQUIRED, 'Path to the source JSON file')
            ->addOption('out-dir', 'o', InputOption::VALUE_REQUIRED, 'Directory where output JSON files will be written', './out');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sourceFile = $input->getArgument('source-file');
        $outDir = $input->getOption('out-dir');

        $io->title('Message Processor CLI');

        if (!file_exists($sourceFile)) {
            $io->error(sprintf('Source file "%s" does not exist.', $sourceFile));
            return Command::FAILURE;
        }

        $io->text(sprintf('Reading source file: <info>%s</info>', $sourceFile));

        $content = file_get_contents($sourceFile);
        if ($content === false) {
            $io->error(sprintf('Failed to read source file "%s".', $sourceFile));
            return Command::FAILURE;
        }

        try {
            $messages = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $io->error(sprintf('Failed to parse JSON content: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        if (!is_array($messages)) {
            $io->error('The JSON content must be an array of objects.');
            return Command::FAILURE;
        }

        $io->text('Processing messages...');
        $result = $this->processor->process($messages);

        $inspections = $result['inspections'];
        $failureReports = $result['failure_reports'];
        $failedMessages = $result['failed_messages'];

        if (!is_dir($outDir)) {
            $io->text(sprintf('Creating output directory: <info>%s</info>', $outDir));
            if (!mkdir($outDir, 0755, true) && !is_dir($outDir)) {
                $io->error(sprintf('Failed to create output directory "%s".', $outDir));
                return Command::FAILURE;
            }
        }

        $io->text('Writing output files...');

        $inspectionsPath = rtrim($outDir, '/') . '/inspections.json';
        $failureReportsPath = rtrim($outDir, '/') . '/failure_reports.json';
        $failedMessagesPath = rtrim($outDir, '/') . '/failed_messages.json';

        try {
            file_put_contents($inspectionsPath, json_encode($inspections, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            file_put_contents($failureReportsPath, json_encode($failureReports, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            file_put_contents($failedMessagesPath, json_encode($failedMessages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } catch (Throwable $e) {
            $io->error(sprintf('Failed to write output files: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $failedReasons = [];
        foreach ($failedMessages as $failedMsg) {
            $reason = $failedMsg->reason;
            if (!isset($failedReasons[$reason])) {
                $failedReasons[$reason] = 0;
            }
            $failedReasons[$reason]++;
        }

        $io->section('Processing Summary');
        $io->horizontalTable(
            ['Total Processed Messages', 'Inspections Created', 'Failure Reports Created', 'Messages Not Processed'],
            [
                [
                    count($messages),
                    count($inspections),
                    count($failureReports),
                    count($failedMessages)
                ]
            ]
        );

        if (count($failedMessages) > 0) {
            $io->text('<comment>Reasons for unprocessed messages:</comment>');
            foreach ($failedReasons as $reason => $count) {
                $io->text(sprintf(' - %s: <comment>%d</comment>', $reason, $count));
            }
        }

        $io->success('Message processing completed successfully.');

        return Command::SUCCESS;
    }
}
