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
namespace GuzzleHttp\Cookie;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface CookieJarInterface extends \Countable, \IteratorAggregate
{
    public function withCookieHeader(RequestInterface $request);

    public function extractCookies(
        RequestInterface $request,
        ResponseInterface $response
    );

    public function setCookie(SetCookie $cookie);

    public function clear($domain = null, $path = null, $name = null);

    public function clearSessionCookies();

    public function toArray();
}
