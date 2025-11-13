<?php
/**
 * @package     models
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\User\User;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Repositories\Contacts\ContactRepository;

if(!class_exists('EmundusHelperCache'))
{
	require_once JPATH_SITE . '/components/com_emundus/helpers/cache.php';
}

jimport('joomla.application.component.model');

class EmundusModelContacts extends ListModel
{
	private ?User $user = null;

	private \EmundusHelperCache $h_cache;

	function __construct($config = [], ?MVCFactoryInterface $factory = null, ?User $user = null)
	{
		parent::__construct();

		$this->app  = Factory::getApplication();
		$this->db   = $this->getDatabase();
		if(empty($user)){
			$this->user = $this->app->getIdentity();
		}
		else {
			$this->user = $user;
		}
		$this->h_cache = new \EmundusHelperCache();

		Log::addLogger(['text_file' => 'com_emundus.error.php'], Log::ERROR, array('com_emundus'));
		Log::addLogger(['text_file' => 'com_emundus.sign.php'], Log::ALL, array('com_emundus.contacts'));
	}

	public function getContactById(int $id): ContactEntity
	{
		try
		{
			$contactRepository = new ContactRepository();
			if(empty($id))
			{
				throw new \Exception('Contact ID is empty.', 400);
			}

			return $contactRepository->getById($id);
		}
		catch (\Exception $e)
		{
			Log::add('Error fetching contact: ' . $e->getMessage(), Log::ERROR, 'com_emundus.contacts');
			throw $e;
		}
	}
}