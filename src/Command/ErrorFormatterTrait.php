<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpTranslationDownloader\Command;

use Symfony\Component\Console\Output\OutputInterface;

trait ErrorFormatterTrait
{

    /**
     * @param OutputInterface $output
     * @param string $message
     *
     * @return void
     */
    protected function writeError(OutputInterface $output, string $message): void
    {
        $words = explode(' ', $message);
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            if (strlen($line.$word) < 60) {
                $line .= $line
                    ? " {$word}"
                    : $word;
                continue;
            }

            $lines[] = "  {$line}  ";
            $line = $word;
        }

        $line and $lines[] = "  {$line}  ";

        $lenMax = max(array_map('strlen', $lines));
        $empty = '<error>'.str_repeat(' ', $lenMax).'</error>';
        $errors = ['', $empty];
        foreach ($lines as $line) {
            $lineLen = strlen($line);
            ($lineLen < $lenMax) and $line .= str_repeat(' ', $lenMax - $lineLen);
            $errors[] = "<error>{$line}</error>";
        }

        $errors[] = $empty;
        $errors[] = '';

        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }

        /** @psalm-suppress MixedMethodCall */
        $output->writeln($errors);
    }
}