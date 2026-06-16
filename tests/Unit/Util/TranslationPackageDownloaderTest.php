<?php

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Util;

use Composer\Downloader\DownloaderInterface;
use Composer\Downloader\DownloadManager;
use Composer\IO\NullIO;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Composer\Util\Loop;
use Inpsyde\WpTranslationDownloader\Util\TranslationPackageDownloader;
use PHPUnit\Framework\TestCase;

class TranslationPackageDownloaderTest extends TestCase
{
    /**
     * @var string
     */
    private $baseDir;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/wp-td-test-' . bin2hex(random_bytes(6));
        mkdir($this->baseDir, 0777, true);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        (new Filesystem())->removeDirectory($this->baseDir);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function testStaleL10nPhpIsRemovedForMoOnlyPack(): void
    {
        $targetDir = $this->baseDir . '/languages';
        mkdir($targetDir, 0777, true);

        // Simulate a previous install: an old .mo with a compiled .l10n.php next to it.
        file_put_contents($targetDir . '/plugin-de_DE.mo', 'old');
        file_put_contents($targetDir . '/plugin-de_DE.l10n.php', '<?php // stale');

        // The new pack ships only .mo/.po/.json, no .l10n.php.
        $downloader = $this->factoryDownloader(
            [
                'plugin-de_DE.mo' => 'new',
                'plugin-de_DE.po' => 'new',
            ]
        );

        $result = $downloader->download($this->factoryPackage(), $targetDir);

        static::assertTrue($result);
        static::assertFileExists($targetDir . '/plugin-de_DE.mo');
        static::assertSame('new', file_get_contents($targetDir . '/plugin-de_DE.mo'));
        static::assertFileDoesNotExist($targetDir . '/plugin-de_DE.l10n.php');
    }

    /**
     * @test
     */
    public function testPackProvidedL10nPhpIsPreserved(): void
    {
        $targetDir = $this->baseDir . '/languages';
        mkdir($targetDir, 0777, true);

        file_put_contents($targetDir . '/plugin-de_DE.mo', 'old');
        file_put_contents($targetDir . '/plugin-de_DE.l10n.php', '<?php // stale');

        // This pack ships its own freshly-extracted .l10n.php, which must be kept.
        $downloader = $this->factoryDownloader(
            [
                'plugin-de_DE.mo' => 'new',
                'plugin-de_DE.l10n.php' => '<?php // fresh',
            ]
        );

        $result = $downloader->download($this->factoryPackage(), $targetDir);

        static::assertTrue($result);
        $l10nPhp = $targetDir . '/plugin-de_DE.l10n.php';
        static::assertFileExists($l10nPhp);
        static::assertSame('<?php // fresh', file_get_contents($l10nPhp));
    }

    /**
     * @test
     */
    public function testStaleL10nPhpForUnrelatedLocaleIsKept(): void
    {
        $targetDir = $this->baseDir . '/languages';
        mkdir($targetDir, 0777, true);

        // A .l10n.php for a locale the new pack does not touch must be left alone.
        file_put_contents($targetDir . '/plugin-fr_FR.mo', 'fr');
        file_put_contents($targetDir . '/plugin-fr_FR.l10n.php', '<?php // french');

        $downloader = $this->factoryDownloader(['plugin-de_DE.mo' => 'new']);

        $result = $downloader->download($this->factoryPackage(), $targetDir);

        static::assertTrue($result);
        static::assertFileExists($targetDir . '/plugin-fr_FR.l10n.php');
    }

    /**
     * Builds a downloader whose underlying Composer downloader unpacks the given files
     * (relative path => contents) into the path it is asked to install into.
     *
     * @param array<string, string> $packFiles
     * @return TranslationPackageDownloader
     */
    private function factoryDownloader(array $packFiles): TranslationPackageDownloader
    {
        $filesystem = new Filesystem();

        $unpack = static function (
            PackageInterface $package,
            string $path
        ) use ($packFiles, $filesystem) {
            foreach ($packFiles as $relative => $contents) {
                $fullPath = "{$path}/{$relative}";
                $filesystem->ensureDirectoryExists(dirname($fullPath));
                file_put_contents($fullPath, $contents);
            }

            return \React\Promise\resolve(null);
        };

        $resolved = \React\Promise\resolve(null);

        $innerDownloader = $this->createMock(DownloaderInterface::class);
        $innerDownloader->method('download')->willReturnCallback($unpack);
        $innerDownloader->method('install')->willReturn($resolved);
        $innerDownloader->method('prepare')->willReturn($resolved);
        $innerDownloader->method('cleanup')->willReturn($resolved);

        $downloadManager = $this->createMock(DownloadManager::class);
        $downloadManager->method('getDownloader')->willReturn($innerDownloader);

        $loop = $this->createMock(Loop::class);

        return new TranslationPackageDownloader(
            $loop,
            $downloadManager,
            new NullIO(),
            $filesystem
        );
    }

    /**
     * @return PackageInterface
     */
    private function factoryPackage(): PackageInterface
    {
        $package = $this->createMock(PackageInterface::class);
        $package->method('getDistUrl')->willReturn('https://example.tld/de_DE.zip');
        $package->method('getDistType')->willReturn('zip');

        return $package;
    }
}
