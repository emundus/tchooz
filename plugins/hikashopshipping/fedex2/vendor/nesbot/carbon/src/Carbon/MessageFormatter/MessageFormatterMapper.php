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


namespace Carbon\MessageFormatter;

use ReflectionMethod;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Formatter\MessageFormatterInterface;

$transMethod = new ReflectionMethod(MessageFormatterInterface::class, 'format');

require $transMethod->getParameters()[0]->hasType()
    ? __DIR__.'/../../../lazy/Carbon/MessageFormatter/MessageFormatterMapperStrongType.php'
    : __DIR__.'/../../../lazy/Carbon/MessageFormatter/MessageFormatterMapperWeakType.php';

final class MessageFormatterMapper extends LazyMessageFormatter
{
    protected $formatter;

    public function __construct(?MessageFormatterInterface $formatter = null)
    {
        $this->formatter = $formatter ?? new MessageFormatter();
    }

    protected function transformLocale(?string $locale): ?string
    {
        return $locale ? preg_replace('/[_@][A-Za-z][a-z]{2,}/', '', $locale) : $locale;
    }
}
