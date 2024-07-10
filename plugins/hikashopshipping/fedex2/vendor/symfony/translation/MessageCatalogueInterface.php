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

use Symfony\Component\Config\Resource\ResourceInterface;

interface MessageCatalogueInterface
{
    public const INTL_DOMAIN_SUFFIX = '+intl-icu';

    public function getLocale(): string;

    public function getDomains(): array;

    public function all(?string $domain = null): array;

    public function set(string $id, string $translation, string $domain = 'messages');

    public function has(string $id, string $domain = 'messages'): bool;

    public function defines(string $id, string $domain = 'messages'): bool;

    public function get(string $id, string $domain = 'messages'): string;

    public function replace(array $messages, string $domain = 'messages');

    public function add(array $messages, string $domain = 'messages');

    public function addCatalogue(self $catalogue);

    public function addFallbackCatalogue(self $catalogue);

    public function getFallbackCatalogue(): ?self;

    public function getResources(): array;

    public function addResource(ResourceInterface $resource);
}
