<?php
/**
 * @package     Joomla\Plugin\Emundus\Parcoursup\Entity
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Emundus\Parcoursup\Factory;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;

class UserFactory
{
	public function __construct()
	{
	}
	
	public function buildUser(string  $name,
	                        string  $firstname,
	                        string  $lastname,
	                        string  $username,
	                        string  $email,
	                        array   $groups,
	                        bool    $skipActivation,
	                        int     $profile,
	                        ?int    $block = 0,
	                        ?int    $activation = 1,
	                        ?string $registerDate = null,
	                        ?string $lastvisitDate = null): User
	{
		$user = clone(Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(0));

		require_once JPATH_SITE . '/components/com_emundus/helpers/emails.php';
		require_once JPATH_SITE . '/components/com_emundus/helpers/date.php';
		$hEmails = new \EmundusHelperEmails();
		if (!$hEmails->correctEmail($email))
		{
			throw new \Exception('The email is not valid');
		}

		$user->name     = $name;
		$user->username = $username;
		$user->email    = $email;
		if (empty($registerDate))
		{
			$user->registerDate = \EmundusHelperDate::getNow();
		}
		else
		{
			$user->registerDate = $registerDate;
		}
		$user->lastvisitDate = $lastvisitDate;
		$user->groups        = $groups;
		$user->block         = $block;
		$user->activation    = $activation;
		if ($skipActivation)
		{
			$user->setParam('skip_activation', $skipActivation);
		}

		$user->otherParam                 = [];
		$user->otherParam['firstname']    = $firstname;
		$user->otherParam['lastname']     = $lastname;
		$user->otherParam['profile']      = $profile;
		$user->otherParam['em_oprofiles'] = [];
		$user->otherParam['em_groups']    = [];
		$user->otherParam['em_campaigns'] = [];

		return $user;
	}
}