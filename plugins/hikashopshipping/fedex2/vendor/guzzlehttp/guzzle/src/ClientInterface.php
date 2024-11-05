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
namespace GuzzleHttp;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

interface ClientInterface
{
    const VERSION = '6.5.5';

    public function send(RequestInterface $request, array $options = []);

    public function sendAsync(RequestInterface $request, array $options = []);

    public function request($method, $uri, array $options = []);

    public function requestAsync($method, $uri, array $options = []);

    public function getConfig($option = null);
}
