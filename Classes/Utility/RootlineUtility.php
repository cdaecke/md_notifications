<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Utility;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility class, which provides some methods about the rootline of a page
 */
class RootlineUtility
{
    /**
     * Get array with UIDs of pages, which are in the rootline of the provided pageUid
     *
     * @param int $storageId The storage id of the record
     * @return list<int>
     */
    public function getRootlineIds(int $storageId): array
    {
        $rootline = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Utility\RootlineUtility::class, $storageId);
        $rootlinePages = $rootline->get();

        $uidArr = [];
        foreach ($rootlinePages as $item) {
            $uidArr[] = $item['uid'];
        }

        return $uidArr;
    }

    /**
     * Check, if one of the provided recordIds is in rootline
     *
     * @param int $storageId The storage id of the record
     * @param list<int> $idArr An array of Ids, which can be in the rootline
     */
    public function isInRootline(int $storageId, array $idArr): bool
    {
        return array_intersect($this->getRootlineIds($storageId), $idArr) !== [];
    }
}
