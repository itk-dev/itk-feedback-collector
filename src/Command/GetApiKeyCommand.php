<?php

namespace App\Command;

use App\Repository\WebsiteRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:website:api-key',
    description: 'Get the API key for a website by its website ID',
)]
class GetApiKeyCommand extends Command
{
    public function __construct(
        private readonly WebsiteRepository $websiteRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('websiteId', InputArgument::REQUIRED, 'The website ID (e.g. tidy-feedback-drupal)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $websiteId = $input->getArgument('websiteId');
        $website = $this->websiteRepository->findOneBy(['websiteId' => $websiteId]);

        if (!$website) {
            $output->writeln('<error>Website "'.$websiteId.'" not found.</error>');

            return Command::FAILURE;
        }

        $output->writeln($website->getApiKey());

        return Command::SUCCESS;
    }
}
