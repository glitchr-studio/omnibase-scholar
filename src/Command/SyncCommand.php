<?php

namespace Base\Scholar\Command;

use Base\Scholar\Repository\ScholarRepository;
use Base\Scholar\Service\Synchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Every scholar's works read again from their sources (OpenAlex, HAL,
 * Crossref... through glitchr/omnischolar), their counts and their CV:
 * new works wait in the back office's "Publications to validate", known
 * ones are brought up to date, what the site added is kept. Run it from
 * the cron container (once a week is plenty: deployments/docker/cron/crontab),
 * or from the back office's "Sync now".
 */
#[AsCommand(name: 'scholar:sync', description: 'Read the scholars\' works, counts and CV from their sources (glitchr/omnischolar).')]
final class SyncCommand extends Command
{
    public function __construct(
        private readonly ScholarRepository $scholars,
        private readonly Synchronizer $synchronizer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('scholar', null, InputOption::VALUE_REQUIRED, 'Only this scholar (id)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $scholars = $input->getOption('scholar') ? array_filter([$this->scholars->find((int) $input->getOption('scholar'))]) : $this->scholars->findAll();
        if (!$scholars) {
            $io->note('No scholar to read: add one in the back office (Scholar › Identity), with the sources of their works.');

            return Command::SUCCESS;
        }

        $failed = 0;
        foreach ($scholars as $scholar) {
            $io->section($scholar->getName());
            $summary = $this->synchronizer->sync($scholar);
            foreach ($summary->errors as $error) {
                $io->error($error);
            }
            foreach ($summary->incomplete as $source => $why) {
                $io->warning(sprintf('%s did not answer: %s', $source, $why));
            }
            $io->definitionList(
                ['read' => $summary->read],
                ['new (to validate)' => $summary->created],
                ['brought up to date' => $summary->updated],
                ['unchanged' => $summary->unchanged],
                ['rejected, found again' => $summary->ignored],
                ['CV lines read' => $summary->cv],
            );
            if ($summary->errors && 0 === $summary->read) {
                ++$failed;
            }
        }

        return $failed === \count($scholars) ? Command::FAILURE : Command::SUCCESS;
    }
}
