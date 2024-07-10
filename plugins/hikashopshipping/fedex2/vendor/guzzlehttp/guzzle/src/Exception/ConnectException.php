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
namespace GuzzleHttp\Exception;

use Psr\Http\Message\RequestInterface;

class ConnectException extends RequestException
{
    public function __construct(
        $message,
        RequestInterface $request,
        \Exception $previous = null,
        array $handlerContext = []
    ) {
        parent::__construct($message, $request, null, $previous, $handlerContext);
    }

    public function getResponse()
    {
        return null;
    }

    public function hasResponse()
    {
        return false;
    }
}
