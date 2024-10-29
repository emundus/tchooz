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


namespace Symfony\Component\Translation\Provider;

use Symfony\Component\Translation\Exception\UnsupportedSchemeException;

class TranslationProviderCollectionFactory
{
    private iterable $factories;
    private array $enabledLocales;

    public function __construct(iterable $factories, array $enabledLocales)
    {
        $this->factories = $factories;
        $this->enabledLocales = $enabledLocales;
    }

    public function fromConfig(array $config): TranslationProviderCollection
    {
        $providers = [];
        foreach ($config as $name => $currentConfig) {
            $providers[$name] = $this->fromDsnObject(
                new Dsn($currentConfig['dsn']),
                !$currentConfig['locales'] ? $this->enabledLocales : $currentConfig['locales'],
                !$currentConfig['domains'] ? [] : $currentConfig['domains']
            );
        }

        return new TranslationProviderCollection($providers);
    }

    public function fromDsnObject(Dsn $dsn, array $locales, array $domains = []): ProviderInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($dsn)) {
                return new FilteringProvider($factory->create($dsn), $locales, $domains);
            }
        }

        throw new UnsupportedSchemeException($dsn);
    }
}
