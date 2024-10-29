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


namespace Symfony\Component\Translation\Formatter;

use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;

class_exists(IntlFormatter::class);

class MessageFormatter implements MessageFormatterInterface, IntlFormatterInterface
{
    private TranslatorInterface $translator;
    private IntlFormatterInterface $intlFormatter;

    public function __construct(?TranslatorInterface $translator = null, ?IntlFormatterInterface $intlFormatter = null)
    {
        $this->translator = $translator ?? new IdentityTranslator();
        $this->intlFormatter = $intlFormatter ?? new IntlFormatter();
    }

    public function format(string $message, string $locale, array $parameters = []): string
    {
        return $this->translator->trans($message, $parameters, null, $locale);
    }

    public function formatIntl(string $message, string $locale, array $parameters = []): string
    {
        return $this->intlFormatter->formatIntl($message, $locale, $parameters);
    }
}
