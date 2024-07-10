<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php


namespace Symfony\Component\Translation\Extractor;

trigger_deprecation('symfony/translation', '6.2', '"%s" is deprecated, use "%s" instead.', PhpExtractor::class, PhpAstExtractor::class);

use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\MessageCatalogue;

class PhpExtractor extends AbstractFileExtractor implements ExtractorInterface
{
    public const MESSAGE_TOKEN = 300;
    public const METHOD_ARGUMENTS_TOKEN = 1000;
    public const DOMAIN_TOKEN = 1001;

    private string $prefix = '';

    protected $sequences = [
        [
            '->',
            'trans',
            '(',
            self::MESSAGE_TOKEN,
            ',',
            self::METHOD_ARGUMENTS_TOKEN,
            ',',
            self::DOMAIN_TOKEN,
        ],
        [
            '->',
            'trans',
            '(',
            self::MESSAGE_TOKEN,
        ],
        [
            'new',
            'TranslatableMessage',
            '(',
            self::MESSAGE_TOKEN,
            ',',
            self::METHOD_ARGUMENTS_TOKEN,
            ',',
            self::DOMAIN_TOKEN,
        ],
        [
            'new',
            'TranslatableMessage',
            '(',
            self::MESSAGE_TOKEN,
        ],
        [
            'new',
            '\\',
            'Symfony',
            '\\',
            'Component',
            '\\',
            'Translation',
            '\\',
            'TranslatableMessage',
            '(',
            self::MESSAGE_TOKEN,
            ',',
            self::METHOD_ARGUMENTS_TOKEN,
            ',',
            self::DOMAIN_TOKEN,
        ],
        [
            'new',
            '\Symfony\Component\Translation\TranslatableMessage',
            '(',
            self::MESSAGE_TOKEN,
            ',',
            self::METHOD_ARGUMENTS_TOKEN,
            ',',
            self::DOMAIN_TOKEN,
        ],
        [
            'new',
            '\\',
            'Symfony',
            '\\',
            'Component',
            '\\',
            'Translation',
            '\\',
            'TranslatableMessage',
            '(',
            self::MESSAGE_TOKEN,
        ],
        [
            'new',
            '\Symfony\Component\Translation\TranslatableMessage',
            '(',
            self::MESSAGE_TOKEN,
        ],
        [
            't',
            '(',
            self::MESSAGE_TOKEN,
            ',',
            self::METHOD_ARGUMENTS_TOKEN,
            ',',
            self::DOMAIN_TOKEN,
        ],
        [
            't',
            '(',
            self::MESSAGE_TOKEN,
        ],
    ];

    public function extract(string|iterable $resource, MessageCatalogue $catalog)
    {
        $files = $this->extractFiles($resource);
        foreach ($files as $file) {
            $this->parseTokens(token_get_all(file_get_contents($file)), $catalog, $file);

            gc_mem_caches();
        }
    }

    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }

    protected function normalizeToken(mixed $token): ?string
    {
        if (isset($token[1]) && 'b"' !== $token) {
            return $token[1];
        }

        return $token;
    }

    private function seekToNextRelevantToken(\Iterator $tokenIterator): void
    {
        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();
            if (\T_WHITESPACE !== $t[0]) {
                break;
            }
        }
    }

    private function skipMethodArgument(\Iterator $tokenIterator): void
    {
        $openBraces = 0;

        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();

            if ('[' === $t[0] || '(' === $t[0]) {
                ++$openBraces;
            }

            if (']' === $t[0] || ')' === $t[0]) {
                --$openBraces;
            }

            if ((0 === $openBraces && ',' === $t[0]) || (-1 === $openBraces && ')' === $t[0])) {
                break;
            }
        }
    }

    private function getValue(\Iterator $tokenIterator): string
    {
        $message = '';
        $docToken = '';
        $docPart = '';

        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();
            if ('.' === $t) {
                continue;
            }
            if (!isset($t[1])) {
                break;
            }

            switch ($t[0]) {
                case \T_START_HEREDOC:
                    $docToken = $t[1];
                    break;
                case \T_ENCAPSED_AND_WHITESPACE:
                case \T_CONSTANT_ENCAPSED_STRING:
                    if ('' === $docToken) {
                        $message .= PhpStringTokenParser::parse($t[1]);
                    } else {
                        $docPart = $t[1];
                    }
                    break;
                case \T_END_HEREDOC:
                    if ($indentation = strspn($t[1], ' ')) {
                        $docPartWithLineBreaks = $docPart;
                        $docPart = '';

                        foreach (preg_split('~(\r\n|\n|\r)~', $docPartWithLineBreaks, -1, \PREG_SPLIT_DELIM_CAPTURE) as $str) {
                            if (\in_array($str, ["\r\n", "\n", "\r"], true)) {
                                $docPart .= $str;
                            } else {
                                $docPart .= substr($str, $indentation);
                            }
                        }
                    }

                    $message .= PhpStringTokenParser::parseDocString($docToken, $docPart);
                    $docToken = '';
                    $docPart = '';
                    break;
                case \T_WHITESPACE:
                    break;
                default:
                    break 2;
            }
        }

        return $message;
    }

    protected function parseTokens(array $tokens, MessageCatalogue $catalog, string $filename)
    {
        $tokenIterator = new \ArrayIterator($tokens);

        for ($key = 0; $key < $tokenIterator->count(); ++$key) {
            foreach ($this->sequences as $sequence) {
                $message = '';
                $domain = 'messages';
                $tokenIterator->seek($key);

                foreach ($sequence as $sequenceKey => $item) {
                    $this->seekToNextRelevantToken($tokenIterator);

                    if ($this->normalizeToken($tokenIterator->current()) === $item) {
                        $tokenIterator->next();
                        continue;
                    } elseif (self::MESSAGE_TOKEN === $item) {
                        $message = $this->getValue($tokenIterator);

                        if (\count($sequence) === ($sequenceKey + 1)) {
                            break;
                        }
                    } elseif (self::METHOD_ARGUMENTS_TOKEN === $item) {
                        $this->skipMethodArgument($tokenIterator);
                    } elseif (self::DOMAIN_TOKEN === $item) {
                        $domainToken = $this->getValue($tokenIterator);
                        if ('' !== $domainToken) {
                            $domain = $domainToken;
                        }

                        break;
                    } else {
                        break;
                    }
                }

                if ($message) {
                    $catalog->set($message, $this->prefix.$message, $domain);
                    $metadata = $catalog->getMetadata($message, $domain) ?? [];
                    $normalizedFilename = preg_replace('{[\\\\/]+}', '/', $filename);
                    $metadata['sources'][] = $normalizedFilename.':'.$tokens[$key][2];
                    $catalog->setMetadata($message, $metadata, $domain);
                    break;
                }
            }
        }
    }

    protected function canBeExtracted(string $file): bool
    {
        return $this->isFile($file) && 'php' === pathinfo($file, \PATHINFO_EXTENSION);
    }

    protected function extractFromDirectory(string|array $directory): iterable
    {
        if (!class_exists(Finder::class)) {
            throw new \LogicException(sprintf('You cannot use "%s" as the "symfony/finder" package is not installed. Try running "composer require symfony/finder".', static::class));
        }

        $finder = new Finder();

        return $finder->files()->name('*.php')->in($directory);
    }
}
