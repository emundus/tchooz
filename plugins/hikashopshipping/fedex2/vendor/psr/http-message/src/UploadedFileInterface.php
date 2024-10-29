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

interface UploadedFileInterface
{
    public function getStream(): StreamInterface;

    public function moveTo(string $targetPath): void;

    public function getSize(): ?int;

    public function getError(): int;

    public function getClientFilename(): ?string;

    public function getClientMediaType(): ?string;
}
