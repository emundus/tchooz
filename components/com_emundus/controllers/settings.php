<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2016 eMundus. All rights reserved.
 * @license     GNU/GPL
 * @author      James Dean
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use enshrined\svgSanitize\Sanitizer;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Config\Controller\ApplicationController;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use classes\SMS\Synchronizer\OvhSMS;

/**
 * Settings Controller
 *
 * @package    Joomla
 * @subpackage eMundus
 * @since      5.0.0
 */
class EmundusControllersettings extends BaseController
{

	protected $app;

	private $user;
	private $m_settings;

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
		$this->m_settings = $this->getModel('settings');
		$this->app        = Factory::getApplication();

		$this->user = $this->app->getIdentity();
	}

	public function getstatus()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{
			$status = $this->m_settings->getStatus();

			if (!empty($status))
			{
				$tab = array('status' => 1, 'msg' => JText::_('STATUS_RETRIEVED'), 'data' => $status);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => JText::_('ERROR_CANNOT_RETRIEVE_STATUS'), 'data' => $status);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function gettags()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];
		$user     = $this->user;

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$tags     = $this->m_settings->getTags();
			$response = ['status' => true, 'msg' => Text::_('TAGS_RETRIEVED'), 'data' => $tags];
		}

		echo json_encode($response);
		exit;
	}

	public function createtag()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{
			$changeresponse = $this->m_settings->createTag();
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function createstatus()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{
			$changeresponse = $this->m_settings->createStatus();
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function deletetag()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$id = $this->input->getInt('id');

			$changeresponse = $this->m_settings->deleteTag($id);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function deletestatus()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$id   = $this->input->getInt('id');
			$step = $this->input->getInt('step');

			$changeresponse = $this->m_settings->deleteStatus($id, $step);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function updatestatus()
	{
		$changeresponse = array('status' => 0, 'msg' => JText::_('ACCESS_DENIED'));
		$user           = $this->user;

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$status = $this->input->getInt('status');
			$label  = $this->input->getString('label');
			$color  = $this->input->getString('color');

			$changeresponse = $this->m_settings->updateStatus($status, $label, $color);
		}

		echo json_encode((object) $changeresponse);
		exit;
	}

	public function updatestatusorder()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$status = $this->input->getString('status');

			$changeresponse = $this->m_settings->updateStatusOrder(explode(',', $status));
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function updatetags()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$tag   = $this->input->getInt('tag');
			$label = $this->input->getString('label');
			$color = $this->input->getString('color');

			$changeresponse = $this->m_settings->updateTags($tag, $label, $color);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function getarticle()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$article_id    = $this->input->getString('article_id', 0);
			$article_alias = $this->input->getString('article_alias', '');
			$lang          = $this->input->getString('lang');
			$field         = $this->input->getString('field');

			$content = $this->m_settings->getArticle($lang, $article_id, $article_alias, $field);

			if (!empty($content))
			{
				$response = array('status' => 1, 'msg' => JText::_('ARTICLE_FIND'), 'data' => $content);
			}
			else
			{
				$response['msg'] = JText::_('ERROR_CANNOT_RETRIEVE_ARTICLE') . $article_id;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function updatearticle()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));
		$user     = $this->user;

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$content       = $this->input->getRaw('content');
			$article_id    = $this->input->getString('article_id', 0);
			$article_alias = $this->input->getString('article_alias', '');
			$lang          = $this->input->getString('lang');
			$field         = $this->input->getString('field');
			$note          = $this->input->getString('note');

			$response = $this->m_settings->updateArticle($content, $lang, $article_id, $article_alias, $field, $note);
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getAllArticleNeedToModify()
	{
		$params        = $this->m_settings->getArticleNeedToBeModify();
		$params['msg'] = JText::_('SUCCESS');
		echo json_encode($params);
		exit;
	}

	public function publisharticle()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$publish       = $this->input->getInt('publish', 1);
			$article_id    = $this->input->getString('article_id', 0);
			$article_alias = $this->input->getString('article_alias', '');

			$response = $this->m_settings->publishArticle($publish, $article_id, $article_alias);
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getfooterarticles()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{
			$content = $this->m_settings->getFooterArticles();

			if (!empty($content))
			{
				$tab = array('status' => 1, 'msg' => JText::_('FOOTER_RETRIEVED'), 'data' => $content);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => JText::_('ERROR_CANNOT_RETRIEVE_FOOTER'), 'data' => $content);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function updatefooter()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$col1 = $this->input->getRaw('col1');
			$col2 = $this->input->getRaw('col2');

			$changeresponse = $this->m_settings->updateFooter($col1, $col2);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function getlogo()
	{
		require_once JPATH_ROOT . '/components/com_emundus/helpers/emails.php';
		$logo = EmundusHelperEmails::getLogo();

		$filename = '';
		if (!empty($logo))
		{
			$logo_path = explode('/', $logo);
			$filename  = $logo_path[count($logo_path) - 1];
		}

		$tab = array('status' => 1, 'msg' => JText::_('LOGO_FOUND'), 'filename' => $filename);

		echo json_encode((object) $tab);
		exit;
	}

	public function getfavicon()
	{
		$favicon = $this->m_settings->getFavicon();

		$tab = array('status' => 1, 'msg' => JText::_('FAVICON_FOUND'), 'filename' => $favicon);

		echo json_encode((object) $tab);
		exit;
	}

	public function updatelogo()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{
			$image    = $this->input->files->get('file');
			$old_logo = EmundusHelperEmails::getLogo(true);

			if (!empty($image))
			{
				$target_dir = 'images/custom/';
				$ext        = pathinfo($image['name'], PATHINFO_EXTENSION);
				if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'gif', 'webp']))
				{
					if (!empty($old_logo))
					{
						unlink($target_dir . $old_logo);
					}

					$target_file = $target_dir . basename('logo_custom.' . $ext);

					$updated = $this->m_settings->updateLogo($target_file, $image["tmp_name"], $ext);

					if ($updated)
					{
						$cache = JCache::getInstance('callback');
						$cache->clean(null, 'notgroup');

						$tab = array('status' => 1, 'msg' => JText::_('LOGO_UPDATED'), 'filename' => 'logo_custom.' . $ext, 'old_logo' => $old_logo);
					}
					else
					{
						$tab = array('status' => 0, 'msg' => JText::_('LOGO_NOT_UPDATED'), 'filename' => '');
					}
				}
				else
				{
					$tab = array('status' => 0, 'msg' => JText::_('LOGO_NOT_UPDATED'), 'filename' => '');
				}
			}
			else
			{
				$tab = array('status' => 0, 'msg' => JText::_('LOGO_NOT_UPDATED'), 'filename' => '');
			}
			echo json_encode((object) $tab);
			exit;
		}
	}

	public function updateicon()
	{
		$result = ['status' => 0, 'msg' => Text::_('ACCESS_DENIED'), 'filename' => '', 'old_favicon' => ''];
		$user   = $this->user;

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$image = $this->input->files->get('file');

			if (isset($image))
			{
				$ext         = pathinfo($image['name'], PATHINFO_EXTENSION);
				$target_dir  = "images/custom/";
				$filename    = 'favicon';
				$old_favicon = glob("{$target_dir}{$filename}.*");

				if (!empty($old_favicon))
				{
					unlink($old_favicon[0]);
				}

				$target_file = $target_dir . basename('favicon.' . $ext);

				if (move_uploaded_file($image["tmp_name"], $target_file))
				{
					require_once(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'update.php');

					EmundusHelperUpdate::updateYamlVariable('favicon', 'gantry-media://custom/favicon.' . $ext, JPATH_ROOT . '/templates/g5_helium/custom/config/default/page/assets.yaml');

					$cache = JCache::getInstance('callback');
					$cache->clean(null, 'notgroup');

					$result['status']      = 1;
					$result['msg']         = Text::_('ICON_UPDATED');
					$result['filename']    = 'favicon.' . $ext;
					$result['old_favicon'] = $old_favicon[0];
				}
				else
				{
					$resul['msg'] = Text::_('ICON_NOT_UPDATED');
				}
			}
			else
			{
				$result['msg'] = Text::_('ICON_NOT_UPDATED');
			}
		}

		echo json_encode((object) $result);
		exit;
	}

	public function removeicon()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{
			$target_dir = "images/custom/";
			unlink($target_dir . 'favicon.png');

			$tab = array('status' => 1, 'msg' => JText::_('ICON_REMOVED'));

			echo json_encode((object) $tab);
			exit;
		}
	}

	public function updatehomebackground()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{

			$image = $this->input->files->get('file');

			if (isset($image))
			{
				$target_dir = "images/custom/";
				unlink($target_dir . 'home_background.png');

				$target_file = $target_dir . basename('home_background.png');

				if (move_uploaded_file($image["tmp_name"], $target_file))
				{
					$tab = array('status' => 1, 'msg' => JText::_('BACKGROUND_UPDATED'));
				}
				else
				{
					$tab = array('status' => 0, 'msg' => JText::_('BACKGROUND_NOT_UPDATED'));
				}
			}
			else
			{
				$tab = array('status' => 0, 'msg' => JText::_('BACKGROUND_NOT_UPDATED'));
			}
			echo json_encode((object) $tab);
			exit;
		}
	}

	public function getbackgroundoption()
	{
		$user  = $this->user;
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{
			try
			{
				$query->select('published,content')
					->from($db->quoteName('#__modules'))
					->where($db->quoteName('module') . ' LIKE ' . $db->quote('mod_emundus_custom'))
					->andWhere($db->quoteName('title') . ' LIKE ' . $db->quote('Homepage background'));

				$db->setQuery($query);
				$module    = $db->loadObject();
				$published = $module->published;
				$content   = $module->content;

				$tab = array('status' => 0, 'msg' => 'success', 'data' => $published, 'content' => $content);
			}
			catch (Exception $e)
			{
				$tab = array('status' => 0, 'msg' => $e->getMessage(), 'data' => null);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function updatebackgroundmodule()
	{
		$user  = $this->user;
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$published = $this->input->getInt('published');

			try
			{
				$query->update($db->quoteName('#__modules'))
					->set($db->quoteName('published') . ' = ' . $db->quote($published))
					->where($db->quoteName('module') . ' LIKE ' . $db->quote('mod_emundus_custom'))
					->andWhere($db->quoteName('title') . ' LIKE ' . $db->quote('Homepage background'));

				$db->setQuery($query);
				$state = $db->execute();

				$tab = array('status' => 0, 'msg' => 'success', 'data' => $state);
			}
			catch (Exception $e)
			{
				$tab = array('status' => 0, 'msg' => $e->getMessage(), 'data' => null);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getappcolors()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{
			$yaml = \Symfony\Component\Yaml\Yaml::parse(file_get_contents('templates/g5_helium/custom/config/default/styles.yaml'));

			$primary   = $yaml['base']['primary-color'];
			$secondary = $yaml['base']['secondary-color'];
			$tab       = array('status' => '1', 'msg' => JText::_("SUCCESS"), 'primary' => $primary, 'secondary' => $secondary);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function updatecolor()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result = 0;
			$tab    = array('status' => '0', 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{

			$preset = $this->input->getRaw('preset');
			if (!empty($preset))
			{
				$preset = json_decode($preset, true);
			}
			else
			{
				$preset = array('primary' => '#000000', 'secondary' => '#000000');
			}

			$yaml = \Symfony\Component\Yaml\Yaml::parse(file_get_contents('templates/g5_helium/custom/config/default/styles.yaml'));

			$yaml['base']['primary-color']   = $preset['primary'];
			$yaml['accent']['color-1']       = $preset['primary'];
			$yaml['base']['secondary-color'] = $preset['secondary'];
			$yaml['accent']['color-2']       = $preset['secondary'];
			$yaml['link']['regular']         = $preset['secondary'];
			$yaml['link']['hover']           = $preset['secondary'];

			$new_yaml = \Symfony\Component\Yaml\Yaml::dump($yaml, 5);

			file_put_contents('templates/g5_helium/custom/config/default/styles.yaml', $new_yaml);

			// Recompile Gantry5 css at each update
			$dir = JPATH_BASE . '/templates/g5_helium/custom/css-compiled';
			if (!empty($dir))
			{
				foreach (glob($dir . '/*') as $file)
				{
					unlink($file);
				}

				rmdir($dir);
			}

			$tab = array('status' => '1', 'msg' => JText::_("SUCCESS"));
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getdatasfromtable()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result   = 0;
			$response = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$dbtable = $this->input->getString('db');

			$datas    = $this->m_settings->getDatasFromTable($dbtable);
			$response = array('status' => '1', 'msg' => 'SUCCESS', 'data' => $datas);
		}
		echo json_encode((object) $response);
		exit;
	}

	public function savedatas()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result   = 0;
			$response = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$form = $this->input->getRaw('form');

			$state    = $this->m_settings->saveDatas($form);
			$response = array('status' => $state, 'msg' => 'SUCCESS');
		}
		echo json_encode((object) $response);
		exit;
	}

	public function saveimporteddatas()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result   = 0;
			$response = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$form  = $this->input->getRaw('form');
			$datas = $this->input->getRaw('datas');

			$state    = $this->m_settings->saveImportedDatas($form, $datas);
			$response = array('status' => $state, 'msg' => 'SUCCESS');
		}
		echo json_encode((object) $response);
		exit;
	}

	public function unlockuser()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result   = 0;
			$response = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$user_id = $this->input->getInt('user');

			$state    = $this->m_settings->unlockUser($user_id);
			$response = array('status' => $state, 'msg' => 'SUCCESS');
		}
		echo json_encode((object) $response);
		exit;
	}

	public function lockuser()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result   = 0;
			$response = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$user_id = $this->input->getInt('user');

			$state    = $this->m_settings->lockUser($user_id);
			$response = array('status' => $state, 'msg' => 'SUCCESS');
		}
		echo json_encode((object) $response);
		exit;
	}

	public function checkfirstdatabasejoin()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result   = 0;
			$response = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$state    = $this->m_settings->checkFirstDatabaseJoin($user->id);
			$response = array('status' => $state, 'msg' => 'SUCCESS');
		}
		echo json_encode((object) $response);
		exit;
	}

	public function removeparam()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result   = 0;
			$response = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$param = $this->input->getString('param');

			$state    = $this->m_settings->removeParam($param, $user->id);
			$response = array('status' => $state, 'msg' => 'SUCCESS');
		}
		echo json_encode((object) $response);
		exit;
	}

	public function redirectjroute()
	{
		$current_link = $this->input->getString('link');
		$language     = $this->input->getString('redirect_language', 'fr-FR');

		$options_to_set = [];
		$segments       = explode('?', $current_link);
		$segments       = explode('&', $segments[1]);

		$exceptions = ['view', 'layout', 'option', 'format', 'formid'];
		foreach ($segments as $key => $segment)
		{
			$segment = explode('=', $segment);

			if (!in_array($segment[0], $exceptions))
			{
				$options_to_set[$segment[0]] = $segment[1];
				unset($segments[$key]);
			}
		}
		$link = 'index.php?' . implode('&', $segments);

		$response = array('status' => true, 'msg' => 'SUCCESS', 'data' => $current_link);

		$menu = Factory::getApplication()->getMenu()->getItems('link', $link, true);

		if (!empty($menu))
		{
			$languages = LanguageHelper::getLanguages('lang_code');
			$sef       = '';
			if (isset($languages[$language]))
			{
				$sef = $languages[$language]->sef;
			}
			$response['data'] = !empty($sef) ? $sef . '/' . $menu->route : $menu->route;

			if (!empty($options_to_set))
			{
				$response['data'] .= '?' . http_build_query($options_to_set);
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function geteditorvariables()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result   = 0;
			$response = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{


			$datas    = $this->m_settings->getEditorVariables();
			$response = array('status' => '1', 'msg' => 'SUCCESS', 'data' => $datas);
		}
		echo json_encode((object) $response);
		exit;
	}

	public function getactivelanguages()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result   = 0;
			$response = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{
			$datas = JLanguageHelper::getLanguages();
			usort($datas, function ($a, $b) {
				return (int) $a->lang_id > (int) $b->lang_id ? 1 : -1;
			});
			$response = array('status' => '1', 'msg' => 'SUCCESS', 'data' => $datas);
		}
		echo json_encode((object) $response);
		exit;
	}

	public function uploadimages()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else
		{

			$image = $this->input->files->get('file');

			if (isset($image))
			{
				$config   = JFactory::getConfig();
				$sitename = strtolower(str_replace(array('\\', '=', '&', ',', '#', '_', '*', ';', '!', '?', ':', '+', '$', '\'', ' ', '£', ')', '(', '@', '%'), '_', $config->get('sitename')));

				$path = $image["name"];
				$ext  = pathinfo($path, PATHINFO_EXTENSION);

				$target_dir = "images/custom/" . $sitename . "/";
				if (!file_exists($target_dir))
				{
					mkdir($target_dir);
				}

				do
				{
					$target_file = $target_dir . rand(1000, 90000) . '.' . $ext;
				} while (file_exists($target_file));

				if (move_uploaded_file($image["tmp_name"], $target_file))
				{
					echo json_encode(array('location' => $target_file));
				}
				else
				{
					echo json_encode(array('msg' => 'ERROR WHILE UPLOADING YOUR IMAGE'));
				}
			}
			else
			{
				echo json_encode(array('msg' => 'ERROR WHILE UPLOADING YOUR IMAGE'));
			}
			exit;
		}
	}

	public function gettasks()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result = 0;
			echo json_encode(array('status' => $result, 'msg' => JText::_("ACCESS_DENIED")));
		}
		else
		{
			$table = JTable::getInstance('user', 'JTable');
			$table->load($user->id);

			// Check if the param exists but is false, this avoids accidetally resetting a param.
			$params = $user->getParameters();
			echo json_encode(array('params' => $params));
		}
		exit;
	}

	public function uploaddropfiledoc()
	{
		$response = array('status' => 0, 'msg' => JText::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			//require_once(JPATH_ROOT . '/components/com_emundus/models/campaign.php');
			$m_campaign = $this->getModel('Campaign');

			$file = $this->input->files->get('file');
			$cid  = $this->input->get('cid');

			if (isset($file))
			{
				$campaign_category = $m_campaign->getCampaignCategory($cid);

				$path     = $file['name'];
				$ext      = pathinfo($path, PATHINFO_EXTENSION);
				$filename = pathinfo($path, PATHINFO_FILENAME);

				if (!file_exists('media/com_dropfiles') || !is_dir('media/com_dropfiles'))
				{
					mkdir('media/com_dropfiles');
				}
				$target_dir = "media/com_dropfiles/$campaign_category/";
				if (!file_exists($target_dir))
				{
					$created = mkdir($target_dir);
				}

				if (!file_exists($target_dir))
				{
					$response['msg'] = 'Error while trying to create the dropbox folder.';
				}
				else
				{
					do
					{
						$target_file = $target_dir . rand(1000, 90000) . '.' . $ext;
					} while (file_exists($target_file));

					if (move_uploaded_file($file['tmp_name'], $target_file))
					{
						$did      = $this->m_settings->moveUploadedFileToDropbox(pathinfo($target_file, PATHINFO_BASENAME), $filename, $ext, $campaign_category, filesize($target_file));
						$response = $m_campaign->getDropfileDocument($did);
					}
					else
					{
						$response['msg'] = 'Error while trying to move the file to the dropbox folder. File ' . $file['name'] . ' not uploaded to ' . $target_file . '.';
					}
				}
			}
			else
			{
				$response['msg'] = 'Missing file';
			}
		}

		echo json_encode($response);
		exit;
	}

	public function getemundusparams()
	{
		$params = ['emundus' => [], 'joomla' => [], 'msg' => JText::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$params        = $this->m_settings->getEmundusParams();
			$params['msg'] = JText::_('SUCCESS');
		}

		echo json_encode($params);
		exit;
	}

	public function updateemundusparam()
	{
		$user     = Factory::getApplication()->getIdentity();
		$response = ['status' => false, 'msg' => JText::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$response['msg'] = JText::_('MISSING_PARAMS');
			$jinput          = Factory::getApplication()->input;
			$component       = $jinput->getString('component');
			$param           = $jinput->getString('param');
			$value           = $jinput->getString('value', null);

			if (!empty($param) && isset($value))
			{
				$config = new JConfig();
				if ($this->m_settings->updateEmundusParam($component, $param, $value, $config))
				{
					$response['msg']    = Text::_('SUCCESS');
					$response['status'] = true;

					if ($param === 'list_limit')
					{
						$this->app->getSession()->set('limit', $value);
					}
				}
				else
				{
					$response['msg'] = JText::_('PARAM_NOT_UPDATED');
				}
			}
		}

		echo json_encode($response);
		exit;
	}

	public function updateemundusparams()
	{
		$user     = Factory::getApplication()->getIdentity();
		$response = ['status' => true, 'msg' => ''];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$params = $this->input->getRaw('params');
			if (!empty($params))
			{
				$config = new JConfig();
				foreach ($params as $param)
				{
					$param = json_decode($param);
					if ($this->m_settings->updateEmundusParam($param->component, $param->param, $param->value, $config))
					{
						$response['msg'] .= JText::_('SUCCESS') . ' for ' . $param->param . $param->value . '. ';


						if ($param === 'list_limit')
						{
							$this->app->getSession()->set('limit', $param->value);
						}

					}
					else
					{
						$response['msg']    .= JText::_('PARAM_NOT_UPDATED') . ' for ' . $param->param . '. ';
						$response['status'] = false;
					}
				}
			}
		}
		else
		{
			$response['status'] = false;
			$response['msg']    = JText::_('ACCESS_DENIED');
		}

		echo json_encode($response);
		exit;
	}

	public function getemailparameters()
	{
		$response = ['status' => false, 'msg' => '', 'data' => []];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$parameters         = $this->m_settings->getEmailParameters();
			$response['status'] = true;
			$response['msg']    = Text::_('SUCCESS');
			$response['data']   = $parameters;
		}
		else
		{
			$response['msg'] = Text::_('ACCESS_DENIED');
		}

		echo json_encode($response);
		exit;
	}

	public function testemail()
	{
		$response = ['status' => false, 'title' => Text::_('ACCESS_DENIED'), 'text' => '', 'desc' => ''];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$emConfig = ComponentHelper::getParams('com_emundus');

			$config              = [];
			$mail_to             = $this->input->getString('testing_email', '');
			$custom_email_config = $this->input->getInt('custom_email_conf', 0);
			if ($custom_email_config != 1)
			{
				// We get default email configuration
				$config['smtpauth']   = $emConfig->get('default_email_smtpauth',$this->app->get('smtpauth', 0));
				$config['smtphost']   = $emConfig->get('default_email_smtphost',$this->app->get('smtphost', ''));
				$config['smtpuser']   = $emConfig->get('default_email_smtpuser',$this->app->get('smtpuser', ''));
				$config['smtppass']   = $emConfig->get('default_email_smtppass',$this->app->get('smtppass', ''));
				$config['smtpsecure'] = $emConfig->get('default_email_smtpsecure',$this->app->get('smtpsecure', ''));
				$config['smtpport']   = $emConfig->get('default_email_smtpport',$this->app->get('smtpport', ''));
				$config['mailfrom']   = $this->input->getString('default_email_mailfrom', $emConfig->get('default_email_smtpport',$this->app->get('mailfrom', '')));
				$config['fromname']   = $emConfig->get('default_email_fromname',$this->app->get('fromname', ''));
			}
			else
			{
				// We get custom email configuration
				$config['smtpauth']   = $this->input->getString('custom_email_smtpauth', 0);
				$config['smtphost']   = $this->input->getString('custom_email_smtphost', '');
				$config['smtpuser']   = $this->input->getString('custom_email_smtpuser', '');
				$config['smtppass']   = $this->input->getString('custom_email_smtppass', '');
				$config['smtpsecure'] = $this->input->getString('custom_email_smtpsecure', '');
				$config['smtpport']   = $this->input->getInt('custom_email_smtpport', '');
				$config['mailfrom']   = $this->input->getString('custom_email_mailfrom', '');
				$config['fromname']   = $this->app->get('fromname', '');

				if(empty($config['smtppass']) || $config['smtppass'] == '************') {
					$config['smtppass'] = $emConfig->get('custom_email_smtppass',$this->app->get('smtppass', ''));
				}

				if (empty($config['smtphost']))
				{
					$response['title'] = Text::_('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_ERROR');
					$response['text']  = Text::_('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_ERROR_SMTPHOST');
					echo json_encode($response);
					exit;
				}

				if (empty($config['mailfrom']))
				{
					$response['title'] = Text::_('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_ERROR');
					$response['text']  = Text::_('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_ERROR_MAILFROM');
					echo json_encode($response);
					exit;
				}
			}

			$config['replyto']     = $this->input->getString('replyto', $this->app->get('replyto', ''));
			$config['replytoname'] = $this->input->getString('replytoname', $this->app->get('replytoname', ''));
			$config['mailer']      = $this->app->get('mailer', 'smtp');

			$model    = $this->getModel('settings', 'EmundusModel');
			$response = $model->sendTestMailSettings($config, $this->user, $mail_to);
		}

		echo json_encode($response);
		exit;
	}

	public function saveemailparameters()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'desc' => ''];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$config = [];
			$oldConfig = [
				'smtpauth'   => $this->app->get('smtpauth', 0),
				'smtphost'   => $this->app->get('smtphost', ''),
				'smtpuser'   => $this->app->get('smtpuser', ''),
				'smtpsecure' => $this->app->get('smtpsecure', ''),
				'smtpport'   => $this->app->get('smtpport', ''),
				'mailfrom'   => $this->app->get('mailfrom', ''),
				'fromname'   => $this->app->get('fromname', ''),
				'replyto'    => $this->app->get('replyto', ''),
				'replytoname'=> $this->app->get('replytoname', ''),
				'mailer'     => $this->app->get('mailer', 'smtp')
			];

			$custom_email_config = $this->input->get('custom_email_conf', 0);
			if ($custom_email_config != 1)
			{
				$emConfig = ComponentHelper::getParams('com_emundus');
				// We get default email configuration
				$config['smtpauth']   = $emConfig->get('default_email_smtpauth',$this->app->get('smtpauth', 0));
				$config['smtphost']   = $emConfig->get('default_email_smtphost',$this->app->get('smtphost', ''));
				$config['smtpuser']   = $emConfig->get('default_email_smtpuser',$this->app->get('smtpuser', ''));
				$config['smtppass']   = $emConfig->get('default_email_smtppass',$this->app->get('smtppass', ''));
				$config['smtpsecure'] = $emConfig->get('default_email_smtpsecure',$this->app->get('smtpsecure', ''));
				$config['smtpport']   = $emConfig->get('default_email_smtpport',$this->app->get('smtpport', ''));
				$config['mailfrom']   = $this->input->getString('default_email_mailfrom', $emConfig->get('default_email_smtpport',$this->app->get('mailfrom', '')));
				$config['fromname']   = $emConfig->get('default_email_fromname',$this->app->get('fromname', ''));
			}
			else
			{
				// We get custom email configuration
				$config['smtpauth']   = $this->input->getString('custom_email_smtpauth', 0);
				$config['smtphost']   = $this->input->getString('custom_email_smtphost', '');
				$config['smtpuser']   = $this->input->getString('custom_email_smtpuser', '');
				$config['smtppass']   = $this->input->getString('custom_email_smtppass', '');
				$config['smtpsecure'] = $this->input->getString('custom_email_smtpsecure', '');
				$config['smtpport']   = $this->input->getInt('custom_email_smtpport', '');
				$config['mailfrom']   = $this->input->getString('custom_email_mailfrom', '');
				$config['fromname']   = $this->app->get('fromname', '');

				if (empty($config['smtphost']))
				{
					$response['title'] = Text::_('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_ERROR');
					$response['text']  = Text::_('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_ERROR_SMTPHOST');
					echo json_encode($response);
					exit;
				}

				if (empty($config['mailfrom']))
				{
					$response['title'] = Text::_('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_ERROR');
					$response['text']  = Text::_('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_ERROR_MAILFROM');
					echo json_encode($response);
					exit;
				}
			}

			$config['replyto']     = $this->input->getString('replyto', $this->app->get('replyto', ''));
			$config['replytoname'] = $this->input->getString('replytoname', $this->app->get('replytoname', ''));
			$config['mailer']      = $this->app->get('mailer', 'smtp');
			$config['mailonline']  = $this->input->getInt('mailonline', 1);

			$model    = $this->getModel('settings', 'EmundusModel');
			$response['status'] = $model->saveEmailParameters($config, $custom_email_config);
			if($response['status']) {
				$response['msg'] = Text::_('COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_UPDATED');
				$response['desc'] = Text::_('COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_UPDATED_DESC');

				unset($config['smtppass']);

				PluginHelper::importPlugin('actionlog'); // si event call event handler
				$dispatcher = Factory::getApplication()->getDispatcher();
				$onAfterUpdateConfiguration             = new GenericEvent(
					'onAfterUpdateConfiguration',
					// Datas to pass to the event
					['data' => $config, 'old_data' => $oldConfig, 'config' => $config, 'type' => 'email_updated', 'status' => 'done', 'context' => 'com_emundus.settings.email']
				);
				$dispatcher->dispatch('onAfterUpdateConfiguration', $onAfterUpdateConfiguration);
			}
		}

		echo json_encode($response);
		exit;
	}

	/// get all users
	public function getallusers()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$result = 0;
			echo json_encode(array('status' => $result, 'msg' => JText::_("ACCESS_DENIED")));
		}
		else
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			try
			{
				$query->clear()
					->select('#__users.*')
					->from($db->quoteName('#__users'));

				$db->setQuery($query);
				$users = $db->loadObjectList();
				echo json_encode(array('status' => true, 'users' => $users));
			}
			catch (Exception $e)
			{
				JLog::add('Cannot get all users ' . $e->getMessage(), JLog::ERROR, 'com_emundus');
				echo json_encode(array('status' => false));
			}
		}
		exit;
	}

	public function isexpiresdatedisplayed()
	{
		$eMConfig = JComponentHelper::getParams('com_emundus');

		echo json_encode(array('display_expires_date' => $eMConfig->get('display_expires_date')));
		exit;
	}

	public function uploadimagetocustomfolder()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asPartnerAccessLevel($user->id))
		{
			$result = 0;
			echo json_encode(array('status' => $result, 'msg' => JText::_("ACCESS_DENIED")));
		}
		else
		{


			$image = $this->input->files->get('image');

			if (isset($image))
			{
				$config = JFactory::getConfig();

				$path     = $image["name"];
				$ext      = pathinfo($path, PATHINFO_EXTENSION);
				$filename = pathinfo($path, PATHINFO_FILENAME);


				$target_root = "images/custom/";
				$target_dir  = $target_root . "editor/";
				if (!file_exists($target_root))
				{
					mkdir($target_root);
				}
				if (!file_exists($target_dir))
				{
					mkdir($target_dir);
				}

				do
				{
					$target_file = $target_dir . rand(1000, 90000) . '.' . $ext;
				} while (file_exists($target_file));

				if (move_uploaded_file($image["tmp_name"], $target_file))
				{
					$result = 1;
					echo json_encode(array('status' => $result, 'msg' => JText::_("UPLOAD_SUCCESS"), 'file' => $target_file));
				}
				else
				{
					echo json_encode(array('msg' => 'ERROR WHILE UPLOADING YOUR DOCUMENT'));
				}
			}
			else
			{
				echo json_encode(array('msg' => 'ERROR WHILE UPLOADING YOUR DOCUMENT'));
			}
			exit;
		}
	}

	public function getbanner()
	{
		$results       = [
			'status'   => true,
			'filename' => null,
		];
		$banner_module = $this->m_settings->getBannerModule();
		if (!empty($banner_module))
		{
			$params   = json_decode($banner_module);
			$filename = $params->mod_em_banner_image;
			if (empty($filename))
			{
				$filename = 'images/custom/default_banner.png';
			}

			$results['filename'] = $filename;
		}

		echo json_encode((object) $results);
		exit;
	}

	public function updatebanner()
	{
		$user = $this->user;

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$results['status'] = false;
			$results['msg']    = JText::_("ACCESS_DENIED");
		}
		else
		{

			$image = $this->input->files->get('file');

			if (isset($image))
			{
				$filename = 'images/custom/default_banner.png';
				unlink($filename);

				if (move_uploaded_file($image["tmp_name"], $filename))
				{
					$this->m_settings->updateBannerImage();
					$results['status'] = true;
					$results['msg']    = JText::_('BANNER_UPDATED');
				}
				else
				{
					$results['status'] = false;
					$results['msg']    = JText::_('BANNER_NOT_UPDATED');
				}
			}
			else
			{
				$results['status'] = false;
				$results['msg']    = JText::_('IMAGE_NOT_FOUND');
			}
		}

		echo json_encode((object) $results);
		exit;
	}

	public function getonboardinglists()
	{
		$user    = $this->user;
		$results = ['status' => false, 'msg' => JText::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$results['status'] = true;
			$results['msg']    = JText::_('ONBOARDING_LISTS');
			$results['data']   = $this->m_settings->getOnboardingLists();
		}

		echo json_encode((object) $results);
		exit;
	}

	public function getOffset()
	{
		// get input format, second, minutes or hours
		$format = $this->input->getString('format', 'hours');

		$config = $this->app->getConfig();
		$offset = $config->get('offset');

		$dateTZ = new DateTimeZone($offset);
		$date   = new DateTime('now', $dateTZ);
		$offset = $dateTZ->getOffset($date);
		if (!empty($offset))
		{
			if ($format == 'hours')
			{
				$offset = $offset / 3600;
			}
			elseif ($format == 'minutes')
			{
				$offset = $offset / 60;
			}
		}

		$results = ['status' => true, 'msg' => '', 'data' => $offset];

		echo json_encode((object) $results);
		exit;
	}

	public function getemailsender()
	{
		$config   = JFactory::getConfig();
		$mailfrom = $config->get('mailfrom');

		$results = ['status' => true, 'msg' => '', 'data' => $mailfrom];

		echo json_encode((object) $results);
		exit;
	}

	public function gethomearticle()
	{
		$results['status'] = true;
		$results['msg']    = 'Home article';
		$results['data']   = $this->m_settings->getHomeArticle();

		echo json_encode((object) $results);
		exit;
	}

	public function getrgpdarticles()
	{
		$results['status'] = true;
		$results['msg']    = 'RGPD Articles';
		$results['data']   = $this->m_settings->getRgpdArticles();

		echo json_encode((object) $results);
		exit;
	}

	public function gettimezonelist()
	{
		$results['status'] = true;
		$results['msg']    = 'Timezones retrieved';
		$results['data']   = [];
		$timezone_groups   = DateTimeZone::listAbbreviations();

		foreach ($timezone_groups as $timezone_group)
		{
			foreach ($timezone_group as $timezone)
			{
				if (!empty($timezone['timezone_id']) && $timezone['timezone_id'] != 'UTC' && !in_array($timezone['timezone_id'], array_keys($results['data'])))
				{
					$value = $timezone['timezone_id'];
					$label = $value . ' : +' . date('H:i', $timezone['offset']) . 'h UTC';

					$results['data'][$timezone['timezone_id']] = [
						"label" => $label,
						"value" => $value
					];
				}
			}
		}

		$results['data'] = array_values($results['data']);

		// Filter out cities that are all in uppercase
		$results['data'] = array_filter($results['data'], function ($data) {
			return $data['label'] !== strtoupper($data['label']);
		});

		if (empty($results['data']))
		{
			$results['status'] = false;
			$results['msg']    = 'No timezones found';
		}

		echo json_encode((object) $results);
		exit;
	}

	public function uploadmedia()
	{
		$result = ['status' => 0, 'msg' => Text::_('ACCESS_DENIED'), 'url' => ''];

		if (!$this->user->guest)
		{
			$file = $_FILES['file'];

			if (!file_exists('images/emundus/custom/'))
			{
				mkdir('images/emundus/custom/');
			}

			$target_dir = 'images/emundus/custom/media/';
			if (!file_exists($target_dir))
			{
				mkdir($target_dir);
			}

			$target_dir = $target_dir . '/' . $this->user->id . '/';
			if (!file_exists($target_dir))
			{
				mkdir($target_dir);
			}

			$target_file = $target_dir . basename($file['name']);

			// Check if extension is allowed (images onyl)
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mtype = finfo_file($finfo, $file['tmp_name']);
			finfo_close($finfo);

			// If svg we have to sanitize it
			if ($mtype == 'image/svg+xml')
			{
				$sanitizer = new Sanitizer();

				$svg_file    = file_get_contents($file['tmp_name']);
				$cleaned_svg = $sanitizer->sanitize($svg_file);

				file_put_contents($file['tmp_name'], $cleaned_svg);
			}

			// Remove exif data from jpeg files
			if ($mtype == 'image/jpeg')
			{
				$img = imagecreatefromjpeg($file['tmp_name']);
				imagejpeg($img, $file['tmp_name'], 100);
				imagedestroy($img);
			}

			$allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
			$ext     = pathinfo($target_file, PATHINFO_EXTENSION);
			if (in_array($ext, $allowed))
			{
				if (move_uploaded_file($file['tmp_name'], $target_file))
				{
					$result['status'] = 1;
					$result['msg']    = Text::_('UPLOAD_SUCCESS');
					$result['url']    = '/' . $target_file;
				}
				else
				{
					$result['msg'] = Text::_('UPLOAD_FAILED');
				}
			}
			else
			{
				$result['msg'] = Text::_('INVALID_EXTENSION');
			}
		}

		echo json_encode((object) $result);
		exit;
	}

	public function getmedia()
	{
		$result = ['status' => 0, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (!$this->user->guest)
		{
			$target_dir = 'images/emundus/custom/media/' . $this->user->id . '/';
			$files      = glob($target_dir . '*');

			if (!empty($files))
			{
				$result['status'] = 1;
				$result['msg']    = Text::_('MEDIA_FOUND');

				foreach ($files as $file)
				{
					$media = new stdClass();
					$ext   = pathinfo($file, PATHINFO_EXTENSION);
					if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg']))
					{
						$media->url  = Uri::base() . $file;
						$media->name = pathinfo($file, PATHINFO_BASENAME);
						$media->type = 'image';
						$media->size = filesize($file);

						$result['data'][] = $media;
					}
				}
			}
			else
			{
				$result['msg'] = Text::_('MEDIA_NOT_FOUND');
			}
		}

		echo json_encode((object) $result);
		exit;
	}

	public function deletemedia()
	{
		$result = ['success' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (!$this->user->guest)
		{
			$filename    = $this->input->getString('file');
			$target_dir  = 'images/emundus/custom/media/' . $this->user->id . '/';
			$target_file = $target_dir . basename($filename);

			if (file_exists($target_file))
			{
				unlink($target_file);
				$result['success'] = true;
				$result['msg']     = Text::_('MEDIA_DELETED');
			}
			else
			{
				$result['msg'] = Text::_('MEDIA_NOT_FOUND');
			}
		}

		echo json_encode((object) $result);
		exit;
	}

	/**
	 * Update the order of the tags
	 *
	 * @since version 1.40.0
	 */
	public function updatetagsorder()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$response['code']    = 500;
			$response['message'] = Text::_('MISSING_PARAMS');

			$ordered_tags_string = $this->input->getString('tags', '');

			if (!empty($ordered_tags_string))
			{
				$ordered_tags = explode(',', $ordered_tags_string);

				$response['status'] = $this->m_settings->updateTagsOrder($ordered_tags);
				$response['code']   = 200;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getlivesite()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => null];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
		{
			$response['code']    = 500;
			$response['message'] = Text::_('MISSING_PARAMS');

			$live_site = $this->app->get('live_site');
			if (empty($live_site))
			{
				$live_site = Uri::base();
			}

			if (!empty($live_site))
			{
				$response['status']  = true;
				$response['code']    = 200;
				$response['message'] = Text::_('LIVE_SITE_FOUND');
				$response['data']    = $live_site;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getsslinfo()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => ''];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
		{
			$response['code']    = 200;
			$response['status']  = true;
			$response['message'] = Text::_('SSL_INFO_FOUND');

			$live_site = $this->app->get('live_site');
			if (empty($live_site))
			{
				$live_site = Uri::base();
			}

			if (!empty($live_site))
			{
				$certinfo = null;
				try
				{
					$orignal_parse = parse_url($live_site, PHP_URL_HOST);
					$get           = stream_context_create(array("ssl" => array("capture_peer_cert" => true)));
					$read          = stream_socket_client("ssl://" . $orignal_parse . ":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $get);
					if (!empty($read))
					{
						$cert     = stream_context_get_params($read);
						$certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
					}
				}
				catch (Exception $e)
				{
					$response['message'] = Text::_('SSL_INFO_NOT_FOUND');
				}

				if (!empty($certinfo))
				{
					$ssl_info['type']        = $certinfo['issuer']['O'];
					$ssl_info['valid_until'] = '';

					$valid_until = date('Y-m-d H:i:s', $certinfo['validTo_time_t']);
					if (!empty($valid_until) && $valid_until !== '1970-01-01 00:00:00')
					{
						$ssl_info['valid_until'] = $valid_until;
					}

					$response['data'] = $ssl_info;
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function sendwebsecurityrequest()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$update_web_address      = $this->input->getString('update_web_address', false);
			$new_address             = $this->input->getString('new_address', '');
			$use_own_ssl_certificate = $this->input->getString('use_own_ssl_certificate', false);
			$technical_contacts      = $this->input->getString('technical_contacts', '');

			if ($update_web_address == 1 || $use_own_ssl_certificate == 1)
			{
				// Log events
				if($update_web_address == 1)
				{
					$data = [
						'new_address'      => $new_address,
					];
					$this->app->triggerEvent('onAfterUpdateConfiguration', [$data, [], 'update_web_address', 'pending', 'com_emundus.settings.web_security']);
				}

				if($use_own_ssl_certificate == 1)
				{
					$this->app->triggerEvent('onAfterUpdateConfiguration', [[], [], 'use_own_ssl_certificate', 'pending', 'com_emundus.settings.web_security']);
				}
				//

				require_once JPATH_ROOT . '/components/com_emundus/models/emails.php';
				$m_emails = new EmundusModelEmails();

				$request = '<ul>';
				if ($update_web_address == 1)
				{
					$request .= '<li>' . Text::sprintf('COM_EMUNDUS_GLOBAL_WEB_SECURITY_UPDATE_WEB_ADDRESS_EMAIL', Uri::base(), $new_address) . '</li>';
				}
				if ($use_own_ssl_certificate == 1)
				{
					$request .= '<li>' . Text::_('COM_EMUNDUS_GLOBAL_WEB_SECURITY_USE_OWN_SSL_CERTIFICATE_EMAIL') . '</li>';
				}
				$request .= '</ul>';

				if (!empty($technical_contacts))
				{
					$technical_contacts = explode(',', $technical_contacts);

					$request .= '<p>' . Text::_('COM_EMUNDUS_GLOBAL_WEB_SECURITY_TECHNICAL_CONTACTS_EMAIL') . '</p>';
				}
				else
				{
					$technical_contacts = [];
				}

				$post = [
					'SITE_NAME'             => $this->app->get('sitename'),
					'SITE_URL'              => Uri::base(),
					'WEB_SECURITY_REQUESTS' => $request
				];

				$response['status'] = $m_emails->sendEmailNoFnum('support@emundus.fr', 'web_security_request', $post, $this->user->id, null, null, true, $technical_contacts);
				if ($response['status'])
				{
					$response['code']    = 200;
					$response['message'] = Text::_('REQUEST_SENT');
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}
	
	public function gethistory()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => []];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$response['code']    = 500;
			$response['message'] = Text::_('MISSING_PARAMS');
			
			$extension = $this->input->getString('extension', '');
			$only_pending = $this->input->getString('only_pending', false);
			$only_pending = filter_var($only_pending, FILTER_VALIDATE_BOOLEAN);
			$page = $this->input->getInt('page', 1);
			$limit = $this->input->getInt('limit', 10);
			$item_id = $this->input->getInt('item_id', 0);

			$length = $this->m_settings->getHistoryLength($extension, $item_id);
			$requests = $this->m_settings->getHistory($extension, $only_pending, $page, $limit, $item_id);

			$response['status']  = true;
			$response['code']    = 200;
			$response['message'] = Text::_('REQUESTS_FOUND');
			$response['data']    = $requests;
			$response['length']    = $length;
		}

		echo json_encode((object) $response);
		exit;
	}

	public function updatehistorystatus()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asAdministratorAccessLevel($this->user->id))
		{
			$response['code'] = 500;
			$response['message'] = Text::_('MISSING_PARAMS');

			$action_log_id = $this->input->getInt('id', 0);
			$action_log_status = $this->input->getString('status', 'done');

			if(!empty($action_log_id)) {
				$response['status'] = $this->m_settings->updateHistoryStatus($action_log_id,$action_log_status);
				if($response['status']) {
					$response['code']    = 200;
					$response['message'] = Text::_('STATUS_UPDATED');
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getapplicants()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => []];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$search_query = $this->input->getString('search_query', '');
			$limit = $this->input->getInt('limit', 100);
			$properties = $this->input->getString('properties', '');

			if(!empty($properties))
			{
				$properties = explode(',', $properties);
			}

			$event_id = $properties[0] ?? null;
			$applicantsExceptions = isset($properties[1]) ? [$properties[1]] : [];

			$applicants = $this->m_settings->getApplicants($search_query, $limit, $event_id, $applicantsExceptions);

			$response['status']  = true;
			$response['code']    = 200;
			$response['message'] = Text::_('APPLICANTS_FOUND');
			$response['data']    = $applicants;
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getavailablemanagers()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => []];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$search_query = $this->input->getString('search_query', '');
			$limit = $this->input->getInt('limit', 100);

			$managers = $this->m_settings->getAvailableManagers($search_query, $limit);

			$response['status']  = true;
			$response['code']    = 200;
			$response['message'] = Text::_('MANAGERS_FOUND');
			$response['data']    = $managers;
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getavailablegroups()
	{
		$this->checkToken();

		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => []];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$search_query = $this->input->getString('search_query', '');
			$limit = $this->input->getInt('limit', 100);

			$profiles = $this->m_settings->getAvailableGroups($search_query, $limit);

			$response['status']  = true;
			$response['code']    = 200;
			$response['message'] = Text::_('PROFILES_FOUND');
			$response['data']    = $profiles;
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getavailablecampaigns()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => []];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$search_query = $this->input->getString('search_query', '');
			$limit = $this->input->getInt('limit', 100);

			$campaigns = $this->m_settings->getAvailableCampaigns($search_query, $limit);

			$response['status']  = true;
			$response['code']    = 200;
			$response['message'] = Text::_('CAMPAIGNS_FOUND');
			$response['data']    = $campaigns;
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getavailableprograms()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => []];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$search_query = $this->input->getString('search_query', '');
			$limit = $this->input->getInt('limit', 100);

			$programs = $this->m_settings->getAvailablePrograms($search_query, $limit);

			$response['status']  = true;
			$response['code']    = 200;
			$response['message'] = Text::_('PROGRAMS_FOUND');
			$response['data']    = $programs;
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getevents()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$search_query = $this->input->getString('search_query', '');
			$limit = $this->input->getInt('limit', 100);

			$events = $this->m_settings->getEvents($search_query, $limit);

			$response['status']  = true;
			$response['code']    = 200;
			$response['message'] = Text::_('EVENTS_FOUND');
			$response['data']    = $events;
		}

		echo json_encode($response);
		exit();
	}


	public function getapps()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => []];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$apps = $this->m_settings->getApps();

			$response['status']  = true;
			$response['code']    = 200;
			$response['message'] = Text::_('APPS_FOUND');
			$response['data']    = $apps;
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getapp()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => null];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$app_id = $this->input->getInt('app_id', 0);
			$app_type = $this->input->getString('app_type', '');

			if(!empty($app_id) || !empty($app_type)) {
				$app = $this->m_settings->getApp($app_id, $app_type);

				$response['status']  = true;
				$response['code']    = 200;
				$response['message'] = Text::_('APP_FOUND');
				$response['data']    = $app;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function setupapp()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$response['code'] = 500;
			$response['message'] = Text::_('MISSING_PARAMS');

			$app_id = $this->input->getInt('app_id', 0);
			$setup = $this->input->getRaw('setup', []);

			if(!empty($app_id) && !empty($setup)) {
				$setup = json_decode($setup);

				$response['status'] = $this->m_settings->setupApp($app_id, $setup, $this->user->id);
				if ($response['status']) {
					$app = $this->m_settings->getApp($app_id);

					// Test authentication
					switch($app->type) {
						case 'ovh':
							if (!class_exists('OvhSMS')) {
								require_once JPATH_ROOT . '/components/com_emundus/classes/SMS/Synchronizer/OvhSMS.php';
							}
							$synchronizer = new OvhSMS();
							$sms_services = $synchronizer->getSmsServices();

							if (!empty($sms_services)) {
								$response['status'] = true;
							} else {
								$response['status'] = false;
							}

							break;
						default:
							require_once JPATH_ROOT . '/components/com_emundus/models/sync.php';
							$m_sync = new EmundusModelSync();
							$response['status'] = $m_sync->testAuthentication($app_id);
							break;
					}

					if($response['status'])
					{
						if($this->m_settings->checkRequirements($app_id))
						{
							$response['code']    = 200;
							$response['message'] = Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_APP_SETUP_SUCCESS');
						} else {
							$response['message'] = Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_APP_SETUP_REQUIREMENTS_FAILED');
						}
					} else {
						$response['message'] = Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_APP_SETUP_FAILED');
					}
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function disableapp()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$response['code'] = 500;
			$response['message'] = Text::_('MISSING_PARAMS');

			$app_id = $this->input->getInt('app_id', 0);
			$enabled = $this->input->getInt('enabled', 1);

			if(!empty($app_id)) {
				$response['status'] = $this->m_settings->toggleEnable($app_id,$enabled);
				if($response['status']) {
					$response['code']    = 200;
					$response['message'] = Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_APP_DISABLED');
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function historyretryevent()
	{
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$response['code'] = 500;
			$response['message'] = Text::_('MISSING_PARAMS');

			$action_log_row_id = $this->input->getInt('action_log_row_id', 0);

			if (!empty($action_log_row_id)) {
				$response['status'] = $this->m_settings->historyRetryEvent($action_log_row_id);
				if($response['status']) {
					$response['code']    = 200;
					$response['message'] = Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_APP_DISABLED');
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getaddons()
	{
		$this->checkToken('get');

		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => []];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$addons = $this->m_settings->getAddons();

			$response['status']  = true;
			$response['code']    = 200;
			$response['message'] = Text::_('ADDONS_FOUND');
			$response['data']    = $addons;
		}

		echo json_encode((object) $response);
		exit;
	}

	public function toggleaddon()
	{
		$this->checkToken('post');

		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$response['code'] = 500;
			$response['message'] = Text::_('MISSING_PARAMS');

			$addon_type = $this->input->getString('addon_type', 0);
			$enabled = $this->input->getInt('enabled', 1);

			if(!empty($addon_type)) {
				$response['status'] = $this->m_settings->toggleAddon($addon_type,$enabled);
				if($response['status']) {
					$response['code']    = 200;
					$response['message'] = Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_ADDON_TOGGLED');
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function setupmessenger()
	{
		$this->checkToken('post');

		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$response['code'] = 500;
			$response['message'] = Text::_('MISSING_PARAMS');

			$setup = $this->input->getRaw('setup', '{}');

			if(!empty($setup)) {
				$setup = json_decode($setup);

				$response['status'] = $this->m_settings->setupMessenger($setup,$this->user->id);
				if($response['status']) {
					$response['code']    = 200;
					$response['message'] = Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_MESSENGER_SETUP_SUCCESS');
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}
}

