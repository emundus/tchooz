<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2016 eMundus. All rights reserved.
 * @license     GNU/GPL
 * @author      Benjamin Rivalland
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Uri\Uri;
use Tchooz\Factories\Language\LanguageFactory;
use Tchooz\Repositories\Language\LanguageRepository;
use Tchooz\Services\Language\DbLanguage;
use Tchooz\Services\Language\ObjectsRegistry;

/**
 * campaign Controller
 *
 * @package    Joomla
 * @subpackage eMundus
 * @since      5.0.0
 */
class EmundusControllerTranslations extends BaseController
{

	protected $app;

	private $model;

	private LanguageRepository $languageRepository;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JController
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'translations.php');

		$this->app   = Factory::getApplication();
		$this->model = $this->getModel('Translations');

		$this->languageRepository = new LanguageRepository();
	}

	public function getdefaultlanguage(): void
	{
		$user = $this->app->getIdentity();
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}

		echo json_encode($this->languageRepository->getDefaultLanguage());
		exit;
	}

	public function getlanguages(): void
	{
		$user = $this->app->getIdentity();
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}

		$result = $this->languageRepository->getLanguages();

		echo json_encode($result);
		exit;
	}

	public function updatelanguage(): void
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}

		$published = $this->input->getInt('published', 1);
		$lang_code = $this->input->getString('lang_code', null);
		$default   = $this->input->getInt('default_lang', 0);

		$result              = $this->languageRepository->updateContentLanguage($lang_code, $published, $default);
		$default_language    = $this->languageRepository->getDefaultLanguage();
		$secondary_languages = $this->languageRepository->getPlatformLanguages();
		foreach ($secondary_languages as $key => $language)
		{
			if ($default_language->lang_code == $language)
			{
				unset($secondary_languages[$key]);
			}
		}

		if (empty($secondary_languages))
		{
			$this->languageRepository->updateFalangModule(0);
		}
		else
		{
			$this->languageRepository->updateFalangModule(1);
		}

		echo json_encode($result);
		exit;
	}

	public function gettranslationsobjects(): void
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}

		echo json_encode(LanguageFactory::getTranslationsObjects());
		exit;
	}

	public function getdatas()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}


		$table   = $this->input->get->getString('table', '');
		$filters = $this->input->get->getString('filters', '');
		if (!empty($filters))
		{
			$filters = explode(',', $filters);
		}
		else
		{
			$filters = [];
		}

		$result = [];

		$objectsRegistry = new ObjectsRegistry();
		$object          = $objectsRegistry->getObjectByType($table);
		if ($object)
		{
			$result = $object->getDatas($filters);
		}

		echo json_encode($result);
		exit;
	}

	public function getchildrens()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}


		$table        = $this->input->get->getString('table', '');
		$reference_id = $this->input->get->getInt('reference_id', '');
		$label        = $this->input->get->getString('label', '');
		$parent_table = $this->input->get->getString('parent_table', '');

		$result = [];

		$objectsRegistry = new ObjectsRegistry();
		$object          = $objectsRegistry->getObjectByType($parent_table);
		if ($object && method_exists($object, 'getChildrens'))
		{
			$result = $object->getChildrens($table, $reference_id, $label, $parent_table);
		}

		echo json_encode($result);
		exit;
	}

	public function gettranslations()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}

		$default_lang     = $this->input->get->getString('default_lang', null);
		$lang_to          = $this->input->get->getString('lang_to', null);
		$references_table = $this->input->get->get('reference_table', null);
		$reference_id     = $this->input->get->getString('reference_id', null);
		$parent_table     = $this->input->get->getString('parent_table', null);

		$objectsRegistry = new ObjectsRegistry();
		$object          = $objectsRegistry->getObjectByType($parent_table);

		$translations = array();

		foreach ($references_table as $reference_table)
		{
			if (method_exists($object, 'getJoinReferenceId') && !empty($reference_table['join_table']) && !empty($reference_table['join_column']) && !empty($reference_table['reference_column']))
			{
				$join_reference_id = $object->getJoinReferenceId($reference_table['table'], $reference_table['reference_column'], $reference_table['join_table'], $reference_table['join_column'], $reference_id);

				if (!empty($join_reference_id))
				{
					$reference_id = $join_reference_id;
				}
			}

			$filters = [
				'type'             => 'override',
				'reference_table'  => $reference_table['table'],
				'reference_id'     => $reference_id,
				'reference_fields' => $reference_table['fields'],
				'lang_code'        => 'all'
			];

			$results = $this->languageRepository->getAll($filters, false);

			foreach ($results as $result)
			{
				if (!empty($translations[$result->getReferenceId()]) && in_array($result->getTag(), array_keys($translations[$result->getReferenceId()])))
				{
					if ($result->getLangCode() == $default_lang)
					{
						$translations[$result->getReferenceId()][$result->getTag()]->default_lang = $result->getOverride();
					}
					elseif ($result->getLangCode() == $lang_to)
					{
						$translations[$result->getReferenceId()][$result->getTag()]->lang_to = $result->getOverride();
					}
				}
				else
				{
					$translation = $result->toObject();

					if ($result->getLangCode() == $default_lang)
					{
						$translation->default_lang = $result->getOverride();
					}
					elseif ($result->getLangCode() == $lang_to)
					{
						$translation->lang_to = $result->getOverride();
					}
					$translations[$result->getReferenceId()][$result->getTag()] = $translation;
				}
			}
		}

		echo json_encode($translations);
		exit;
	}

	public function inserttranslation()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}

		$override        = $this->input->getString('value', '');
		$lang_to         = $this->input->getString('lang_to', '');
		$reference_table = $this->input->getString('reference_table', '');
		$reference_id    = $this->input->getInt('reference_id', 0);
		$tag             = $this->input->getString('tag', '');

		// Sanitize override to avoid XSS
		if (!class_exists('HtmlSanitizerSingleton'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
		}
		$htmlSanitizer = HtmlSanitizerSingleton::getInstance();
		$override      = $htmlSanitizer->sanitize($override);

		$result = LanguageFactory::translate($tag, [$lang_to => $override], $reference_table, $reference_id);

		echo json_encode($result);
		exit;
	}

	public function updatetranslation()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}

		$override        = $this->input->getRaw('value', null);
		$lang_to         = $this->input->getString('lang_to', null);
		$reference_table = $this->input->getString('reference_table', null);
		$reference_id    = $this->input->getInt('reference_id', 0);
		$reference_field = $this->input->getString('reference_field', null);
		$tag             = $this->input->getString('tag', null);

		// Sanitize override to avoid XSS
		if (!class_exists('HtmlSanitizerSingleton'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
		}
		$htmlSanitizer = HtmlSanitizerSingleton::getInstance();
		$override      = $htmlSanitizer->sanitize($override);

		$result = LanguageFactory::translate($tag, [$lang_to => $override], $reference_table, $reference_id, $reference_field);

		echo json_encode($result);
		exit;
	}

	public function getfalangtranslations()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED')];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$default_lang    = $this->input->get->getString('default_lang', null);
			$lang_to         = $this->input->get->getString('lang_to', null);
			$reference_table = $this->input->get->getString('reference_table', null);
			$reference_id    = $this->input->get->getString('reference_id', null);
			$fields          = $this->input->get->getString('fields', null);

			$translation = $this->model->getTranslationsFalang($default_lang, $lang_to, $reference_id, $fields, $reference_table);

			if (!empty($translation))
			{
				$response = ['status' => true, 'message' => Text::_('SUCCESS'), 'data' => $translation];
			}
			else
			{
				$response['message'] = Text::_('NO_TRANSLATION_FOUND');
			}
		}

		echo json_encode($response);
		exit;
	}

	public function updatefalangtranslation()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED')];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$value           = $this->input->getRaw('value', null);
			$lang_to         = $this->input->getString('lang_to', null);
			$reference_table = $this->input->getString('reference_table', null);
			$reference_id    = $this->input->getInt('reference_id', 0);
			$field           = $this->input->getString('field', null);

			$updated = $this->model->updateFalangTranslation($value, $lang_to, $reference_table, $reference_id, $field, $user->id);

			if ($updated)
			{
				$response['status']  = true;
				$response['message'] = Text::_('COM_EMUNDUS_TRANSLATION_UPDATED');
			}
			else
			{
				$response['message'] = Text::_('COM_EMUNDUS_TRANSLATION_NOT_UPDATED');
			}
		}

		echo json_encode($response);
		exit;
	}

	public function getorphelins()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}

		$default_lang = $this->input->getString('default_lang', '');
		$lang_to      = $this->input->getString('lang_to', '');

		$result = $this->languageRepository->getOrphans($default_lang, $lang_to);

		echo json_encode($result);
		exit;
	}

	public function sendpurposenewlanguage()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}

		$language = $this->input->getString('suggest_language', null);
		$comment  = $this->input->getString('comment', null);

		if (!class_exists('EmundusHelperEmails'))
		{
			include_once(JPATH_SITE . '/components/com_emundus/helpers/emails.php');
		}
		if (!class_exists('EmundusModelEmails'))
		{
			include_once(JPATH_SITE . '/components/com_emundus/models/emails.php');
		}
		$m_emails = new EmundusModelEmails();

		$config = Factory::getApplication()->getConfig();

		$post = [
			'SITE_NAME'      => $config->get('sitename'),
			'SITE_URL'       => Uri::base(),
			'LANGUAGE_FIELD' => $language,
			'LOGO'           => EmundusHelperEmails::getLogo()
		];

		$result = $m_emails->sendEmailNoFnum('support@emundus.fr', 'installation_new_language', $post);

		echo json_encode($result);
		exit;
	}

	public function export()
	{
		$user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}

		$profile         = $this->input->getString('profile', null);
		$reference_table = $this->input->getString('reference_table', null);

		$objectsRegistry = new ObjectsRegistry();
		$object          = $objectsRegistry->getObjectByType($reference_table);
		if ($object && method_exists($object, 'getTablesToExport') && method_exists($object, 'getReferencesIds'))
		{
			$languages = $this->languageRepository->getPlatformLanguages();

			$tables_to_export = $object->getTablesToExport();
			$reference_ids    = $object->getReferencesIds($profile);

			$results = array();
			foreach ($tables_to_export as $table)
			{
				$filters = [
					'type'            => 'override',
					'reference_table' => $table,
					'reference_id'    => $reference_ids,
					'lang_code'       => 'all'
				];

				$results = array_merge($this->languageRepository->getAll($filters, false), $results);
			}

			$results_to_export = array();
			foreach ($results as $result)
			{
				$results_to_export[$result->getTag()][0]                      = $result->getTag();
				$results_to_export[$result->getTag()][$result->getLangCode()] = $result->getOverride();
				foreach ($languages as $language)
				{
					if (!isset($results_to_export[$result->getTag()][$language]))
					{
						$results_to_export[$result->getTag()][$language] = '';
					}
				}
				ksort($results_to_export[$result->getTag()]);
			}

			$filename = 'export_translation_' . date('Y-m-d H:i') . '.csv';
			$path     = JPATH_SITE . '/tmp/' . $filename;
			$f        = fopen($path, 'w');

			// Manage UTF-8 in Excel
			fputs($f, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

			$header = array(Text::_('COM_EMUNDUS_ONBOARD_TRANSLATION_TAG_EXPORT'));
			foreach ($languages as $language)
			{
				$header[] = $language;
			}
			fputcsv($f, $header, ';');

			foreach ($results_to_export as $line)
			{
				// generate csv lines from the inner arrays
				fputcsv($f, (array) $line, ';');
			}
			// reset the file pointer to the start of the file
			fseek($f, 0);

			header('Content-type: text/csv');
			header('Content-Disposition: attachment; filename=' . $filename);
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header('Pragma: anytextexeptno-cache', true);
			header('Cache-control: private');
			header('Expires: 0');

			ob_clean();
			ob_end_flush();
			readfile($path);
		}
		else
		{
			die(Text::_('ACCESS_DENIED'));
		}

		exit;
	}

	public function reloadtranslations(): void
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED')];
		$user     = $this->app->getIdentity();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			die(Text::_('ACCESS_DENIED'));
		}

		$dbLanguage = new DbLanguage();
		$response   = $dbLanguage->databaseToFiles();

		echo json_encode($response);
		exit;
	}
}
