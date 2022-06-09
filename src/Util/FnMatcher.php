<?php

declare(strict_types=1);

namespace Inpsyde\WpTranslationDownloader\Util;

class FnMatcher
{
    private const FNMATCH_FLAGS = FNM_CASEFOLD | FNM_PATHNAME | FNM_NOESCAPE;

    /**
     * @param array $patterns
     * @param string $subject
     * @return bool
     */
    public static function isMatchingAny(array $patterns, string $subject): bool
    {
        if (in_array('*', $patterns, true) || in_array('*/*', $patterns, true)) {
            return true;
        }

        foreach ($patterns as $pattern) {
            if (is_string($pattern) && FnMatcher::isMatching($pattern, $subject)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $pattern
     * @param string $subject
     * @return bool
     */
    public static function isMatching(string $pattern, string $subject): bool
    {
        $pattern = trim(strtolower($pattern));
        $subject = strtolower($subject);

        if (($pattern === $subject) || ($pattern === '*') || ($pattern === '*/*')) {
            return true;
        }

        return (strpos($pattern, '*') !== false)
            && fnmatch($pattern, $subject, self::FNMATCH_FLAGS);
    }
}
