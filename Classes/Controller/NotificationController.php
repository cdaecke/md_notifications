<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Controller;

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

use Psr\Http\Message\ResponseInterface;

/**
 * NotificationController
 */
class NotificationController extends AbstractController
{
    /**
     * List notification records
     * If `$this->settings['recordKeys']` is provided, it will show the records of the selected keys only
     *
     * @return ResponseInterface
     */
    public function listAction(): ResponseInterface
    {
        if ($this->feuserUid !== null) {
            $notifications = $this->notificationRepository->getList(
                $this->feuserUid,
                $this->settings['recordKeys'] ?? '',
            );

            $this->view->assignMultiple($this->getPaginatedItems($notifications));

            $recordKeys = $this->settings['recordKeys'] ?? null;
            if (is_string($recordKeys) && $recordKeys !== '') {
                $this->view->assign('recordKeys', explode(',', $recordKeys));
            }
        }

        return $this->htmlResponse();
    }

    /**
     * Get number of items for user
     * If `$this->settings['recordKeys']` is provided, it will return the records for the selected keys only
     * `$this->settings['recordKeys']` can be a comma separated string, eg. 'pages,tx_news_domain_model_news'
     *
     * @return ResponseInterface
     */
    public function countAction(): ResponseInterface
    {
        if ($this->feuserUid !== null) {
            $notifications = $this->notificationRepository->countItems(
                $this->feuserUid,
                $this->settings['recordKeys'] ?? null,
            );

            $this->view->assign('notifications', $notifications);

            $recordKeys = $this->settings['recordKeys'] ?? null;
            if (is_string($recordKeys) && $recordKeys !== '') {
                $this->view->assign('recordKeys', explode(',', $recordKeys));
            }
        }

        return $this->htmlResponse();
    }

    /**
     * Get information, if given record was seen already
     *
     * @return ResponseInterface
     */
    public function hasSeenAction(): ResponseInterface
    {
        if ($this->feuserUid !== null) {
            $hasSeen = $this->notificationRepository->hasSeen(
                $this->settings['recordKey'],
                (int)$this->settings['recordUid'],
                $this->feuserUid
            );

            $this->view->assign('hasSeen', $hasSeen);
        }

        return $this->htmlResponse();
    }

    /**
     * Delete item for given user
     */
    public function deleteAction(): ResponseInterface
    {
        $recordKey = $this->settings['recordKey'] ?? null;
        $recordUid = (int)($this->settings['recordUid'] ?? 0);
        if (
            is_string($recordKey)
            && $recordKey !== ''
            && $recordUid > 0
            && $this->feuserUid !== null
        ) {
            $this->notificationRepository->deleteEntry(
                $recordKey,
                $recordUid,
                $this->feuserUid
            );
        }

        // No output
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($this->streamFactory->createStream(''));
    }
}
