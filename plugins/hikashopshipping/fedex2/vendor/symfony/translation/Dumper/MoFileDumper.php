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

use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Translation\MessageCatalogue;

class MoFileDumper extends FileDumper
{
    public function formatCatalogue(MessageCatalogue $messages, string $domain, array $options = []): string
    {
        $sources = $targets = $sourceOffsets = $targetOffsets = '';
        $offsets = [];
        $size = 0;

        foreach ($messages->all($domain) as $source => $target) {
            $offsets[] = array_map('strlen', [$sources, $source, $targets, $target]);
            $sources .= "\0".$source;
            $targets .= "\0".$target;
            ++$size;
        }

        $header = [
            'magicNumber' => MoFileLoader::MO_LITTLE_ENDIAN_MAGIC,
            'formatRevision' => 0,
            'count' => $size,
            'offsetId' => MoFileLoader::MO_HEADER_SIZE,
            'offsetTranslated' => MoFileLoader::MO_HEADER_SIZE + (8 * $size),
            'sizeHashes' => 0,
            'offsetHashes' => MoFileLoader::MO_HEADER_SIZE + (16 * $size),
        ];

        $sourcesSize = \strlen($sources);
        $sourcesStart = $header['offsetHashes'] + 1;

        foreach ($offsets as $offset) {
            $sourceOffsets .= $this->writeLong($offset[1])
                          .$this->writeLong($offset[0] + $sourcesStart);
            $targetOffsets .= $this->writeLong($offset[3])
                          .$this->writeLong($offset[2] + $sourcesStart + $sourcesSize);
        }

        $output = implode('', array_map($this->writeLong(...), $header))
               .$sourceOffsets
               .$targetOffsets
               .$sources
               .$targets
        ;

        return $output;
    }

    protected function getExtension(): string
    {
        return 'mo';
    }

    private function writeLong(mixed $str): string
    {
        return pack('V*', $str);
    }
}
