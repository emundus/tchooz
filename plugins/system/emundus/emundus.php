<?php
/**
 * @package         eMundus.Plugin
 * @subpackage      System.emundus
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

use Gantry\Framework\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Event\GenericEvent;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla! eMundus system
 *
 * @package     Joomla.Plugin
 * @subpackage  System
 * @since       3.0
 */
class PlgSystemEmundus extends CMSPlugin
{
	private $label_colors = [
        'lightpurple' => '--em-purple-2',
        'purple'=> '--em-purple-2',
        'darkpurple'=> '--em-purple-2',
        'lightblue'=> '--em-light-blue-2',
        'blue'=> '--em-blue-2',
        'darkblue'=> '--em-blue-3',
        'lightgreen'=> '--em-green-2',
        'green'=> '--em-green-2',
        'darkgreen'=> '--em-green-2',
        'lightyellow'=> '--em-yellow-2',
        'yellow'=> '--em-yellow-2',
        'darkyellow'=> '--em-yellow-2',
        'lightorange'=> '--em-orange-2',
        'orange'=> '--em-orange-2',
        'darkorange'=> '--em-orange-2',
        'lightred'=> '--em-red-1',
        'red'=> '--em-red-2',
        'darkred'=> '--em-red-2',
        'pink'=> '--em-pink-2',
        'default'=> '--neutral-600',
    ];
	/**
	 * Constructor
	 *
	 * @param   object &$subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @since    1.0
	 */
	public function __construct(&$subject, $config)
	{
		// Could be component was uninstalled but not the plugin
		if (!File::exists(JPATH_SITE . '/components/com_emundus/emundus.php'))
		{
			return;
		}
	}

	public function onBeforeCompileHead()
	{
		if (version_compare(JVERSION, '3.7', '<'))
		{
			return;
		}

		$app = Factory::getApplication();
		if ($app->isClient('administrator'))
		{
			if (empty($_REQUEST['option']) || $_REQUEST['option'] != 'com_emundus')
				return;
		}

		$doc  = $app->getDocument();
		$head = $doc->getHeadData();
		$wa   = $doc->getWebAssetManager();

		$e_session = $app->getSession()->get('emundusUser');

		$profile_details = null;
		if (!$app->getIdentity()->guest)
		{
			if (!empty($e_session->profile))
			{
				require_once JPATH_ROOT . '/components/com_emundus/models/users.php';
				$m_users = $app->bootComponent('com_emundus')->getMVCFactory()->createModel('Users', 'EmundusModel');

				$profile_details = $m_users->getProfileDetails($e_session->profile);

				if(strpos($profile_details->class, 'label-') !== false)
				{
					$profile_details->class = str_replace('label-','--em-',$profile_details->class);
				}
				elseif(!empty($this->label_colors[$profile_details->class])) {
					$profile_details->class = $this->label_colors[$profile_details->class];
				}

				$profile_font = $profile_details->published !== 1 ? '--em-coordinator-font' : '--em-applicant-font';
				$profile_font_title = $profile_details->published !== 1 ? '--em-coordinator-font-title' : '--em-applicant-font-title';

				$style = ':root {
					--em-profile-color: var(' . $profile_details->class . ');
					--em-profile-font: var(' . $profile_font . ');
					--em-profile-font-title: var(' . $profile_font_title . ');
				}';

				$wa->addInlineStyle($style);
			}
		}

		$doc->setHeadData($head);
	}

	/**
	 * Insert classes into the body tag depending on user profile
	 *
	 * @return  void
	 */
	public function onAfterRender()
	{
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		if ($app->isClient('site'))
		{
			// If samlredirect plugin is active and we're coming from saml login page we can try to update user informations
			$userParams = (!empty($user->params) && json_validate($user->params)) ? json_decode($user->params) : [];
			if(!$user->guest && PluginHelper::isEnabled('system', 'samlredirect') && !empty($userParams) && $userParams->saml == 1)
			{
				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->getQuery(true);

				$query->select('single_signon_service_url')
					->from($db->quoteName('#__miniorange_saml_config'));
				$db->setQuery($query);
				$singleSignOnServiceUrl = $db->loadResult();

				if(!empty($singleSignOnServiceUrl))
				{
					$parsedUrl = parse_url($singleSignOnServiceUrl);
					$httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

					if(!empty($httpReferer) && $httpReferer == ($parsedUrl['scheme'].'://'.$parsedUrl['host'].'/'))
					{
						$query->select('profile_key,profile_value')
							->from($db->quoteName('#__user_profiles'))
							->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));
						$db->setQuery($query);
						$profileDatas = $db->loadAssocList('profile_key', 'profile_value');

						foreach ($profileDatas as $profileKey => $profileValue)
						{
							$profileKeyParts = explode('.', $profileKey);
							if(!empty($profileKeyParts[1]) && !empty($profileKeyParts[2]))
							{
								$query = 'SHOW COLUMNS FROM ' . $db->quoteName('#__'.$profileKeyParts[1]) . ' LIKE ' . $db->quote($profileKeyParts[2]);
								$db->setQuery($query);
								$columnExists = $db->loadResult();

								if(!empty($columnExists))
								{
									// Update the user profile field in the users table
									$query = $db->getQuery(true);
									$query->update($db->quoteName('#__'.$profileKeyParts[1]))
										->set($db->quoteName($profileKeyParts[2]) . ' = ' . $db->quote($profileValue))
										->where($db->quoteName('user_id') . ' = ' . $db->quote($user->id));
									$db->setQuery($query);
									$db->execute();
								}
							}
						}
					}
				}
			}

			$body = $app->getBody();

			$e_session = $app->getSession()->get('emundusUser');

			// Define class via emundus profile
			if (!empty($e_session))
			{
				$class = $e_session->applicant == 1 ? 'em-applicant' : 'em-coordinator';
			}
			else
			{
				$class = 'em-guest';
			}

			preg_match_all(\chr(1) . '(<div.*\s+id="g-page-surround".*>)' . \chr(1) . 'i', $body, $matches);
			foreach ($matches[0] as $match)
			{
				if (!strpos($match, 'class='))
				{
					$replace = '<div id="g-page-surround" class="' . $class . '">';
					$body    = str_replace($match, $replace, $body);
				}
			}

			$app->setBody($body);

			PluginHelper::importPlugin('emundus');
			$dispatcher = $app->getDispatcher();
			$onAfterRender = new GenericEvent('onCallEventHandler', ['onAfterRender', ['session' => $e_session]]);
			$dispatcher->dispatch('onCallEventHandler', $onAfterRender);
		}
	}
}
