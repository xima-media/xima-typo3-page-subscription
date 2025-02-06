<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use Xima\XimaTypo3PageSubscription\Domain\Repository\SubscriptionRepository;
use Xima\XimaTypo3PageSubscription\Service\Crawler;
use Xima\XimaTypo3PageSubscription\Service\Mailer;
use Xima\XimaTypo3PageSubscription\Service\UpdateHandler;
use Xima\XimaTypo3PageSubscription\Utility\SubscriptionUtility;

class InitSubscriptionsCommand extends Command
{
    public function __construct(protected SubscriptionRepository $subscriptionRepository, protected Crawler $crawler, protected UpdateHandler $updateHandler, protected Mailer $mailer, protected readonly EventDispatcher $eventDispatcher, $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setHelp('');
        $this->addOption('clear', 'c', null, 'Clear all hashes');
        $this->addArgument('subscription', InputArgument::OPTIONAL, 'Init only a specific subscription');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subscriptionCount = 0;
        $clear = $input->getOption('clear');

        $io = new SymfonyStyle($input, $output);
        $io->title('Init subscriptions');

        $output->writeln(
            'Fetching all subscriptions by pid',
            OutputInterface::VERBOSITY_VERBOSE
        );
        $subscriptions = $input->getArgument('subscription') ? [$this->subscriptionRepository->find((int)$input->getArgument('subscription'))] : $this->subscriptionRepository->findAll();

        if ($clear) {
            $output->writeln('Clearing all hashes');
            $this->subscriptionRepository->clearAllHashes();
            $output->writeln('🧼 Subscriptions hashes cleared');
        } else {
            $pids = array_map(fn($subscription) => $subscription->getPid(), $subscriptions);
            $pids = array_unique($pids);

            $elements = [];
            foreach ($pids as $pid) {
                $output->writeln(
                    sprintf('[%s] Crawling page for elements', $pid),
                    OutputInterface::VERBOSITY_VERBOSE
                );

                $elements[$pid] = $this->crawler->checkPageForElements($pid, true);
                $output->writeln(
                    sprintf('[%s] Found ', $pid) . count($elements) . ' elements',
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }

            foreach ($subscriptions as $subscription) {
                $subscriptionCount++;
                $uid = $subscription->getUid();

                $output->writeln(
                    sprintf('[%s][%s] Generating hashes', $subscription->getPid(), $uid),
                    OutputInterface::VERBOSITY_VERBOSE
                );
                $subscriptionElements = $elements[$subscription->getPid()] ?? [];
                $subscriptionElementsHash = SubscriptionUtility::getHashArray($subscriptionElements);

                $this->subscriptionRepository->updateLastChecked($uid, $subscriptionElementsHash);
            }

            $io->section('Result');
            $output->writeln('🔎 ' . $subscriptionCount . ' subscription(s) initialized');
        }

        return Command::SUCCESS;
    }
}
