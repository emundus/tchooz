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

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\Exception\RuntimeException;
use Symfony\Component\Translation\MessageCatalogue;

class QtFileLoader implements LoaderInterface
{
    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        if (!class_exists(XmlUtils::class)) {
            throw new RuntimeException('Loading translations from the QT format requires the Symfony Config component.');
        }

        if (!stream_is_local($resource)) {
            throw new InvalidResourceException(sprintf('This is not a local file "%s".', $resource));
        }

        if (!file_exists($resource)) {
            throw new NotFoundResourceException(sprintf('File "%s" not found.', $resource));
        }

        try {
            $dom = XmlUtils::loadFile($resource);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidResourceException(sprintf('Unable to load "%s".', $resource), $e->getCode(), $e);
        }

        $internalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->evaluate('//TS/context/name[text()="'.$domain.'"]');

        $catalogue = new MessageCatalogue($locale);
        if (1 == $nodes->length) {
            $translations = $nodes->item(0)->nextSibling->parentNode->parentNode->getElementsByTagName('message');
            foreach ($translations as $translation) {
                $translationValue = (string) $translation->getElementsByTagName('translation')->item(0)->nodeValue;

                if (!empty($translationValue)) {
                    $catalogue->set(
                        (string) $translation->getElementsByTagName('source')->item(0)->nodeValue,
                        $translationValue,
                        $domain
                    );
                }
            }

            if (class_exists(FileResource::class)) {
                $catalogue->addResource(new FileResource($resource));
            }
        }

        libxml_use_internal_errors($internalErrors);

        return $catalogue;
    }
}
