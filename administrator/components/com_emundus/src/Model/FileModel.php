<?php

namespace Joomla\Component\Emundus\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\InputFilter;
use Tchooz\Repositories\ApplicationFile\StatusRepository;

class FileModel extends AdminModel
{

	private $translations = [];

	/**
	 * Constructor
	 *
	 * @param   array                 $config   An array of configuration options (name, state, dbo, table_path, ignore_request).
	 * @param   ?MVCFactoryInterface  $factory  The factory.
	 *
	 * @since   3.7.0
	 * @throws  \Exception
	 */
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);

		$this->typeAlias = Factory::getApplication()->getInput()->getCmd('context', 'com_emundus.files') . '.file';
		Log::addLogger(['text_file' => 'com_emundus.api.log'], Log::ALL, 'com_emundus.api');
	}


	public function getItem($pk = null)
	{
		$item = null;

		if (empty($pk)) {
			// Load language ?
			$lang = Factory::getContainer()->get(LanguageFactoryInterface::class)->createLanguage('fr-FR', false);
			$this->loadOverrideTranslations();

			$app = Factory::getApplication();
			$pk = $app->input->getString('fnum', '');

			if (!empty($pk)) {
				Log::add('FileModel::getItem() - fnum: ' . $pk, Log::DEBUG, 'com_emundus.api');

				$db    = $this->getDatabase();
				$query = $db->createQuery();

				$query->select('cc.*, p.id as program_id, c.id as campaign_id, c.profile_id')
					->from($db->quoteName('#__emundus_campaign_candidature', 'cc'))
					->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'c') . ' ON ' . $db->quoteName('cc.campaign_id') . ' = ' . $db->quoteName('c.id'))
					->leftJoin($db->quoteName('#__emundus_setup_programmes', 'p') . ' ON ' . $db->quoteName('c.training') . ' = ' . $db->quoteName('p.code'))
					->where($db->quoteName('fnum') . ' = ' . $db->quote($pk));

				$filters = $app->input->get('filter', [], 'array');

				try {
					$db->setQuery($query);
					$item = $db->loadObject();

					if (!empty($item)) {
						$statusRepository = new StatusRepository();
						$status = $statusRepository->getByStep($item->status);
						$item->status = $status->__serialize();

						$query->clear()
							->select('esat.id, esat.label')
							->from($this->getDatabase()->quoteName('#__emundus_setup_action_tag', 'esat'))
							->leftJoin(
								$this->getDatabase()->quoteName('#__emundus_tag_assoc', 'eta')
								. ' ON ' . $this->getDatabase()->quoteName('esat.id') . ' = ' . $this->getDatabase()->quoteName('eta.id_tag')
							)
							->where($this->getDatabase()->quoteName('eta.fnum') . ' = ' . $this->getDatabase()->quote($item->fnum));
						$this->getDatabase()->setQuery($query);
						$item->stickers = $this->getDatabase()->loadObjectList();

						$profile_ids = [$item->profile_id];

						if (!class_exists('EmundusHelperFabrik'))
						{
							require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
						}
						if (!class_exists('EmundusModelWorkflow')) {
							require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
						}
						$m_workflow = new \EmundusModelWorkflow();
						$workflow = $m_workflow->getWorkflowByFnum($item->fnum);

						foreach ($workflow['steps'] as $step) {
							if (!empty($step->profile_id) && $m_workflow->isApplicantStep($step->type)) {
								$profile_ids[] = $step->profile_id;
							}
						}

						if (!empty($profile_ids)) {
							$item->steps = [];

							$query->clear()
								->select('esp.id, esp.label, esp.menutype, GROUP_CONCAT(DISTINCT m.link) AS form_links')
								->from($db->quoteName('#__emundus_setup_profiles', 'esp'))
								->leftJoin($db->quoteName('#__menu', 'm') . ' ON ' . $db->quoteName('esp.menutype') . ' = ' . $db->quoteName('m.menutype'))
								->where('esp.id IN (' . implode(',', $db->quote($profile_ids)) . ')')
								->andWhere($db->quoteName('m.link') . ' LIKE ' . $db->quote('%option=com_fabrik&view=form&formid=%'))
								->group($db->quoteName('esp.id'));

							if (\array_key_exists('profile_id', $filters)) {
								$profile_id = InputFilter::getInstance()->clean($filters['profile_id'], 'INT');

								if ($profile_id > 0) {
									$query->andWhere($db->quoteName('esp.id') . ' = ' . $db->quote($profile_id));
								}
							}

							$db->setQuery($query);
							$profiles = $db->loadObjectList();

							foreach ($profiles as $profile) {
								$form_ids = [];

								$profile->form_links = explode(',', $profile->form_links);
								foreach ($profile->form_links as $link) {
									// Extract the form ID from the link
									if (preg_match('/formid=(\d+)/', $link, $matches)) {
										$form_ids[] = (int) $matches[1];
									}
								}

								$forms = [];
								if (!empty($form_ids)) {
									foreach ($form_ids as $form_id) {
										$query->clear()
											->select('jfe.id, jfe.name, jfe.label, jfe.plugin, jfg.params as group_params, jfg.id as group_id, jff.id AS form_id, jff.label AS form_label, jfl.db_table_name')
											->from($db->quoteName('#__fabrik_elements', 'jfe'))
											->leftJoin($db->quoteName('#__fabrik_groups', 'jfg') . ' ON ' . $db->quoteName('jfe.group_id') . ' = ' . $db->quoteName('jfg.id'))
											->leftJoin($db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON ' . $db->quoteName('jfg.id') . ' = ' . $db->quoteName('jffg.group_id'))
											->leftJoin($db->quoteName('#__fabrik_forms', 'jff') . ' ON ' . $db->quoteName('jff.id') . ' = ' . $db->quoteName('jffg.form_id'))
											->leftJoin($db->quoteName('#__fabrik_lists', 'jfl') . ' ON ' . $db->quoteName('jfl.form_id') . ' = ' . $db->quoteName('jff.id'))
											->where($db->quoteName('jff.id') . ' = ' . $db->quote($form_id))
											->andWhere($db->quoteName('jfe.published') . ' = 1')
											->order($db->quoteName('jfe.hidden') . ' = 0');

										$db->setQuery($query);
										$elements = $db->loadAssocList();

										$form = [
											'id' => $form_id,
											'label' => '',
											'count_elements' => count($elements),
											'elements' => []
										];

										if (!empty($elements)) {
											$form['label'] = $this->translations[$elements[0]['form_label']] ?? Text::_($elements[0]['form_label']);

											$query->clear()
												->select('*')
												->from($db->quoteName($elements[0]['db_table_name']))
												->where($db->quoteName('fnum') . ' = ' . $db->quote($item->fnum));
											$db->setQuery($query);
											$row = $db->loadObject();

											foreach ($elements as $element) {
												$value = \EmundusHelperFabrik::formatElementValue($element['name'], $row->{$element['name']}, $element['group_id']);
												if (!empty($value)) {
													if (in_array($element['plugin'], ['radiobutton', 'dropdown', 'checkbox']) && isset($this->translations[$value])) {
														$value = $this->translations[$value];
													}
												}

												$form['elements'][] = [
													'id' => $element['id'],
													'name' => $element['name'],
													'label' => $this->translations[$element['label']] ?? Text::_($element['label']),
													'raw' => $row->{$element['name']} ?? '',
													'value' => $value
												];
											}
										}

										$forms[] = $form;
									}
								}

								// get the form_ids and then the elements
								$item->steps[] = [
									'id' => $profile->id,
									'label' => $profile->label,
									'forms' => $forms
								];
							}
						}
					}
				} catch (\Exception $e) {
					$app->enqueueMessage($e->getMessage(), 'error');
				}
			}
		}

		return $item;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \Joomla\CMS\Form\Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		return false;
	}

	/**
	 * @param   string  $code
	 *
	 * @return void
	 */
	private function loadOverrideTranslations(string $code = 'fr-FR'): void
	{
		$file = JPATH_ROOT . '/language/overrides/'. $code .'.override.ini';
		if (file_exists($file)) {
			$this->translations = parse_ini_file($file);
		}
	}
}