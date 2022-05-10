<?php

/*
 * This file is part of the WP Translation Downloader package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit;

use Composer\IO\IOInterface;
use Inpsyde\WpTranslationDownloader\Io;
use PHPUnit\Framework\TestCase;

class IoTest extends TestCase
{
    /**
     * @test
     */
    public function testBasic(): void
    {
        $expectedMessage = 'foo';

        $ioStub = \Mockery::mock(IOInterface::class);
        $ioStub->expects('isVerbose')->andReturnFalse();
        $ioStub->expects('write')->with($expectedMessage);

        $testee = new Io($ioStub);

        static::assertNull($testee->write($expectedMessage));
        static::assertNull($testee->writeOnVerbose($expectedMessage));
        static::assertNull($testee->infoOnVerbose($expectedMessage));
        static::assertNull($testee->errorOnVerbose($expectedMessage));
    }
}