<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs\Checker;

use Emundus\Plugin\Console\Tchooz\Attributes\CheckAttribute;
use Emundus\Plugin\Console\Tchooz\Jobs\TchoozJob;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Emundus\Plugin\Console\Tchooz\Style\EmundusProgressBar;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ReflectionClass;

class CheckHealthJob extends TchoozJob
{
	public function __construct(
		private readonly object          $logger,
		private readonly DatabaseService $databaseServiceSource,
		private readonly DatabaseService $databaseService,
	)
	{
		parent::__construct($logger);

		require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
	}

	public function execute(InputInterface $input, OutputInterface $output): void
	{
		$section1 = $output->section();

		$methods       = $this->getCheckMethods();
		$count_methods = count($methods);

		$progressBar = new EmundusProgressBar($section1, $count_methods);
		$progressBar->start();

		foreach ($methods as $method => $description)
		{
			$progressBar->setMessage('Checking ' . $description);
			if (!$this->$method())
			{
				Log::add('Error while checking ' . $description, Log::ERROR, self::getJobName());
				throw new \RuntimeException('Error while checking ' . $description);
			}
			$progressBar->advance();
		}
		$progressBar->finish('Health check completed');
	}

	private function getCheckMethods(): array
	{
		$reflection = new ReflectionClass(self::class);
		$methods    = $reflection->getMethods();
		$results    = [];

		foreach ($methods as $method)
		{
			$attributes = $method->getAttributes(CheckAttribute::class);

			if (!empty($attributes))
			{
				$attributeInstance           = $attributes[0]->newInstance();
				$results[$method->getName()] = $attributeInstance->description;
			}
		}

		return $results;
	}

	#[CheckAttribute(description: "Check if the user module is correctly configured")]
	private function checkUserModule(): bool
	{
		$query = $this->databaseService->getDatabase()->createQuery();

		$query->select('id,params')
			->from($this->databaseService->getDatabase()->quoteName('#__modules'))
			->where($this->databaseService->getDatabase()->quoteName('module') . ' LIKE ' . $this->databaseService->getDatabase()->quote('mod_emundus_user_dropdown'));
		$this->databaseService->getDatabase()->setQuery($query);
		$modules = $this->databaseService->getDatabase()->loadObjectList();

		if (empty($modules))
		{
			return true;
		}

		$updated = [];
		foreach ($modules as $module)
		{
			$params = json_decode($module->params);

			if (isset($params->layout) && $params->layout !== '_:tchooz')
			{
				$params->layout = '_:tchooz';
				$module->params = json_encode($params);
				if ($this->databaseService->getDatabase()->updateObject('#__modules', $module, 'id'))
				{
					$updated[] = $module;
				}
			}
			else
			{
				$updated[] = $module;
			}
		}

		return count($modules) === count($updated);
	}

	#[CheckAttribute(description: "Check if the application module is correctly configured")]
	private function checkApplicationModule(): bool
	{
		$query = $this->databaseService->getDatabase()->createQuery();

		$query->select('id,params')
			->from($this->databaseService->getDatabase()->quoteName('#__modules'))
			->where($this->databaseService->getDatabase()->quoteName('module') . ' LIKE ' . $this->databaseService->getDatabase()->quote('mod_emundus_applications'));
		$this->databaseService->getDatabase()->setQuery($query);
		$modules = $this->databaseService->getDatabase()->loadObjectList();

		if (empty($modules))
		{
			return true;
		}

		$updated = [];
		foreach ($modules as $module)
		{
			$params = json_decode($module->params);

			if (isset($params->layout) && $params->layout !== '_:tchooz')
			{
				$params->layout = '_:tchooz';
				$module->params = json_encode($params);
				if ($this->databaseService->getDatabase()->updateObject('#__modules', $module, 'id'))
				{
					$updated[] = $module;
				}
			}
			else
			{
				$updated[] = $module;
			}
		}

		return count($modules) === count($updated);
	}

	#[CheckAttribute(description: "Check if the falang module is correctly configured")]
	private function checkFalangModule(): bool
	{
		$query = $this->databaseService->getDatabase()->createQuery();

		$query->select('id,params')
			->from($this->databaseService->getDatabase()->quoteName('#__modules'))
			->where($this->databaseService->getDatabase()->quoteName('module') . ' LIKE ' . $this->databaseService->getDatabase()->quote('mod_falang'));
		$this->databaseService->getDatabase()->setQuery($query);
		$modules = $this->databaseService->getDatabase()->loadObjectList();

		if (empty($modules))
		{
			return true;
		}

		$updated = [];
		foreach ($modules as $module)
		{
			$params = json_decode($module->params);

			if (isset($params->layout) && $params->layout !== '_:emundus')
			{
				$params->layout = '_:emundus';
				$module->params = json_encode($params);
				if ($this->databaseService->getDatabase()->updateObject('#__modules', $module, 'id'))
				{
					$updated[] = $module;
				}
			}
			else
			{
				$updated[] = $module;
			}
		}

		return count($modules) === count($updated);
	}

