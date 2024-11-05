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

class PumpStream implements StreamInterface
{

    private $source;


    private $size;


    private $tellPos = 0;


    private $metadata;


    private $buffer;

    public function __construct(callable $source, array $options = [])
    {
        $this->source = $source;
        $this->size = isset($options['size']) ? $options['size'] : null;
        $this->metadata = isset($options['metadata']) ? $options['metadata'] : [];
        $this->buffer = new BufferStream();
    }

    public function __toString()
    {
        try {
            return Utils::copyToString($this);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function close()
    {
        $this->detach();
    }

    public function detach()
    {
        $this->tellPos = false;
        $this->source = null;

        return null;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function tell()
    {
        return $this->tellPos;
    }

    public function eof()
    {
        return !$this->source;
    }

    public function isSeekable()
    {
        return false;
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \RuntimeException('Cannot seek a PumpStream');
    }

    public function isWritable()
    {
        return false;
    }

    public function write($string)
    {
        throw new \RuntimeException('Cannot write to a PumpStream');
    }

    public function isReadable()
    {
        return true;
    }

    public function read($length)
    {
        $data = $this->buffer->read($length);
        $readLen = strlen($data);
        $this->tellPos += $readLen;
        $remaining = $length - $readLen;

        if ($remaining) {
            $this->pump($remaining);
            $data .= $this->buffer->read($remaining);
            $this->tellPos += strlen($data) - $readLen;
        }

        return $data;
    }

    public function getContents()
    {
        $result = '';
        while (!$this->eof()) {
            $result .= $this->read(1000000);
        }

        return $result;
    }

    public function getMetadata($key = null)
    {
        if (!$key) {
            return $this->metadata;
        }

        return isset($this->metadata[$key]) ? $this->metadata[$key] : null;
    }

    private function pump($length)
    {
        if ($this->source) {
            do {
                $data = call_user_func($this->source, $length);
                if ($data === false || $data === null) {
                    $this->source = null;
                    return;
                }
                $this->buffer->write($data);
                $length -= strlen($data);
            } while ($length > 0);
        }
    }
}
