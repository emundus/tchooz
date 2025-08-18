<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs\Checker;

use Emundus\Plugin\Console\Tchooz\Jobs\TchoozJob;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckInformationsJob extends TchoozJob
{

	private $interconnections = [
		'addpipe' => [
			'label' => 'AddPipe',
			'param' => 'addpipe_activation'
		],
		'yousign' => [
			'label' => 'Yousign',
			'param' => 'yousign_api_key'
		],
		'ametys' => [
			'label' => 'CMS Ametys',
			'param' => 'ametys_integration'
		],
		'zoom' => [
			'label' => 'Zoom',
			'param' => 'zoom_jwt'
		],
		'filemaker' => [
			'label' => 'Filemaker',
			'param' => 'file_maker_api_base_url'
		],
		'ged_alfresco' => [
			'label' => 'GED Alfresco',
			'param' => 'external_storage_ged_alfresco_integration'
		],
		'flywire' => [
			'label' => 'Flywire',
			'param' => 'flywire_recipient'
		],
		'axepta' => [
			'label' => 'Axepta',
			'param' => 'axepta_merchant_id'
		],
		'smartagenda' => [
			'label' => 'SmartAgenda',
			'param' => 'smart_agenda_login'
		],
		'postgrest' => [
			'label' => 'PostgRest',
			'param' => 'postgrest_api_base_url'
		],
		'glpi' => [
			'label' => 'GLPI',
			'param' => 'glpi_api_base_url'
		],
		'insee' => [
			'label' => 'INSEE',
			'param' => 'insee_api_consumer_key'
		],
		'ixparapheur' => [
			'label' => 'IXParapheur',
			'param' => 'ixparapheur_api_base_url'
		]
	];

	public function __construct(
		private readonly object $logger,
		private readonly DatabaseService $databaseService,
		private readonly string          $projectToMigrate
	)
	{
		$this->allowFailure = true;

		parent::__construct($logger);
	}

	public function execute(InputInterface $input, OutputInterface $output): void
	{
		// Get creation date of the project
		$output->writeln('🔄'.$this->colors['yellow'].'Creation date of the project...'.$this->colors['reset']);
		$this->getCreationDate($output);
		$output->writeln('');

		// Interconnections
		$output->writeln('🔄'.$this->colors['yellow'].'Checking interconnections...'.$this->colors['reset']);
		$this->checkInterconnections($output);
		$output->writeln('');

		// Forms count
		$output->writeln('🔄'.$this->colors['yellow'].'Checking applicants forms count...'.$this->colors['reset']);
		$this->checkFormsCount($output);
		$output->writeln('');

		// Campaigns count
		$output->writeln('🔄'.$this->colors['yellow'].'Checking campaigns count...'.$this->colors['reset']);
		$this->checkCampaignsCount($output);
		$output->writeln('');

		// Programs count
		$output->writeln('🔄'.$this->colors['yellow'].'Checking programmes count...'.$this->colors['reset']);
		$this->checkProgramsCount($output);
		$output->writeln('');

		// Workflows count
		$output->writeln('🔄'.$this->colors['yellow'].'Checking workflows count...'.$this->colors['reset']);
		$this->checkWorkflowsCount($output);
		$output->writeln('');

		// Check custom css
		$output->writeln('🔄'.$this->colors['yellow'].'Checking custom css...'.$this->colors['reset']);
		$this->checkCustomCss($output);
		$output->writeln('');

		// Check custom php controller and model
		$output->writeln('🔄'.$this->colors['yellow'].'Checking custom php controller/model...'.$this->colors['reset']);
		$this->checkCustomPhpControllerModel($output);
		$output->writeln('');

		// Check custom php code
		$output->writeln('🔄'.$this->colors['yellow'].'Checking custom php code...'.$this->colors['reset']);
		$this->checkCustomPhpCode($output);
		$output->writeln('');

		// Check templates
		$output->writeln('🔄'.$this->colors['yellow'].'Checking Yootheme...'.$this->colors['reset']);
		$this->checkWebsite($output);
		$output->writeln('');

		// Check referent forms
		$output->writeln('🔄'.$this->colors['yellow'].'Checking referent forms...'.$this->colors['reset']);
		$this->checkReferentForms($output);
		$output->writeln('');

		// Check expert feature
		$output->writeln('🔄'.$this->colors['yellow'].'Checking expert feature...'.$this->colors['reset']);
		$this->checkExpertFeature($output);
		$output->writeln('');

		// Check emundus_logs count
		$output->writeln('🔄'.$this->colors['yellow'].'Checking emundus_logs count...'.$this->colors['reset']);
		$this->checkEmundusLogsCount($output);
		$output->writeln('');
	}

	private function checkInterconnections(OutputInterface $output): void
	{
		$query = $this->databaseService->getDatabase()->getQuery(true);
		
		$query->select('params')
			->from( $this->databaseService->getDatabase()->quoteName('jos_extensions'))
			->where('element = ' . $this->databaseService->getDatabase()->quote('com_emundus'));
		$this->databaseService->getDatabase()->setQuery($query);
		$params = $this->databaseService->getDatabase()->loadResult();

		$params = json_decode($params);

		$stateIcon = [
			1 => $this->colors['green'] . '✔' . $this->colors['reset'],
			0 => $this->colors['red'] . '🚫' . $this->colors['reset']
		];

		$stateText = [
			1 => 'Activated',
			0 => 'Deactivated'
		];

		foreach ($this->interconnections as $interconnection) {
			$enabled = !empty($params->{$interconnection['param']}) ? 1 : 0;
			$output->writeln($stateIcon[$enabled].' '.$interconnection['label'].' : '.$stateText[$enabled]);
		}
	}

	private function checkFormsCount(OutputInterface $output): void
	{
		$query = $this->databaseService->getDatabase()->getQuery(true);

		$query->select('COUNT(id)')
			->from( $this->databaseService->getDatabase()->quoteName('jos_emundus_setup_profiles'))
			->where('published = 1')
			->where('status = 1');
		$this->databaseService->getDatabase()->setQuery($query);
		$formsCount = $this->databaseService->getDatabase()->loadResult();

		$output->writeln('Applicants forms count : '.$this->colors['bold'].$formsCount.$this->colors['reset']);
	}

	private function checkCampaignsCount(OutputInterface $output): void
	{
		$query = $this->databaseService->getDatabase()->getQuery(true);

		$query->select('COUNT(id)')
			->from( $this->databaseService->getDatabase()->quoteName('jos_emundus_setup_campaigns'))
			->where('published = 1');
		$this->databaseService->getDatabase()->setQuery($query);
		$campaignsCount = $this->databaseService->getDatabase()->loadResult();

		$output->writeln('Campaigns count : '.$this->colors['bold'].$campaignsCount.$this->colors['reset']);
	}

	private function checkProgramsCount(OutputInterface $output): void
	{
		$query = $this->databaseService->getDatabase()->getQuery(true);

		$query->select('COUNT(id)')
			->from( $this->databaseService->getDatabase()->quoteName('jos_emundus_setup_programmes'))
			->where('published = 1');
		$this->databaseService->getDatabase()->setQuery($query);
		$programmesCount = $this->databaseService->getDatabase()->loadResult();

		$output->writeln('Programmes count : '.$this->colors['bold'].$programmesCount.$this->colors['reset']);
	}

	private function checkWorkflowsCount(OutputInterface $output): void
	{
		$query = $this->databaseService->getDatabase()->getQuery(true);

		try {
			$query->clear()
				->select('COUNT(id)')
				->from( $this->databaseService->getDatabase()->quoteName('jos_emundus_campaign_workflow'));

			$this->databaseService->getDatabase()->setQuery($query);
			$workflowsCount = $this->databaseService->getDatabase()->loadResult();

			$output->writeln('Workflows count : '.$this->colors['bold'].$workflowsCount.$this->colors['reset']);

			$query->clear()
				->select('COUNT(DISTINCT ecw.id)')
				->from( $this->databaseService->getDatabase()->quoteName('jos_emundus_campaign_workflow', 'ecw'))
				->innerJoin('jos_emundus_campaign_workflow_repeat_programs AS jecwrp ON jecwrp.parent_id = ecw.id');

			$this->databaseService->getDatabase()->setQuery($query);
			$workflowsCount = $this->databaseService->getDatabase()->loadResult();

			$output->writeln('Workflows related to programs count : '.$this->colors['bold'].$workflowsCount.$this->colors['reset']);

			$query->clear()
				->select('COUNT(DISTINCT ecw.id)')
				->from( $this->databaseService->getDatabase()->quoteName('jos_emundus_campaign_workflow', 'ecw'))
				->leftJoin('jos_emundus_campaign_workflow_repeat_campaign as ecwrc ON ecwrc.parent_id = ecw.id');

			$this->databaseService->getDatabase()->setQuery($query);
			$workflowsCount = $this->databaseService->getDatabase()->loadResult();

			$output->writeln('Workflows related to campaigns count : '.$this->colors['bold'].$workflowsCount.$this->colors['reset']);
		} catch (\Exception $e) {
			$output->writeln($this->colors['red'].'🚫'.$this->colors['reset'].'Error while fetching workflows count '  . $e->getMessage());
		}
	}

	private function checkCustomCss(OutputInterface $output): void
	{
		$customCssPath = $this->projectToMigrate.'/templates/g5_helium/custom/scss/custom.scss';

		if (file_exists($customCssPath) && filesize($customCssPath) > 0) {
			$output->writeln($this->colors['green'].'✔ '.$this->colors['reset'].'Custom CSS file exists and is not empty');
		} else {
			$output->writeln($this->colors['red'].'🚫'.$this->colors['reset'].'Custom CSS file does not exist or is empty');
		}
	}

	private function checkCustomPhpControllerModel(OutputInterface $output): void
	{
		$customPhpControllerPath = $this->projectToMigrate.'/components/com_emundus/controllers/custom.php';

		if(file_exists($customPhpControllerPath) && filesize($customPhpControllerPath) > 0) {
			$output->writeln($this->colors['green'].'✔ '.$this->colors['reset'].'Custom PHP controller file exists and is not empty');
		} else {
			$output->writeln($this->colors['red'].'🚫'.$this->colors['reset'].'Custom PHP controller file does not exist or is empty');
		}

		$customPhpModelPath = $this->projectToMigrate.'/components/com_emundus/models/custom.php';

		if(file_exists($customPhpModelPath) && filesize($customPhpModelPath) > 0) {
			$output->writeln($this->colors['green'].'✔ '.$this->colors['reset'].'Custom PHP model file exists and is not empty');
		} else {
			$output->writeln($this->colors['red'].'🚫'.$this->colors['reset'].'Custom PHP model file does not exist or is empty');
		}
	}

	private function checkCustomPhpCode(OutputInterface $output): void
	{
		// Display count of custom emundus_setup_tags
		$query = $this->databaseService->getDatabase()->getQuery(true);

		$query->select('COUNT(id)')
			->from( $this->databaseService->getDatabase()->quoteName('jos_emundus_setup_tags'))
			->where('request LIKE ' . $this->databaseService->getDatabase()->quote('php|%'));
		$this->databaseService->getDatabase()->setQuery($query);
		$phpTagsCount = $this->databaseService->getDatabase()->loadResult();

		$output->writeln('Custom PHP tags count : '.$this->colors['bold'].$phpTagsCount.$this->colors['reset']);
		//

		// Display count of jumi modules
		$query->clear()
			->select('COUNT(id)')
			->from( $this->databaseService->getDatabase()->quoteName('jos_modules'))
			->where('module LIKE ' . $this->databaseService->getDatabase()->quote('mod_jumi'))
			->where('published = 1');
		$this->databaseService->getDatabase()->setQuery($query);
		$jumiModulesCount = $this->databaseService->getDatabase()->loadResult();

		$output->writeln('Jumi modules count : '.$this->colors['bold'].$jumiModulesCount.$this->colors['reset']);
		//

		// Display count of custom event handler
		$query->clear()
			->select('params')
			->from( $this->databaseService->getDatabase()->quoteName('jos_extensions'))
			->where('element = ' . $this->databaseService->getDatabase()->quote('custom_event_handler'));
		$this->databaseService->getDatabase()->setQuery($query);
		$params = $this->databaseService->getDatabase()->loadResult();

		$params = json_decode($params);

		$eventHandlerCount = !empty($params->event_handlers) ? sizeof((array)$params->event_handlers) : 0;
		$output->writeln('Custom event handler count : '.$this->colors['bold'].$eventHandlerCount.$this->colors['reset']);
		//

		// Display count of custom php plugin in fabrik forms
		$query->clear()
			->select('params')
			->from( $this->databaseService->getDatabase()->quoteName('jos_fabrik_forms'))
			->where('params LIKE ' . $this->databaseService->getDatabase()->quote('%curl_code%'))
			->where('published = 1');
		$this->databaseService->getDatabase()->setQuery($query);
		$forms = $this->databaseService->getDatabase()->loadObjectList();

		$phpPluginCount = 0;
		foreach ($forms as $form) {
			$params = json_decode($form->params);
			if (!empty($params->curl_code)) {
				$phpPluginCount++;
			}
		}

		$output->writeln('Custom PHP plugin in Fabrik forms count : '.$this->colors['bold'].$phpPluginCount.$this->colors['reset']);
		//

		// Display count of calc elements
		$query->clear()
			->select('COUNT(id)')
			->from( $this->databaseService->getDatabase()->quoteName('jos_fabrik_elements'))
			->where('plugin = ' . $this->databaseService->getDatabase()->quote('calc'))
			->where('published = 1');
		$this->databaseService->getDatabase()->setQuery($query);
		$calcElementsCount = $this->databaseService->getDatabase()->loadResult();

		$output->writeln('Calc elements count : '.$this->colors['bold'].$calcElementsCount.$this->colors['reset']);
		//
	}

	private function getCreationDate(OutputInterface $output): void
	{
		$query = $this->databaseService->getDatabase()->getQuery(true);

		// Get creation date via the first registerDate of a user exclude @emundus.fr emails
		$query->select('DATE_FORMAT(registerDate, "%d/%m/%Y")')
			->from( $this->databaseService->getDatabase()->quoteName('jos_users'))
			->where('email NOT LIKE ' . $this->databaseService->getDatabase()->quote('%@emundus.fr%'))
			->andWhere('email NOT LIKE ' . $this->databaseService->getDatabase()->quote('%@emundus.io%'))
			->order('registerDate ASC');
		$this->databaseService->getDatabase()->setQuery($query);
		$creationDate = $this->databaseService->getDatabase()->loadResult();

		$output->writeln('Creation date : '.$this->colors['bold'].$creationDate.$this->colors['reset']);
	}

	private function checkWebsite(OutputInterface $output): void
	{
		// Check if we have a Yootheme template
		$query = $this->databaseService->getDatabase()->getQuery(true);

		$query->select('template')
			->from( $this->databaseService->getDatabase()->quoteName('jos_template_styles'))
			->where('template = ' . $this->databaseService->getDatabase()->quote('yootheme'));
		$this->databaseService->getDatabase()->setQuery($query);
		$yootheme = $this->databaseService->getDatabase()->loadResult();

		if ($yootheme) {
			$output->writeln('🖌️Yootheme template detected');
		} else {
			$output->writeln('🖌️Yootheme template not detected');
		}
	}

	private function checkReferentForms(OutputInterface $output): void
	{
		$query = $this->databaseService->getDatabase()->getQuery(true);

		$query->select('id')
			->from( $this->databaseService->getDatabase()->quoteName('jos_fabrik_forms'))
			->where('params LIKE' . $this->databaseService->getDatabase()->quote('%emundusreferentletter%'));
		$this->databaseService->getDatabase()->setQuery($query);
		$referentForms = $this->databaseService->getDatabase()->loadObjectList();

		$publishedMenu = 0;
		foreach ($referentForms as $referentForm)
		{
			// Check if the form is use in a published menu
			$query->clear()
				->select('COUNT(id)')
				->from( $this->databaseService->getDatabase()->quoteName('jos_menu'))
				->where('link LIKE ' . $this->databaseService->getDatabase()->quote('index.php?option=com_fabrik&view=form&formid='.$referentForm->id.'%'))
				->where('published = 1');
			$this->databaseService->getDatabase()->setQuery($query);
			$publishedMenu += $this->databaseService->getDatabase()->loadResult();
		}

		$output->writeln('Referent form : '.$this->colors['bold'].$publishedMenu.$this->colors['reset']);
	}

	private function checkExpertFeature(OutputInterface $output): void
	{
		$query = $this->databaseService->getDatabase()->getQuery(true);

		$query->select('COUNT(id)')
			->from( $this->databaseService->getDatabase()->quoteName('jos_menu'))
			->where('published = 1')
			->where('link LIKE ' . $this->databaseService->getDatabase()->quote('%layout=expert%'))
			->where('menutype = ' . $this->databaseService->getDatabase()->quote('actions'));
		$this->databaseService->getDatabase()->setQuery($query);
		$expertFeature = $this->databaseService->getDatabase()->loadResult();

		if ($expertFeature > 0) {
			$output->writeln($this->colors['green'].'✔ '.$this->colors['reset'].'Expert feature enabled');
		} else {
			$output->writeln($this->colors['red'].'🚫'.$this->colors['reset'].'Expert feature disabled');
		}
	}

	private function checkEmundusLogsCount(OutputInterface $output): void
	{
		$query = $this->databaseService->getDatabase()->getQuery(true);

		$query->select('COUNT(id)')
			->from( $this->databaseService->getDatabase()->quoteName('jos_emundus_logs'));
		$this->databaseService->getDatabase()->setQuery($query);
		$emundusLogsCount = $this->databaseService->getDatabase()->loadResult();
		// Format number
		$emundusLogsCount = number_format($emundusLogsCount, 0, ',', ' ');

		$output->writeln('Emundus logs count : '.$this->colors['bold'].$emundusLogsCount.' rows'.$this->colors['reset']);
	}

	public static function getJobName(): string {
		return 'Informations';
	}

	public static function getJobDescription(): ?string {
		return 'Ouput some informations helpful for the migration';
	}
}