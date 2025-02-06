<?php

declare(strict_types=1);

namespace Xima\XimaTypo3PageSubscription\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3PageSubscription\Configuration;
use Xima\XimaTypo3PageSubscription\Domain\Model\Dto\UpdateItem;
use Xima\XimaTypo3PageSubscription\Domain\Repository\SubscriptionRepository;
use Xima\XimaTypo3PageSubscription\Enumeration\Type;
use Xima\XimaTypo3PageSubscription\Service\Crawler;
use Xima\XimaTypo3PageSubscription\Service\Mailer;
use Xima\XimaTypo3PageSubscription\Service\UpdateHandler;
use Xima\XimaTypo3PageSubscription\Utility\RecordUtility;
use Xima\XimaTypo3PageSubscription\Utility\SubscriptionUtility;
use Xima\XimaTypo3PageSubscription\Utility\UrlHelper;

class CheckUpdatesCommand extends Command
{
    private const ACTIONTYPES = [
        1 => 'Create',
        2 => 'Update',
        3 => 'Delete',
    ];

    public function __construct(protected SubscriptionRepository $subscriptionRepository, protected Crawler $crawler, protected UpdateHandler $updateHandler, protected Mailer $mailer, protected readonly EventDispatcher $eventDispatcher, $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setHelp('');
        $this->addOption('dry-run', 'd', null, "Just check for updates, don't notify");
        $this->addArgument('subscription', InputArgument::OPTIONAL, 'Check only a specific subscription');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $updateCount = 0;
        $subscriptionCount = 0;
        $subscriptionUpdates = [];

        $dryRun = $input->getOption('dry-run');

        $io = new SymfonyStyle($input, $output);
        $io->title('Checking for updates');

        $output->writeln(
            'Fetching all subscriptions by pid',
            OutputInterface::VERBOSITY_VERBOSE
        );
        $subscriptions = $input->getArgument('subscription') ? [$this->subscriptionRepository->find((int)$input->getArgument('subscription'))] : $this->subscriptionRepository->findAll();
        $pids = array_map(fn($subscription) => $subscription->getPid(), $subscriptions);
        $pids = array_unique($pids);

        $elements = [];
        foreach ($pids as $pid) {
            $output->writeln(
                sprintf('[%s] Check page is available', $pid),
                OutputInterface::VERBOSITY_VERBOSE
            );
            if (!RecordUtility::isPageVisible($pid)) {
                $extensionConfiguration = (GeneralUtility::makeInstance(ExtensionConfiguration::class))->get(Configuration::EXT_KEY);
                if ($extensionConfiguration['deleteSubscriptionOnMissingPage'] === false) {
                    $elements[$pid] = [];
                    continue;
                }

                $elements[$pid] = false;
                continue;
            }

            $output->writeln(
                sprintf('[%s] Crawling page for updates', $pid),
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
            $subscription->setLink(UrlHelper::getAbsoluteUrl($subscription->getPid()));

            $uid = $subscription->getUid();
            $output->writeln(
                sprintf('[%s][%s] Checking subscription', $subscription->getPid(), $uid),
                OutputInterface::VERBOSITY_VERBOSE
            );

            $subscriptionElements = $elements[$subscription->getPid()];
            if ($subscriptionElements === []) {
                $output->writeln(
                    sprintf('[%s] Page is missing, skip', $pid),
                    OutputInterface::VERBOSITY_VERBOSE
                );
                continue;
            }

            if ($subscriptionElements === false) {
                $output->writeln(
                    sprintf('[%s][%s] Page is missing, delete subscription', $subscription->getPid(), $uid),
                    OutputInterface::VERBOSITY_VERBOSE
                );
                $this->subscriptionRepository->remove($subscription, Type::SUBSCRIPTION->value);
                continue;
            }

            if ($subscription->getElementId() !== null && $subscription->getElementId() !== '') {
                $subscriptionElements = $this->crawler->filterByElementId($subscriptionElements, $subscription->getElementId());
            }

            // Initial complete hash comparison
            $output->writeln(
                sprintf('[%s][%s] Comparing hashes', $subscription->getPid(), $uid),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $subscriptionElementsHash = SubscriptionUtility::getHashArray($subscriptionElements);
            $compareResult = SubscriptionUtility::compareHashes($subscription, $subscriptionElements);
            if ($compareResult) {
                $output->writeln(
                    sprintf('[%s][%s] No hash updates found, skip', $subscription->getPid(), $uid),
                    OutputInterface::VERBOSITY_VERBOSE
                );
                continue;
            }

            $updates = [];
            foreach ($subscriptionElements as $element) {
                $handle = $this->updateHandler->handle($subscription, clone $element);

                if (!$handle instanceof UpdateItem) {
                    continue;
                }

                $updateCount++;
                $updateCount += count($handle->getChildren());
                $updates[] = $handle;
            }

            if ($updates !== []) {
                $feUserId = $subscription->getFeUser();
                $feUser = RecordUtility::getFeUser($feUserId);
                $subscriptionId = $subscription->getUid();

                if (!isset($subscriptionUpdates[$feUserId])) {
                    $subscriptionUpdates[$feUserId] = ['feUser' => $feUser, 'subscriptions' => []];
                }

                if (!isset($subscriptionUpdates[$feUserId]['subscriptions'][$subscriptionId])) {
                    $subscriptionUpdates[$feUserId]['subscriptions'][$subscriptionId] = ['subscription' => $subscription, 'updates' => []];
                }

                $subscriptionUpdates[$feUserId]['subscriptions'][$subscriptionId]['updates'] = $updates;

                if (!$dryRun) {
                    $this->subscriptionRepository->updateLastChecked($uid, $subscriptionElementsHash);
                }
            }
        }

        $io->section('Result');
        $output->writeln(
            sprintf('Found %d updates for %d subscriptions', $updateCount, $subscriptionCount),
            OutputInterface::VERBOSITY_VERBOSE
        );

        $output->writeln('🔎  ' . $subscriptionCount . ' subscription(s) checked');
        if ($updateCount === 0) {
            $output->writeln('⛔  No updates found');
            return Command::SUCCESS;
        }

        $output->writeln('✅  ' . $updateCount . ' subscription update(s) found');
        $this->renderTable($output, $subscriptionUpdates);

        if (!$dryRun) {
            $output->writeln('📧 Notifying subscriber(s)');
            $this->mailer->notify($subscriptionUpdates);
        }

        return Command::SUCCESS;
    }

    private function renderTable(OutputInterface $output, array $subscriptionUpdates): void
    {
        $table = new Table($output);
        $rows = [];
        foreach ($subscriptionUpdates as $subscriptionUpdate) {
            $rows[] = [new TableCell('User (' . $subscriptionUpdate['feUser']['username'] . ' / ' . $subscriptionUpdate['feUser']['email'] . ')', ['colspan' => 6])];
            foreach ($subscriptionUpdate['subscriptions'] as $subscription) {
                $rows[] = new TableSeparator();
                $rows[] = [new TableCell('⇨ Subscription ' . ($subscription['subscription']->getElementId() ? '"' . $subscription['subscription']->getElementId() . '"' : '') . ' (' . date('Y-m-d H:i', $subscription['subscription']->getLastChecked()) . ') [' . $subscription['subscription']->getTitle() . ' / ' . $subscription['subscription']->getPid() . ' / ' . $subscription['subscription']->getLink() . ')', ['colspan' => 6])];
                $rows[] = new TableSeparator();
                foreach ($subscription['updates'] as $update) {
                    $rowData = [
                        ' ↳ ' . $update->getRecuid(),
                        $update->getTable(),
                        $update->getCtype(),
                        mb_strimwidth((string)$update->getTitle(), 0, 30, '…'),
                        $this->getActionType($update->getType()),
                        date('Y-m-d H:i', $update->getTstamp()),
                    ];
                    $rows[] = $rowData;

                    if (count($update->getChildren()) > 0) {
                        foreach ($update->getChildren() as $child) {
                            $rows[] = [
                                '   ↳ ' . $child->getRecuid(),
                                $child->getTable(),
                                '',
                                ' ↳ ' . mb_strimwidth((string)$child->getTitle(), 0, 30, '…'),
                                $this->getActionType($child->getType()),
                                date('Y-m-d H:i', $child->getTstamp()),
                            ];
                        }
                    }
                }
            }
        }

        $table
            ->setHeaders(['UID', 'Table', 'Type', 'Title', 'Action', 'Date'])
            ->setRows($rows);

        $table->render();
    }

    private function getActionType(?int $type): string
    {
        $actionType = $type ? self::ACTIONTYPES[$type] : '';
        $actionColor = match ($type) {
            1 => "\033[32m",
            2 => "\033[33m",
            3 => "\033[31m",
            default => "\033[0m",
        };
        return $actionColor . $actionType . "\033[0m";
    }
}
