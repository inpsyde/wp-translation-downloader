<?php

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Tests\Unit\Package;

use Composer\Package\CompletePackage;
use Composer\Package\Package;
use Inpsyde\WpTranslationDownloader\Package\ProjectTranslation;
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

    /**
     * @test
     */
    public function testGenerationFromParsedJson(): void
    {
        $json = <<<JSON
{
	"translations": [
		{
			"language": "de_DE",
			"version": "2.0.5",
			"updated": "2020-07-01T14:15:26+00:00",
			"english_name": "German",
			"native_name": "Deutsch",
			"package": "https://translate.example.com/traduttore/test-de_DE-2.0.5.zip",
			"iso": [
				"de"
			]
		},
		{
			"language": "de_AT",
			"version": "2.0.5",
			"updated": "2020-07-01T14:15:39+00:00",
			"english_name": "German (Austria)",
			"native_name": "Deutsch (Österreich)",
			"package": "https:\/\/translate.example.com\/traduttore\/test-de_AT-2.0.5.zip",
			"iso": [
				"de"
			]
		}
	]
}
JSON;
        $translatablePackage = new class ($json) extends TranslatablePackage
        {
            private $json;
            public function __construct(string $json)
            {
                $this->json = $json;
                parent::__construct(
                    new Package('test/test', '2.0.5.0', '1.2.0.5'),
                    __DIR__,
                    'https://example.com'
                );
            }

            protected function readEndpointContent(string $apiUrl): ?array
            {
                $result = json_decode($this->json, true);

                return is_array($result) ? $result : null;
            }
        };

        $languages = ['de_DE', 'de_AT'];
        $parsedTranslations = $translatablePackage->translations($languages);
        foreach ($parsedTranslations as $projectTranslation) {
            static::assertSame('zip', $projectTranslation->distType());
            static::assertTrue(in_array($projectTranslation->language(), $languages, true));
        }
    }
}
