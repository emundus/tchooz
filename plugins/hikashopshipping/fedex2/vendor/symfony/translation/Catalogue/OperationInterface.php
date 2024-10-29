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


namespace Symfony\Component\Translation\Catalogue;

use Symfony\Component\Translation\MessageCatalogueInterface;

interface OperationInterface
{
    public function getDomains(): array;

    public function getMessages(string $domain): array;

    public function getNewMessages(string $domain): array;

    public function getObsoleteMessages(string $domain): array;

    public function getResult(): MessageCatalogueInterface;
}
