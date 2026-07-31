<?php

namespace Tchooz\Enums\Payment;

use Joomla\CMS\Language\Text;

enum PayboxEnvironmentEnum: string
{
	case TEST       = 'TEST';
	case PRODUCTION = 'PRODUCTION';

	public function getEndpoint(): string
	{
		return match ($this)
		{
			self::TEST       => 'https://preprod-tpeweb.paybox.com/cgi/MYchoix_pagepaiement.cgi',
			self::PRODUCTION => 'https://tpeweb.paybox.com/cgi/MYchoix_pagepaiement.cgi',
		};
	}

	public function getLabel(): string
	{
		return match ($this)
		{
			self::TEST       => Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_PAYBOX_SETUP_MODE_TEST'),
			self::PRODUCTION => Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_PAYBOX_SETUP_MODE_PROD'),
		};
	}
}