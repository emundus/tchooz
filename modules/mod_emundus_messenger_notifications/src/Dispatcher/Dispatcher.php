<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_articles_category
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Emundus\Module\Messenger\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

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

	    require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
	    require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'messenger.php');

	    $user      = $this->app->getIdentity();
	    $data['applicant'] = !\EmundusHelperAccess::asPartnerAccessLevel($user->id);
	    $data['files_count'] = 0;

	    if (!$data['applicant']) {
		    $emundusUser     = $this->app->getSession()->get('emundusUser');
		    $current_profile = $emundusUser->profile;

		    require_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
		    if (class_exists('EmundusModelProfile')) {
			    $m_profile          = new \EmundusModelProfile();
			    $applicant_profiles = $m_profile->getApplicantsProfilesArray();

			    if (in_array($current_profile, $applicant_profiles)) {
				    $data['applicant'] = true;
			    }
		    }
	    }

	    if ($data['applicant']) {
		    $m_messenger = new \EmundusModelMessenger();
		    $data['files_count'] = $m_messenger->getFilesByUser();
	    }

        return $data;
    }
}
