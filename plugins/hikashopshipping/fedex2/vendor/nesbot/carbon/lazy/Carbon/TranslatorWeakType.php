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

if (!class_exists(LazyTranslator::class, false)) {
    class LazyTranslator extends AbstractTranslator
    {
        public function trans($id, array $parameters = [], $domain = null, $locale = null)
        {
            return $this->translate($id, $parameters, $domain, $locale);
        }
    }
}
