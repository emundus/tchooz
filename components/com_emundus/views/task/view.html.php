<?php


// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * eMundus Onboard Task View
 *
 * @since  0.0.1
 */
class EmundusViewTask extends JViewLegacy
{
	public $hash = '';
	public $user = null;

	function display($tpl = null)
	{
		$app        = Factory::getApplication();
		$this->user = $app->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
			$this->hash = EmundusHelperCache::getCurrentGitHash();

			parent::display($tpl);
		}
		else
		{
			$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$app->redirect('/');
		}
	}
}