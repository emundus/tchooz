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


namespace Symfony\Component\Translation;

interface CatalogueMetadataAwareInterface
{
    public function getCatalogueMetadata(string $key = '', string $domain = 'messages'): mixed;

    public function setCatalogueMetadata(string $key, mixed $value, string $domain = 'messages');

    public function deleteCatalogueMetadata(string $key = '', string $domain = 'messages');
}
