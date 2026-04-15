<?php

namespace Tchooz\Enums\User;

use Joomla\CMS\Language\Text;

enum AuthenticatioAuthenticationModeEnumnModeEnum: string
{
	case DEFAULT = 'default';
	case SSO = 'sso';
	case ACCESS_KEY = 'access_key';

	public function getLabel(): string
	{
		return match($this) {
			AuthenticationModeEnum::DEFAULT => Text::_('COM_EMUNDUS_AUTH_MODE_DEFAULT'),
			AuthenticationModeEnum::SSO => Text::_('COM_EMUNDUS_AUTH_MODE_SSO'),
			AuthenticationModeEnum::ACCESS_KEY =>  Text::_('COM_EMUNDUS_AUTH_MODE_ACCESS_KEY'),
		};
	}

	/**
	 * Map a Joomla authentication response "type" to our internal mode.
	 *
	 * The public access flow is detected via the dedicated type string
	 * injected by plg_system_emunduspublicaccess on onUserLogin, so this
	 * enum does not depend on any plugin class.
	 */
	public static function tryFromJoomlaType(string $type): ?AuthenticationModeEnum
	{
		return match ($type)
		{
			'Oauth2' => self::SSO,
			'access_key' => self::ACCESS_KEY,
			'Joomla' => self::DEFAULT,
		};
	}
}
