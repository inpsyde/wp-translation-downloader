<?php

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Util;

use Composer\IO\IOInterface;
use Inpsyde\WpTranslationDownloader\Package\ProjectTranslation;
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

    /**
     * @return void
     */
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
        $testee = $this->factoryLocker();
        static::assertEmpty($testee->lockData());
    }

    /**
     * @dataProvider provideLockData
     * @test
     */
    public function testIsLocked(
        array $lockData,
        string $projectName,
        array $translationData,
        bool $expected
    ): void {

        $this->mockLockFile($lockData);
        $testee = $this->factoryLocker();
        $translation = ProjectTranslation::load($translationData, $projectName);

        static::assertSame($expected, $testee->isLocked($translation));
    }

    /**
     * @return \Generator
     */
    public function provideLockData(): \Generator
    {
        $now = date('c', time());
        $past = date('c', time() - 1);

        $version = '1.0';
        $lowerVersion = '0.9';

        $projectName = 'project-name';
        $language = 'de';

        $translationData = [
            'language' => $language,
            'version' => $version,
            'updated' => $now,
            'package' => 'https://example.com/languages/de_DE.zip',
        ];

        yield 'No lock data written yet' => [
            [],
            $projectName,
            $translationData,
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
            $translationData,
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
            $translationData,
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
            $translationData,
            false,
        ];
    }

    /**
     * @test
     */
    public function testRemoveLockData(): void
    {
        $testee = $this->factoryLocker();

        $translation = ProjectTranslation::load(
            [
                'language' => 'de',
                'updated' => date('c', time() - 1),
                'version' => '1.0',
                'package' => 'https://example.com/de.zip'
            ],
            'project-name'
        );

        static::assertTrue($testee->lockTranslation($translation));
        $testee->writeLockFile();

        static::assertTrue($testee->removeLockFile());
    }

    /**
     * @test
     */
    public function testLockTranslation(): void
    {
        $expectedProjectName = 'project-name';
        $expectedLanguage = 'de';
        $expectedVersion = '1.0';
        $expectedUpdated = date('c', time() - 1);

        $translation = ProjectTranslation::load(
            [
                'language' => $expectedLanguage,
                'updated' => $expectedUpdated,
                'version' => $expectedVersion,
                'package' => 'https://example.com/de.zip',
            ],
            $expectedProjectName
        );

        $testee = $this->factoryLocker();

        static::assertTrue($testee->lockTranslation($translation));
        static::assertTrue($testee->isLocked($translation));
        static::assertTrue($testee->writeLockFile());

        // re-access the written file.
        $testee = $this->factoryLocker();
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
            $testee->lockData()
        );
    }

    /**
     * @test
     */
    public function testRemoveProjectLock(): void
    {
        $expectedProjectName = 'project-name';
        $expectedLanguage = 'de';
        $expectedVersion = '1.0';
        $expectedUpdated = date('c', time() - 1);

        $this->mockLockFile(
            [
                $expectedProjectName => [
                    'translations' => [
                        $expectedLanguage => [
                            'version' => $expectedVersion,
                            'updated' => $expectedUpdated,
                        ],
                    ],
                ],
            ]
        );

        $testee = $this->factoryLocker();
        static::assertTrue($testee->removeProjectLock($expectedProjectName));
    }

    /**
     * @return Locker
     */
    private function factoryLocker(): Locker
    {
        $ioStub = \Mockery::mock(IOInterface::class);
        $ioStub->allows('write');
        $ioStub->allows('writeError');

        return new Locker($ioStub, $this->root->url() . '/');
    }

    /**
     * @param array $json
     * @return void
     */
    private function mockLockFile(array $json): void
    {
        vfsStream::newFile(Locker::LOCK_FILE)
            ->withContent(json_encode($json))
            ->at($this->root);
    }
}