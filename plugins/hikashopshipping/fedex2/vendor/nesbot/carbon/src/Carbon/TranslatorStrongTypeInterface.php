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

interface TranslatorStrongTypeInterface
{
    public function getFromCatalogue(MessageCatalogueInterface $catalogue, string $id, string $domain = 'messages');
}
