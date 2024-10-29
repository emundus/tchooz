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

use Symfony\Component\Translation\Exception\InvalidArgumentException;

final class TranslationProviderCollection
{
    private array $providers;

    public function __construct(iterable $providers)
    {
        $this->providers = \is_array($providers) ? $providers : iterator_to_array($providers);
    }

    public function __toString(): string
    {
        return '['.implode(',', array_keys($this->providers)).']';
    }

    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    public function get(string $name): ProviderInterface
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException(sprintf('Provider "%s" not found. Available: "%s".', $name, (string) $this));
        }

        return $this->providers[$name];
    }

    public function keys(): array
    {
        return array_keys($this->providers);
    }
}
