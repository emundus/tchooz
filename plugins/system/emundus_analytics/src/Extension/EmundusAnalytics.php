<?php

/**
 * @package         Emundus.Plugin
 * @subpackage      System.emundus
 *
 * @copyright       Copyright (C) 2005-2025 eMundus - All rights reserved.
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\EmundusAnalytics\Extension;

use EmundusModelForm;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Application\AfterInitialiseEvent;
use Joomla\CMS\Event\Application\AfterRenderEvent;
use Joomla\CMS\Event\Application\AfterRouteEvent;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Event\User\LoginEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Component\Users\Administrator\Helper\Mfa as MfaHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Tchooz\Entities\Analytics\PageAnalyticsEntity;
use Tchooz\Entities\Emails\Modifiers\CapitalizeModifier;
use Tchooz\Entities\Emails\Modifiers\LettersModifier;
use Tchooz\Entities\Emails\Modifiers\LowercaseModifier;
use Tchooz\Entities\Emails\Modifiers\NumberModifier;
use Tchooz\Entities\Emails\Modifiers\TrimModifier;
use Tchooz\Entities\Emails\Modifiers\UppercaseModifier;
use Tchooz\Entities\Emails\TagModifierRegistry;
use Tchooz\Repositories\Analytics\PageAnalyticsRepository;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

final class EmundusAnalytics extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;

	public static function getSubscribedEvents(): array
	{
		$app = Factory::getApplication();

		$mapping = [];

		if ($app->isClient('site') || $app->isClient('administrator'))
		{
			$mapping['onAfterRender']       = 'onAfterRender';
		}

		return $mapping;
	}

	public function onAfterRender(AfterRenderEvent $event): void
	{
		$app = $this->getApplication();
		if (!$app->isClient('site'))
		{
			return;
		}

		$currentUrl = Uri::getInstance()->toString(['scheme','host','port','path']);

		// Exclude index.php url
		if (str_ends_with($currentUrl, '/index.php'))
		{
			return;
		}

		$date = new \DateTime();
		$pageAnalyticsRepository = new PageAnalyticsRepository();
		$analyticsEntity = $pageAnalyticsRepository->get(0, $currentUrl, $date);
		if(empty($analyticsEntity))
		{
			$analyticsEntity = new PageAnalyticsEntity(0, $date, 1, $currentUrl);
		}
		else
		{
			$analyticsEntity->setCount($analyticsEntity->getCount() + 1);
		}

		$pageAnalyticsRepository->flush($analyticsEntity);
	}
}
