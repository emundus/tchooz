<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use scripts\ReleaseInstaller;
use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Repositories\Language\LanguageRepository;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Factories\Language\LanguageFactory;
use Tchooz\Repositories\Actions\ActionRepository;

class Release2_15_2Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query = $this->db->createQuery();

		try
		{
			\EmundusHelperUpdate::addYamlVariable('error-page', 'block', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'tchoozy', false, true, false);

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}
