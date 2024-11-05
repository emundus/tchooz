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

use Symfony\Component\Translation\Exception\InvalidArgumentException;

abstract class AbstractFileExtractor
{
    protected function extractFiles(string|iterable $resource): iterable
    {
        if (is_iterable($resource)) {
            $files = [];
            foreach ($resource as $file) {
                if ($this->canBeExtracted($file)) {
                    $files[] = $this->toSplFileInfo($file);
                }
            }
        } elseif (is_file($resource)) {
            $files = $this->canBeExtracted($resource) ? [$this->toSplFileInfo($resource)] : [];
        } else {
            $files = $this->extractFromDirectory($resource);
        }

        return $files;
    }

    private function toSplFileInfo(string $file): \SplFileInfo
    {
        return new \SplFileInfo($file);
    }

    protected function isFile(string $file): bool
    {
        if (!is_file($file)) {
            throw new InvalidArgumentException(sprintf('The "%s" file does not exist.', $file));
        }

        return true;
    }

    abstract protected function canBeExtracted(string $file);

    abstract protected function extractFromDirectory(string|array $resource);
}