	#[CheckAttribute(description: "Check if the OAuth module is correctly configured")]
	private function checkOAuth(): bool
	{
		$checked = false;

		$query = $this->databaseServiceSource->getDatabase()->createQuery();

		$query->select('params')
			->from($this->databaseServiceSource->getDatabase()->quoteName('jos_extensions'))
			->where($this->databaseServiceSource->getDatabase()->quoteName('element') . ' LIKE ' . $this->databaseServiceSource->getDatabase()->quote('emundus_oauth2'));
		$this->databaseServiceSource->getDatabase()->setQuery($query);
		$oauthSourceParams = $this->databaseServiceSource->getDatabase()->loadResult();

		if (!empty($oauthSourceParams))
		{
			$oauthSourceParams = json_decode($oauthSourceParams);

			if (!empty($oauthSourceParams->client_id) && !empty($oauthSourceParams->client_secret))
			{
				$query = $this->databaseService->getDatabase()->createQuery();

				$query->select('extension_id,params')
					->from($this->databaseService->getDatabase()->quoteName('jos_extensions'))
					->where($this->databaseService->getDatabase()->quoteName('element') . ' LIKE ' . $this->databaseService->getDatabase()->quote('emundus_oauth2'));
				$this->databaseService->getDatabase()->setQuery($query);
				$oauth = $this->databaseService->getDatabase()->loadObject();

				if (!empty($oauth))
				{
					$buttonLabel = 'MOD_EMUNDUS_OAUTH_BUTTON_LABEL';

					// Get mod_emundus_cas deprecated
					$query = $this->databaseServiceSource->getDatabase()->createQuery();

					$query->select('params')
						->from($this->databaseServiceSource->getDatabase()->quoteName('jos_modules'))
						->where($this->databaseServiceSource->getDatabase()->quoteName('module') . ' LIKE ' . $this->databaseServiceSource->getDatabase()->quote('mod_emundus_cas'));
					$this->databaseServiceSource->getDatabase()->setQuery($query);
					$casSourceParams = $this->databaseServiceSource->getDatabase()->loadResult();
					if (!empty($casSourceParams))
					{
						$casSourceParams = json_decode($casSourceParams);
						$buttonLabel     = $casSourceParams->mod_emundus_cas_btn1;
					}


					$oauthParams = json_decode($oauth->params);

					$oauthParams->configurations                                         = new \stdClass();
					$oauthParams->configurations->configurations0                        = new \stdClass();
					$oauthParams->configurations->configurations0->type                  = 'external';
					$oauthParams->configurations->configurations0->source                = 1;
					$oauthParams->configurations->configurations0->display_on_login      = 1;
					$oauthParams->configurations->configurations0->button_label          = $buttonLabel;
					$oauthParams->configurations->configurations0->button_type           = 'custom';
					$oauthParams->configurations->configurations0->button_icon           = '';
					$oauthParams->configurations->configurations0->well_known_url        = $oauthSourceParams->well_known_url;
					$oauthParams->configurations->configurations0->client_id             = $oauthSourceParams->client_id;
					$oauthParams->configurations->configurations0->client_secret         = $oauthSourceParams->client_secret;
					$oauthParams->configurations->configurations0->scopes                = $oauthSourceParams->scopes;
					$oauthParams->configurations->configurations0->auth_url              = $oauthSourceParams->auth_url;
					$oauthParams->configurations->configurations0->token_url             = $oauthSourceParams->token_url;
					$oauthParams->configurations->configurations0->redirect_url          = $oauthSourceParams->redirect_url;
					$oauthParams->configurations->configurations0->sso_account_url       = $oauthSourceParams->sso_account_url;
					$oauthParams->configurations->configurations0->emundus_profile       = $oauthSourceParams->emundus_profile;
					$oauthParams->configurations->configurations0->email_id              = $oauthSourceParams->email_id;
					$oauthParams->configurations->configurations0->logout_url            = $oauthSourceParams->logout_url;
					$oauthParams->configurations->configurations0->platform_redirect_url = $oauthSourceParams->platform_redirect_url;
					if (!empty($oauthSourceParams->attributes))
					{
						$oauthParams->configurations->configurations0->attributes = new \stdClass();
						$sourceAttributes                                         = json_decode($oauthSourceParams->attributes);
						foreach ($sourceAttributes->table_name as $key => $table_name)
						{
							$index                                                                                 = 'attributes' . $key;
							$oauthParams->configurations->configurations0->attributes->$index                      = new \stdClass();
							$oauthParams->configurations->configurations0->attributes->$index->table_name          = $table_name;
							$oauthParams->configurations->configurations0->attributes->$index->column_name         = $sourceAttributes->column_name[$key];
							$oauthParams->configurations->configurations0->attributes->$index->column_join_user_id = $sourceAttributes->column_join_user_id[$key];
							$oauthParams->configurations->configurations0->attributes->$index->attribute_name      = $sourceAttributes->attribute_name[$key];
						}
					}
					$oauthParams->configurations->configurations0->attribute_mapping = '';
					$oauthParams->configurations->configurations0->mapping           = '';
					$oauthParams->configurations->configurations0->debug_mode        = 0;

					$oauth->params = json_encode($oauthParams);
					if ($checked = $this->databaseService->getDatabase()->updateObject('#__extensions', $oauth, 'extension_id'))
					{
						// Disable mod_emundus_cas
						$query = $this->databaseService->getDatabase()->createQuery();

						$query->update($this->databaseService->getDatabase()->quoteName('#__modules'))
							->set($this->databaseService->getDatabase()->quoteName('published') . ' = 0')
							->where($this->databaseService->getDatabase()->quoteName('module') . ' LIKE ' . $this->databaseService->getDatabase()->quote('mod_emundus_cas'));
						$this->databaseService->getDatabase()->setQuery($query);
						$checked = $this->databaseService->getDatabase()->execute();
					}
				}
			}
			else
			{
				$checked = true;
			}
		}
		else
		{
			$checked = true;
		}

		return $checked;
	}

