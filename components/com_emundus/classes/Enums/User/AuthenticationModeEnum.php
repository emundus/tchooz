<?php

namespace Tchooz\Enums\User;

use Joomla\CMS\Language\Text;
use Joomla\Plugin\System\EmundusPublicAccess\Extension\EmundusPublicAccess;

enum AuthenticationModeEnum: string
{
	case DEFAULT = 'default';
	case SSO = 'sso';
	case ACCESS_KEY = 'token';

	public function getLabel(): string
	{
		return match($this) {
			AuthenticationModeEnum::DEFAULT => Text::_('COM_EMUNDUS_AUTH_MODE_DEFAULT'),
			AuthenticationModeEnum::SSO => Text::_('COM_EMUNDUS_AUTH_MODE_SSO'),
			AuthenticationModeEnum::ACCESS_KEY =>  Text::_('COM_EMUNDUS_AUTH_MODE_ACCESS_KEY'),
		};
	}

	public static function tryFromJoomlaType(string $type): ?AuthenticationModeEnum
	{
		$mode = null;

		switch($type)
		{
			case 'Joomla':
			default:
				if (EmundusPublicAccess::isPublicAccessSession())
				{
					$mode = self::ACCESS_KEY;
				}
				else
				{
					$mode = self::DEFAULT;
				}
				break;
		}

		return $mode;
	}
}
