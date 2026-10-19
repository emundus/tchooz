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
use Tchooz\Repositories\Addons\AddonRepository;

class Release2_25_6Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		try
		{
			$published = false;
			$addonRepository = new AddonRepository();
			$customReferenceAddon = $addonRepository->getByName('custom_reference_format');
			if($customReferenceAddon->isActivated())
			{
				$published = true;
			}
			$this->tasks[] = EmundusHelperUpdate::addSelectableProviderTag('SHORT_REFERENCE', 'Short reference of application file',  'Référence courte du dossier', $published);
			$this->tasks[] = EmundusHelperUpdate::addSelectableProviderTag('REFERENCE', 'Custom reference of application file', 'Référence personnalisé du dossier', $published);
			$this->tasks[] = EmundusHelperUpdate::addSelectableProviderTag('FULL_REFERENCE', 'Full reference (short + custom) of application file', 'Référence complète (courte + personnalisée) du dossier', $published);
			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Throwable $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}