	#[CheckAttribute(description: "Check if the back button module is correctly configured")]
	private function checkBackButton(): bool
	{
		$query = $this->databaseService->getDatabase()->createQuery();

		$query->select('id,title,note,content,module,params')
			->from($this->databaseService->getDatabase()->quoteName('#__modules'))
			->where($this->databaseService->getDatabase()->quoteName('module') . ' LIKE ' . $this->databaseService->getDatabase()->quote('mod_custom'))
			->where($this->databaseService->getDatabase()->quoteName('title') . ' LIKE ' . $this->databaseService->getDatabase()->quote('%Back Button%'));
		$this->databaseService->getDatabase()->setQuery($query);
		$modules = $this->databaseService->getDatabase()->loadObjectList();

		if (empty($modules))
		{
			return true;
		}

		$updated = [];
		foreach ($modules as $module)
		{
			$module->module  = 'mod_emundus_back';
			$module->title   = '[GUEST] Back Button';
			$module->note    = 'Back button available on login and register views';
			$module->content = '';
			$params          = ['back_type' => 'homepage', 'link' => 1, 'button_text' => 'MOD_EMUNDUS_BACK_BUTTON_LABEL', 'module_tag' => 'div', 'bootstrap_size' => 0, 'header_tag' => 'h3', 'header_class' => '', 'style' => 0];
			$module->params  = json_encode($params);
			if ($this->databaseService->getDatabase()->updateObject('#__modules', $module, 'id'))
			{
				$updated[] = $module;
			}
		}

		return count($modules) === count($updated);
	}

	#[CheckAttribute(description: "Remove deprecated fabrik inline edit plugin")]
	private function checkFabrikInlineEdit(): bool
	{
		$query = $this->databaseService->getDatabase()->createQuery();

		$query->select('id,params')
			->from($this->databaseService->getDatabase()->quoteName('#__fabrik_lists'))
			->where($this->databaseService->getDatabase()->quoteName('params') . ' LIKE ' . $this->databaseService->getDatabase()->quote('%inlineedit%'));
		$this->databaseService->getDatabase()->setQuery($query);
		$lists = $this->databaseService->getDatabase()->loadObjectList();

		$updated = [];
		foreach ($lists as $list)
		{
			$params         = json_decode($list->params, true);
			$inlineedit_key = array_search('inlineedit', array_column($params['plugins'], 'name'));
			// Remove inlineedit plugin
			if ($inlineedit_key !== false)
			{
				unset($params['plugins'][$inlineedit_key]);
				unset($params['plugin_description'][$inlineedit_key]);
				unset($params['plugin_state'][$inlineedit_key]);
				unset($params['inline_access']);
				unset($params['inline_editable_elements']);
				unset($params['inline_edit_event']);
				unset($params['inline_tab_save']);
				unset($params['inline_show_cancel']);
				unset($params['inline_show_save']);
			}

			$list->params = json_encode($params);
			if ($this->databaseService->getDatabase()->updateObject('#__fabrik_lists', $list, 'id'))
			{
				$updated[] = $list;
			}
		}

		return count($lists) === count($updated);
	}

	#[CheckAttribute(description: "Check if activation menu item is correctly configured")]
	private function checkActivationMenuItem(): bool
	{
		$query = $this->databaseService->getDatabase()->createQuery();

		$query->select('id,link,type,component_id,params')
			->from($this->databaseService->getDatabase()->quoteName('#__menu'))
			->where($this->databaseService->getDatabase()->quoteName('link') . ' LIKE ' . $this->databaseService->getDatabase()->quote('index.php?option=com_users&task=edit'))
			->where($this->databaseService->getDatabase()->quoteName('menutype') . ' LIKE ' . $this->databaseService->getDatabase()->quote('mainmenu'));
		$this->databaseService->getDatabase()->setQuery($query);
		$activation_menu = $this->databaseService->getDatabase()->loadObject();

		if (empty($activation_menu->id))
		{
			$datas = [
				'menutype'     => 'mainmenu',
				'title'        => 'Activation',
				'alias'        => 'activation',
				'path'         => 'activation',
				'link'         => 'index.php?option=com_emundus&view=user',
				'type'         => 'component',
				'component_id' => ComponentHelper::getComponent('com_emundus')->id,
				'params'       => [
					'menu-anchor_title' => 'activation-page',
					'menu-anchor_css'   => 'activation-page',
					'menu_text'         => 1,
					'menu_show'         => 1,
				]
			];

			return \EmundusHelperUpdate::addJoomlaMenu($datas)['status'];
		}
		else
		{
			$activation_menu->link         = 'index.php?option=com_emundus&view=user';
			$activation_menu->component_id = ComponentHelper::getComponent('com_emundus')->id;
			$activation_menu->type         = 'component';
			$params                        = json_decode($activation_menu->params, true);
			$params['menu_text']           = 1;
			$params['menu_show']           = 1;
			$params['menu-anchor_title']   = 'activation-page';
			$params['menu-anchor_css']     = 'activation-page';

			$activation_menu->params = json_encode($params);

			return $this->databaseService->getDatabase()->updateObject('#__menu', $activation_menu, 'id');
		}
	}

