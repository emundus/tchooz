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


namespace Symfony\Component\Translation\Dumper;

use Symfony\Component\Translation\MessageCatalogue;

class PoFileDumper extends FileDumper
{
    public function formatCatalogue(MessageCatalogue $messages, string $domain, array $options = []): string
    {
        $output = 'msgid ""'."\n";
        $output .= 'msgstr ""'."\n";
        $output .= '"Content-Type: text/plain; charset=UTF-8\n"'."\n";
        $output .= '"Content-Transfer-Encoding: 8bit\n"'."\n";
        $output .= '"Language: '.$messages->getLocale().'\n"'."\n";
        $output .= "\n";

        $newLine = false;
        foreach ($messages->all($domain) as $source => $target) {
            if ($newLine) {
                $output .= "\n";
            } else {
                $newLine = true;
            }
            $metadata = $messages->getMetadata($source, $domain);

            if (isset($metadata['comments'])) {
                $output .= $this->formatComments($metadata['comments']);
            }
            if (isset($metadata['flags'])) {
                $output .= $this->formatComments(implode(',', (array) $metadata['flags']), ',');
            }
            if (isset($metadata['sources'])) {
                $output .= $this->formatComments(implode(' ', (array) $metadata['sources']), ':');
            }

            $sourceRules = $this->getStandardRules($source);
            $targetRules = $this->getStandardRules($target);
            if (2 == \count($sourceRules) && [] !== $targetRules) {
                $output .= sprintf('msgid "%s"'."\n", $this->escape($sourceRules[0]));
                $output .= sprintf('msgid_plural "%s"'."\n", $this->escape($sourceRules[1]));
                foreach ($targetRules as $i => $targetRule) {
                    $output .= sprintf('msgstr[%d] "%s"'."\n", $i, $this->escape($targetRule));
                }
            } else {
                $output .= sprintf('msgid "%s"'."\n", $this->escape($source));
                $output .= sprintf('msgstr "%s"'."\n", $this->escape($target));
            }
        }

        return $output;
    }

    private function getStandardRules(string $id): array
    {
        $parts = [];
        if (preg_match('/^\|++$/', $id)) {
            $parts = explode('|', $id);
        } elseif (preg_match_all('/(?:\|\||[^\|])++/', $id, $matches)) {
            $parts = $matches[0];
        }

        $intervalRegexp = <<<'EOF'
/^(?P<interval>
    ({\s*
        (\-?\d+(\.\d+)?[\s*,\s*\-?\d+(\.\d+)?]*)
    \s*})

        |

    (?P<left_delimiter>[\[\]])
        \s*
        (?P<left>-Inf|\-?\d+(\.\d+)?)
        \s*,\s*
        (?P<right>\+?Inf|\-?\d+(\.\d+)?)
        \s*
    (?P<right_delimiter>[\[\]])
)\s*(?P<message>.*?)$/xs
EOF;

        $standardRules = [];
        foreach ($parts as $part) {
            $part = trim(str_replace('||', '|', $part));

            if (preg_match($intervalRegexp, $part)) {
                return [];
            } else {
                $standardRules[] = $part;
            }
        }

        return $standardRules;
    }

    protected function getExtension(): string
    {
        return 'po';
    }

    private function escape(string $str): string
    {
        return addcslashes($str, "\0..\37\42\134");
    }

    private function formatComments(string|array $comments, string $prefix = ''): ?string
    {
        $output = null;

        foreach ((array) $comments as $comment) {
            $output .= sprintf('#%s %s'."\n", $prefix, $comment);
        }

        return $output;
    }
}
