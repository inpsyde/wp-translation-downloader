<?php

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Package;

use Composer\Package\CompletePackage;
use Inpsyde\WpTranslationDownloader\Package\TranslatablePackage;
use PHPUnit\Framework\TestCase;

class ProjectTranslationTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideDistTypePackageData
     */
    public function testDistType(array $data, ?string $fileType, ?string $expected): void
    {
        $translatable = new class ($fileType, $data) extends TranslatablePackage
        {
            /** @var array */
            protected $data;

            public function __construct(?string $endpointFileType, array $data)
            {
                $this->data = $data;
                parent::__construct(
                    new CompletePackage('test/test', '1.0.0.0', '1.0'),
                    __DIR__,
                    'https://example.com/test/test/translations',
                    $endpointFileType
                );
            }

            protected function loadTranslations(): bool
            {
                $this->translations = $this->parseTranslations([$this->data]);

                return true;
            }
        };

        $translation = $translatable->translations()[0];

        static::assertSame($expected, $translation->distType());
    }

    /**
     * @return array
     */
    public function provideDistTypePackageData(): array
    {
        return [
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me',
                ],
                null,
                'zip',
            ],
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me.rar',
                ],
                null,
                'rar',
            ],
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me.7z',
                ],
                null,
                'zip',
            ],
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me.mo',
                ],
                null,
                'file',
            ],
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me.json',
                ],
                null,
                'file',
            ],
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me.exe',
                ],
                null,
                null,
            ],
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me',
                ],
                'rar',
                'rar',
            ],
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me.gzip',
                ],
                'xz',
                'xz',
            ],
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me.GZIP',
                ],
                null,
                'gzip',
            ],
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me.TaR',
                ],
                null,
                'tar',
            ],
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me.TAR.GZ',
                ],
                null,
                'tar',
            ],
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me.TAR.GZ',
                ],
                null,
                'tar',
            ],
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me.TGZ',
                ],
                null,
                'tar',
            ],
            [
                [
                    'language' => 'it_IT',
                    'version' => '1.0',
                    'package' => 'https://example.com/test-me.tar.bz2',
                ],
                null,
                'tar',
            ],
        ];
    }
}
