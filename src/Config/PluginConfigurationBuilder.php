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
     * @param IOInterface $io
     */
    public function __construct(IOInterface $io)
    {
        $this->io = $io;
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

        if (!$this->validateSchema($config)) {
            return null;
        }

        return new PluginConfiguration($config);
    }

    /**
     * Validates the wp-translation-downloader configuration against the JSON Schema.
     *
     * @param array $input
     *
     * @return bool
     */
    public function validateSchema(array $input): bool
    {
        // phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
        $schema = [
            '$ref' => 'file://' . realpath(__DIR__ . '/../../resources/wp-translation-downloader-schema.json'),
        ];

        $validator = new Validator();

        $input = (object) json_decode(json_encode($input));

        $validator->validate($input, $schema);

        $isValid = $validator->isValid();

        if (!$isValid) {
            // phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
            $this->io->writeError(
                "<error>[ERROR]</error> validation of wp-translation-downloader configuration failed:"
            );

            foreach ($validator->getErrors() as $error) {
                $this->io->writeError(
                    sprintf(
                        '   <fg=yellow>%1$s</> - %2$s.',
                        $error['pointer'],
                        $error['message']
                    )
                );
            }
        }

        return $isValid;
    }
}
