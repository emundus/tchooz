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


namespace Symfony\Component\Translation\Loader;

class PoFileLoader extends FileLoader
{
    protected function loadResource(string $resource): array
    {
        $stream = fopen($resource, 'r');

        $defaults = [
            'ids' => [],
            'translated' => null,
        ];

        $messages = [];
        $item = $defaults;
        $flags = [];

        while ($line = fgets($stream)) {
            $line = trim($line);

            if ('' === $line) {
                if (!\in_array('fuzzy', $flags)) {
                    $this->addMessage($messages, $item);
                }
                $item = $defaults;
                $flags = [];
            } elseif (str_starts_with($line, '#,')) {
                $flags = array_map('trim', explode(',', substr($line, 2)));
            } elseif (str_starts_with($line, 'msgid "')) {
                $this->addMessage($messages, $item);
                $item = $defaults;
                $item['ids']['singular'] = substr($line, 7, -1);
            } elseif (str_starts_with($line, 'msgstr "')) {
                $item['translated'] = substr($line, 8, -1);
            } elseif ('"' === $line[0]) {
                $continues = isset($item['translated']) ? 'translated' : 'ids';

                if (\is_array($item[$continues])) {
                    end($item[$continues]);
                    $item[$continues][key($item[$continues])] .= substr($line, 1, -1);
                } else {
                    $item[$continues] .= substr($line, 1, -1);
                }
            } elseif (str_starts_with($line, 'msgid_plural "')) {
                $item['ids']['plural'] = substr($line, 14, -1);
            } elseif (str_starts_with($line, 'msgstr[')) {
                $size = strpos($line, ']');
                $item['translated'][(int) substr($line, 7, 1)] = substr($line, $size + 3, -1);
            }
        }
        if (!\in_array('fuzzy', $flags)) {
            $this->addMessage($messages, $item);
        }
        fclose($stream);

        return $messages;
    }

    private function addMessage(array &$messages, array $item): void
    {
        if (!empty($item['ids']['singular'])) {
            $id = stripcslashes($item['ids']['singular']);
            if (isset($item['ids']['plural'])) {
                $id .= '|'.stripcslashes($item['ids']['plural']);
            }

            $translated = (array) $item['translated'];
            ksort($translated);
            end($translated);
            $count = key($translated);
            $empties = array_fill(0, $count + 1, '-');
            $translated += $empties;
            ksort($translated);

            $messages[$id] = stripcslashes(implode('|', $translated));
        }
    }
}
