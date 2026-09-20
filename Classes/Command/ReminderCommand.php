<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Command;

/**
 * This file is part of the "Notifications" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 *
 * (c) 2025 Christoph Daecke <typo3@mediadreams.org>
 */

use Mediadreams\MdNotifications\Domain\Repository\NotificationRepository;
use Mediadreams\MdNotifications\Service\MailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand(
    name: 'mdNotifications:reminder',
    description: 'This command will send emails about open notifications.',
)]
class ReminderCommand extends Command
{
    public function __construct(
        protected readonly NotificationRepository $notificationRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command will send emails about open notifications')
            ->addArgument(
                'storages',
                InputArgument::REQUIRED,
                'Comma separated list of IDs where the notification data is stored.'
            )->addArgument(
                'listPageUid',
                InputArgument::REQUIRED,
                'The Uid of the page, which holds the list of notification items.'
            )->addArgument(
                'mailSubject',
                InputArgument::REQUIRED,
                'The subject of the email.'
            )->addArgument(
                'mailTemplate',
                InputArgument::OPTIONAL,
                'Add the name of the HTML file, which is used for the e-mail.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storageIds = (string)$input->getArgument('storages');
        $storageArr = GeneralUtility::intExplode(',', $storageIds);
        $users = $this->notificationRepository->getUsersWithNotifications($storageArr);
        $listPageUri = $this->getPageUri((int)$input->getArgument('listPageUid'));
        $mailSubject = (string)$input->getArgument('mailSubject');
        $mailTemplate = (string)$input->getArgument('mailTemplate');

        foreach ($users as $data) {
            $email = $data['user']['email'] ?? null;
            if (is_string($email) && GeneralUtility::validEmail($email)) {
                MailService::sendMail(
                    $email,
                    $mailSubject,
                    array_merge(
                        $data['user'],
                        ['listPageUri' => $listPageUri],
                        ['notificationItems' => count($data['notification_records'])],
                        ['notificationData' => $data['notification_records']]
                    ),
                    $mailTemplate !== '' ? $mailTemplate : 'Notifications'
                );
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Get the URI of the page with the list of notifications
     *
     * @param int $listPageId The page id of the page which should be linked in the email
     * @return string
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    protected function getPageUri(int $listPageId): string
    {
        $site = GeneralUtility::makeInstance(SiteFinder::class)
            ->getSiteByPageId($listPageId);

        return (string)$site->getRouter()->generateUri($listPageId);
    }
}
