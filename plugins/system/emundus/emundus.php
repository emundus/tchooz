<?php
/**
 * @package     eMundus.Plugin
 * @subpackage  System.emundus
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Plugin\CMSPlugin;

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
	/**
	 * Constructor
	 *
	 * @param   object &$subject The object to observe
	 * @param   array  $config   An array that holds the plugin configuration
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

	/**
	 * Insert classes into the body tag depending on user profile
	 *
	 * @return  void
	 */
	public function onAfterRender()
	{
		$app = Factory::getApplication();
		
		if ($app->isClient('site')) {
			$e_session = $app->getSession()->get('emundusUser');

			$body = $app->getBody();

			preg_match_all(\chr(1) . '(<div.*\s+id="g-page-surround".*>)' . \chr(1) . 'i', $body, $matches);
			
			if(!empty($e_session))
			{
				$class = $e_session->applicant == 1 ? 'em-applicant' : 'em-coordinator';
			}
			else {
				$class = 'em-guest';
			}

			foreach ($matches[0] as $match)
			{
				if (!strpos($match, 'class='))
				{
					$replace = '<div id="g-page-surround" class="'.$class.'">';
					$body    = str_replace($match, $replace, $body);
				}
			}

			$app->setBody($body);
		}
	}
}
