<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Tchooz\Repositories\Payment\PaymentRepository;

class Release2_6_1Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query  = $this->db->getQuery(true);

		$tasks = [];

		try
		{
			$payment_repository = new PaymentRepository();

			if (!$payment_repository->activated) {
				$query->update('#__emundus_setup_step_types')
					->set('published = 0')
					->where('action_id = ' . $this->db->quote($payment_repository->getActionId()));

				$this->db->setQuery($query);
				$tasks[] = $this->db->execute();
			}

			$result['status']  = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}