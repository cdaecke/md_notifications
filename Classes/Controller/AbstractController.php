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

use Mediadreams\MdNotifications\Domain\Repository\NotificationRepository;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class AbstractController
 */
abstract class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * User Id of the logged in user
     *
     * @var int|null
     */
    protected ?int $feuserUid = null;

    public function __construct(protected NotificationRepository $notificationRepository) {}

    /**
     * Initialize actions
     */
    protected function initializeAction(): void
    {
        // Use stdWrap for given defined settings
        // Thanks to Georg Ringer:
        // https://github.com/georgringer/news/blob/976fe5930cea9693f6cd56b650abe4e876fc70f0/Classes/Controller/NewsController.php#L627
        $useStdWrap = $this->settings['useStdWrap'] ?? null;
        $currentContentObject = $this->request->getAttribute('currentContentObject');
        if (is_string($useStdWrap) && $useStdWrap !== '' && $currentContentObject instanceof ContentObjectRenderer) {
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            $typoScriptArray = $typoScriptService->convertPlainArrayToTypoScriptArray($this->settings);
            $stdWrapProperties = GeneralUtility::trimExplode(',', $useStdWrap, true);
            foreach ($stdWrapProperties as $key) {
                if (is_array($typoScriptArray[$key . '.'] ?? null)) {
                    $this->settings[$key] = $currentContentObject->stdWrap(
                        $typoScriptArray[$key] ?? '',
                        $typoScriptArray[$key . '.']
                    );
                }
            }
        }

        $this->feuserUid = $this->request->getAttribute('frontend.user')->user['uid'] ?? null;
    }

    /**
     * Get paginated items and paginator for query result
     *
     * @param QueryResultInterface<int, \Mediadreams\MdNotifications\Domain\Model\Notification> $items
     * @return array{
     *     notifications: iterable<mixed>,
     *     pagination: SlidingWindowPagination,
     *     paginator: QueryResultPaginator,
     *     currentPageNumber: int
     * }
     */
    protected function getPaginatedItems(QueryResultInterface $items): array
    {
        $currentPage = $this->request->hasArgument('currentPageNumber')
            ? (int)$this->request->getArgument('currentPageNumber')
            : 1;

        $itemsPerPage = isset($this->settings['pagination']['itemsPerPage']) ? (int)$this->settings['pagination']['itemsPerPage'] : 10;
        $maxNumPages = isset($this->settings['pagination']['maxNumPages']) ? (int)$this->settings['pagination']['maxNumPages'] : 5;

        $paginator = new QueryResultPaginator(
            $items,
            $currentPage,
            $itemsPerPage,
        );
        $pagination = new SlidingWindowPagination(
            $paginator,
            $maxNumPages,
        );

        return [
            'notifications' => $pagination->getPaginator()->getPaginatedItems(),
            'pagination' => $pagination,
            'paginator' => $paginator,
            'currentPageNumber' => $paginator->getCurrentPageNumber(),
        ];
    }
}
