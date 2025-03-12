<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs;

use Emundus\Plugin\Console\Tchooz\Jobs\TchoozJob;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Emundus\Plugin\Console\Tchooz\Services\StorageService;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\Logger;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckExtensionsJob extends TchoozJob
{
	public function __construct(
		private readonly object $logger,
		private readonly DatabaseService $databaseService
	)
	{
		$this->allowFailure = true;

		parent::__construct($logger);
	}

	public function execute(): void
	{
		$checkStatus = true;

		$query_source                 = $this->databaseService->getDatabase()->getQuery(true);
		$components_not_found_warning = [];
		$modules_not_found_warning    = [];
		$libraries_not_found          = [];
		$plugins_not_found_warning    = [];
		$templates_not_found_warning  = [];

		$components_to_warning = ['com_dpcalendar', 'com_eventbooking', 'com_externallogin', 'com_hikamarket', 'com_miniorange_saml', 'com_loginguard', 'com_jce'];
		$modules_to_warning    = ['mod_emundus_evaluations'];
		$plugins_to_warning    = ['content/emundusSchoolyear', 'authentication/emundus_oauth2_cci'];
		$template_to_warning   = ['yootheme', 'emundus_vanilla'];

		try
		{
			$component_base_dir = [
				0 => 'components',
				1 => 'administrator/components'
			];
			$modules_base_dir   = [
				0 => 'modules',
				1 => 'administrator/modules'
			];
			$templates_base_dir = [
				0 => 'templates',
				1 => 'administrator/templates'
			];

			$query_source->select('*')
				->from($this->databaseService->getDatabase()->quoteName('jos_extensions'))
				->order('type ASC');
			$this->databaseService->getDatabase()->setQuery($query_source);
			$extensions = $this->databaseService->getDatabase()->loadAssocList();

			foreach ($extensions as $extension)
			{
				if ($extension['type'] == 'component')
				{
					if (!is_dir($component_base_dir[$extension['client_id']] . '/' . $extension['element']))
					{
						if (in_array($extension['element'], $components_to_warning))
						{
							$components_not_found_warning[] = $extension['name'] . ' [' . $extension['element'] . ']';
						}
					}
				}

				if ($extension['type'] == 'module')
				{
					if (!is_dir($modules_base_dir[$extension['client_id']] . '/' . $extension['element'])
						&& (strpos($extension['element'], 'mod_eb') === false)
						&& (strpos($extension['element'], 'hikashop') === false)
						&& (strpos($extension['element'], 'dpcalendar') === false)
						&& (strpos($extension['element'], 'loginguard') === false)
						&& (strpos($extension['element'], 'jce') === false)
						&& (strpos($extension['element'], 'externallogin') === false)
					)
					{
						if (in_array($extension['element'], $modules_to_warning))
						{
							$modules_not_found_warning[] = $extension['name'] . ' [' . $extension['element'] . ']';
						}
					}
				}

				if ($extension['type'] == 'library')
				{
					if (!is_dir('libraries/' . $extension['element']))
					{
						$libraries_not_found[] = $extension['name'] . ' [' . $extension['element'] . ']';
					}
				}

				// We exclude plugins link to components
				if ($extension['type'] == 'plugin')
				{
					if (!is_dir('plugins/' . $extension['folder'] . '/' . $extension['element'])
						&& (strpos($extension['folder'], 'eventbooking') === false && strpos($extension['element'], 'eventbooking') === false)
						&& (strpos($extension['folder'], 'hikashop') === false && strpos($extension['element'], 'hikashop') === false)
						&& (strpos($extension['folder'], 'dpcalendar') === false && strpos($extension['element'], 'dpcalendar') === false)
						&& (strpos($extension['folder'], 'loginguard') === false && strpos($extension['element'], 'loginguard') === false)
						&& (strpos($extension['folder'], 'jce') === false && strpos($extension['element'], 'jce') === false)
					)
					{
						if (in_array($extension['folder'] . '/' . $extension['element'], $plugins_to_warning))
						{
							$plugins_not_found_warning[] = $extension['name'] . ' [' . $extension['folder'] . '/' . $extension['element'] . ']';
						}
					}
				}

				if ($extension['type'] == 'template')
				{
					if (!is_dir($templates_base_dir[$extension['client_id']] . '/' . $extension['element']))
					{
						if (in_array($extension['element'], $template_to_warning))
						{
							$templates_not_found_warning[] = $extension['name'] . ' [' . $extension['element'] . ']';
						}
					}
				}
			}
		}
		catch (\Exception $e)
		{
			throw new \Exception('Error while checking extensions: ' . $e->getMessage());
		}

		if (!empty($components_not_found_warning))
		{
			$checkStatus = false;
			Log::add('These following components are not found in Tchooz V2, you should check if they are still used in the platform: '.implode(', ',$components_not_found_warning), Log::WARNING, self::getJobName());
		}

		if (!empty($modules_not_found_warning))
		{
			$checkStatus = false;
			Log::add('These following modules are not found in Tchooz V2, you should check if they are still used in the platform: '.implode(', ',$modules_not_found_warning), Log::WARNING, self::getJobName());
		}

		if (!empty($libraries_not_found))
		{
			$checkStatus = false;
			Log::add('Some libraries are not found in the destination site: '.implode(', ',$libraries_not_found), Log::INFO, self::getJobName());
		}

		if (!empty($plugins_not_found_warning))
		{
			$checkStatus = false;
			Log::add('These following plugins are not found in Tchooz V2, you should check if they are still used in the platform: '.implode(', ',$plugins_not_found_warning), Log::WARNING, self::getJobName());
		}

		if (!empty($templates_not_found_warning))
		{
			$checkStatus = false;
			Log::add('These following templates are not found in Tchooz V2, you should check if they are still used in the platform: '.implode(', ',$templates_not_found_warning), Log::WARNING, self::getJobName());
		}

		if(!$checkStatus) {
			throw new \Exception('Some extensions are not found in Tchooz V2, please check the logs for more information.');
		}
	}

	public static function getJobName(): string {
		return 'Extensions';
	}

	public static function getJobDescription(): ?string {
		return 'Check if some extensions installed are deprecated in Tchooz v2';
	}
}