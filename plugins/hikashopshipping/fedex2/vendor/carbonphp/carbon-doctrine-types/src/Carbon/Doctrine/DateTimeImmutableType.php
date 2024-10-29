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

declare(strict_types=1);

namespace Carbon\Doctrine;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\VarDateTimeImmutableType;

class DateTimeImmutableType extends VarDateTimeImmutableType implements CarbonDoctrineType
{

    use CarbonTypeConverter;

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?CarbonImmutable
    {
        return $this->doConvertToPHPValue($value);
    }

    protected function getCarbonClassName(): string
    {
        return CarbonImmutable::class;
    }
}
