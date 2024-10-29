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
namespace GuzzleHttp\Handler;

use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;

class Proxy
{
    public static function wrapSync(
        callable $default,
        callable $sync
    ) {
        return function (RequestInterface $request, array $options) use ($default, $sync) {
            return empty($options[RequestOptions::SYNCHRONOUS])
                ? $default($request, $options)
                : $sync($request, $options);
        };
    }

    public static function wrapStreaming(
        callable $default,
        callable $streaming
    ) {
        return function (RequestInterface $request, array $options) use ($default, $streaming) {
            return empty($options['stream'])
                ? $default($request, $options)
                : $streaming($request, $options);
        };
    }
}
