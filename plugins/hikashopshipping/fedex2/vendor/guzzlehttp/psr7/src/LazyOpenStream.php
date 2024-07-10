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

namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamInterface;

class LazyOpenStream implements StreamInterface
{
    use StreamDecoratorTrait;


    private $filename;


    private $mode;

    public function __construct($filename, $mode)
    {
        $this->filename = $filename;
        $this->mode = $mode;
    }

    protected function createStream()
    {
        return Utils::streamFor(Utils::tryFopen($this->filename, $this->mode));
    }
}
