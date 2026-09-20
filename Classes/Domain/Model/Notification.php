<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

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
/**
 * Notification
 */
class Notification extends AbstractEntity
{
    /**
     * The key of the data record.
     *
     * @var string
     */
    protected string $recordKey = '';

    /**
     * The Id of the data record.
     *
     * @var int
     */
    protected int $recordId = 0;

    /**
     * The creation date of the data record.
     *
     * @var ?\DateTime
     */
    protected ?\DateTime $recordDate = null;

    /**
     * The Id of the frontend user.
     *
     * @var int
     */
    protected int $feuser = 0;

    /**
     * A JSON representation of the data of the record.
     *
     * @var string
     */
    protected string $data = '';

    public function getRecordKey(): string
    {
        return $this->recordKey;
    }

    public function setRecordKey(string $recordKey): void
    {
        $this->recordKey = $recordKey;
    }

    public function getRecordId(): int
    {
        return $this->recordId;
    }

    public function setRecordId(int $recordId): void
    {
        $this->recordId = $recordId;
    }

    public function getRecordDate(): ?\DateTime
    {
        return $this->recordDate;
    }

    public function setRecordDate(?\DateTime $recordDate): void
    {
        $this->recordDate = $recordDate;
    }

    public function getFeuser(): int
    {
        return $this->feuser;
    }

    public function setFeuser(int $feuser): void
    {
        $this->feuser = $feuser;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDataArr(): array
    {
        return json_decode($this->data, true);
    }
}
