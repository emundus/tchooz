<?php
/**
 * Messages controller used for the creation and emission of messages from the platform.
 *
 * @package    Joomla
 * @subpackage Emundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Hugo Moracchini
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Tchooz\Traits\TraitDispatcher;
use Tchooz\Traits\TraitResponse;

class EmundusControllerContacts extends BaseController
{
	use TraitResponse;

	use TraitDispatcher;

	private ?User $user;

	private DatabaseInterface $db;

	private EmundusModelContacts $model;

	public function __construct($config = array(), MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);

		$this->user = $this->app->getIdentity();

		if (!class_exists('EmundusHelperAccess'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';
		}
		if (!class_exists('EmundusModelContacts'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/contacts.php';
		}
		$this->model = new EmundusModelContacts([], null, $this->user);
	}

	public function getcontact(): void
	{
		$response = ['code' => 400, 'status' => false, 'message' => '', 'data' => 0];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$response['code']    = 403;
			$response['message'] = 'Access denied.';
			$this->sendJsonResponse($response);

			return;
		}

		$id = $this->input->getInt('id', 0);

		if (empty($id))
		{
			$response['code']    = 400;
			$response['message'] = 'Missing required fields.';
			$this->sendJsonResponse($response);

			return;
		}

		try
		{
			if ($contact = $this->model->getContactById($id))
			{
				$response['code']    = 200;
				$response['status']  = true;
				$response['message'] = 'Contact retrieved successfully.';
				$response['data']    = $contact->__serialize();
			}
			else
			{
				throw new \Exception('Failed to retrieve contact.', 500);
			}
		}
		catch (\Exception $e)
		{
			$response['code']    = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		$this->sendJsonResponse($response);
	}

	public function savefilteremail()
	{
		$email = $this->input->getString('email', 0);

		$session = Factory::getApplication()->getSession();
		$session->set('em-quick-search-filters', [
			[
				'value' => $email,
				'scope' => 'u.email'
			]
		]);

		$menu = Factory::getApplication()->getMenu();
		$emundusUser      = $this->app->getSession()->get('emundusUser');
		$files_menu = $menu->getItems(['link', 'menutype'], ['index.php?option=com_emundus&view=files', $emundusUser->menutype], 'true');

		if(empty($files_menu)) {
			$files_menu = $menu->getItems(['link', 'menutype'], ['index.php?option=com_emundus&view=evaluation', $emundusUser->menutype], 'true');
		}

		$response = [];
		$response['code']    = 200 ;
		$response['message'] = 'Filter saved successfully.';
		$response['data']    = $files_menu->route;
		$response['status']  = true;

		$this->sendJsonResponse($response);
	}
}
