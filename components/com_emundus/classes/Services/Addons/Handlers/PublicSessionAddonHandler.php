<?php

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserHelper;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Entities\Fields\YesnoField;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Services\Addons\AbstractAddonHandler;
use Tchooz\Services\Addons\AddonHandlerInterface;
use Tchooz\Services\PublicAccess\PublicApplicationGuard;

class PublicSessionAddonHandler extends AbstractAddonHandler
{
	public function toggle(bool $state): bool
	{
		$updates = [];
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->clear()
			->select('value')
			->from($db->quoteName('#__emundus_setup_config'))
			->where($db->quoteName('namekey') . ' = ' . $db->quote($this->addon->getNamekey()));

		$db->setQuery($query);
		$config = json_decode($db->loadResult(), true);

		$config['enabled'] = $state;
		$query->clear()
			->update($db->quoteName('#__emundus_setup_config'))
			->set($db->quoteName('value') . ' = ' . $db->quote(json_encode($config)))
			->where($db->quoteName('namekey') . ' = ' . $db->quote($this->addon->getNamekey()));

		$db->setQuery($query);
		$updates[] = $db->execute();

		// toggle menus published state
		$links = ['index.php?option=com_emundus&view=publicaccess&layout=storetoken', 'index.php?option=com_emundus&view=publicaccess'];
		$query->clear()
			->update($db->quoteName('#__menu'))
			->set($db->quoteName('published') . ' = ' . (int) $state)
			->where($db->quoteName('link') . ' IN (' . implode(',', $db->quote($links)) . ')');
		$db->setQuery($query);
		$updates[] = $db->execute();

		return !in_array(false, $updates, true);
	}

	/**
	 * @return array<Field>
	 */
	public function getParameters(): array
	{
		// Dedicated section for the public-application rate-limit windows. Each
		// field advertises the guard's hard bounds as min/max so the form
		// cannot even submit an out-of-range value; the guard re-clamps server
		// side, which stays the single source of truth.
		$rateLimitGroup = new FieldGroup('rate_limit', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_RATE_LIMIT_GROUP_LABEL'));

		return [
			(new NumericField('token_validity_duration', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_DISPLAY_TOKEN_VALIDITY_DURATION_LABEL'), true))->setMin(1)->setMax(365),
			new YesnoField('display_import_public_file_action', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_DISPLAY_IMPORT_FILE_ACTION_LABEL'), true),
			new YesnoField('confirm_public_application_creation', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_CONFIRM_APPLICATION_CREATION_LABEL'), true),
			new YesnoField('display_retrieve_public_access_file_login_page', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_DISPLAY_RETRIEVE_PUBLIC_ACCESS_FILE_LOGIN_PAGE_LABEL'), true),
			(new NumericField('rate_limit_cooldown', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_RATE_LIMIT_COOLDOWN_LABEL'), false, $rateLimitGroup))
				->setMin(PublicApplicationGuard::MIN_RATE_LIMIT_WINDOW)->setMax(PublicApplicationGuard::MAX_RATE_LIMIT_WINDOW),
			(new NumericField('rate_limit_per_minute', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_RATE_LIMIT_PER_MINUTE_LABEL'), false, $rateLimitGroup))
				->setMin(1)->setMax(PublicApplicationGuard::MAX_RATE_LIMIT_GLOBAL_PER_MINUTE),
			(new NumericField('rate_limit_per_hour', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_RATE_LIMIT_PER_HOUR_LABEL'), false, $rateLimitGroup))
				->setMin(1)->setMax(PublicApplicationGuard::MAX_RATE_LIMIT_GLOBAL_PER_HOUR),
			(new NumericField('rate_limit_per_day', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_RATE_LIMIT_PER_DAY_LABEL'), false, $rateLimitGroup))
				->setMin(1)->setMax(PublicApplicationGuard::MAX_RATE_LIMIT_GLOBAL_PER_DAY),
			(new NumericField('rate_limit_per_campaign_per_day', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_RATE_LIMIT_PER_CAMPAIGN_PER_DAY_LABEL'), false, $rateLimitGroup))
				->setMin(1)->setMax(PublicApplicationGuard::MAX_RATE_LIMIT_PER_CAMPAIGN_PER_DAY),
		];
	}

	public function onActivate(): bool
	{
		if (!class_exists('EmundusHelperUpdate'))
		{
			require_once(JPATH_ROOT . '/administrator/components/com_emundus/helpers/update.php');
		}
		\EmundusHelperUpdate::enableEmundusPlugins('emunduspublicaccess', 'system');

		$params = $this->addon->getParams();
		$params['has_been_activated_once'] = 1;
		$this->addon->setParams($params);
		$addonRepository = new AddonRepository();
		$addonRepository->flush($this->addon);

		return $this->toggle(true);
	}

	public function onDeactivate(): bool
	{
		$systemUserId = (int) ComponentHelper::getParams('com_emundus')->get('system_public_user_id', 0);
		if ($systemUserId > 0)
		{
			$destroyed = UserHelper::destroyUserSessions($systemUserId);
		}
		else
		{
			$destroyed = true;
		}

		return $this->toggle(false) && $destroyed;
	}
}