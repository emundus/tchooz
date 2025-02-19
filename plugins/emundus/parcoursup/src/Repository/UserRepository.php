<?php
/**
 * @package     Joomla\Plugin\Emundus\Parcoursup\Repository
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Emundus\Parcoursup\Repository;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;

class UserRepository
{
	public function __construct(private \EmundusModelUsers $mUsers)
	{
	}

	public function flushUser(User $user): int
	{
		$acl_aro_groups = $this->mUsers->getDefaultGroup($user->otherParam['profile']);
		$user->groups   = $acl_aro_groups;

		$usertype       = $this->mUsers->found_usertype($acl_aro_groups[0]);
		$user->usertype = $usertype;

		try
		{
			$uid = $this->mUsers->adduser($user, $user->otherParam);
		}
		catch (\Exception $e)
		{
			Log::add(Text::_('PLG_EMUNDUS_PARCOURSUP_ERROR_CREATING_USER') . ' ' . $e->getMessage(), Log::ERROR, 'emundus');
			throw new \Exception('Error creating user');
		}

		return $uid;
	}
}