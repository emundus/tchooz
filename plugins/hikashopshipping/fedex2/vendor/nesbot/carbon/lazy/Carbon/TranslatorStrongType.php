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


namespace Carbon;

use Symfony\Component\Translation\MessageCatalogueInterface;

if (!class_exists(LazyTranslator::class, false)) {
    class LazyTranslator extends AbstractTranslator implements TranslatorStrongTypeInterface
    {
        public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
        {
            return $this->translate($id, $parameters, $domain, $locale);
        }

        public function getFromCatalogue(MessageCatalogueInterface $catalogue, string $id, string $domain = 'messages')
        {
            $messages = $this->getPrivateProperty($catalogue, 'messages');

            if (isset($messages[$domain.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX][$id])) {
                return $messages[$domain.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX][$id];
            }

            if (isset($messages[$domain][$id])) {
                return $messages[$domain][$id];
            }

            $fallbackCatalogue = $this->getPrivateProperty($catalogue, 'fallbackCatalogue');

            if ($fallbackCatalogue !== null) {
                return $this->getFromCatalogue($fallbackCatalogue, $id, $domain);
            }

            return $id;
        }

        private function getPrivateProperty($instance, string $field)
        {
            return (function (string $field) {
                return $this->$field;
            })->call($instance, $field);
        }
    }
}
