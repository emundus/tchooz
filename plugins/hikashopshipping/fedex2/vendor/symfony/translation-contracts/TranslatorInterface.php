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


namespace Symfony\Contracts\Translation;

interface TranslatorInterface
{
    public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string;

    public function getLocale(): string;
}
