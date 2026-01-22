<?php
/**
 * @copyright      Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Log\Log;
use Tchooz\Repositories\User\EmundusUserRepository;
use Joomla\CMS\Factory;

/**
 * @package        Joomla.Site
 * @subpackage     mod_emundusmenu
 * @since          1.5
 */
class modEmundusProfileHelper
{
	static function getProfilePicture(): string
	{
		$pp = '/media/com_emundus/images/profile/default-profile.jpg';

		try {
			$emundusUserRepository = new EmundusUserRepository();
			$emundusUser = $emundusUserRepository->getByUserId(Factory::getApplication()->getIdentity()->id);

			if (!empty($emundusUser->getProfilePicture())) {
				$pp = $emundusUser->getProfilePicture();
			}
		}
		catch (Exception $e) {
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $pp;
	}
}
