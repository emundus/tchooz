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


namespace Symfony\Component\Translation;

use Symfony\Component\Routing\RequestContext;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class LocaleSwitcher implements LocaleAwareInterface
{
    private string $defaultLocale;

    public function __construct(
        private string $locale,
        private iterable $localeAwareServices,
        private ?RequestContext $requestContext = null,
    ) {
        $this->defaultLocale = $locale;
    }

    public function setLocale(string $locale): void
    {
        if (class_exists(\Locale::class)) {
            \Locale::setDefault($locale);
        }
        $this->locale = $locale;
        $this->requestContext?->setParameter('_locale', $locale);

        foreach ($this->localeAwareServices as $service) {
            $service->setLocale($locale);
        }
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function runWithLocale(string $locale, callable $callback): mixed
    {
        $original = $this->getLocale();
        $this->setLocale($locale);

        try {
            return $callback($locale);
        } finally {
            $this->setLocale($original);
        }
    }

    public function reset(): void
    {
        $this->setLocale($this->defaultLocale);
    }
}
