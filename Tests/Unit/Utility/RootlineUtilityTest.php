<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Tests\Unit\Utility;

use Mediadreams\MdNotifications\Utility\RootlineUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RootlineUtilityTest extends TestCase
{
    /**
     * @return iterable<string, array{list<int>, list<int>, bool}>
     */
    public static function rootlineProvider(): iterable
    {
        yield 'matching page identifier' => [[1, 13, 42], [42, 99], true];
        yield 'multiple matching page identifiers' => [[1, 13, 42], [1, 42], true];
        yield 'no matching page identifier' => [[1, 13, 42], [23, 99], false];
        yield 'empty rootline' => [[], [42], false];
        yield 'empty candidate list' => [[1, 13, 42], [], false];
    }

    /**
     * @param list<int> $rootlineIds
     * @param list<int> $candidateIds
     */
    #[Test]
    #[DataProvider('rootlineProvider')]
    public function isInRootlineReportsWhetherCandidateIsPartOfRootline(
        array $rootlineIds,
        array $candidateIds,
        bool $expected,
    ): void {
        /** @var RootlineUtility&MockObject $subject */
        $subject = $this->getMockBuilder(RootlineUtility::class)
            ->onlyMethods(['getRootlineIds'])
            ->getMock();
        $subject->expects($this->once())
            ->method('getRootlineIds')
            ->with(42)
            ->willReturn($rootlineIds);

        self::assertSame($expected, $subject->isInRootline(42, $candidateIds));
    }
}
