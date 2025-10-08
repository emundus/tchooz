<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      User.joomla
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Emundus\Plugin\System\EmundusUserCategory\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Application\AfterRenderEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla User plugin
 *
 * @since  1.5
 */
final class EmundusUserCategory extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return array
	 *
	 * @since   5.3.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterRender'     => 'onAfterRender',
		];
	}

	/**
	 * This method should handle any login logic and report back to the subject
	 *
	 * @param   AfterRenderEvent  $event  The event instance.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function onAfterRender(AfterRenderEvent $event)
	{
		$user = $this->getApplication()->getIdentity();
		$currentMenu = $this->getApplication()->getMenu()->getActive();

		// Only for logged in users and in site application
		if($user->guest || empty($user->id) || !$this->getApplication()->isClient('site')){
			return;
		}

		$emConfig = ComponentHelper::getParams('com_emundus');
		$userTypeEnabled = $emConfig->get('enable_user_categories', 0);

		if ($userTypeEnabled != 1)
		{
			return;
		}

		$mandatory = $emConfig->get('user_category_mandatory', 0);
		if($mandatory == 1)
		{
			$query = $this->getDatabase()->getQuery(true);

			// Ony apply if current profile is applicant
			$query->clear()
				->select('id')
				->from($this->getDatabase()->quoteName('#__emundus_setup_profiles'))
				->where($this->getDatabase()->quoteName('published') . ' = 1');
			$this->getDatabase()->setQuery($query);
			$applicant_profiles = $this->getDatabase()->loadColumn();
			
			$emUser = $this->getApplication()->getSession()->get('emundusUser');
			if(!in_array($emUser->profile, $applicant_profiles)) {
				return;
			}

			$query->clear()
				->select('user_category')
				->from($this->getDatabase()->quoteName('#__emundus_users'))
				->where($this->getDatabase()->quoteName('user_id') . ' = :user_id')
				->bind(':user_id', $user->id);
			$this->getDatabase()->setQuery($query);
			$userType = $this->getDatabase()->loadResult();

			if (!empty($userType)) {
				return;
			}

			$query->clear()
				->select('form_id')
				->from($this->getDatabase()->quoteName('#__emundus_setup_formlist'))
				->where($this->getDatabase()->quoteName('type') . ' LIKE ' . $this->getDatabase()->quote('profile'));
			$this->getDatabase()->setQuery($query);
			$profile_form = $this->getDatabase()->loadResult();

			if(empty($profile_form)) {
				return;
			}

			$link = 'index.php?option=com_fabrik&view=form&formid='.$profile_form;

			if($currentMenu->link === $link) {
				return;
			}

			$query->clear()
				->select('id')
				->from($this->getDatabase()->quoteName('#__menu'))
				->where($this->getDatabase()->quoteName('link') . ' LIKE ' . $this->getDatabase()->quote($link))
				->where($this->getDatabase()->quoteName('published') . ' = 1');
			$this->getDatabase()->setQuery($query);
			$menuId = $this->getDatabase()->loadResult();

			if(!empty($menuId)) {
				// Load language file of plugin
				$lang = $this->getApplication()->getLanguage();
				$this->getApplication()->enqueueMessage('Veuillez sélectionner un type de profil avant de continuer.', 'warning');
				$this->getApplication()->redirect(Route::_('index.php?option=com_fabrik&view=form&formid='.$profile_form.'&Itemid='.$menuId));
			}
		}
	}
}
