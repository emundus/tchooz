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

class QtFileDumper extends FileDumper
{
    public function formatCatalogue(MessageCatalogue $messages, string $domain, array $options = []): string
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $ts = $dom->appendChild($dom->createElement('TS'));
        $context = $ts->appendChild($dom->createElement('context'));
        $context->appendChild($dom->createElement('name', $domain));

        foreach ($messages->all($domain) as $source => $target) {
            $message = $context->appendChild($dom->createElement('message'));
            $metadata = $messages->getMetadata($source, $domain);
            if (isset($metadata['sources'])) {
                foreach ((array) $metadata['sources'] as $location) {
                    $loc = explode(':', $location, 2);
                    $location = $message->appendChild($dom->createElement('location'));
                    $location->setAttribute('filename', $loc[0]);
                    if (isset($loc[1])) {
                        $location->setAttribute('line', $loc[1]);
                    }
                }
            }
            $message->appendChild($dom->createElement('source', $source));
            $message->appendChild($dom->createElement('translation', $target));
        }

        return $dom->saveXML();
    }

    protected function getExtension(): string
    {
        return 'ts';
    }
}