	#[CheckAttribute(description: "Check if paybox files are present and correctly configured if we have a paybox payment method")]
	private function checkPayboxFiles(): bool
	{
		$query = $this->databaseService->getDatabase()->createQuery();

		$query->select('payment_id')
			->from($this->databaseService->getDatabase()->quoteName('#__hikashop_payment'))
			->where($this->databaseService->getDatabase()->quoteName('payment_type') . ' = ' . $this->databaseService->getDatabase()->quote('paybox'));
		$this->databaseService->getDatabase()->setQuery($query);
		$paybox_payments = $this->databaseService->getDatabase()->loadColumn();

		$checked = [];
		foreach ($paybox_payments as $payboxPayment)
		{
			$payboxFile = JPATH_SITE . '/paybox_' . $payboxPayment . '.php';

			if (file_exists($payboxFile))
			{
				$checked[] = $payboxPayment;
			}
			else
			{
				// Create the file
				$payboxFileContent = '<?php
$_GET[\'option\']=\'com_hikashop\';
$_GET[\'tmpl\']=\'component\';
$_GET[\'ctrl\']=\'checkout\';
$_GET[\'task\']=\'notify\';
$_GET[\'notif_payment\']=\'paybox\';
$_GET[\'format\']=\'html\';
$_GET[\'lang\']=\'fr\';
$_GET[\'notif_id\']=\'' . $payboxPayment . '\';
$_REQUEST[\'option\']=\'com_hikashop\';
$_REQUEST[\'tmpl\']=\'component\';
$_REQUEST[\'ctrl\']=\'checkout\';
$_REQUEST[\'task\']=\'notify\';
$_REQUEST[\'notif_payment\']=\'paybox\';
$_REQUEST[\'format\']=\'html\';
$_REQUEST[\'lang\']=\'fr\';
$_REQUEST[\'notif_id\']=\'' . $payboxPayment . '\';
include(\'index.php\');
?>';
				if (file_put_contents($payboxFile, $payboxFileContent))
				{
					$checked[] = $payboxPayment;
					break;
				}
			}
		}

		return count($paybox_payments) === count($checked);
	}

	#[CheckAttribute(description: "Check if registration form is correctly configured")]
	private function checkRegistrationForm(): bool
	{
		$query = $this->databaseService->getDatabase()->createQuery();

		$checked = \EmundusHelperUpdate::disableEmundusPlugins('emundus_period', 'system');

		// Check campaign element
		$query->select('fe.id,fe.params')
			->from($this->databaseService->getDatabase()->quoteName('#__fabrik_elements', 'fe'))
			->leftJoin($this->databaseService->getDatabase()->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->databaseService->getDatabase()->quoteName('ffg.group_id') . ' = ' . $this->databaseService->getDatabase()->quoteName('fe.group_id'))
			->leftJoin($this->databaseService->getDatabase()->quoteName('#__fabrik_forms', 'ff') . ' ON ' . $this->databaseService->getDatabase()->quoteName('ff.id') . ' = ' . $this->databaseService->getDatabase()->quoteName('ffg.form_id'))
			->where($this->databaseService->getDatabase()->quoteName('ff.label') . ' = ' . $this->databaseService->getDatabase()->quote('FORM_REGISTRATION'))
			->where($this->databaseService->getDatabase()->quoteName('fe.name') . ' = ' . $this->databaseService->getDatabase()->quote('campaign_id'));
		$this->databaseService->getDatabase()->setQuery($query);
		$campaign_id_element = $this->databaseService->getDatabase()->loadObject();

		if (!empty($campaign_id_element->id))
		{
			$params                = json_decode($campaign_id_element->params, true);
			$params['validations'] = [];
			unset($params['notempty-message']);
			unset($params['notempty-validation_condition']);
			unset($params['tip_text']);
			unset($params['icon']);
			$campaign_id_element->params = json_encode($params);
			$checked                     = $this->databaseService->getDatabase()->updateObject('#__fabrik_elements', $campaign_id_element, 'id');
		}
		//

		// Check password element
		$query->clear()
			->select('fe.id,fe.params')
			->from($this->databaseService->getDatabase()->quoteName('#__fabrik_elements', 'fe'))
			->leftJoin($this->databaseService->getDatabase()->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->databaseService->getDatabase()->quoteName('ffg.group_id') . ' = ' . $this->databaseService->getDatabase()->quoteName('fe.group_id'))
			->leftJoin($this->databaseService->getDatabase()->quoteName('#__fabrik_forms', 'ff') . ' ON ' . $this->databaseService->getDatabase()->quoteName('ff.id') . ' = ' . $this->databaseService->getDatabase()->quoteName('ffg.form_id'))
			->where($this->databaseService->getDatabase()->quoteName('ff.label') . ' = ' . $this->databaseService->getDatabase()->quote('FORM_REGISTRATION'))
			->where($this->databaseService->getDatabase()->quoteName('fe.name') . ' = ' . $this->databaseService->getDatabase()->quote('password'));
		$this->databaseService->getDatabase()->setQuery($query);
		$password_element = $this->databaseService->getDatabase()->loadObject();

		if (!empty($password_element->id))
		{
			$params                   = json_decode($password_element->params, true);
			$params['rollover']       = 'require_once(JPATH_SITE.\'/components/com_emundus/helpers/fabrik.php\');return EmundusHelperFabrik::displayPasswordTip();';
			$password_element->params = json_encode($params);
			$checked                  = $this->databaseService->getDatabase()->updateObject('#__fabrik_elements', $password_element, 'id');

			\EmundusHelperUpdate::insertTranslationsTag('USER_PASSWORD_TIP', 'Minimum %d caractères, %d chiffre(s), %d symbole(s), %d lettre(s) majuscule et %d lettre(s) minuscule');
			\EmundusHelperUpdate::insertTranslationsTag('USER_PASSWORD_TIP', 'Minimum %d characters, %d digit(s), %d symbol(s), %d upper case letter(s) and %d lower case letter(s)', 'override', null, null, null, 'en-GB');
		}
		//

		// Check password rules
		$params = ComponentHelper::getParams('com_users');

		if ($params->get('minimum_length') < 12)
		{
			\EmundusHelperUpdate::updateComponentParameter('com_users', 'minimum_length', 12);
		}
		if ($params->get('minimum_integers') < 1)
		{
			\EmundusHelperUpdate::updateComponentParameter('com_users', 'minimum_integers', 1);
		}
		if ($params->get('minimum_symbols') < 1)
		{
			\EmundusHelperUpdate::updateComponentParameter('com_users', 'minimum_symbols', 1);
		}
		if ($params->get('minimum_uppercase') < 1)
		{
			\EmundusHelperUpdate::updateComponentParameter('com_users', 'minimum_uppercase', 1);
		}
		if ($params->get('minimum_lowercase') < 1)
		{
			\EmundusHelperUpdate::updateComponentParameter('com_users', 'minimum_lowercase', 1);
		}

		//

		return $checked;
	}

	#[CheckAttribute(description: "Replace some deprecated profile colors")]
	private function checkProfileColors(): bool
	{
		$query = $this->databaseService->getDatabase()->createQuery();

		$query->select('id,class')
			->from($this->databaseService->getDatabase()->quoteName('#__emundus_setup_profiles'));
		$this->databaseService->getDatabase()->setQuery($query);
		$profiles = $this->databaseService->getDatabase()->loadObjectList();

		$updated = [];
		foreach ($profiles as $profile)
		{
			if (is_null($profile->class))
			{
				$profile->class = 'label-green-2';
			}
			if (!in_array($profile->class, ['label-red-1', 'label-red-2', 'label-pink-2', 'label-purple-2', 'label-light-blue-2', 'label-blue-2', 'label-blue-3', 'label-red-1', 'label-green-2', 'label-orange-2', 'label-brown', 'label-grey-2', 'label-black']))
			{
				$profile->class = 'label-green-2';
			}

			if ($this->databaseService->getDatabase()->updateObject('#__emundus_setup_profiles', $profile, 'id'))
			{
				$updated[] = $profile;
			}
		}

		return count($profiles) === count($updated);
	}

	#[CheckAttribute(description: "Check if we have the emundus_filters position in Gantry template")]
	private function checkFilterPosition(): bool
	{
		$checked = false;

		$xml_file = JPATH_SITE . '/templates/g5_helium/templateDetails.xml';
		$xml      = simplexml_load_file($xml_file);
		if ($xml)
		{
			$positions = $xml->xpath('//extension/positions');

			// Check if position emundus_filters exist
			$checked = false;
			foreach ($positions[0]->children() as $position)
			{
				if ($position == 'emundus_filters')
				{
					$checked = true;
				}
			}
			if (!$checked)
			{
				$positions[0]->addChild('position', 'emundus_filters');
				$checked = true;
			}

			$xml->asXML($xml_file);
		}

		return $checked;
	}

	#[CheckAttribute(description: "Check if emundus registration redirect plugin is enabled")]
	private function checkEmundusRegistrationRedirect(): bool
	{
		$installed = \EmundusHelperUpdate::installExtension('Emundus Registration Redirect Plugin', 'emundusregistrationredirect', '{"name":"Emundus Registration Redirect Plugin","type":"plugin","creationDate":"16 May 2018","author":"eMundus","copyright":"(C) 2010-2018 EMUNDUS SOFTWARE. All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"http:\/\/www.emundus.fr","version":"6.6.3","description":"This plugin enables you to handle redirect to a custom registration page","group":"","filename":"emundusregistrationredirect"}', 'plugin', 1, 'system', '{"url_to_registration":"PLG_EMUNDUS_REGISTRATION_REDIRECT_URL","item_id":""}');
		if ($installed)
		{
			\EmundusHelperUpdate::enableEmundusPlugins('emundusregistrationredirect', 'system');
		}

		return $installed;
	}

	#[CheckAttribute(description: "Check if emundus authentication plugin is enabled")]
	private function checkEmundusAuthenticationPlugin(): bool
	{
		$installed = \EmundusHelperUpdate::installExtension('Authentication - eMundus', 'emundus', '{"name":"Authentication - eMundus","type":"plugin","creationDate":"March 2023","author":"J\u00e9r\u00e9my LEGENDRE","copyright":"(C) 2023 eMundus All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"emundus.fr","version":"1.0.0","description":"PLG_AUTHENTICATION_EMUNDUS_XML_DESCRIPTION","group":"","filename":"emundus"}', 'plugin', 1, 'authentication');
		if ($installed)
		{
			\EmundusHelperUpdate::enableEmundusPlugins('emundus', 'authentication');
		}

		return $installed;
	}

	#[CheckAttribute(description: "Rebuild fnum elements now initialized by forms plugins")]
	private function rebuildFnumElements(): bool
	{
		$query = $this->databaseService->getDatabase()->createQuery();

		$query->select(['id', $this->databaseService->getDatabase()->quoteName('default'), 'params'])
			->from($this->databaseService->getDatabase()->quoteName('#__fabrik_elements'))
			->where($this->databaseService->getDatabase()->quoteName('name') . ' = ' . $this->databaseService->getDatabase()->quote('fnum'));
		$this->databaseService->getDatabase()->setQuery($query);
		$elements = $this->databaseService->getDatabase()->loadObjectList();

		$updated = [];
		foreach ($elements as $element)
		{
			$params = json_decode($element->params, true);
			$params['text_format'] = 'text';
			$params['integer_length'] = '28';
			$element->params = json_encode($params);

			if(str_contains($element->default, '->input->get(\'rowid\')'))
			{
				$element->default = '';
			}

			if ($this->databaseService->getDatabase()->updateObject('#__fabrik_elements', $element, 'id'))
			{
				$updated[] = $element;
			}
		}

		return count($elements) === count($updated);
	}

	#[CheckAttribute(description: "Replace old plugins by emundustriggers")]
	private function checkTriggersFormPlugins(): bool
	{
		$query = $this->databaseService->getDatabase()->createQuery();

		$query->select('id,params')
			->from($this->databaseService->getDatabase()->quoteName('#__fabrik_forms'))
			->where($this->databaseService->getDatabase()->quoteName('params') . ' LIKE ' . $this->databaseService->getDatabase()->quote('%emundusisapplicationsent%'))
			->orWhere($this->databaseService->getDatabase()->quoteName('params') . ' LIKE ' . $this->databaseService->getDatabase()->quote('%emundus-isApplicationSent.php%'));
		$this->databaseService->getDatabase()->setQuery($query);
		$forms = $this->databaseService->getDatabase()->loadObjectList();

		$updated = [];
		foreach ($forms as $form)
		{
			$params = json_decode($form->params, true);

			// Replace emundusisapplicationsent by emundustriggers
			$emundusisapplicationsent_key = array_search('emundusisapplicationsent', $params['plugins']);
			if ($emundusisapplicationsent_key !== false)
			{
				$params['plugins'][$emundusisapplicationsent_key]            = 'emundustriggers';
				$params['plugin_description'][$emundusisapplicationsent_key] = 'emundus_events';
				unset($params['applicationsent_status']);
			}

			if(!empty($params['plugin_description']))
			{
				// Remove redirect plugin
				$redirect_key = array_search('redirect', $params['plugin_description']);
				if ($redirect_key !== false)
				{
					unset($params['plugins'][$redirect_key]);
					unset($params['plugin_locations'][$redirect_key]);
					unset($params['plugin_events'][$redirect_key]);
					unset($params['plugin_description'][$redirect_key]);
					unset($params['plugin_state'][$redirect_key]);
					unset($params['redirect_url']);
				}

				// Search SweetAlert2 in form_php_file
				$sweetPhp = array_search('Sweet', $params['plugin_description']);
				if ($sweetPhp !== false)
				{
					unset($params['plugins'][$sweetPhp]);
					unset($params['plugin_description'][$sweetPhp]);
					unset($params['plugin_locations'][$sweetPhp]);
					unset($params['plugin_events'][$sweetPhp]);
					unset($params['plugin_state'][$sweetPhp]);
					unset($params['form_php_file'][$sweetPhp]);
					unset($params['curl_code'][$sweetPhp]);
					unset($params['only_process_curl'][$sweetPhp]);
					unset($params['form_php_require_once'][$sweetPhp]);
				}
			}

			if(!empty($params['form_php_file']))
			{
				// Search emundus-isApplicationSent.php in form_php_file
				$isApplicationSent = array_search('emundus-isApplicationSent.php', $params['form_php_file']);
				if ($isApplicationSent !== false)
				{
					unset($params['form_php_file'][$isApplicationSent]);
					unset($params['curl_code'][$isApplicationSent]);
					unset($params['only_process_curl'][$isApplicationSent]);
					unset($params['form_php_require_once'][$isApplicationSent]);
					$params['plugins'][$isApplicationSent]            = 'emundustriggers';
					$params['plugin_description'][$isApplicationSent] = 'emundus_events';
				}

				// Search emundus-redirect.php in form_php_file
				$redirectPhp = array_search('emundus-redirect.php', $params['form_php_file']);
				if ($redirectPhp !== false)
				{
					unset($params['plugins'][$redirectPhp]);
					unset($params['plugin_description'][$redirectPhp]);
					unset($params['plugin_locations'][$redirectPhp]);
					unset($params['plugin_events'][$redirectPhp]);
					unset($params['plugin_state'][$redirectPhp]);
					unset($params['form_php_file'][$redirectPhp]);
					unset($params['curl_code'][$redirectPhp]);
					unset($params['only_process_curl'][$redirectPhp]);
					unset($params['form_php_require_once'][$redirectPhp]);
				}
			}

			$form->params = json_encode($params);
			if ($this->databaseService->getDatabase()->updateObject('#__fabrik_forms', $form, 'id'))
			{
				$updated[] = $form;
			}
		}

		return count($forms) === count($updated);
	}

	#[CheckAttribute(description: "Rebuild admin menu")]
	private function rebuildAdminProfile(): bool
	{
		$builded = false;

		require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
		$adminMenuFile = JPATH_SITE . '/plugins/console/tchooz_cli/src/Datas/Menus/adminmenu.json';
		$adminMenuFile = file_get_contents($adminMenuFile);
		$adminMenus    = json_decode($adminMenuFile, true);

		if (!empty($adminMenus))
		{
			$query = $this->databaseService->getDatabase()->createQuery();
			$query->delete($this->databaseService->getDatabase()->quoteName('#__menu'))
				->where($this->databaseService->getDatabase()->quoteName('menutype') . ' LIKE ' . $this->databaseService->getDatabase()->quote('adminmenu'));
			$this->databaseService->getDatabase()->setQuery($query);

			if ($this->databaseService->getDatabase()->execute())
			{
				$inserted = [];
				foreach ($adminMenus as $adminMenu)
				{
					if (is_string($adminMenu["component_id"]))
					{
						$component_id              = $adminMenu["component_id"];
						$adminMenu["component_id"] = ComponentHelper::getComponent($component_id)->id;
					}

					if (is_string($adminMenu['parent_id']))
					{
						$parent_id = $adminMenu['parent_id'];

						$query->clear()
							->select('id')
							->from($this->databaseService->getDatabase()->quoteName('#__menu'))
							->where($this->databaseService->getDatabase()->quoteName('menutype') . ' LIKE ' . $this->databaseService->getDatabase()->quote('adminmenu'))
							->where($this->databaseService->getDatabase()->quoteName('alias') . ' LIKE ' . $this->databaseService->getDatabase()->quote($parent_id));
						$this->databaseService->getDatabase()->setQuery($query);
						$parent = $this->databaseService->getDatabase()->loadResult();

						if (empty($parent))
						{
							continue;
						}

						$adminMenu['parent_id'] = $parent;
					}

					$menu = \EmundusHelperUpdate::addJoomlaMenu($adminMenu, $adminMenu['parent_id']);
					if ($menu['status'])
					{
						$params = json_decode($adminMenu['params']);
						if (!empty($params->em_use_module_for_filters) && $params->em_use_module_for_filters == 1)
						{
							// Associate mod_emundus_filters to the menu
							$query->clear()
								->select('id')
								->from($this->databaseService->getDatabase()->quoteName('#__modules'))
								->where($this->databaseService->getDatabase()->quoteName('module') . ' LIKE ' . $this->databaseService->getDatabase()->quote('mod_emundus_filters'));
							$this->databaseService->getDatabase()->setQuery($query);
							$moduleid = $this->databaseService->getDatabase()->loadResult();

							if (empty($moduleid))
							{
								// Create mod_emundus_filters
								$datas    = [
									'title'    => 'Filtres avancés',
									'note'     => 'Advanced filters for files, evaluations views',
									'content'  => '',
									'position' => 'emundus_filters',
									'module'   => 'mod_emundus_filters',
									'access'   => 1,
									'params'   => [
										'filter_on_fnums'  => 1,
										'element_id'       => 0,
										'filter_status'    => 1,
										'filter_campaign'  => 1,
										'filter_programs'  => 1,
										'filter_years'     => 1,
										'filter_tags'      => 1,
										'filter_published' => 1,
										'layout'           => '_:vue',
										'module_tag'       => 'div',
										'bootstrap_size'   => 0,
										'header_tag'       => 'h3',
										'header_class'     => '',
										'style'            => 0,
									]
								];
								$moduleid = \EmundusHelperUpdate::addJoomlaModule($datas);
							}

							$menuModule = [
								'moduleid' => $moduleid,
								'menuid'   => $menu['id'],
							];
							$menuModule = (object) $menuModule;
							$this->databaseService->getDatabase()->insertObject('#__modules_menu', $menuModule);
						}

						$inserted[] = $adminMenu;
					}
				}

				$builded = count($adminMenus) === count($inserted);
			}
		}
		else
		{
			$builded = true;
		}

		return $builded;
	}

	#[CheckAttribute(description: "Check auto increment in primary key")]
	private function checkAutoIncrement(): bool
	{
		// Check if all tables have auto increment in primary key
		$query = $this->databaseService->getDatabase()->createQuery();

		$query->select('TABLE_NAME, COLUMN_NAME')
			->from('information_schema.COLUMNS')
			->where('TABLE_SCHEMA = ' . $this->databaseService->getDatabase()->quote($this->databaseService->getDbName()))
			->where('EXTRA NOT LIKE ' . $this->databaseService->getDatabase()->quote('%auto_increment%'))
			->where('COLUMN_KEY = ' . $this->databaseService->getDatabase()->quote('PRI'))
			->where('COLUMN_NAME = ' . $this->databaseService->getDatabase()->quote('id'))
			->where('TABLE_NAME LIKE ' . $this->databaseService->getDatabase()->quote('%jos_emundus%'));
		$this->databaseService->getDatabase()->setQuery($query);
		$columns = $this->databaseService->getDatabase()->loadObjectList();
		
		$updated = [];
		foreach ($columns as $column)
		{
			$query->clear()
				->select('AUTO_INCREMENT')
				->from('information_schema.TABLES')
				->where('TABLE_SCHEMA = ' . $this->databaseService->getDatabase()->quote($this->databaseService->getDbName()))
				->where('TABLE_NAME = ' . $this->databaseService->getDatabase()->quote($column->TABLE_NAME));
			$this->databaseService->getDatabase()->setQuery($query);
			$autoIncrement = $this->databaseService->getDatabase()->loadResult();

			if (empty($autoIncrement))
			{
				$this->databaseService->getDatabase()->setQuery('ALTER TABLE ' . $this->databaseService->getDatabase()->quoteName($column->TABLE_NAME) . ' AUTO_INCREMENT = 1;')->execute();
			}

			$updated[] = $this->databaseService->getDatabase()->setQuery('ALTER TABLE ' . $this->databaseService->getDatabase()->quoteName($column->TABLE_NAME) . ' MODIFY ' . $this->databaseService->getDatabase()->quoteName($column->COLUMN_NAME) . ' INT(11) NOT NULL AUTO_INCREMENT;')->execute();
		}

		return count($columns) === count($updated);
	}

	#[CheckAttribute(description: "Replace emundus_fileupload_new by emundus_fileupload")]
	private function checkEmundusFileuploadNew(): bool
	{
		$query = $this->databaseService->getDatabase()->createQuery();

		$query->update($this->databaseService->getDatabase()->quoteName('#__fabrik_elements'))
			->set($this->databaseService->getDatabase()->quoteName('plugin') . ' = ' . $this->databaseService->getDatabase()->quote('emundus_fileupload'))
			->where($this->databaseService->getDatabase()->quoteName('plugin') . ' = ' . $this->databaseService->getDatabase()->quote('emundus_fileupload_new'));
		$this->databaseService->getDatabase()->setQuery($query);
		return $this->databaseService->getDatabase()->execute();
	}

	#[CheckAttribute(description: "Set a default value for status field in form 102 if not set")]
	private function checkStatusFieldNewApplication(): bool
	{
		$query = $this->databaseService->getDatabase()->createQuery();

		$query->update($this->databaseService->getDatabase()->quoteName('#__fabrik_elements','fe'))
			->set($this->databaseService->getDatabase()->quoteName('fe.default') . ' = 0')
			->leftJoin($this->databaseService->getDatabase()->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->databaseService->getDatabase()->quoteName('ffg.group_id') . ' = ' . $this->databaseService->getDatabase()->quoteName('fe.group_id'))
			->leftJoin($this->databaseService->getDatabase()->quoteName('#__fabrik_forms', 'ff') . ' ON ' . $this->databaseService->getDatabase()->quoteName('ff.id') . ' = ' . $this->databaseService->getDatabase()->quoteName('ffg.form_id'))
			->where($this->databaseService->getDatabase()->quoteName('fe.name') . ' = ' . $this->databaseService->getDatabase()->quote('status'))
			->where($this->databaseService->getDatabase()->quoteName('ff.label') . ' = ' . $this->databaseService->getDatabase()->quote('SETUP_FILL_A_NEW_APPLICATION_FORM'));
		$this->databaseService->getDatabase()->setQuery($query);
		return $this->databaseService->getDatabase()->execute();
	}

	#[CheckAttribute(description: "Replace old Swal version by new one in G5 template")]
	private function replaceSwalVersion(): bool
	{
		$replaced = true;

		$gantry_assets = JPATH_ROOT . '/templates/g5_helium/custom/config/default/page/assets.yaml';
		if(file_exists($gantry_assets))
		{
			$yaml_content = file_get_contents($gantry_assets);
			$yaml_content = str_replace('https://cdn.jsdelivr.net/npm/sweetalert2@8', 'media/com_emundus/js/lib/sweetalert/sweetalert.min.js', $yaml_content);
			$replaced = !empty(file_put_contents($gantry_assets, $yaml_content));
		}

		return $replaced;
	}

	#[CheckAttribute(description: "Rebuild menu filters for files and evaluations")]
	private function rebuildMenuFilters()
	{
		$rebuilded = true;

		$query = $this->databaseService->getDatabase()->createQuery();

		// Get all files and evaluations menu items
		$query->select('id,params')
			->from($this->databaseService->getDatabase()->quoteName('#__menu'))
			->where($this->databaseService->getDatabase()->quoteName('link') . ' LIKE ' . $this->databaseService->getDatabase()->quote('index.php?option=com_emundus&view=files'))
			->orWhere($this->databaseService->getDatabase()->quoteName('link') . ' LIKE ' . $this->databaseService->getDatabase()->quote('index.php?option=com_emundus&view=evaluation'));
		$this->databaseService->getDatabase()->setQuery($query);
		$menu_items = $this->databaseService->getDatabase()->loadObjectList();

		foreach ($menu_items as $menu_item)
		{
			$params = json_decode($menu_item->params, true);

			if(!empty($params['em_filters_names']))
			{
				$filters_to_keep = explode(',', $params['em_filters_names']);

				foreach ($filters_to_keep as $filter)
				{
					if($filter === 'tag' && $params['filter_tags'] == 0)
					{
						$params['filter_tags'] = 1;
					}

					if($filter === 'published' && $params['filter_published'] == 0)
					{
						$params['filter_published'] = 1;
					}

					if($filter === 'schoolyear' && $params['filter_years'] == 0)
					{
						$params['filter_years'] = 1;
					}

					if($filter === 'programme' && $params['filter_programs'] == 0)
					{
						$params['filter_programs'] = 1;
					}

					if($filter === 'campaign' && $params['filter_campaign'] == 0)
					{
						$params['filter_campaign'] = 1;
					}

					if($filter === 'status' && $params['filter_status'] == 0)
					{
						$params['filter_status'] = 1;
					}

					if($filter === 'adv_filter' && $params['allow_add_filter'] == 0)
					{
						$params['allow_add_filter'] = 1;
					}

					$menu_item->params = json_encode($params);
					if (!$this->databaseService->getDatabase()->updateObject('#__menu', $menu_item, 'id'))
					{
						$rebuilded = false;
					}
				}
			}
		}

		return $rebuilded;
	}

	#[CheckAttribute(description: "Check if menus are correctly configured")]
	private function checkMenu()
	{
		$checks = [];
		$db = $this->databaseService->getDatabase();
		$query = $db->createQuery();

		// check that the submenu in application menu for 12|r (profile edition) is using the correct link
		$query->clear()
			->select('id, link')
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('menutype') . ' = ' . $db->quote('application'))
			->andWhere($db->quoteName('note') . ' = ' . $db->quote('12|r'));

		$db->setQuery($query);
		$menu = $db->loadObject();

		if ($menu->link !== 'index.php?option=com_emundus&view=application&format=raw&layout=account') {
			$menu->link = 'index.php?option=com_emundus&view=application&format=raw&layout=account';

			$query->clear()
				->update($db->quoteName('#__menu'))
				->set($db->quoteName('link') . ' = ' . $db->quote($menu->link))
				->where($db->quoteName('id') . ' = ' . $db->quote($menu->id));

			$db->setQuery($query);
			$checks[] = $db->execute();
		}

		return !in_array(false, $checks);
	}

	public static function getJobName(): string
	{
		return 'Health';
	}

	public static function getJobDescription(): ?string
	{
		return 'Check health of the project after migration';
	}
}