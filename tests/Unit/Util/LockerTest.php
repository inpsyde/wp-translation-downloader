<?php

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Util;

use Inpsyde\WpTranslationDownloader\Io;
use Inpsyde\WpTranslationDownloader\Util\Locker;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class LockerTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    public function setUp(): void
    {
        $this->root = vfsStream::setup('tmp');
        parent::setUp();
    }

    /**
     * @test
     */
    public function testBasic(): void
    {
        $testee = $this->locker();
        static::assertEmpty($testee->cachedLockData());
    }

    /**
     * @dataProvider provideLockData
     * @test
     */
    public function testIsLocked(
        array $lockData,
        string $projectName,
        string $language,
        string $lastUpdated,
        string $version,
        bool $expected
    ): void {

        $this->mockLockFile($lockData);
        $testee = $this->locker();

        static::assertSame(
            $expected,
            $testee->isLocked($projectName, $language, (string) $lastUpdated, $version)
        );
    }

    public function provideLockData(): \Generator
    {
        $now = date('c', time());
        $past = date('c', time() - 1);

        $version = '1.0';
        $lowerVersion = '0.9';

        $projectName = 'project-name';
        $language = 'de';

        yield 'No lock data written yet' => [
            [],
            $projectName,
            $language,
            $now,
            $version,
            false,
        ];

        yield 'is locked' => [
            [
                $projectName => [
                    'translations' => [
                        $language => [
                            'version' => $version,
                            'updated' => $now,
                        ],
                    ],
                ],
            ],
            $projectName,
            $language,
            $now,
            $version,
            true,
        ];

        yield 'is not locked - last updated' => [
            [
                $projectName => [
                    'translations' => [
                        $language => [
                            'version' => $version,
                            'updated' => $past,
                        ],
                    ],
                ],
            ],
            $projectName,
            $language,
            $now,
            $version,
            false,
        ];

        yield 'is not locked - version' => [
            [
                $projectName => [
                    'translations' => [
                        $language => [
                            'version' => $version,
                            'updated' => $lowerVersion,
                        ],
                    ],
                ],
            ],
            $projectName,
            $language,
            $now,
            $version,
            false,
        ];
    }

    /**
     * @test
     */
    public function testAddProjectLockAndReadAgain(): void
    {
        $expectedProjectName = 'project-name';
        $expectedLanguage = 'de';
        $expectedVersion = '1.0';
        $expectedUpdated = date('c', time() - 1);

        $testee = $this->locker();

        static::assertTrue(
            $testee->addProjectLock(
                $expectedProjectName,
                $expectedLanguage,
                $expectedUpdated,
                $expectedVersion
            )
        );
        $testee->writeLockData();

        // re-access the written file.
        $testee = $this->locker();
        $cachedLockData = $testee->cachedLockData();
        static::assertSame(
            [
                $expectedProjectName => [
                    'translations' => [
                        $expectedLanguage => [
                            'updated' => $expectedUpdated,
                            'version' => $expectedVersion,
                        ],
                    ],
                ],
            ],
            $cachedLockData
        );
    }

    private function locker(): Locker
    {
        $ioStub = \Mockery::mock(Io::class);
        $ioStub->expects('writeOnVerbose')->andReturns();
        $ioStub->expects('write')->andReturns();

        return new Locker($ioStub, $this->root->url() . '/');
    }

    private function mockLockFile(array $json): string
    {
        return vfsStream::newFile(Locker::LOCK_FILE)
            ->withContent(json_encode($json))
            ->at($this->root)
            ->url();
    }
}