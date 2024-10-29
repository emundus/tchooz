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


namespace Symfony\Component\Translation\Command;

use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBag;

trait TranslationTrait
{
    private function readLocalTranslations(array $locales, array $domains, array $transPaths): TranslatorBag
    {
        $bag = new TranslatorBag();

        foreach ($locales as $locale) {
            $catalogue = new MessageCatalogue($locale);
            foreach ($transPaths as $path) {
                $this->reader->read($path, $catalogue);
            }

            if ($domains) {
                foreach ($domains as $domain) {
                    $bag->addCatalogue($this->filterCatalogue($catalogue, $domain));
                }
            } else {
                $bag->addCatalogue($catalogue);
            }
        }

        return $bag;
    }

    private function filterCatalogue(MessageCatalogue $catalogue, string $domain): MessageCatalogue
    {
        $filteredCatalogue = new MessageCatalogue($catalogue->getLocale());

        $intlDomain = $domain.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX;
        if ($intlMessages = $catalogue->all($intlDomain)) {
            $filteredCatalogue->add($intlMessages, $intlDomain);
        }

        if ($messages = array_diff($catalogue->all($domain), $intlMessages)) {
            $filteredCatalogue->add($messages, $domain);
        }
        foreach ($catalogue->getResources() as $resource) {
            $filteredCatalogue->addResource($resource);
        }

        if ($metadata = $catalogue->getMetadata('', $intlDomain)) {
            foreach ($metadata as $k => $v) {
                $filteredCatalogue->setMetadata($k, $v, $intlDomain);
            }
        }

        if ($metadata = $catalogue->getMetadata('', $domain)) {
            foreach ($metadata as $k => $v) {
                $filteredCatalogue->setMetadata($k, $v, $domain);
            }
        }

        return $filteredCatalogue;
    }
}
