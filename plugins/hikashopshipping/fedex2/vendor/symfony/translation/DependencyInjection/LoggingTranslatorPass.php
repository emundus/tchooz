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


namespace Symfony\Component\Translation\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoggingTranslatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasAlias('logger') || !$container->hasAlias('translator')) {
            return;
        }

        if (!$container->hasParameter('translator.logging') || !$container->getParameter('translator.logging')) {
            return;
        }

        $translatorAlias = $container->getAlias('translator');
        $definition = $container->getDefinition((string) $translatorAlias);
        $class = $container->getParameterBag()->resolveValue($definition->getClass());

        if (!$r = $container->getReflectionClass($class)) {
            throw new InvalidArgumentException(sprintf('Class "%s" used for service "%s" cannot be found.', $class, $translatorAlias));
        }

        if (!$r->isSubclassOf(TranslatorInterface::class) || !$r->isSubclassOf(TranslatorBagInterface::class)) {
            return;
        }

        $container->getDefinition('translator.logging')->setDecoratedService('translator');
        $warmer = $container->getDefinition('translation.warmer');
        $subscriberAttributes = $warmer->getTag('container.service_subscriber');
        $warmer->clearTag('container.service_subscriber');

        foreach ($subscriberAttributes as $k => $v) {
            if ((!isset($v['id']) || 'translator' !== $v['id']) && (!isset($v['key']) || 'translator' !== $v['key'])) {
                $warmer->addTag('container.service_subscriber', $v);
            }
        }
        $warmer->addTag('container.service_subscriber', ['key' => 'translator', 'id' => 'translator.logging.inner']);
    }
}
