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

namespace Inpsyde\WpTranslationDownloader\Config;

use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use JsonSchema\Validator;

final class PluginConfigurationBuilder
{
    public const KEY = 'wp-translation-downloader';

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var string
     */
    private $schemaFile;

    /**
     * @param IOInterface $io
     * @param string $resourcedDir
     */
    public function __construct(IOInterface $io, string $resourcedDir)
    {
        $this->io = $io;
        $this->schemaFile = "{$resourcedDir}/wp-translation-downloader-schema.json";
    }

    /**
     * @param array $extra
     *
     * @return PluginConfiguration|null
     *
     * @throws \Throwable
     */
    public function build(array $extra = []): ?PluginConfiguration
    {
        if (!isset($extra[self::KEY])) {
            $this->io->write(
                sprintf(
                    '<info>No "%s" in "extra" found.</info>',
                    self::KEY
                ),
                true,
                IOInterface::VERBOSE
            );

            return null;
        }

        $config = $extra[self::KEY];

        if (is_string($config) && is_file($config)) {
            $file = new JsonFile($config);
            $config = $file->read();
        }

        if (!is_array($config)) {
            $this->io->writeError(
                '<error>[ERROR]</error> wp-translation-downloader configuration must be an array '
                . 'or the path to a JSON configuration file.'
            );

            return null;
        }

        return $this->validateSchema($config)
            ? new PluginConfiguration($config)
            : null;
    }

    /**
     * Validates the wp-translation-downloader configuration against the JSON Schema.
     *
     * @param array $input
     * @return bool
     */
    public function validateSchema(array $input): bool
    {
        $schema = ['$ref' => "file://{$this->schemaFile}"];

        $validator = new Validator();

        $input = (object) json_decode(json_encode($input) ?: '{}');

        $validator->validate($input, $schema);

        $isValid = (bool)$validator->isValid();

        if (!$isValid) {
            // phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
            $this->io->writeError(
                "<error>[ERROR]</error> Failed validating wp-translation-downloader configuration:"
            );

            foreach ($validator->getErrors() as $error) {
                assert(is_array($error));
                $pointer = $error['pointer'] ?? '';
                $message = $error['message'] ?? '';
                $prefix = is_string($pointer) ? "   <fg=yellow>{$pointer}</> - " : '   ';
                is_string($message) or $message = 'Generic error';
                $this->io->writeError($prefix . $message);
            }
        }

        return $isValid;
    }
}
