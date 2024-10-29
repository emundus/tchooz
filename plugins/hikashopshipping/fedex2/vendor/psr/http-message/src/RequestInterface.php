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

namespace Psr\Http\Message;

interface RequestInterface extends MessageInterface
{
    public function getRequestTarget(): string;

    public function withRequestTarget(string $requestTarget): RequestInterface;


    public function getMethod(): string;

    public function withMethod(string $method): RequestInterface;

    public function getUri(): UriInterface;

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface;
}
