<?php

namespace Tchooz\Services\Integrations\Configurations;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Services\Field\DisplayRule;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;

class WorldlineIntegrationConfiguration extends EmundusIntegrationConfiguration
{
	/**
	 * Credentials are held once per environment so switching the mode does not mean pasting a new
	 * set every time. These prefixes are the single source of truth for that split: the fields are
	 * built from them here, and Worldline::getAuthentication() reads back the set matching the
	 * active mode.
	 */
	public const PRODUCTION_PREFIX = 'production_';

	public const PREPRODUCTION_PREFIX = 'preprod_';

	/**
	 * Credential keys held per environment, without their prefix.
	 */
	public const CREDENTIAL_KEYS = [
		'merchant_id',
		'api_key_id',
		'api_secret',
		'webhook_key_id',
		'webhook_secret',
		'checkout_subdomain',
	];

	public function getParameters(): array
	{
		$authGroup = new FieldGroup('authentication', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_AUTH'));

		$modeField = new BooleanField('mode', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_PRODUCTION_MODE'), false, $authGroup);
		$modeField->setHelpText(Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_PRODUCTION_MODE_HELP'));

		// Displayed so the administrator can paste it into the Worldline Configuration Center.
		// The value itself is built by IntegrationSetup.vue, which knows the site origin and
		// the synchronizer id; it is read-only and never persisted.
		$webhookUrlField = new StringField('webhook_url', Text::_('COM_EMUNDUS_WORLDLINE_SETUP_WEBHOOK_ENDPOINT_LABEL'), false, $authGroup);
		$webhookUrlField->setHelpText(Text::_('COM_EMUNDUS_WORLDLINE_SETUP_WEBHOOK_ENDPOINT_LABEL_HELP'));
		$webhookUrlField->setReadonly(true)->setCopyable(true);

		return array_merge(
			[$modeField],
			$this->getCredentialFields($modeField, self::PREPRODUCTION_PREFIX, 0),
			$this->getCredentialFields($modeField, self::PRODUCTION_PREFIX, 1),
			[$webhookUrlField]
		);
	}

	/**
	 * One environment's credential set. Every field carries the same display rule, so the set
	 * appears and disappears as a whole with the mode toggle.
	 *
	 * The fields are declared optional on purpose. Marking them required would flag the inactive
	 * set too, and would block saving preproduction credentials before the production ones exist.
	 * Missing credentials are caught where it actually matters: Worldline::__construct() refuses to
	 * start without them, for the active environment only.
	 *
	 * @param   Field   $modeField  Toggle the rules are bound to
	 * @param   string  $prefix     Key prefix isolating this environment's values
	 * @param   int     $modeValue  Toggle value this set belongs to
	 *
	 * @return Field[]
	 */
	private function getCredentialFields(Field $modeField, string $prefix, int $modeValue): array
	{
		$group = $modeField->getGroup();
		$rule  = new DisplayRule($modeField, ConditionOperatorEnum::EQUALS, $modeValue);

		$fields = [
			new StringField($prefix . 'merchant_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_MERCHANT_ID'), false, $group),
			new StringField($prefix . 'api_key_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_API_KEY_ID'), false, $group),
			new PasswordField($prefix . 'api_secret', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_API_SECRET'), false, $group),
			// Worldline signs its webhooks with a key pair that is distinct from the API one.
			new StringField($prefix . 'webhook_key_id', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_WEBHOOK_KEY_ID'), false, $group),
			new PasswordField($prefix . 'webhook_secret', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_WEBHOOK_SECRET'), false, $group),
		];

		$subdomain = new StringField($prefix . 'checkout_subdomain', Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_CHECKOUT_SUBDOMAIN'), false, $group);
		$subdomain->setHelpText(Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_WORLDLINE_SETUP_CHECKOUT_SUBDOMAIN_HELP'));

		$fields[] = $subdomain;

		foreach ($fields as $field)
		{
			$field->setDisplayRules([$rule]);
			$field->setPreserveValueWhenHidden(true);
		}

		return $fields;
	}

	public function getDefaultParameters(): array
	{
		return [
			'authentication' => [
				'mode' => 0,
			],
		];
	}
}
