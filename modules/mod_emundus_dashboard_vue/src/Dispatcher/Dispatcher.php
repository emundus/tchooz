<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_articles_category
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Emundus\Module\Dashboard\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_emundus_dashboard
 *
 * @since  4.4.0
 */
class Dispatcher extends AbstractModuleDispatcher
{

    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   4.4.0
     */
    protected function getLayoutData(): array
    {
        $data   = parent::getLayoutData();
        $params = $data['params'];

	    $user = $this->app->getIdentity();
	    $emundusUser = $this->app->getSession()->get('emundusUser');

	    $profiles    = $params->get('profile', []);

	    require_once JPATH_ROOT . '/components/com_emundus/models/profile.php';
	    $m_profiles = $this->app->bootComponent('com_emundus')->getMVCFactory()->createModel('Profile', 'EmundusModel');
	    $applicant_profiles = $m_profiles->getApplicantsProfilesArray();
		$data['display_dashboard'] = in_array($emundusUser->profile, $profiles) && !in_array($emundusUser->profile, $applicant_profiles);

	    if ($data['display_dashboard']) {
		    $wa = $this->app->getDocument()->getWebAssetManager();
		    $wa->registerAndUseStyle('mod_emundus_dashboard_css','modules/mod_emundus_dashboard_vue/assets/mod_emundus_dashbord_vue.css');
		    $wa->registerAndUseStyle('com_emundus_app', 'media/com_emundus_vue/app_emundus.css');

		    $data['programme_filter'] = $params->get('filter_programmes', 0);

		    $data['display_description'] = $params->get('display_description', 0);
		    $data['display_shapes'] = $params->get('display_shapes', 1);
		    $data['display_tchoozy'] = $params->get('display_dashboard_tchoozy', 1);
		    $data['display_name']        = $params->get('display_name', 0);
		    $data['name']               = $emundusUser->name;

		    $current_lang = $this->app->getLanguage()->getTag();
		    $data['language']     = $current_lang == 'fr-FR' ? 1 : 0;

		    require_once JPATH_ROOT . '/components/com_emundus/models/dashboard.php';
		    require_once JPATH_ROOT . '/components/com_emundus/models/users.php';
		    $m_users = $this->app->bootComponent('com_emundus')->getMVCFactory()->createModel('Users', 'EmundusModel');
		    $m_dashboard = $this->app->bootComponent('com_emundus')->getMVCFactory()->createModel('Dashboard', 'EmundusModel');

		    $dashboard   = $m_dashboard->getDashboard($emundusUser->id);
		    if (empty($dashboard)) {
			    $m_dashboard->createDashboard($emundusUser->id);
		    }

		    $data['profile_details'] = new \stdClass();
		    if (!$user->guest) {
			    if (!empty($emundusUser->profile)) {
				    $data['profile_details'] = $m_users->getProfileDetails($emundusUser->profile);
					if(isset($data['profile_details']->display_description) && is_numeric($data['profile_details']->display_description)) {
						$data['display_description'] = $data['profile_details']->display_description;
					}
			    } else {
				    $data['profile_details']->label = '';
				    $data['profile_details']->description = '';
			    }
		    }
		}

        return $data;
    }
}
