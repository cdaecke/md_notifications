<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Tests\Unit\Domain\Model;

use Mediadreams\MdNotifications\Domain\Model\Notification;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NotificationTest extends TestCase
{
    private Notification $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new Notification();
    }

    #[Test]
    public function initialPropertyValuesAreReturned(): void
    {
        self::assertSame('', $this->subject->getRecordKey());
        self::assertSame(0, $this->subject->getRecordId());
        self::assertNull($this->subject->getRecordDate());
        self::assertSame(0, $this->subject->getFeuser());
        self::assertSame('', $this->subject->getData());
    }

    #[Test]
    public function propertyValuesCanBeSet(): void
    {
        $recordDate = new \DateTime('2026-09-17 10:30:00');

        $this->subject->setRecordKey('tx_news_domain_model_news');
        $this->subject->setRecordId(42);
        $this->subject->setRecordDate($recordDate);
        $this->subject->setFeuser(23);
        $this->subject->setData('{"title":"TYPO3"}');

        self::assertSame('tx_news_domain_model_news', $this->subject->getRecordKey());
        self::assertSame(42, $this->subject->getRecordId());
        self::assertSame($recordDate, $this->subject->getRecordDate());
        self::assertSame(23, $this->subject->getFeuser());
        self::assertSame('{"title":"TYPO3"}', $this->subject->getData());
    }

    #[Test]
    public function recordDateCanBeReset(): void
    {
        $this->subject->setRecordDate(new \DateTime('2026-09-17 10:30:00'));

        $this->subject->setRecordDate(null);

        self::assertNull($this->subject->getRecordDate());
    }

    #[Test]
    public function getDataArrDecodesJsonData(): void
    {
        $this->subject->setData('{"title":"TYPO3","metadata":{"uid":42}}');

        self::assertSame(
            [
                'title' => 'TYPO3',
                'metadata' => ['uid' => 42],
            ],
            $this->subject->getDataArr(),
        );
    }
}
