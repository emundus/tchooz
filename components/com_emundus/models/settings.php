<?php
/**
 * Messages model used for the new message dialog.
 *
 * @package    Joomla
 * @subpackage eMundus
 *             components/com_emundus/emundus.php
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use PHPMailer\PHPMailer\Exception as phpMailerException;
use Smalot\PdfParser\Parser;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\Yaml\Yaml;
use Tchooz\Entities\Settings\AddonEntity;
use Tchooz\Repositories\Payment\PaymentRepository;

class EmundusModelSettings extends ListModel
{

	private $db;
	private $user;
	private $app;

	function __construct()
	{
		parent::__construct();

		$this->app  = Factory::getApplication();
		$this->db   = $this->getDatabase();
		$this->user = $this->app->getIdentity();

		Log::addLogger(['text_file' => 'com_emundus.error.php'], Log::ERROR, array('com_emundus'));
		Log::addLogger(['text_file' => 'com_emundus.updated_settings.php'], Log::ALL, array('com_emundus.settings'));
	}

	/**
	 * Get all colors available for status and tags
	 *
	 * @return string[]
	 *
	 * @since 1.0
	 */
	function getColorClasses()
	{
		return array(
			'lightpurple' => '#FBE8FF',
			'purple'      => '#EBE9FE',
			'darkpurple'  => '#663399',
			'lightblue'   => '#E0F2FE',
			'blue'        => '#D1E9FF',
			'darkblue'    => '#D1E0FF',
			'lightgreen'  => '#CCFBEF',
			'green'       => '#C4F0E1',
			'darkgreen'   => '#BEDBD0',
			'lightyellow' => '#FFFD7E',
			'yellow'      => '#FDF7C3',
			'darkyellow'  => '#FEF0C7',
			'lightorange' => '#FFEDCF',
			'orange'      => '#FCEAD7',
			'darkorange'  => '#FFE5D5',
			'lightred'    => '#EC644B',
			'red'         => '#FEE4E2',
			'darkred'     => '#FEE4E2',
			'lightpink'   => '#ffeaea',
			'pink'        => '#FCE7F6',
			'darkpink'    => '#FFE4E8',
			'default'     => '#EBECF0',
		);
	}

	/**
	 * A helper function that replace spaces and special characters
	 *
	 * @param $string
	 *
	 * @return array|string|string[]|null
	 *
	 * @since 1.12.0
	 */
	function clean($string)
	{
		$string = str_replace(' ', '_', $string); // Replaces all spaces with hyphens.

		return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	}

	/**
	 * Get all status available and check if files is associated
	 *
	 * @return array|false|mixed
	 *
	 * @since 1.0
	 */
	function getStatus()
	{
		$query = $this->db->getQuery(true);

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'falang.php');
		$falang = new EmundusModelFalang;

		$emConfig = ComponentHelper::getParams('com_emundus');

		$query->select('*')
			->from($this->db->quoteName('#__emundus_setup_status'))
			->order('ordering ASC');

		try
		{
			$this->db->setQuery($query);
			$status = $this->db->loadObjectList();
			foreach ($status as $statu)
			{
				$statu->label = new stdClass;

				$statu->label = $falang->getFalang($statu->step, 'emundus_setup_status', 'value', $statu->value);

				$statu->edit = 1;
				$query->clear()
					->select('count(id)')
					->from($this->db->quoteName('#__emundus_campaign_candidature'))
					->where($this->db->quoteName('status') . ' = ' . $this->db->quote($statu->step));
				$this->db->setQuery($query);
				$files = $this->db->loadResult();

				$status_for_send     = $emConfig->get('status_for_send', 0);
				$default_send_status = $emConfig->get('default_send_status');

				if ($files > 0 || in_array($statu->step, [$status_for_send, $default_send_status]))
				{
					$statu->edit = 0;
				}
			}

			return $status;
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Error at getting status : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Get all emundus tags available
	 *
	 * @return array|false|mixed
	 *
	 * @since 1.0
	 */
	function getTags()
	{
		$tags = [];

		$query = $this->db->getQuery(true);

		$query->select('*')
			->from($this->db->quoteName('#__emundus_setup_action_tag'))
			->order($this->db->quoteName('ordering') . ',' . $this->db->quoteName('label'));

		try
		{
			$this->db->setQuery($query);
			$tags = $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Error at getting action tags : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $tags;
	}

	/**
	 * Delete a tag, foreign key delete also all files associated to this tag
	 *
	 * @param $id
	 *
	 * @return false|mixed
	 *
	 * @since 1.0
	 */
	function deleteTag($id)
	{
		$deleted = false;

		if (!empty($id))
		{

			$query = $this->db->getQuery(true);

			$query->delete($this->db->quoteName('#__emundus_setup_action_tag'))
				->where($this->db->quoteName('id') . ' = ' . $id);

			try
			{
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus/models/settings | Cannot delete the tag ' . $id . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $deleted;
	}

	/**
	 * Create a emundus tag with a default label and color
	 *
	 * @return false|mixed|null
	 *
	 * @since 1.0
	 */
	function createTag()
	{
		$query = $this->db->getQuery(true);

		$query->insert('#__emundus_setup_action_tag')
			->set($this->db->quoteName('label') . ' = ' . $this->db->quote('Nouvelle étiquette'))
			->set($this->db->quoteName('class') . ' = ' . $this->db->quote('label-default'));

		try
		{
			$this->db->setQuery($query);
			$this->db->execute();
			$newtagid = $this->db->insertid();

			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__emundus_setup_action_tag'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($newtagid));

			$this->db->setQuery($query);

			return $this->db->loadObject();

		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Cannot create a tag : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Update the order of the tags
	 *
	 * @param $orderedTags
	 *
	 * @return boolean
	 *
	 * @since version 1.40.0
	 */
	function updateTagsOrder($orderedTags)
	{
		$updated = false;

		if (!empty($orderedTags))
		{
			$query = $this->db->getQuery(true);

			try
			{
				foreach ($orderedTags as $order => $tag_id)
				{
					$query->clear()
						->update('#__emundus_setup_action_tag')
						->set($this->db->quoteName('ordering') . ' = ' . $this->db->quote($order))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($tag_id));

					$this->db->setQuery($query);
					$updated = $this->db->execute();
				}
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus/models/settings | Cannot update tags order : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $updated;
	}

	/**
	 * Create a new status
	 *
	 * @return false|mixed|null
	 *
	 * @since 1.0
	 */
	function createStatus()
	{
		$query = $this->db->getQuery(true);

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'falang.php');
		$falang = new EmundusModelFalang;

		$query->select('MAX(step)')
			->from($this->db->quoteName('#__emundus_setup_status'));
		$this->db->setQuery($query);
		$newstep = $this->db->loadResult() + 1;

		$query->clear()
			->select('MAX(ordering)')
			->from($this->db->quoteName('#__emundus_setup_status'));
		$this->db->setQuery($query);
		$newordering = $this->db->loadResult() + 1;

		$query->clear()
			->select('COUNT(*)')
			->from($this->db->quoteName('#__emundus_setup_status'))
			->where($this->db->quoteName('value') . ' LIKE ' . $this->db->quote('Nouveau statut%'));
		$this->db->setQuery($query);
		$existing = $this->db->loadResult();
		if ($existing > 0)
		{
			$increment = $existing + 1;
		}
		else
		{
			$increment = '';
		}

		$query->clear()
			->insert('#__emundus_setup_status')
			->set($this->db->quoteName('value') . ' = ' . $this->db->quote('Nouveau statut ' . $increment))
			->set($this->db->quoteName('step') . ' = ' . $this->db->quote($newstep))
			->set($this->db->quoteName('ordering') . ' = ' . $this->db->quote($newordering))
			->set($this->db->quoteName('class') . ' = ' . $this->db->quote('default'));

		try
		{
			$this->db->setQuery($query);
			$this->db->execute();
			$newstatusid = $this->db->insertid();

			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__emundus_setup_status'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($newstatusid));

			$this->db->setQuery($query);
			$status = $this->db->loadObject();

			$status->label     = new stdClass;
			$status->label->fr = 'Nouveau statut';
			$status->label->en = 'New status';
			$status->edit      = 1;

			$falang->insertFalang($status->label, $newstep, 'emundus_setup_status', 'value');

			return $status;
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Cannot create a status : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Update a status (label and colors)
	 *
	 * @param $status
	 *
	 * @return array|false
	 *
	 * @since 1.0
	 */
	function updateStatus($status, $label, $color)
	{
		$query = $this->db->getQuery(true);

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'translations.php');
		$m_translations = new EmundusModelTranslations;

		$lang_to = Factory::getApplication()->getLanguage()->getTag();

		$results = [];

		try
		{
			$query->clear()
				->update('#__falang_content')
				->set($this->db->quoteName('value') . ' = ' . $this->db->quote($color))
				->where(array(
					$this->db->quoteName('reference_id') . ' = ' . $this->db->quote($status),
					$this->db->quoteName('reference_table') . ' = ' . $this->db->quote('emundus_setup_status'),
					$this->db->quoteName('reference_field') . ' = ' . $this->db->quote('class'),
					$this->db->quoteName('language_id') . ' = 2'
				));
			$this->db->setQuery($query);
			$this->db->execute();

			$results[] = $m_translations->updateFalangTranslation($label, $lang_to, 'emundus_setup_status', $status, 'value');

			$query->clear()
				->update('#__emundus_setup_status')
				->set($this->db->quoteName('value') . ' = ' . $this->db->quote($label))
				->set($this->db->quoteName('class') . ' = ' . $this->db->quote($color))
				->where($this->db->quoteName('step') . ' = ' . $this->db->quote($status));
			$this->db->setQuery($query);
			$this->db->execute();

			return $results;
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Cannot update status : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function updateStatusOrder($status)
	{
		$query = $this->db->getQuery(true);

		try
		{
			foreach ($status as $order => $statu)
			{
				$query->clear()
					->update('#__emundus_setup_status')
					->set($this->db->quoteName('ordering') . ' = ' . $this->db->quote($order))
					->where($this->db->quoteName('step') . ' = ' . $this->db->quote($statu));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			return true;
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Cannot update status order : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Delete a status that is not associated to files
	 *
	 * @param $id
	 * @param $step
	 *
	 * @return false|mixed
	 *
	 * @since 1.0
	 */
	function deleteStatus($id, $step)
	{
		$query = $this->db->getQuery(true);

		$query->delete($this->db->quoteName('#__falang_content'))
			->where($this->db->quoteName('reference_id') . ' = ' . $this->db->quote($step))
			->andWhere($this->db->quoteName('reference_table') . ' = ' . $this->db->quote('emundus_setup_status'));
		try
		{
			$this->db->setQuery($query);
			$this->db->execute();

			$query->clear()
				->delete($this->db->quoteName('#__emundus_setup_status'))
				->where($this->db->quoteName('id') . ' = ' . $id);

			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Cannot delete the status ' . $id . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Update emundus tags (label and colors)
	 *
	 * @param $tags
	 *
	 * @return array|false
	 *
	 * @since 1.0
	 */
	function updateTags($tag, $label, $color)
	{
		$query = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select('id')
				->from('#__emundus_setup_action_tag')
				->where($this->db->quoteName('label') . ' = ' . $this->db->quote($label));
			$this->db->setQuery($query);
			$result = $this->db->loadResult();

			if (empty($result))
			{
				$query->clear()
					->update('#__emundus_setup_action_tag')
					->set($this->db->quoteName('label') . ' = ' . $this->db->quote($label))
					->set($this->db->quoteName('class') . ' = ' . $this->db->quote('label-' . $color))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($tag));
				$this->db->setQuery($query);

				return $this->db->execute();
			}
			else {
				// Update only color
				$query->clear()
					->update('#__emundus_setup_action_tag')
					->set($this->db->quoteName('class') . ' = ' . $this->db->quote('label-' . $color))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($tag));
				$this->db->setQuery($query);

				return $this->db->execute();
			}

			return false;
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Cannot update tags : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Get footer articles from the module mod_emundus_footer
	 *
	 * @return false|stdClass
	 *
	 * @since 1.28.0
	 */
	function getFooterArticles()
	{
		$query = $this->db->getQuery(true);

		$footers = new stdClass();

		$query->select('id as id, params as params')
			->from($this->db->quoteName('#__modules'))
			->where($this->db->quoteName('position') . ' LIKE ' . $this->db->quote('footer-a'))
			->andWhere($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus_footer'));

		try
		{
			$this->db->setQuery($query);
			$params = $this->db->loadObject();

			if (!empty($params))
			{
				$params = json_decode($params->params);

				$footers->column1 = $params->mod_emundus_footer_texte_col_1 !== 'null' ? $params->mod_emundus_footer_texte_col_1 : '';
				$footers->column2 = $params->mod_emundus_footer_texte_col_2 !== 'null' ? $params->mod_emundus_footer_texte_col_2 : '';

				return $footers;
			}
			else
			{
				return $this->getOldFooterArticles();
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Error at getting footer articles : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Deprecated footer handling
	 * Get footer content from custom module in footer-a position
	 *
	 * @since 1.0
	 */
	private function getOldFooterArticles()
	{
		$query = $this->db->getQuery(true);

		$footers = new stdClass();
		$query->select('id as id,content as content')
			->from($this->db->quoteName('#__modules'))
			->where($this->db->quoteName('position') . ' LIKE ' . $this->db->quote('footer-a'));

		try
		{
			$this->db->setQuery($query);
			$footers->column1 = $this->db->loadObject()->content;

			$query->clear()
				->select('id as id,content as content')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('position') . ' LIKE ' . $this->db->quote('footer-b'));

			$this->db->setQuery($query);
			$footers->column2 = $this->db->loadObject()->content;

			return $footers;
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Error at getting footer articles : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Get a Joomla article
	 *
	 * @param $lang_code
	 * @param $article_id
	 * @param $article_alias
	 * @param $reference_field
	 *
	 * @return false|mixed|null
	 *
	 * @since 1.29.0
	 */
	function getArticle($lang_code, $article_id = 0, $article_alias = '', $reference_field = 'introtext')
	{
		$query = $this->db->getQuery(true);

		$query->select('lang_id')
			->from($this->db->quoteName('#__languages'))
			->where($this->db->quoteName('lang_code') . ' = ' . $this->db->quote($lang_code));
		$this->db->setQuery($query);
		$lang_id = $this->db->loadResult();

		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__content'));

		if (!empty($article_id))
		{
			$query->where($this->db->quoteName('id') . ' = ' . $article_id);
		}
		else
		{
			$query->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($article_alias));
		}

		try
		{
			$this->db->setQuery($query);
			$article = $this->db->loadObject();

			if (!empty($article))
			{
				$query->clear()
					->select('value')
					->from($this->db->quoteName('#__falang_content'))
					->where(array(
						$this->db->quoteName('reference_id') . ' = ' . $article->id,
						$this->db->quoteName('reference_table') . ' = ' . $this->db->quote('content'),
						$this->db->quoteName('reference_field') . ' = ' . $this->db->quote($reference_field),
						$this->db->quoteName('language_id') . ' = ' . $this->db->quote($lang_id),
						$this->db->quoteName('published') . ' = ' . $this->db->quote(1)
					));
				$this->db->setQuery($query);
				$result = $this->db->loadResult();

				if (!empty($result))
				{
					$article->{$reference_field} = $result;
				}
				else
				{
					$currentLang = JFactory::getLanguage();
					if ($currentLang->lang_code != $lang_code)
					{
						$query->clear()
							->select('title, introtext, alias')
							->from($this->db->quoteName('#__content'))
							->where('id = ' . $article->id);

						$this->db->setQuery($query);
						$article_content = $this->db->loadAssoc();

						foreach ($article_content as $key => $content)
						{
							$article->{$key} = $content;
						}
					}
				}

				$article->published = $this->getArticlePublishedState($article_id, $article_alias);

				return $article;
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Cannot get article ' . $article_id . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Update a Joomla article
	 *
	 * @param $content
	 * @param $lang_code
	 * @param $article_id
	 * @param $article_alias
	 * @param $reference_field
	 *
	 * @return false|mixed
	 *
	 * @since 1.29.0
	 */
	function updateArticle($content, $lang_code, $article_id = 0, $article_alias = '', $reference_field = 'introtext', $note = null)
	{
		$updated = false;

		$query = $this->db->getQuery(true);

		$config = (new HtmlSanitizerConfig())
			->allowSafeElements()
			->allowElement('a', ['href', 'title', 'target'])
			->allowElement('img', '*')
			->allowElement('p', ['style', 'class'])
			->allowElement('span', ['style', 'class'])
			->allowAttribute('img', ['src', 'style', 'alt', 'title', 'width', 'height', 'draggable'])
			->allowAttribute('*', 'style')
			->allowRelativeLinks(true)
			->allowRelativeMedias(true)
			->forceHttpsUrls(true);

		if (!class_exists('HtmlSanitizerSingleton'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
		}
		$htmlSanitizer = HtmlSanitizerSingleton::getInstance($config);
		$content       = $htmlSanitizer->sanitizeFor('body', $content);

		try
		{
			$query->select('lang_id')
				->from($this->db->quoteName('#__languages'))
				->where($this->db->quoteName('lang_code') . ' = ' . $this->db->quote($lang_code));
			$this->db->setQuery($query);
			$lang_id = $this->db->loadResult();

			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__content'));

			if (!empty($article_id))
			{
				$query->where($this->db->quoteName('id') . ' = ' . $article_id);
			}
			else
			{
				$query->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($article_alias));
			}
			$this->db->setQuery($query);
			$article = $this->db->loadObject();

			// Update content
			$query->clear()
				->select('value')
				->from($this->db->quoteName('#__falang_content'))
				->where(array(
					$this->db->quoteName('reference_id') . ' = ' . $article->id,
					$this->db->quoteName('reference_table') . ' = ' . $this->db->quote('content'),
					$this->db->quoteName('reference_field') . ' = ' . $this->db->quote($reference_field),
					$this->db->quoteName('language_id') . ' = ' . $this->db->quote($lang_id),
					$this->db->quoteName('published') . ' = ' . $this->db->quote(1)
				));
			$this->db->setQuery($query);
			$falang_result = $this->db->loadResult();

			if (empty($falang_result))
			{
				$query->clear()
					->insert('#__falang_content')
					->columns(['reference_id', 'reference_table', 'reference_field', 'language_id', 'value', 'published'])
					->values($article->id . ', ' . $this->db->quote('content') . ', ' . $this->db->quote($reference_field) . ', ' . $lang_id . ', ' . $this->db->quote($content) . ', 1');

				$this->db->setQuery($query);
				$updated = $this->db->execute();
			}
			else
			{
				$query->clear()
					->update('#__falang_content')
					->set($this->db->quoteName('value') . ' = ' . $this->db->quote($content))
					->where(array(
						$this->db->quoteName('reference_id') . ' = ' . $article->id,
						$this->db->quoteName('reference_table') . ' = ' . $this->db->quote('content'),
						$this->db->quoteName('reference_field') . ' = ' . $this->db->quote($reference_field),
						$this->db->quoteName('language_id') . ' = ' . $this->db->quote($lang_id)
					));
				$this->db->setQuery($query);

				$updated = $this->db->execute();
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Error at updating article ' . $article_id . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $updated;
	}

	/**
	 * Update the emundus footer module with 2 columns
	 *
	 * @param $col1
	 * @param $col2
	 *
	 * @return bool|mixed
	 *
	 * @since 1.28.0
	 */
	function updateFooter($col1, $col2)
	{
		$query = $this->db->getQuery(true);

		$query->select('params')
			->from($this->db->quoteName('#__modules'))
			->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus_footer'));

		$this->db->setQuery($query);
		$params = $this->db->loadResult();

		if (!empty($params))
		{
			$params = json_decode($params);

			$params->mod_emundus_footer_texte_col_1 = $col1;
			$params->mod_emundus_footer_texte_col_2 = $col2;

			$query->clear()
				->update($this->db->quoteName('#__modules'))
				->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
				->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus_footer'));

			try
			{
				$this->db->setQuery($query);

				return $this->db->execute();
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus/models/settings | Error at updating footer : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

				return false;
			}
		}
		else
		{
			return $this->updateOldFooter($col1, $col2);
		}
	}

	/**
	 * Deprecated footer handling
	 *
	 * @param $col1
	 * @param $col2
	 *
	 * @return bool
	 *
	 * @since 1.0
	 */
	private function updateOldFooter($col1, $col2)
	{
		$query = $this->db->getQuery(true);

		$query->update($this->db->quoteName('#__modules'))
			->set($this->db->quoteName('content') . ' = ' . $this->db->quote($col1))
			->where($this->db->quoteName('position') . ' LIKE ' . $this->db->quote('footer-a'));

		try
		{
			$this->db->setQuery($query);
			$this->db->execute();

			$query->clear()
				->update($this->db->quoteName('#__modules'))
				->set($this->db->quoteName('content') . ' = ' . $this->db->quote($col2))
				->where($this->db->quoteName('position') . ' LIKE ' . $this->db->quote('footer-b'));
			$this->db->setQuery($query);
			$this->db->execute();

			return true;
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Error at updating footer articles : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Get emundus tags published for wysiwig editor (emails, settings, formbuilder)
	 *
	 * @return array|false|mixed
	 *
	 * @since 1.10.0
	 */
	function getEditorVariables()
	{
		$query = $this->db->getQuery(true);

		$lang           = $this->app->getLanguage();
		$actualLanguage = substr($lang->getTag(), 0, 2);
		if ($actualLanguage == 'fr')
		{
			$language = 2;
		}
		else
		{
			$language = 1;
		}

		$query->select('st.id as id,CONCAT("[",st.tag,"]") as `value`,fc.value as label')
			->from($this->db->quoteName('#__emundus_setup_tags', 'st'))
			->leftJoin($this->db->quoteName('#__falang_content', 'fc') . ' ON ' . $this->db->quoteName('fc.reference_id') . ' = ' . $this->db->quoteName('st.id'))
			->where($this->db->quoteName('st.published') . ' = ' . $this->db->quote(1))
			->andWhere($this->db->quoteName('fc.reference_field') . ' = ' . $this->db->quote('description'))
			->andWhere($this->db->quoteName('fc.language_id') . ' = ' . $this->db->quote($language))
			->andWhere($this->db->quoteName('fc.reference_table') . ' = ' . $this->db->quote('emundus_setup_tags'));

		try
		{
			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Error at getting editor variables : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Update the main logo store in a module
	 *
	 * @param $newcontent
	 *
	 * @return false|mixed
	 *
	 * @since 1.0
	 */
	function updateLogo($target_file, $new_logo, $ext)
	{
		$updated = false;
		$query   = $this->db->getQuery(true);

		try
		{
			$query->select('id,content')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' = ' . $this->db->quote('mod_custom'))
				->where($this->db->quoteName('title') . ' LIKE ' . $this->db->quote('Logo'));
			$this->db->setQuery($query);
			$logo_module = $this->db->loadObject();

			if (move_uploaded_file($new_logo, $target_file))
			{
				$regex = '/(logo.(png+|jpeg+|jpg+|svg+|gif+|webp+))|(logo_custom.(png+|jpeg+|jpg+|svg+|gif+|webp+))/m';

				$new_content = preg_replace($regex, 'logo_custom.' . $ext, $logo_module->content);

				$query->clear()
					->update($this->db->quoteName('#__modules'))
					->set($this->db->quoteName('content') . ' = ' . $this->db->quote($new_content))
					->where($this->db->quoteName('id') . ' = ' . $logo_module->id);
				$this->db->setQuery($query);
				$updated = $this->db->execute();

				if (file_exists(JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml'))
				{
					$yaml = Yaml::parse(file_get_contents(JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml'));

					if (!empty($yaml))
					{
						$yaml['image'] = 'gantry-media://custom/logo_custom.' . $ext;

						file_put_contents(JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml', Yaml::dump($yaml));
					}
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $updated;
	}

	function onAfterCreateCampaign($user_id = null)
	{
		$event_runned = false;

		if (empty($user_id))
		{
			if (!empty($this->_user->id))
			{
				$user_id = $this->_user->id;
			}
			else
			{
				$user = Factory::getApplication()->getIdentity();

				if (!empty($user->id))
				{
					$user_id = $user->id;
				}
				else
				{
					return false;
				}
			}
		}


		$query = $this->db->getQuery(true);
		$query->select('count(id)')
			->from($this->db->quoteName('#__emundus_setup_campaigns'));

		try
		{
			$this->db->setQuery($query);

			if ($this->db->loadResult() === '1')
			{
				$this->removeParam('first_login', $user_id);

				$event_runned = $this->createParam('first_form', $user_id);
			}
			else
			{
				$event_runned = true;
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Error at set tutorial param after create a campaign : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $event_runned;
	}

	function onAfterCreateForm($user_id)
	{
		try
		{
			$this->removeParam('first_form', $user_id);
			$this->createParam('first_formbuilder', $user_id);
			$this->createParam('first_documents', $user_id);
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Error at set tutorial param after create a campaign : ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}
	}


	/**
	 * @param         $param String The param to be saved in the user account.
	 *
	 * @param   null  $user_id
	 *
	 * @return bool
	 * @since version
	 */
	function createParam($param, $user_id)
	{

		$user = Factory::getUser($user_id);

		$table = JTable::getInstance('user', 'JTable');
		$table->load($user->id);

		// Check if the param exists but is false, this avoids accidetally resetting a param.
		$params = $user->getParameters();
		if (!$params->get($param, true))
		{
			return true;
		}

		// Store token in User's Parameters
		$user->setParam($param, true);

		// Get the raw User Parameters
		$params = $user->getParameters();

		// Set the user table instance to include the new token.
		$table->params = $params->toString();

		// Save user data
		if (!$table->store())
		{
			Log::add('component/com_emundus/models/settings | Error when create a param in the user ' . $user_id . ' : ' . $table->getError(), Log::ERROR, 'com_emundus');

			return false;
		}

		return true;
	}

	function removeParam($param, $user_id)
	{
		$user = Factory::getUser($user_id);

		$table = JTable::getInstance('user', 'JTable');
		$table->load($user->id);

		// Check if the param exists but is false, this avoids accidetally resetting a param.
		$params = $user->getParameters();
		if (!$params->get($param, true))
		{
			return true;
		}

		// Store token in User's Parameters
		$user->setParam($param, false);

		// Get the raw User Parameters
		$params = $user->getParameters();

		// Set the user table instance to include the new token.
		$table->params = $params->toString();

		// Save user data
		if (!$table->store())
		{
			Log::add('component/com_emundus/models/settings | Error when remove a param from the user ' . $user_id . ' : ' . $table->getError(), Log::ERROR, 'com_emundus');

			return false;
		}

		return true;
	}

	function getDatasFromTable($table)
	{
		$query = $this->db->getQuery(true);

		if (strpos($table, 'data_') !== false)
		{

			$query->select('join_column_val,translation')
				->from($this->db->quoteName('#__emundus_datas_library'))
				->where($this->db->quoteName('database_name') . ' LIKE ' . $this->db->quote($table));
			$this->db->setQuery($query);
			$columntodisplay = $this->db->loadObject();

			if (boolval($columntodisplay->translation))
			{
				$columntodisplay->join_column_val = $columntodisplay->join_column_val . '_en,' . $columntodisplay->join_column_val . '_fr';
			}

			$query->clear()
				->select('*')
				->from($this->db->quoteName($table));
			$this->db->setQuery($query);

			try
			{
				return $this->db->loadAssocList();
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus/models/settings | Error at getting datas from databasejoin table ' . $table . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

				return false;
			}
		}
		else
		{
			return false;
		}
	}

	function saveDatas($form)
	{
		$query = $this->db->getQuery(true);

		$name = strtolower($this->clean($form['label']));

		// Check if a table already get the same name and increment them
		$query->clear()
			->select('COUNT(*)')
			->from($this->db->quoteName('information_schema.tables'))
			->where($this->db->quoteName('table_name') . ' LIKE ' . $this->db->quote('%data_' . $name . '%'));
		$this->db->setQuery($query);
		$result = $this->db->loadResult();

		$increment = '00';
		if ($result < 10)
		{
			$increment = '0' . strval($result);
		}
		elseif ($result > 10)
		{
			$increment = strval($result);
		}

		$table_name = 'data_' . $name . '_' . $increment;
		//

		$query->insert($this->db->quoteName('#__emundus_datas_library'));
		$query->set($this->db->quoteName('database_name') . ' = ' . $this->db->quote($table_name))
			->set($this->db->quoteName('join_column_val') . ' = ' . $this->db->quote('value'))
			->set($this->db->quoteName('label') . ' = ' . $this->db->quote($form['label']))
			->set($this->db->quoteName('description') . ' = ' . $this->db->quote($form['desc']))
			->set($this->db->quoteName('created') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')));
		$this->db->setQuery($query);
		try
		{
			$this->db->execute();

			// Create the new table
			$table_query = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
            id int(11) NOT NULL AUTO_INCREMENT,
            value_fr varchar(255) NOT NULL,
            value_en varchar(255) NOT NULL,
            PRIMARY KEY (id)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4";
			$this->db->setQuery($table_query);
			$this->db->execute();
			//

			// Insert values
			$query = $this->db->getQuery(true);
			foreach ($form['db_values'] as $values)
			{
				$query->clear()
					->insert($this->db->quoteName($table_name));
				$query->set($this->db->quoteName('value_fr') . ' = ' . $this->db->quote($values['fr']))
					->set($this->db->quoteName('value_en') . ' = ' . $this->db->quote($values['en']));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			//

			return true;
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Error at saving datas in a new databasejion table : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function saveImportedDatas($form, $datas)
	{
		$query = $this->db->getQuery(true);

		$name = strtolower($this->clean($form['label']));

		// Check if a table already get the same name and increment them
		$query->clear()
			->select('COUNT(*)')
			->from($this->db->quoteName('information_schema.tables'))
			->where($this->db->quoteName('table_name') . ' LIKE ' . $this->db->quote('%data_' . $name . '%'));
		$this->db->setQuery($query);
		$result = $this->db->loadResult();

		$increment = '00';
		if ($result < 10)
		{
			$increment = '0' . strval($result);
		}
		elseif ($result > 10)
		{
			$increment = strval($result);
		}

		$table_name = 'data_' . $name . '_' . $increment;
		//

		$columns = array_keys($datas[0]);
		unset($datas[0]);
		foreach ($columns as $key => $column)
		{
			$columns[$key] = strtolower($this->clean($column));
		}

		$query->insert($this->db->quoteName('#__emundus_datas_library'));
		$query->set($this->db->quoteName('database_name') . ' = ' . $this->db->quote($table_name))
			->set($this->db->quoteName('join_column_val') . ' = ' . $this->db->quote($columns[0]))
			->set($this->db->quoteName('label') . ' = ' . $this->db->quote($form['label']))
			->set($this->db->quoteName('description') . ' = ' . $this->db->quote($form['desc']))
			->set($this->db->quoteName('translation') . ' = ' . $this->db->quote(0))
			->set($this->db->quoteName('created') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')));
		$this->db->setQuery($query);
		try
		{
			$this->db->execute();

			// Create the new table
			$table_query = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
            id int(11) NOT NULL AUTO_INCREMENT,
            PRIMARY KEY (id)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4";
			$this->db->setQuery($table_query);
			$this->db->execute();

			foreach ($columns as $key => $column)
			{
				$query = "ALTER TABLE " . $table_name . " ADD " . $column . " VARCHAR(255) NULL";
				$this->db->setQuery($query);
				$this->db->execute();
			}
			//

			// Insert values
			$query = $this->db->getQuery(true);
			foreach ($datas as $value)
			{
				$query->clear()
					->insert($this->db->quoteName($table_name));
				foreach (array_keys($value) as $key => $column)
				{
					$query->set($this->db->quoteName(strtolower($this->clean($column))) . ' = ' . $this->db->quote(array_values($value)[$key]));
				}
				$this->db->setQuery($query);
				$this->db->execute();
			}

			//

			return true;
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Error at saving imported datas : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function checkFirstDatabaseJoin($user_id)
	{
		$user = Factory::getUser($user_id);

		try
		{
			$table = JTable::getInstance('user', 'JTable');
			$table->load($user->id);

			// Check if the param exists but is false, this avoids accidetally resetting a param.
			$params = $user->getParameters();

			return $params->get('first_databasejoin', true);
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Error at checking if its the first databasejoin of the user ' . $user_id . ' : ' . $table->getError(), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function moveUploadedFileToDropbox($file, $name, $extension, $campaign_cat, $filesize)
	{
		$query = $this->db->getQuery(true);

		try
		{
			//CHECK OREDERING BEFORE INSERT
			$query->select('ordering')
				->from($this->db->quoteName('#__dropfiles_files'))
				->where($this->db->quoteName('catid') . ' = ' . $this->db->quote($campaign_cat));
			$this->db->setQuery($query);
			$orderings = $this->db->loadColumn();
			$order     = $orderings[sizeof($orderings) - 1] + 1;

			$dateTime = new Date('now', 'UTC');
			$now      = $dateTime->toSQL();

			$query->clear()
				->insert($this->db->quoteName('#__dropfiles_files'));
			$query->set($this->db->quoteName('catid') . ' = ' . $this->db->quote($campaign_cat))
				->set($this->db->quoteName('file') . ' = ' . $this->db->quote($file))
				->set($this->db->quoteName('state') . ' = ' . $this->db->quote(1))
				->set($this->db->quoteName('ordering') . ' = ' . $this->db->quote($order))
				->set($this->db->quoteName('title') . ' = ' . $this->db->quote($name))
				->set($this->db->quoteName('description') . ' = ' . $this->db->quote(''))
				->set($this->db->quoteName('ext') . ' = ' . $this->db->quote($extension))
				->set($this->db->quoteName('size') . ' = ' . $this->db->quote($filesize))
				->set($this->db->quoteName('hits') . ' = ' . $this->db->quote(0))
				->set($this->db->quoteName('version') . ' = ' . $this->db->quote(''))
				->set($this->db->quoteName('created_time') . ' = ' . $this->db->quote($now))
				->set($this->db->quoteName('modified_time') . ' = ' . $this->db->quote($now))
				->set($this->db->quoteName('publish') . ' = ' . $this->db->quote($now))
				->set($this->db->quoteName('author') . ' = ' . $this->db->quote(JFactory::getUser()->id))
				->set($this->db->quoteName('language') . ' = ' . $this->db->quote(''));
			$this->db->setQuery($query);
			$this->db->execute();

			return $this->db->insertid();
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/settings | Error at moving uploaded file ' . $file . ' to the dropbox category ' . $campaign_cat . ': ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function getBannerModule()
	{
		$query = $this->db->getQuery(true);

		try
		{
			$query->select('params')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus_banner'))
				->andWhere($this->db->quoteName('published') . ' = 1');
			$this->db->setQuery($query);

			return $this->db->loadResult();
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	function updateBannerImage()
	{
		$query = $this->db->getQuery(true);

		try
		{
			$query->select('*')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus_banner'))
				->andWhere($this->db->quoteName('published') . ' = 1');
			$this->db->setQuery($query);
			$module = $this->db->loadObject();

			if (!empty($module))
			{
				$params                      = json_decode($module->params);
				$params->mod_em_banner_image = 'images/custom/default_banner.png';

				$query->clear()
					->update($this->db->quoteName('#__modules'))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($module->id));
				$this->db->setQuery($query);

				return $this->db->execute();
			}
			else
			{
				return false;
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	function getOnboardingLists()
	{
		$lists = [];

		$group      = 'com_emundus';
		$cache_id   = 'onboarding_lists';
		$cache_data = null;

		require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
		$h_cache = new EmundusHelperCache('com_emundus', '', 86400, 'component');
		if ($h_cache->isEnabled())
		{
			$cache_data = $h_cache->get($cache_id);
		}

		if (empty($cache_data))
		{
			$this->db = JFactory::getDbo();
			$query    = $this->db->getQuery(true);
			$query->select('`default`, value')
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('onboarding_lists'));

			try
			{
				$this->db->setQuery($query);

				$data = $this->db->loadObject();
				if (!empty($data->value) || !empty($data->default))
				{
					if (!empty($data->value))
					{
						$lists = json_decode($data->value, true);
					}
					else
					{
						$lists = json_decode($data->default, true);
					}

					foreach ($lists as $lk => $list)
					{
						if ($lk === 'campaigns')
						{
							$eMConfig              = JComponentHelper::getParams('com_emundus');
							$allow_pinned_campaign = $eMConfig->get('allow_pinned_campaign', 0);

							if (!$allow_pinned_campaign)
							{
								foreach ($list['tabs'] as $tk => $tab)
								{
									if ($tab['key'] === 'campaign')
									{
										foreach ($tab['actions'] as $ak => $action)
										{
											if ($action['name'] === 'pin' || $action['name'] === 'unpin')
											{
												unset($tab['actions'][$ak]);
											}
										}
										$list['tabs'][$tk] = $tab;
										break;
									}
								}
							}
						}

						$list['title'] = JText::_($list['title']);

						foreach ($list['tabs'] as $tk => $tab)
						{
							$tab['title'] = JText::_($tab['title']);

							foreach ($tab['actions'] as $ak => $action)
							{
								$action['label'] = JText::_($action['label']);
								if (!empty($action['confirm']))
								{
									$action['confirm'] = JText::_($action['confirm']);
								}
								$tab['actions'][$ak] = $action;
							}

							foreach ($tab['filters'] as $fk => $filter)
							{
								$filter['label']     = JText::_($filter['label']);
								$tab['filters'][$fk] = $filter;
							}

							$list['tabs'][$tk] = $tab;
						}

						$lists[$lk] = $list;
					}
					if ($h_cache->isEnabled())
					{
						$h_cache->set($cache_id, $lists);
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('Error getting onboarding lists in model at query : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}
		else
		{
			$lists = $cache_data;
		}

		return $lists;
	}

	function getHomeArticle()
	{
		$query = $this->db->getQuery(true);

		$article_id = 52;

		try
		{
			$query->select('id')
				->from($this->db->quoteName('#__content'))
				->where($this->db->quoteName('featured') . ' = 1')
				->andWhere($this->db->quoteName('state') . ' IN (0,1)');
			$this->db->setQuery($query);
			$article_id = $this->db->loadResult();
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $article_id;
	}

	function getRgpdArticles()
	{
		$query = $this->db->getQuery(true);

		$rgpd_articles = [];

		try
		{
			$query->select('params')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus_footer'));
			$this->db->setQuery($query);
			$params = $this->db->loadResult();

			if (!empty($params))
			{
				$params = json_decode($params);

				$legal_info = new stdClass();
				if (!empty($params->mod_emundus_footer_legal_info_alias))
				{
					$query->clear()
						->select('SUBSTRING_INDEX(SUBSTRING(link, LOCATE("id=",link)+3, 6), "&", 1)')
						->from($this->db->quoteName('#__menu'))
						->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($params->mod_emundus_footer_legal_info_alias));
					$this->db->setQuery($query);
					$legal_info->id = $this->db->loadResult();
				}
				else
				{
					$legal_info->alias = 'mentions-legales';
				}
				$legal_info->title     = JText::_('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_LEGAL_MENTION');
				$legal_info->published = $params->mod_emundus_footer_legal_info;
				$rgpd_articles[]       = $legal_info;

				$data_privacy = new stdClass();
				if (!empty($params->mod_emundus_footer_data_privacy_alias))
				{
					$query->clear()
						->select('SUBSTRING_INDEX(SUBSTRING(link, LOCATE("id=",link)+3, 6), "&", 1)')
						->from($this->db->quoteName('#__menu'))
						->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($params->mod_emundus_footer_data_privacy_alias));
					$this->db->setQuery($query);
					$data_privacy->id = $this->db->loadResult();
				}
				else
				{
					$data_privacy->alias = 'politique-de-confidentialite-des-donnees';
				}
				$data_privacy->title     = JText::_('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_DATAS');
				$data_privacy->published = $params->mod_emundus_footer_data_privacy;
				$rgpd_articles[]         = $data_privacy;

				$rights = new stdClass();
				if (!empty($params->mod_emundus_footer_rights_alias))
				{
					$query->clear()
						->select('SUBSTRING_INDEX(SUBSTRING(link, LOCATE("id=",link)+3, 6), "&", 1)')
						->from($this->db->quoteName('#__menu'))
						->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($params->mod_emundus_footer_rights_alias));
					$this->db->setQuery($query);
					$rights->id = $this->db->loadResult();
				}
				else
				{
					$rights->alias = 'gestion-des-droits';
				}
				$rights->title     = JText::_('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_RIGHTS');
				$rights->published = $params->mod_emundus_footer_rights;
				$rgpd_articles[]   = $rights;

				$cookies = new stdClass();
				if (!empty($params->mod_emundus_footer_cookies_alias))
				{
					$query->clear()
						->select('SUBSTRING_INDEX(SUBSTRING(link, LOCATE("id=",link)+3, 6), "&", 1)')
						->from($this->db->quoteName('#__menu'))
						->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($params->mod_emundus_footer_cookies_alias));
					$this->db->setQuery($query);
					$cookies->id = $this->db->loadResult();
				}
				else
				{
					$cookies->alias = 'politique-des-cookies';
				}
				$cookies->title     = JText::_('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_COOKIES');
				$cookies->published = $params->mod_emundus_footer_cookies;
				$rgpd_articles[]    = $cookies;

				$accessibility = new stdClass();
				if (!empty($params->mod_emundus_footer_accessibility_alias))
				{
					$query->clear()
						->select('SUBSTRING_INDEX(SUBSTRING(link, LOCATE("id=",link)+3, 6), "&", 1)')
						->from($this->db->quoteName('#__menu'))
						->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($params->mod_emundus_footer_accessibility_alias));
					$this->db->setQuery($query);
					$accessibility->id = $this->db->loadResult();
				}
				else
				{
					$accessibility->alias = 'accessibilite';
				}
				$accessibility->title     = JText::_('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_ACCESSIBILITY');
				$accessibility->published = $params->mod_emundus_footer_accessibility;
				$rgpd_articles[]          = $accessibility;
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $rgpd_articles;
	}

	function publishArticle($publish, $article_id = 0, $article_alias = '')
	{
		$result = false;

		$query = $this->db->getQuery(true);

		try
		{
			$query->select('id,params')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus_footer'));
			$this->db->setQuery($query);
			$footer = $this->db->loadObject();

			if (!empty($footer->params))
			{
				$params = json_decode($footer->params, true);

				if (empty($article_id))
				{
					switch ($article_alias)
					{
						case 'mentions-legales':
							$params['mod_emundus_footer_legal_info'] = $publish;
							break;
						case 'politique-de-confidentialite-des-donnees':
							$params['mod_emundus_footer_data_privacy'] = $publish;
							break;
						case 'gestion-des-droits':
							$params['mod_emundus_footer_rights'] = $publish;
							break;
						case 'politique-de-cookies':
						case 'gestion-des-cookies':
							$params['mod_emundus_footer_cookies'] = $publish;
							break;
						case 'accessibilite':
							$params['mod_emundus_footer_accessibility'] = $publish;
							break;
					}
				}
				else
				{
					$query->clear()
						->select('alias')
						->from($this->db->quoteName('#__menu'))
						->where('SUBSTRING_INDEX(SUBSTRING(link, LOCATE("id=",link)+3, 6), "&", 1) = ' . $this->db->quote($article_id));
					$this->db->setQuery($query);
					$article_alias = $this->db->loadResult();

					if (!empty($article_alias))
					{
						$section         = array_search($article_alias, $params, true);
						$section_to_edit = str_replace('_alias', '', $section);

						$params[$section_to_edit] = $publish;
					}
				}

				$query->clear()
					->update($this->db->quoteName('#__modules'))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($footer->id));
				$this->db->setQuery($query);
				$result = $this->db->execute();

				if ($result)
				{
					$query->clear()
						->select('id, value')
						->from('#__falang_content')
						->where('reference_id = ' . $this->db->quote($footer->id))
						->andWhere('reference_table = ' . $this->db->quote('modules'))
						->andWhere('reference_field = ' . $this->db->quote('params'));

					$this->db->setQuery($query);
					$falang_contents = $this->db->loadObjectList();

					if (!empty($falang_contents))
					{
						foreach ($falang_contents as $falang_content)
						{
							$falang_content->value = json_decode($falang_content->value, true);

							$falang_content->value['mod_emundus_footer_legal_info']    = $params['mod_emundus_footer_legal_info'];
							$falang_content->value['mod_emundus_footer_data_privacy']  = $params['mod_emundus_footer_data_privacy'];
							$falang_content->value['mod_emundus_footer_rights']        = $params['mod_emundus_footer_rights'];
							$falang_content->value['mod_emundus_footer_cookies']       = $params['mod_emundus_footer_cookies'];
							$falang_content->value['mod_emundus_footer_accessibility'] = $params['mod_emundus_footer_accessibility'];

							$query->clear()
								->update('#__falang_content')
								->set('value = ' . $this->db->quote(json_encode($falang_content->value)))
								->where('id = ' . $this->db->quote($falang_content->id));

							$this->db->setQuery($query);
							$this->db->execute();
						}
					}

					require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php');
					$h_update = new EmundusHelperUpdate();
					$h_update->clearJoomlaCache();
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $result;
	}


	function getArticlePublishedState($article_id = 0, $article_alias = '')
	{
		$published = 0;

		$query = $this->db->getQuery(true);

		try
		{
			$query->select('params')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus_footer'));
			$this->db->setQuery($query);
			$footer = $this->db->loadObject();

			if (!empty($footer->params))
			{
				$params = json_decode($footer->params, true);

				if (empty($article_id))
				{
					switch ($article_alias)
					{
						case 'mentions-legales':
							$published = $params['mod_emundus_footer_legal_info'];
							break;
						case 'politique-de-confidentialite-des-donnees':
							$published = $params['mod_emundus_footer_data_privacy'];
							break;
						case 'gestion-des-droits':
							$published = $params['mod_emundus_footer_rights'];
							break;
						case 'gestion-des-cookies':
						case 'politique-de-cookies':
							$published = $params['mod_emundus_footer_cookies'];
							break;
						case 'accessibilite':
							$published = $params['mod_emundus_footer_accessibility'];
							break;
					}
				}
				else
				{
					$query->clear()
						->select('alias')
						->from($this->db->quoteName('#__menu'))
						->where('SUBSTRING_INDEX(SUBSTRING(link, LOCATE("id=",link)+3, 6), "&", 1) = ' . $this->db->quote($article_id));
					$this->db->setQuery($query);
					$article_alias = $this->db->loadResult();

					if (!empty($article_alias))
					{
						$section         = array_search($article_alias, $params, true);
						$section_to_edit = str_replace('_alias', '', $section);
						$published       = $params[$section_to_edit];
					}
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $published;
	}

	function getMenuId($link = '', $alias = '')
	{
		$itemId = 0;
		$query  = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'));
			if (!empty($link))
			{
				$query->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote($link));
			}
			elseif (!empty($alias))
			{
				$query->where($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote($alias));
			}
			$this->db->setQuery($query);
			$itemId = $this->db->loadResult();
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $itemId;
	}

	/**
	 * Get only accessibles parameters based on settings-applicants.json and settings-general.json files
	 * This function is used to avoid exposing all parameters to the front-end
	 * @return array
	 */
	public function getEmundusParams()
	{
		$params = ['emundus' => [], 'joomla' => []];

		$settings_general            = file_get_contents(JPATH_ROOT . '/components/com_emundus/src/assets/data/settings/sections/json/site-settings.json');
		$settings_applicants         = file_get_contents(JPATH_ROOT . '/components/com_emundus/src/assets/data/settings/sections/json/file-settings.json');
		$settings_mail_server_custom = file_get_contents(JPATH_ROOT . '/components/com_emundus/src/assets/data/settings/emails/json/custom.json');
		$settings_mail_base          = file_get_contents(JPATH_ROOT . '/components/com_emundus/src/assets/data/settings/emails/json/global.json');
		$settings_mail_value         = file_get_contents(JPATH_ROOT . '/components/com_emundus/src/assets/data/settings/emails/json/values.json');

		$settings_applicants         = json_decode($settings_applicants, true);
		$settings_general            = json_decode($settings_general, true);
		$settings_mail_base          = json_decode($settings_mail_base, true);
		$settings_mail_server_custom = json_decode($settings_mail_server_custom, true);
		$settings_mail_value         = json_decode($settings_mail_value, true);

		$emundus_parameters = ComponentHelper::getParams('com_emundus');

		// Users params
		$params['emundus']['disable_inactive_accounts_after_delay'] = $emundus_parameters->get('disable_inactive_accounts_after_delay', 12);
		$params['emundus']['delete_testing_accounts_after_delay']   = $emundus_parameters->get('delete_testing_accounts_after_delay', 12);

		foreach ($settings_applicants as $settings_applicant)
		{
			if ($settings_applicant['component'] === 'emundus')
			{
				$params['emundus'][$settings_applicant['param']] = $emundus_parameters->get($settings_applicant['param']);
			}
			else
			{
				$params['joomla']->$settings_applicant['param'] = $this->app->getConfig()->get($settings_applicant['param']);
			}
		}

		foreach ($settings_general as $setting_general)
		{
			if ($setting_general['component'] === 'emundus')
			{
				$params['emundus'][$setting_general['param']] = $emundus_parameters->get($setting_general['param']);
			}
			else
			{
				$params['joomla'][$setting_general['param']] = $this->app->getConfig()->get($setting_general['param']);
			}
		}

		foreach ($settings_mail_base as $setting_mail_base)
		{
			if ($setting_mail_base['component'] === 'emundus')
			{
				$params['emundus'][$setting_mail_base['param']] = $emundus_parameters->get($setting_mail_base['param']);
			}
			else
			{
				$params['joomla'][$setting_mail_base['param']] = $this->app->getConfig()->get($setting_mail_base['param']);
			}
		}

		foreach ($settings_mail_server_custom as $setting_mail_server_custom)
		{
			if ($setting_mail_server_custom['component'] === 'emundus')
			{
				if ($setting_mail_server_custom['type'] == 'password')
				{
					$params['emundus'][$setting_mail_server_custom['param']] = '************';
				}
				else
				{
					$params['emundus'][$setting_mail_server_custom['param']] = $emundus_parameters->get($setting_mail_server_custom['param']);
				}
				$params['emundus'][$setting_mail_server_custom['param']] = $emundus_parameters->get($setting_mail_server_custom['param']);
			}
			else
			{
				$params['joomla'][$setting_mail_server_custom['param']] = $this->app->getConfig()->get($setting_mail_server_custom['param']);
			}
		}

		foreach ($settings_mail_value as $setting_mail_value)
		{
			if ($setting_mail_value['component'] === 'emundus')
			{
				if ($setting_mail_value['param'] == 'custom_email_smtppass')
				{
					$params['emundus'][$setting_mail_value['param']] = '************';
				}
				else
				{
					$params['emundus'][$setting_mail_value['param']] = $emundus_parameters->get($setting_mail_value['param']);
				}
			}
			else
			{
				$params['joomla'][$setting_mail_value['param']] = $this->app->getConfig()->get($setting_mail_value['param']);
			}
		}

		$params['emundus']['attachment_storage'] = $emundus_parameters->get('attachment_storage');

		return $params;
	}

	public function getEmailParameters()
	{
		$params   = [];
		$emConfig = ComponentHelper::getComponent('com_emundus')->getParams();

		$params['mailonline']              = $this->app->get('mailonline');
		$params['replyto']                 = $this->app->get('replyto', '');
		$params['replytoname']             = $this->app->get('replytoname', '');
		$params['fromname']                = $this->app->get('fromname', '');
		$params['custom_email_conf']       = $emConfig->get('custom_email_conf', 0);
		$params['custom_email_mailfrom']   = $emConfig->get('custom_email_mailfrom', '');
		$params['custom_email_smtphost']   = $emConfig->get('custom_email_smtphost', '');
		$params['custom_email_smtpport']   = $emConfig->get('custom_email_smtpport', '');
		$params['custom_email_smtpauth']   = $emConfig->get('custom_email_smtpauth', 1);
		$params['custom_email_smtpsecure'] = $emConfig->get('custom_email_smtpsecure', 0);
		$params['custom_email_smtpuser']   = $emConfig->get('custom_email_smtpuser', '');
		$params['custom_email_smtppass']   = '************';
		$params['default_email_mailfrom']  = $emConfig->get('default_email_mailfrom', $this->app->get('mailfrom'));

		return $params;
	}

	/**
	 * @param $component
	 * @param $param
	 * @param $value
	 *
	 * @return bool
	 */
	public function updateEmundusParam($component, $param, $value, $config)
	{
		$updated = false;

		if (!empty($param))
		{
			$params = $this->getEmundusParams();
			switch ($component)
			{
				case 'emundus':
					if (array_key_exists($param, $params['emundus']))
					{
						if ($param === 'limit_files_status')
						{
							$value = array_map(function ($item) {
								return (int) $item->step;
							}, $value);
						}

						$eMConfig = ComponentHelper::getParams('com_emundus');
						$eMConfig->set($param, $value);
						$componentid = ComponentHelper::getComponent('com_emundus')->id;
						$query       = $this->db->getQuery(true);

						$query->update($this->db->quoteName('#__extensions'))
							->set($this->db->quoteName('params') . ' = ' . $this->db->quote($eMConfig->toString()))
							->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($componentid));

						try
						{
							$this->db->setQuery($query);
							$updated = $this->db->execute();

						}
						catch (Exception $e)
						{
							Log::add('Error set param ' . $param . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
						}
					}
					else
					{
						Log::add('Error : unable to detect if param is writable or not : ' . $param, Log::WARNING, 'com_emundus.error');
					}

					break;
				case 'joomla':
				default:
					if (array_key_exists($param, $params['joomla']))
					{
						if (!class_exists('EmundusHelperUpdate'))
						{
							require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php');
						}

						$options = array(
							$param => $value
						);
						$updated = EmundusHelperUpdate::updateConfigurationFile($options);

					}
					else
					{
						Log::add('Error : unable to detect if param is writable or not : ' . $param, Log::WARNING, 'com_emundus.error');
					}
					break;
			}
		}

		if ($updated)
		{
			$user           = Factory::getApplication()->getIdentity();
			$this->userID   = $user->id;
			$this->userName = $user->name;
			if ($value !== "")
			{
				if ($param == 'smtppass' || $param == 'custom_email_smtppass')
				{
					$value = '************';
				}
				Log::add("User, $this->userName  id: $this->userID, change $param to, new value $value", Jlog::INFO, 'com_emundus.settings');
			}
			else
			{
				Log::add("User, , $this->userName id: $this->userID, change $param to, to an emphy value", Jlog::INFO, 'com_emundus.settings');
			}
		}

		return $updated;

	}

	public function setArticleNeedToBeModify()
	{

		$componentid = ComponentHelper::getComponent('com_emundus')->id;
		$query       = $this->db->getQuery(true);

		$query->update($this->db->quoteName('#__content'))
			->set($this->db->quoteName('attribs') . ' = ' . $this->db->quote('{"note":"need to modify"}'))
			->where($this->db->quoteName('attribs') . ' = ' . $this->db->quote('{"asset_id":"106"}'));
		$this->db->setQuery($query);
		$updated = $this->db->execute();
	}

	public function getArticleNeedToBeModify()
	{
		$query = $this->db->getQuery(true);
		$query->select('*')
			->from($this->db->quoteName('#__content'))
			->where($this->db->quoteName('note') . ' = ' . $this->db->quote("need to modify"));
		$this->db->setQuery($query);
		$result = $this->db->loadObjectList();

		return $result;
	}


	public function getFavicon()
	{
		$favicon = 'images/custom/default_favicon.ico';

		$yaml = Yaml::parse(file_get_contents(JPATH_ROOT . '/templates/g5_helium/custom/config/default/page/assets.yaml'));

		if (!empty($yaml))
		{
			$favicon_gantry = $yaml['favicon'];

			if (!empty($favicon_gantry))
			{
				$favicon = str_replace('gantry-media:/', 'images', $favicon_gantry);

				if (!file_exists(JPATH_ROOT . '/' . $favicon))
				{
					$favicon = 'images/custom/default_favicon.ico';
				}
			}
		}

		return $favicon;
	}

	public function getEmailTemplate($subject)
	{
		$query = $this->db->getQuery(true);
		$query->select('id')
			->from($this->db->quoteName('#__emundus_setup_emails'))
			->where($this->db->quoteName('subject') . ' = ' . $this->db->quote($subject));
		$this->db->setQuery($query);
		$this->db->execute();
		$result = $this->db->loadResult();

		return $result;
	}

	public function sendTestMailSettings($variables, $user = null, $mail_to = null)
	{
		$result = [
			'status' => false,
			'title'  => Text::_('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_SUCCESS'),
			'text'   => Text::sprintf('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_SUCCESS_BODY', !empty($mail_to) ? $mail_to : $variables['mailfrom']),
			'desc'   => ''
		];

		$app    = Factory::getApplication();
		$config = $app->getConfig();
		if (empty($user))
		{
			$user = $app->getIdentity();
		}

		$logo = EmundusHelperEmails::getLogo(true);

		// Prepare post data
		$post = [
			'SITE_URL'  => Uri::base(),
			'SITE_NAME' => $app->get('sitename'),
			'LOGO'      => Uri::base() . 'images/custom/' . $logo,
		];
		$keys = [];
		foreach (array_keys($post) as $key)
		{
			$keys[] = '/\[' . $key . '\]/';
		}

		require_once(JPATH_ROOT . '/components/com_emundus/models/messages.php');
		$m_messages = new EmundusModelMessages();
		$template   = $m_messages->getEmail('mail_tester');
		$body       = $template->message;
		$subject    = $template->subject;

		$subject  = preg_replace($keys, $post, $subject);
		$body_raw = strip_tags($body);
		if (isset($template->Template))
		{
			$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $template->Template);
			$body = preg_replace($keys, $post, $body);
		}
		else
		{
			$body = preg_replace($keys, $post, $body);
		}

		// Create a new mailer instance
		$config = new Registry();
		foreach ($variables as $key => $variable)
		{
			$config->set($key, $variable);
		}

		$mail_to = !empty($mail_to) ? $mail_to : $variables['mailfrom'];

		$mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer($config);
		$mailer->setSender($variables['mailfrom'], $variables['fromname']);
		$mailer->addRecipient($mail_to, $variables['fromname']);
		if (!empty($variables['replyto']))
		{
			$mailer->addReplyTo($variables['replyto'], $variables['replytoname']);
		}
		$mailer->setSubject($subject);
		$mailer->isHTML(true);
		$mailer->Encoding = 'base64';
		$mailer->setBody($body);
		$mailer->AltBody = $body_raw;

		try
		{
			$result['status'] = $mailer->send();
		}
		catch (MailDisabledException|phpMailerException $e)
		{
			$result['status'] = false;
			$result['title']  = Text::_('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_ERROR');
			$result['text']   = Text::sprintf('COM_CONFIG_SENDMAIL_ERROR', $config['mailfrom']);
			$result['desc']   = Text::_($this->convertTextException($e->getMessage()));
		}

		if ($result['status'] && $mailer->Mailer !== $app->get('mailer'))
		{
			$result['title']  = Text::_('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_ERROR');
			$result['status'] = false;
		}

		return $result;
	}

	public function convertTextException($textException)
	{
		if ($textException === 'SMTP Error: Could not connect to SMTP host. Failed to connect to server')
		{
			$textException = 'COM_EMUNDUS_ERROR_SMTP_HOST';
		}
		elseif ($textException === 'SMTP Error: Could not authenticate.')
		{
			$textException = 'COM_EMUNDUS_ERROR_SMTP_AUTH';
		}
		elseif (strpos($textException, 'Authentication required') !== false)
		{
			$textException = 'COM_EMUNDUS_ERROR_SMTP_TOGGLE_AUTH';
		}
		elseif ($textException === 'Could not instantiate mail function.')
		{
			$textException = 'COM_EMUNDUS_ERROR_MAIL_FUNCTION';
		}

		return $textException;
	}

	public function saveEmailParameters($config, $custom_config)
	{
		$saved = false;

		$emConfig = ComponentHelper::getParams('com_emundus');
		if (!empty($config))
		{
			// unset the password if it is not changed
			if ($config['smtppass'] == '************' || empty($config['smtppass']))
			{
				if ($custom_config == 1)
				{
					$config['smtppass'] = $emConfig->get('custom_email_smtppass');
				}
				else
				{
					$config['smtppass'] = $emConfig->get('default_email_smtppass');
				}
			}

			// First update configuration php
			require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
			EmundusHelperUpdate::updateConfigurationFile($config);

			// Then update default or emundus configuration
			if ($custom_config == 1)
			{
				$emConfig->set('custom_email_conf', 1);
				$emConfig->set('custom_email_mailfrom', $config['mailfrom']);
				$emConfig->set('custom_email_fromname', $config['fromname']);
				$emConfig->set('custom_email_replyto', $config['replyto']);
				$emConfig->set('custom_email_replytoname', $config['replytoname']);
				$emConfig->set('custom_email_smtphost', $config['smtphost']);
				$emConfig->set('custom_email_smtpport', $config['smtpport']);
				$emConfig->set('custom_email_smtpsecure', $config['smtpsecure']);
				$emConfig->set('custom_email_smtpauth', $config['smtpauth']);
				$emConfig->set('custom_email_smtpuser', $config['smtpuser']);
				if (!empty($config['smtppass']))
				{
					$emConfig->set('custom_email_smtppass', $config['smtppass']);
				}
			}
			else
			{
				$emConfig->set('custom_email_conf', 0);
				$emConfig->set('default_email_mailfrom', $config['mailfrom']);
			}

			$componentid = ComponentHelper::getComponent('com_emundus')->id;
			$query       = $this->db->getQuery(true);

			try
			{
				$query->update('#__extensions')
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote($emConfig->toString()))
					->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($componentid));
				$this->db->setQuery($query);
				$saved = $this->db->execute();
			}
			catch (Exception $e)
			{
				Log::add('Failed to update extension email parameters with error ' . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $saved;
	}

	public function getHistory($extension = 'com_emundus.settings', $only_pending = false, $page = 1, $limit = 10, $item_id = 0)
	{
		$history = [];

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('al.*,concat(u.name," - ",u.email) as logged_by')
				->from($this->db->quoteName('#__action_logs', 'al'))
				->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('al.user_id'))
				->where($this->db->quoteName('al.extension') . ' = ' . $this->db->quote($extension))
				->where('JSON_VALID(' . $this->db->quoteName('al.message') . ')');

			if ($only_pending)
			{
				$query->where('JSON_EXTRACT(' . $this->db->quoteName('al.message') . ', "$.status") = ' . $this->db->quote('pending'));
			}

			if (!empty($item_id))
			{
				$query->andWhere('item_id = ' . $this->db->quote($item_id));
			}

			$query->order($this->db->quoteName('al.log_date') . ' DESC');
			$this->db->setQuery($query, ($page - 1) * $limit, $limit);
			$history = $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $history;
	}

	public function getHistoryLength($extension = 'com_emundus.settings', $item_id = 0)
	{
		$length = 0;

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('COUNT(*)')
				->from($this->db->quoteName('#__action_logs', 'al'))
				->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('al.user_id'))
				->where($this->db->quoteName('al.extension') . ' = ' . $this->db->quote($extension))
				->where('JSON_VALID(' . $this->db->quoteName('al.message') . ')');

			if (!empty($item_id))
			{
				$query->andWhere('item_id = ' . $this->db->quote($item_id));
			}

			$this->db->setQuery($query);
			$length = $this->db->loadResult();
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $length;
	}

	public function updateHistoryStatus($action_log_id, $action_log_status = 'done')
	{
		$updated = false;

		try
		{
			$query = $this->db->getQuery(true);

			$query->update($this->db->quoteName('#__action_logs'))
				->set($this->db->quoteName('message') . ' = JSON_SET(' . $this->db->quoteName('message') . ', "$.status", ' . $this->db->quote($action_log_status) . ')')
				->set($this->db->quoteName('message') . ' = JSON_SET(' . $this->db->quoteName('message') . ', "$.status_updated", ' . $this->db->quote(date('Y-m-d H:i:s')) . ')')
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($action_log_id));
			$this->db->setQuery($query);
			$updated = $this->db->execute();
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $updated;
	}

	public function historyRetryEvent(int $action_log_id): bool
	{
		$retried = false;

		try
		{
			$query = $this->db->getQuery(true);
			$query->select('*')
				->from('#__action_logs')
				->where('id = ' . $this->db->quote($action_log_id));

			$this->db->setQuery($query);
			$action_log = $this->db->loadObject();

			$message = json_decode($action_log->message, true);
			if (!empty($message['retry']) && $message['retry'] === true)
			{
				if (!empty($message['retry_event']))
				{
					$event_parameters = $message['retry_event_parameters'] ?? [];

					PluginHelper::importPlugin('emundus');
					$event      = new GenericEvent($message['retry_event'], $event_parameters);
					$dispatcher = Factory::getApplication()->getDispatcher();
					$dispatcher->dispatch($message['retry_event'], $event);

					$message['retry'] = false;

					$query->clear()
						->update('#__action_logs')
						->set('message = ' . $this->db->quote(json_encode($message)))
						->where('id = ' . $this->db->quote($action_log_id));
					$this->db->setQuery($query);
					$this->db->execute();

					$retried = true;
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $retried;
	}

	public function getApplicants($search_query, $limit = 100, $event_id = 0, $applicantsExceptions = [])
	{
		$applicants = [];

		try
		{
			$query = $this->db->getQuery(true);

			$query->clear()
				->select('campaign')
				->from($this->db->quoteName('#__emundus_setup_events_repeat_campaign'))
				->where($this->db->quoteName('event') . ' = ' . $event_id);
			$this->db->setQuery($query);
			$campaigns = $this->db->loadColumn();

			if (empty($campaigns))
			{
				$query->clear()
					->select('programme')
					->from($this->db->quoteName('#__emundus_setup_events_repeat_program'))
					->where($this->db->quoteName('event') . ' = ' . $event_id);
				$this->db->setQuery($query);
				$programs = $this->db->loadColumn();

				if (!empty($programs))
				{
					$query->clear()
						->select('esc.id')
						->from($this->db->quoteName('#__emundus_setup_campaigns', 'esc'))
						->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esp.code') . ' = ' . $this->db->quoteName('esc.training'))
						->where($this->db->quoteName('esp.id') . ' IN (' . implode(',', $programs) . ')');
					$this->db->setQuery($query);
					$campaigns = $this->db->loadColumn();
				}
			}

			if (!empty($campaigns))
			{
				$query->clear()
					->select('ecc.id, ecc.applicant_id, esc.label')
					->from($this->db->quoteName('#__emundus_campaign_candidature', 'ecc'))
					->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('ecc.campaign_id') . ' = ' . $this->db->quoteName('esc.id'))
					->where($this->db->quoteName('ecc.campaign_id') . ' IN (' . implode(',', $campaigns) . ')');

				$this->db->setQuery($query);
				$candidatures = $this->db->loadObjectList();

				if (!empty($candidatures))
				{
					$ccIds = array_column($candidatures, 'id');

					$query->clear()
						->select('ccid')
						->from($this->db->quoteName('#__emundus_registrants'))
						->where($this->db->quoteName('event') . ' = ' . (int) $event_id)
						->where($this->db->quoteName('ccid') . ' IN (' . implode(',', $ccIds) . ')');

					$this->db->setQuery($query);
					$excludedIds = $this->db->loadColumn();


					if (!empty($excludedIds))
					{
						if (!empty($applicantsExceptions))
						{
							$excludedIds = array_diff($excludedIds, $applicantsExceptions);
						}

						$candidatures = array_filter($candidatures, fn($c) => !in_array($c->id, $excludedIds));
					}

					if (!empty($candidatures))
					{
						$applicantIds = array_column($candidatures, 'applicant_id');
						$query->clear()
							->select('user_id, firstname, lastname')
							->from($this->db->quoteName('#__emundus_users'))
							->where($this->db->quoteName('user_id') . ' IN (' . implode(',', $applicantIds) . ')');
						if (!empty($search_query))
						{
							$query->where('CONCAT(' . $this->db->quoteName('firstname') . ', " ", ' . $this->db->quoteName('lastname') . ') LIKE ' . $this->db->quote('%' . $search_query . '%'));
						}
						$this->db->setQuery($query);
						$users = $this->db->loadObjectList('user_id');

						foreach ($candidatures as $candidature)
						{
							if (isset($users[$candidature->applicant_id]))
							{
								$applicants[] = (object) [
									'value' => $candidature->id,
									'name'  => $users[$candidature->applicant_id]->lastname . ' ' . $users[$candidature->applicant_id]->firstname . ' - ' . $candidature->label
								];
							}
						}
						usort($applicants, function ($a, $b) {
							return strcmp($a->name, $b->name);
						});
					}
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $applicants;
	}


	public function getAvailableManagers($search_query, $limit = 100)
	{
		$managers = [];

		try
		{
			$query = $this->db->getQuery(true);

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_profiles'))
				->where($this->db->quoteName('published') . ' = 0')
				->where($this->db->quoteName('status') . ' = 1');
			$this->db->setQuery($query);
			$managers_profiles = $this->db->loadColumn();

			$query->clear()
				->select('u.id as value, CONCAT(' . $this->_db->quoteName('u.name') . '," - ",' . $this->_db->quoteName('u.email') . ') as name')
				->from($this->db->quoteName('#__users', 'u'))
				->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('eu.user_id') . ' = ' . $this->db->quoteName('u.id'))
				->leftJoin($this->db->quoteName('#__emundus_users_profiles', 'eup') . ' ON ' . $this->db->quoteName('eup.user_id') . ' = ' . $this->db->quoteName('u.id'))
				->where($this->db->quoteName('u.block') . ' = 0');
			$query->extendWhere(
				'AND',
				[
					$this->db->quoteName('eu.profile') . ' IN (' . implode(',', $managers_profiles) . ')',
					$this->db->quoteName('eup.profile_id') . ' IN (' . implode(',', $managers_profiles) . ')',
				],
				'OR'
			);

			if (!empty($search_query))
			{
				$query->extendWhere(
					'AND',
					[
						$this->db->quoteName('u.name') . ' LIKE ' . $this->db->quote('%' . $search_query . '%'),
						$this->db->quoteName('u.email') . ' LIKE ' . $this->db->quote('%' . $search_query . '%'),
					],
					'OR'
				);
			}
			$query->group([$this->db->quoteName('u.id'), $this->db->quoteName('u.name')]);
			$query->order($this->db->quoteName('u.name') . ' ASC');
			$this->db->setQuery($query);
			$managers = $this->db->loadObjectList();
		}

		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $managers;
	}

	public function getAvailableCampaigns($search_query, $limit = 100)
	{
		$campaigns = [];

		try
		{
			$query = $this->db->getQuery(true)
				->select(
					$this->db->quoteName('c.id') . ' AS value, ' .
					'CONCAT(' . $this->db->quoteName('c.label') . ', " (", ' .
					'IF(' . $this->db->quoteName('p.label') . ' IS NULL OR ' . $this->db->quoteName('p.label') . ' = "", ' .
					$this->db->quoteName('c.training') . ', ' . $this->db->quoteName('p.label') . '), ")") AS name'
				)
				->from($this->db->quoteName('#__emundus_setup_campaigns', 'c'))
				->join('LEFT', $this->db->quoteName('#__emundus_setup_programmes', 'p') .
					' ON ' . $this->db->quoteName('c.training') . ' = ' . $this->db->quoteName('p.code'));

			if (!empty($search_query))
			{
				$query->where($this->db->quoteName('c.label') . ' LIKE ' . $this->db->quote('%' . $search_query . '%'));
			}

			$query->order($this->db->quoteName('c.label') . ' ASC');

			$this->db->setQuery($query);
			$campaigns = $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $campaigns;
	}


	public function getAvailablePrograms($search_query, $limit = 100)
	{
		$programs = [];

		try
		{
			$query = $this->db->getQuery(true);

			if (empty($search_query))
			{
				$query->select('esp.id as value, esp.label as name')
					->from($this->db->quoteName('#__emundus_setup_programmes', 'esp'));
			}
			else
			{
				$current_lang_tag = Factory::getApplication()->getLanguage()->getTag();
				$query->clear()
					->select($this->db->quoteName('lang_id'))
					->from($this->db->quoteName('#__languages'))
					->where($this->db->quoteName('lang_code') . ' = ' . $this->db->quote($current_lang_tag));

				$this->db->setQuery($query);
				$current_lang_id = $this->db->loadResult();

				$query->clear()
					->select('esp.id as value, esp.label as name, fc.value as translated_label')
					->from($this->db->quoteName('#__emundus_setup_programmes', 'esp'));

				$query->leftJoin($this->db->quoteName('#__falang_content', 'fc') . ' ON ' . $this->db->quoteName('fc.reference_id') . ' = ' . $this->db->quoteName('esp.id')
					. ' AND ' . $this->db->quoteName('fc.reference_table') . ' = ' . $this->db->quote('emundus_setup_programmes')
					. ' AND ' . $this->db->quoteName('fc.reference_field') . ' = ' . $this->db->quote('label')
					. ' AND ' . $this->db->quoteName('fc.language_id') . ' = ' . $this->db->quote($current_lang_id));

				$query->where($this->db->quoteName('esp.label') . ' LIKE ' . $this->db->quote('%' . $search_query . '%')
					. ' OR ' . $this->db->quoteName('fc.value') . ' LIKE ' . $this->db->quote('%' . $search_query . '%'));
			}

			$query->order($this->db->quoteName('esp.label') . ' ASC');

			$this->db->setQuery($query);
			$programs = $this->db->loadObjectList();

			$programs = array_map(function ($program) {
				$program->name = !empty($program->translated_label) ? $program->translated_label : $program->name;

				return $program;
			}, $programs);
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $programs;
	}

	public function getAvailableProfiles($search_query, $limit = 100)
	{
		$profiles = [];

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('id as value, label as name')
				->from($this->db->quoteName('#__emundus_setup_profiles'))
				->where($this->db->quoteName('published') . ' = 0');

			if (!empty($search_query))
			{
				$query->where($this->db->quoteName('label') . ' LIKE ' . $this->db->quote('%' . $search_query . '%'));
			}

			$query->order($this->db->quoteName('label') . ' ASC');

			$this->db->setQuery($query, 0, $limit);
			$profiles = $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $profiles;
	}

	public function getApps()
	{
		$apps = [];

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_sync'))
				->where($this->db->quoteName('published') . ' = 1');
			$this->db->setQuery($query);
			$apps = $this->db->loadObjectList();

			foreach ($apps as $app)
			{
				$app->config = json_decode($app->config);
				if (!empty($app->config->authentication))
				{
					if (!empty($app->config->authentication->client_secret))
					{
						$app->config->authentication->client_secret = '************';
					}
					if (!empty($app->config->authentication->token))
					{
						$app->config->authentication->token = '************';
					}
					if (!empty($app->config->authentication->password))
					{
						$app->config->authentication->password = '************';
					}
				}

				$app->config = json_encode($app->config);
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $apps;
	}

	public function getApp($app_id = 0, $app_type = '')
	{
		$app = new stdClass();

		if (!empty($app_id) || !empty($app_type))
		{
			try
			{
				$query = $this->db->getQuery(true);

				$query->select('*')
					->from($this->db->quoteName('#__emundus_setup_sync'));
				if (!empty($app_id))
				{
					$query->where($this->db->quoteName('id') . ' = ' . $this->db->quote($app_id));
				}
				elseif (!empty($app_type))
				{
					$query->where($this->db->quoteName('type') . ' = ' . $this->db->quote($app_type));
				}
				$this->db->setQuery($query);
				$app = $this->db->loadObject();
			}
			catch (Exception $e)
			{
				Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $app;
	}

	public function setupApp($app_id, $setup, $user = null)
	{
		$mainframe = Factory::getApplication();
		$updated   = false;

		if (!empty($app_id) && !empty($setup))
		{
			try
			{
				$app = $this->getApp($app_id);

				if (!empty($app->id))
				{
					switch ($app->type)
					{
						case 'teams':
							$updated = $this->setupTeams($app, $setup);
							break;
						case 'microsoft_dynamics':
							$updated = $this->setupMicrosoftDynamics($app, $setup);
							break;
						case 'ammon':
							$updated = $this->setupAmmon($app, $setup);
							break;
						case 'ovh':
							$updated = $this->setupOvh($app, $setup);
							break;
						case 'sogecommerce':
							$updated = $this->setupSogecommerce($app, $setup);
							break;
						case 'stripe':
							$updated = $this->setupStripe($app, $setup);
							break;
						case 'yousign':
							$updated = $this->setupYousign($app, $setup);
						default:
							break;
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $updated;
	}

	public function updateConsumptions(string $type, string|array $consumptions): bool
	{
		$updated = false;

		try
		{
			if (!empty($type))
			{
				if (is_array($consumptions))
				{
					$consumptions = json_encode($consumptions);
				}

				$query = $this->db->getQuery(true);

				$query->update($this->db->quoteName('#__emundus_setup_sync'))
					->set($this->db->quoteName('consumptions') . ' = ' . $this->db->quote($consumptions))
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote($type));
				$this->db->setQuery($query);
				$updated = $this->db->execute();
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $updated;
	}

	public function setupTeams($app, $setup)
	{
		$updated = false;

		if (empty($app->config) || $app->config == '{}')
		{
			$config = [
				'base_url'       => 'https://graph.microsoft.com',
				'api_url'        => 'v1.0',
				'authentication' => [
					'route'            => 'https://login.microsoftonline.com/' . $setup->tenant_id . '/oauth2/v2.0/token',
					'method'           => 'post',
					'client_id'        => $setup->client_id,
					'client_secret'    => EmundusHelperFabrik::encryptDatas($setup->client_secret),
					'tenant_id'        => $setup->tenant_id,
					'email'            => $setup->email,
					'grant_type'       => 'client_credentials',
					'scope'            => 'https://graph.microsoft.com/.default',
					'type'             => 'bearer',
					'create_token'     => true,
					'token_attribute'  => 'access_token',
					'token_storage'    => 'database',
					'token_validity'   => 3600,
					'token'            => '',
					'token_expiration' => '',
					'content_type'     => 'form_params',
				]
			];
		}
		else
		{
			$config = json_decode($app->config, true);

			$config['authentication']['client_id']     = $setup->client_id;
			$config['authentication']['client_secret'] = EmundusHelperFabrik::encryptDatas($setup->client_secret);
			$config['authentication']['tenant_id']     = $setup->tenant_id;
			$config['authentication']['grant_type']    = 'client_credentials';
			$config['authentication']['content_type']  = 'form_params';
			$config['authentication']['email']         = $setup->email;
			$config['authentication']['scope']         = 'https://graph.microsoft.com/.default';
			$config['authentication']['route']         = 'https://login.microsoftonline.com/' . $setup->tenant_id . '/oauth2/v2.0/token';
		}

		try
		{
			$query = $this->db->getQuery(true);

			$query->update($this->db->quoteName('#__emundus_setup_sync'))
				->set($this->db->quoteName('config') . ' = ' . $this->db->quote(json_encode($config)))
				->set($this->db->quoteName('enabled') . ' = 1')
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($app->id));
			$this->db->setQuery($query);
			$updated = $this->db->execute();
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $updated;
	}

	public function setupMicrosoftDynamics($app, $setup)
	{
		$updated = false;

		if (empty($app->config) || $app->config == '{}')
		{
			$config = [
				'base_url'       => $setup->domain,
				'api_url'        => 'api/data/v9.2',
				'authentication' => [
					'route'            => 'https://login.microsoftonline.com/' . $setup->tenant_id . '/oauth2/v2.0/token',
					'method'           => 'post',
					'client_id'        => $setup->client_id,
					'client_secret'    => EmundusHelperFabrik::encryptDatas($setup->client_secret),
					'tenant_id'        => $setup->tenant_id,
					'email'            => $setup->email,
					'grant_type'       => 'client_credentials',
					'scope'            => $setup->domain . '/.default',
					'domain'           => $setup->domain,
					'type'             => 'bearer',
					'create_token'     => true,
					'token_attribute'  => 'access_token',
					'token_storage'    => 'database',
					'token_validity'   => 3600,
					'token'            => '',
					'token_expiration' => '',
					'content_type'     => 'form_params',
				]
			];
		}
		else
		{
			$config = json_decode($app->config, true);

			$config['base_url']                    = $setup->domain;
			$config['authentication']['client_id'] = $setup->client_id;
			// If the client secret is not changed, we keep the old one
			// check if all character of the client secret are * to avoid to encrypt a new one
			if (!empty($setup->client_secret) && strspn($setup->client_secret, '*') !== strlen($setup->client_secret))
			{
				$config['authentication']['client_secret'] = EmundusHelperFabrik::encryptDatas($setup->client_secret);
			}
			$config['authentication']['tenant_id']    = $setup->tenant_id;
			$config['authentication']['grant_type']   = 'client_credentials';
			$config['authentication']['content_type'] = 'form_params';
			$config['authentication']['scope']        = $setup->domain . '/.default';
			$config['authentication']['domain']       = $setup->domain;
			$config['authentication']['route']        = 'https://login.microsoftonline.com/' . $setup->tenant_id . '/oauth2/v2.0/token';
		}

		try
		{
			$query = $this->db->getQuery(true);

			$query->update($this->db->quoteName('#__emundus_setup_sync'))
				->set($this->db->quoteName('config') . ' = ' . $this->db->quote(json_encode($config)))
				->set($this->db->quoteName('enabled') . ' = 1')
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($app->id));
			$this->db->setQuery($query);
			$updated = $this->db->execute();
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $updated;
	}

	public function setupAmmon($app, $setup): bool
	{
		$updated = false;

		if (empty($app->config) || $app->config == '{}')
		{
			$config = [
				'base_url'       => $setup->base_url,
				'api_url'        => 'v1.0',
				'authentication' => [
					'route'            => 'api/init/' . $setup->api_key . '/Ammon?languageCode=FR&select=User',
					'method'           => 'post',
					'client_id'        => '',
					'client_secret'    => '',
					'login'            => $setup->login,
					'password'         => EmundusHelperFabrik::encryptDatas($setup->password),
					'login_attribute'  => 'username',
					'tenant_id'        => '',
					'email'            => '',
					'grant_type'       => 'client_credentials',
					'scope'            => 'https://graph.microsoft.com/.default',
					'type'             => 'token',
					'create_token'     => true,
					'token_attribute'  => 'results.token',
					'token_storage'    => 'database',
					'token_validity'   => 3600,
					'token'            => '',
					'token_expiration' => '',
					'content_type'     => 'json',
				]
			];
		}
		else
		{
			$config                               = json_decode($app->config, true);
			$config['base_url']                   = $setup->base_url;
			$config['api_key']                    = $setup->api_key;
			$config['authentication']['login']    = $setup->login;
			$config['authentication']['password'] = EmundusHelperFabrik::encryptDatas($setup->password);
			$config['authentication']['route']    = 'api/init/' . $setup->api_key . '/Ammon?languageCode=FR&select=User';
		}

		try
		{
			$query = $this->db->getQuery(true);
			$query->update($this->db->quoteName('#__emundus_setup_sync'))
				->set($this->db->quoteName('config') . ' = ' . $this->db->quote(json_encode($config)))
				->set($this->db->quoteName('enabled') . ' = 1')
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($app->id));
			$this->db->setQuery($query);
			$updated = $this->db->execute();

			if ($updated)
			{
				require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php');
				$columns = [
					['name' => 'created_by', 'type' => 'INT', 'null' => 0],
					['name' => 'created_date', 'type' => 'DATETIME', 'null' => 0],
					['name' => 'updated_by', 'type' => 'INT', 'null' => 1],
					['name' => 'updated_date', 'type' => 'DATETIME', 'null' => 1],
					['name' => 'fnum', 'type' => 'VARCHAR', 'length' => 28, 'null' => 0],
					['name' => 'session_id', 'type' => 'INT', 'null' => 0],
					['name' => 'file_status', 'type' => 'INT', 'null' => 0, 'default' => 0],
					['name' => 'force_new_user_if_not_found', 'type' => 'TINYINT', 'null' => 0, 'default' => 0],
					['name' => 'attempts', 'type' => 'INT', 'null' => 0, 'default' => 0],
					['name' => 'status', 'type' => 'VARCHAR', 'length' => 16, 'null' => 0, 'default' => 'pending']
				];
				EmundusHelperUpdate::createTable('jos_emundus_ammon_queue', $columns);

				// Enable scheduler task
				EmundusHelperUpdate::installExtension('plg_task_ammon', 'ammon', '', 'plugin', 1, 'task');
				EmundusHelperUpdate::enableEmundusPlugins('ammon', 'task');

				// Enable scheduler task
				$query->clear()
					->update($this->db->quoteName('#__scheduler_tasks'))
					->set($this->db->quoteName('state') . ' = 1')
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plg_task_ammon'));
				$this->db->setQuery($query);
				$this->db->execute();
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $updated;
	}

	private function setupOvh($app, $setup): bool
	{
		$updated = false;

		if (empty($app->config) || $app->config == '{}')
		{
			$config = [
				'authentication' => [
					'client_id'     => isset($setup->client_id) ?? '',
					'client_secret' => isset($setup->client_secret) ? EmundusHelperFabrik::encryptDatas($setup->client_secret) : '',
					'consumer_key'  => isset($setup->consumer_key) ?? '',
				]
			];
		}
		else
		{
			$config = json_decode($app->config, true);

			if (isset($setup->client_id))
			{
				$config['authentication']['client_id'] = $setup->client_id;
			}
			if (isset($setup->client_secret))
			{
				$config['authentication']['client_secret'] = EmundusHelperFabrik::encryptDatas($setup->client_secret);
			}
			if (isset($setup->consumer_key))
			{
				$config['authentication']['consumer_key'] = $setup->consumer_key;
			}
		}

		try
		{
			$query = $this->db->getQuery(true);
			$query->update($this->db->quoteName('#__emundus_setup_sync'))
				->set($this->db->quoteName('config') . ' = ' . $this->db->quote(json_encode($config)))
				->set($this->db->quoteName('enabled') . ' = 1')
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($app->id));
			$this->db->setQuery($query);
			$updated = $this->db->execute();

			if ($updated)
			{
				require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php');
				EmundusHelperUpdate::enableEmundusPlugins('sendsms', 'task');

				// Enable scheduler task
				$query->clear()
					->update($this->db->quoteName('#__scheduler_tasks'))
					->set($this->db->quoteName('state') . ' = 1')
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plg_task_sms_task_get'));
				$this->db->setQuery($query);
				$this->db->execute();

				// publish application menu link
				$query->clear()
					->update($this->db->quoteName('#__menu'))
					->set($this->db->quoteName('published') . ' = 1')
					->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=application&layout=sms&format=raw'));

				$this->db->setQuery($query);
				$this->db->execute();

				// publish send sms action menu link
				$query->clear()
					->update($this->db->quoteName('#__menu'))
					->set($this->db->quoteName('published') . ' = 1')
					->where($this->db->quoteName('link') . ' = ' . $this->db->quote('%index.php?option=com_emundus&view=sms&layout=send&format=raw%'));
				$this->db->setQuery($query);
				$this->db->execute();
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $updated;
	}

	private function setupSogecommerce($app, $setup): bool
	{
		$updated = false;

		if (empty($app->config) || $app->config == '{}')
		{
			$config = [
				'authentication' => [
					'client_id'     => isset($setup->authentication->client_id) ?? '',
					'client_secret' => isset($setup->authentication->client_secret) ? EmundusHelperFabrik::encryptDatas($setup->authentication->client_secret) : '',
				],
				'endpoint'       => isset($setup->endpoint) ?? '',
				'mode'           => isset($setup->mode) ?? 'TEST',
				'return_url'     => $setup->success_url ?? '',
			];
		}
		else
		{
			$config_keys = ['authentication', 'endpoint', 'mode', 'return_url'];
			$config      = json_decode($app->config, true);
			foreach ($config_keys as $key)
			{
				if (isset($setup->$key))
				{
					if ($key == 'authentication')
					{
						foreach ($setup->$key as $sub_key => $sub_value)
						{
							if (isset($config[$key][$sub_key]))
							{
								if ($sub_key == 'client_secret')
								{
									$config[$key][$sub_key] = EmundusHelperFabrik::encryptDatas($setup->$key->$sub_key);
								}
								else
								{
									$config[$key][$sub_key] = $setup->$key->$sub_key;
								}
							}
						}
					}
					else
					{
						$config[$key] = $setup->$key;
					}
				}
			}
		}

		try
		{
			$query = $this->db->getQuery(true);
			$query->update($this->db->quoteName('#__emundus_setup_sync'))
				->set($this->db->quoteName('config') . ' = ' . $this->db->quote(json_encode($config)))
				->set($this->db->quoteName('enabled') . ' = 1')
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($app->id));
			$this->db->setQuery($query);
			$updated = $this->db->execute();

		}
		catch (Exception $e)
		{
			Log::add('Failed to update sogecommerce settings : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $updated;
	}

	private function setupStripe($app, $setup): bool
	{
		$updated = false;

		if (empty($app->config) || $app->config == '{}')
		{
			$config = [
				'authentication' => [
					'client_secret' => isset($setup->authentication->client_secret) ? EmundusHelperFabrik::encryptDatas($setup->authentication->client_secret) : '',
					'webhook_secret' => isset($setup->authentication->webhook_secret) ? EmundusHelperFabrik::encryptDatas($setup->authentication->webhook_secret) : '',
				]
			];
		} else {
			$config = json_decode($app->config, true);

			if (isset($setup->authentication->client_secret))
			{
				$config['authentication']['client_secret'] = EmundusHelperFabrik::encryptDatas($setup->authentication->client_secret);
			}

			if (isset($setup->authentication->webhook_secret))
			{
				$config['authentication']['webhook_secret'] = EmundusHelperFabrik::encryptDatas($setup->authentication->webhook_secret);
			}
		}

		try
		{
			$query = $this->db->getQuery(true);
			$query->update($this->db->quoteName('#__emundus_setup_sync'))
				->set($this->db->quoteName('config') . ' = ' . $this->db->quote(json_encode($config)))
				->set($this->db->quoteName('enabled') . ' = 1')
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($app->id));
			$this->db->setQuery($query);
			$updated = $this->db->execute();
		}
		catch (Exception $e)
		{
			Log::add('Failed to update stripe settings : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $updated;
	}

	public function checkRequirements($app_id)
	{
		$checked = true;

		try
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';

			$app = $this->getApp($app_id);

			if ($app->type === 'microsoft_dynamics')
			{
				$checked = false;

				// Check if table data_microsoft_dynamics_entities exists
				$query = 'SHOW TABLES LIKE ' . $this->db->quote('data_microsoft_dynamics_entities');
				$this->db->setQuery($query);
				$table_exists = $this->db->loadResult();

				if (empty($table_exists))
				{
					// Create table jos_emundus_setup_availabilities
					$columns = [
						[
							'name'   => 'collectionname',
							'type'   => 'VARCHAR',
							'length' => 255,
							'null'   => 0
						],
						[
							'name'   => 'name',
							'type'   => 'VARCHAR',
							'length' => 255,
							'null'   => 0
						],
						[
							'name'   => 'entityid',
							'type'   => 'VARCHAR',
							'length' => 255,
							'null'   => 0
						]
					];
					$comment = 'This table is used to store all entities for Microsoft Dynamics integration';
					$created = EmundusHelperUpdate::createTable('data_microsoft_dynamics_entities', $columns, [], $comment);

					if (!$created['status'])
					{
						return false;
					}
				}

				// Insert entities
				require_once JPATH_SITE . '/components/com_emundus/models/sync.php';
				$m_sync = new EmundusModelSync();
				$params = [
					'$select' => 'logicalcollectionname,logicalname'
				];
				$result = $m_sync->callApi($app, 'entities', 'GET', $params, true);

				if ($result['status'] == 200)
				{
					if (empty($result['data']))
					{
						$checked = true;
					}
					else
					{
						$query = $this->db->getQuery(true);

						$query->delete($this->db->quoteName('data_microsoft_dynamics_entities'));
						$this->db->setQuery($query);
						if ($this->db->execute())
						{
							$query->clear()
								->insert($this->db->quoteName('data_microsoft_dynamics_entities'))
								->columns($this->db->quoteName('collectionname') . ', ' . $this->db->quoteName('name') . ', ' . $this->db->quoteName('entityid'));
							foreach ($result['data']->value as $entity)
							{
								if (!empty($entity->logicalcollectionname) && !empty($entity->logicalname) && !empty($entity->entityid))
								{
									$query->values($this->db->quote($entity->logicalcollectionname) . ', ' . $this->db->quote($entity->logicalname) . ', ' . $this->db->quote($entity->entityid));
								}
							}
							$this->db->setQuery($query);
							$checked = $this->db->execute();
						}
					}
				}
				//

				// Install event subscriber plugin
				EmundusHelperUpdate::installExtension('plg_emundus_microsoft_dynamics', 'microsoft_dynamics', null, 'plugin', 1, 'emundus', '{}', false, false);
				//
			}

			if ($app->type == 'teams')
			{
				// Install event subscriber plugin
				EmundusHelperUpdate::installExtension('plg_emundus_teams', 'teams', null, 'plugin', 1, 'emundus', '{}', false, false);
				//
			}

			if ($app->type === 'ovh')
			{
				// TODO: make sure action id sms is created
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $checked;
	}

	public function toggleEnable($app_id, $enabled = 1)
	{
		$updated = false;

		if (!empty($app_id))
		{
			$query = $this->db->getQuery(true);

			try
			{
				$query->update($this->db->quoteName('#__emundus_setup_sync'))
					->set($this->db->quoteName('enabled') . ' = ' . $enabled)
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($app_id));
				$this->db->setQuery($query);
				$updated = $this->db->execute();

				if ($updated)
				{
					$query->clear()
						->select('type')
						->from($this->db->quoteName('#__emundus_setup_sync'))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($app_id));
					$this->db->setQuery($query);
					$app_type = $this->db->loadResult();

					if ($app_type === 'ovh')
					{
						// Enable scheduler task
						$query->clear()
							->update($this->db->quoteName('#__scheduler_tasks'))
							->set($this->db->quoteName('state') . ' = ' . $enabled)
							->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plg_task_sms_task_get'));
						$this->db->setQuery($query);
						$this->db->execute();
					}
					if ($app_type === 'yousign')
					{
						// Enable scheduler task
						$query->clear()
							->update($this->db->quoteName('#__scheduler_tasks'))
							->set($this->db->quoteName('state') . ' = ' . $enabled)
							->where($this->db->quoteName('type') . ' = ' . $this->db->quote('yousign.api'));
						$this->db->setQuery($query);
						$this->db->execute();
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $updated;
	}

	public function saveAddon(array $addon): bool
	{
		$saved = false;

		if (!empty($addon))
		{
			switch ($addon['type'])
			{
				// todo: add more addons
				case 'sms':
				case 'payment':
				case 'anonymous':
					$config = [
						'enabled'   => $addon['enabled'],
						'displayed' => $addon['displayed'],
						'params'    => $addon['configuration']
					];

					$query = $this->db->createQuery();

					$query->update('#__emundus_setup_config')
						->set('value = ' . $this->db->quote(json_encode($config)))
						->where('namekey = ' . $this->db->quote($addon['type']));

					try
					{
						$this->db->setQuery($query);
						$saved = $this->db->execute();
					}
					catch (Exception $e)
					{
						Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
					}

					break;
			}
		}

		return $saved;
	}

	public function getAvailableGroups($search_query, $limit = 100)
	{
		$profiles = [];

		try
		{
			$query = $this->db->createQuery();

			$query->select('id as value,label')
				->from('#__emundus_setup_groups')
				->where('published = 1');
			if (!empty($search_query))
			{
				$query->where('label LIKE ' . $this->db->quote('%' . $search_query . '%'));
			}
			$query->order('label ASC');
			$this->db->setQuery($query);
			$profiles = $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $profiles;
	}

	public function getSAMLSettings()
	{
		$config = [];
		$query  = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select('idp_name,idp_entity_id')
				->from($this->db->quoteName('#__miniorange_saml_config'));
			$this->db->setQuery($query);
			$config = $this->db->loadAssoc();

			if (!empty($config) && !empty($config['idp_entity_id']))
			{
				$config['metadata_url'] = Uri::base() . '?morequest=sso&idp=' . $config['idp_entity_id'];
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $config;
	}

	public function getAddons()
	{
		$addons = [];

		try
		{
			$emConfig = ComponentHelper::getParams('com_emundus');

			if (!class_exists('AddonEntity'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/classes/Entities/Settings/AddonEntity.php');
			}

			$addon = new AddonEntity(
				'COM_EMUNDUS_ADDONS_MESSENGER',
				'messenger',
				'chat_bubble',
				'COM_EMUNDUS_ADDONS_MESSENGER_DESC'
			);

			$query = $this->db->getQuery(true);
			$query->select('enabled')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('module'))
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('mod_emundus_messenger_notifications'));
			$this->db->setQuery($query);
			$enabled = $this->db->loadResult();

			if ($enabled)
			{
				$query->clear()
					->select('count(id)')
					->from($this->db->quoteName('#__modules'))
					->where($this->db->quoteName('module') . ' = ' . $this->db->quote('mod_emundus_messenger_notifications'))
					->where($this->db->quoteName('published') . ' = 1');
				$this->db->setQuery($query);
				$enabled = $this->db->loadResult();

				$addon->setEnabled((bool) $enabled);
			}

			$messenger_configuration                                      = [
				'messenger_anonymous_coordinator'  => $emConfig->get('messenger_anonymous_coordinator', 0),
				'messenger_notifications_on_send'  => $emConfig->get('messenger_notifications_on_send', 1),
				'messenger_add_message_notif'      => $emConfig->get('messenger_add_message_notif', 0),
				'messenger_notify_users_programs'  => $emConfig->get('messenger_notify_users_programs', 0),
				'messenger_notify_groups'          => $emConfig->get('messenger_notify_groups', ''),
				'messenger_notify_users'           => $emConfig->get('messenger_notify_users', ''),
				'messenger_notify_frequency'       => $emConfig->get('messenger_notify_frequency', 'daily'),
				'messenger_notify_frequency_times' => $emConfig->get('messenger_notify_frequency_times', 0),
				'messenger_notify_frequency_type'  => $emConfig->get('messenger_notify_frequency_type', 'daily'),
			];
			$messenger_configuration['messenger_notify_frequency_custom'] = $messenger_configuration['messenger_notify_frequency_times'] . ' ' . $messenger_configuration['messenger_notify_frequency_type'];
			$addon->setConfiguration($messenger_configuration);

			$addons[] = $addon;

			$smsAddon = new AddonEntity(
				'COM_EMUNDUS_ADDONS_SMS',
				'sms',
				'send_to_mobile',
				'COM_EMUNDUS_ADDONS_SMS_DESC'
			);

			$query->clear()
				->select($this->db->quoteName('value'))
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('sms'));

			$this->db->setQuery($query);
			$params = json_decode($this->db->loadResult(), true);
			if ($params['displayed'])
			{
				$smsAddon->setEnabled($params['enabled'] ?? 0);
				$smsAddon->setDisplayed(true);
				$smsAddon->setConfiguration($params['params'] ?? []);
				$addons[] = $smsAddon;
			}

			$query->clear()
				->select($this->db->quoteName('value'))
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('ranking'));

			$this->db->setQuery($query);
			$params = $this->db->loadResult();

			if (!empty($params))
			{
				$params = json_decode($params, true);
				if ($params['displayed'])
				{
					$rankingAddon = new AddonEntity(
						Text::_('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_RANKING'),
						'ranking',
						'leaderboard',
						Text::_('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_RANKING_DESC')
					);
					$rankingAddon->setEnabled($params['enabled'] ?? 0);
					$rankingAddon->setDisplayed(true);
					$rankingAddon->setConfiguration($params['params'] ?? []);
					$addons[] = $rankingAddon;
				}
			}

			require_once(JPATH_ROOT . '/components/com_emundus/classes/Repositories/Payment/PaymentRepository.php');
			$payment_repo = new PaymentRepository();
			$paymentAddon = $payment_repo->getAddon();

			if ($paymentAddon->displayed == 1)
			{
				$addons[] = $paymentAddon;
			}

			$importAddon = new AddonEntity(
				'COM_EMUNDUS_ADDONS_IMPORT',
				'import',
				'csv',
				'COM_EMUNDUS_ADDONS_IMPORT_DESC'
			);

			$query->clear()
				->select($this->db->quoteName('value'))
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('import'));

			$this->db->setQuery($query);
			$params = json_decode($this->db->loadResult(), true);
			if ($params['displayed'])
			{
				$importAddon->setEnabled($params['enabled'] ?? 0);
				$importAddon->setDisplayed(true);
				$importAddon->setConfiguration($params['params'] ?? []);
				$addons[] = $importAddon;
			}

			$query->clear()
				->select($this->db->quoteName('value'))
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('anonymous'));

			$this->db->setQuery($query);
			$params = json_decode($this->db->loadResult(), true);

			if ($params['displayed']) {
				$addons[] = new AddonEntity(
					'COM_EMUNDUS_ADDONS_ANONYMOUS',
					'anonymous',
					'domino_mask',
					'COM_EMUNDUS_ADDONS_ANONYMOUS_DESC',
					json_encode($params['params']),
					$params['enabled'] ?? 0,
					1
				);
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $addons;
	}

	public function toggleAddon(string $type, int $enabled): bool
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';

		$updated = false;

		$query = $this->db->getQuery(true);

		try
		{
			switch ($type)
			{
				case 'messenger':
					// Check if the extension and module is installed
					$query->clear()
						->select('count(extension_id)')
						->from($this->db->quoteName('#__extensions'))
						->where($this->db->quoteName('type') . ' = ' . $this->db->quote('module'))
						->where($this->db->quoteName('element') . ' = ' . $this->db->quote('mod_emundus_messenger_notifications'));
					$this->db->setQuery($query);
					$extension_installed = $this->db->loadResult();

					if (empty($extension_installed))
					{
						// Install the extension
						EmundusHelperUpdate::installExtension('mod_emundus_messenger_notifications', 'mod_emundus_messenger_notifications', null, 'module', $enabled, '', '{}', false, false);
					}
					else
					{
						$query->clear()
							->update($this->db->quoteName('#__extensions'))
							->set($this->db->quoteName('enabled') . ' = ' . $this->db->quote($enabled))
							->where($this->db->quoteName('element') . ' = ' . $this->db->quote('mod_emundus_messenger_notifications'));
						$this->db->setQuery($query);
						$updated = $this->db->execute();
					}

					// Check if the module is installed
					$query->clear()
						->select('count(id)')
						->from($this->db->quoteName('#__modules'))
						->where($this->db->quoteName('module') . ' = ' . $this->db->quote('mod_emundus_messenger_notifications'));
					$this->db->setQuery($query);
					$module_installed = $this->db->loadResult();

					if (empty($module_installed))
					{
						// Install the module
						EmundusHelperUpdate::createModule('[APPLICANT] Messenger', 'header-c', 'mod_emundus_messenger_notifications', '{}', $enabled, 1, 1, 0, 0, false);
					}
					else
					{
						$query->clear()
							->update($this->db->quoteName('#__modules'))
							->set($this->db->quoteName('published') . ' = ' . $this->db->quote($enabled))
							->where($this->db->quoteName('module') . ' = ' . $this->db->quote('mod_emundus_messenger_notifications'));
						$this->db->setQuery($query);
						$updated = $this->db->execute();
					}

					// Publish emails
					$emails = ['messenger_reminder', 'messenger_reminder_group'];
					$query->clear()
						->update($this->db->quoteName('#__emundus_setup_emails'))
						->set('published = ' . $this->db->quote($enabled))
						->where('lbl IN (' . implode(',', $this->db->quote($emails)) . ')');
					$this->db->setQuery($query);
					$updated = $this->db->execute();

					// Publish messages menu in application menu
					$query->clear()
						->update($this->db->quoteName('#__menu'))
						->set('published = ' . $this->db->quote($enabled))
						->where('menutype = ' . $this->db->quote('application'))
						->where('link LIKE ' . $this->db->quote('index.php?option=com_emundus&view=messenger&format=raw&layout=coordinator'));
					$this->db->setQuery($query);
					$updated = $this->db->execute();
					break;
				case 'anonymous':
				case 'sms':
				case 'payment':
					$query->select('value')
						->from($this->db->quoteName('#__emundus_setup_config'))
						->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote($type));
					$this->db->setQuery($query);
					$params = json_decode($this->db->loadResult(), true);

					$params['enabled'] = $enabled === 1;
					$query->clear()
						->update($this->db->quoteName('#__emundus_setup_config'))
						->set($this->db->quoteName('value') . ' = ' . $this->db->quote(json_encode($params)))
						->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote($type));
					$this->db->setQuery($query);
					$updated = $this->db->execute();

				if ($type === 'payment')
					{
						$payment_repository = new PaymentRepository();
						$payment_action_id  = $payment_repository->getActionId();
						$query->clear()
							->update($this->db->quoteName('#__emundus_setup_step_types'));

						if ($enabled)
						{
							$query->set($this->db->quoteName('published') . ' = ' . $this->db->quote(1));
						}
						else
						{
							$query->set($this->db->quoteName('published') . ' = ' . $this->db->quote(0));
						}

						$query->where($this->db->quoteName('action_id') . ' = ' . $this->db->quote($payment_action_id));

						$this->db->setQuery($query);
						$updated = $this->db->execute();
					} else if ($type === 'anonymous')
					{
						$query->clear()
							->update($this->db->quoteName('#__menu'))
							->set('published = ' . $this->db->quote($enabled ? 1 : 0))
							->where('alias IN (' . $this->db->quote('connect-from-token') . ', ' . $this->db->quote('anonym-registration') . ')');

						$this->db->setQuery($query);
						$this->db->execute();
					}

					break;
				case 'ranking':
					$query->clear()
						->update($this->db->quoteName('#__menu'))
						->set('published = ' . $this->db->quote($enabled))
						->where('link LIKE ' . $this->db->quote('index.php?option=com_emundus&view=ranking'));
					$this->db->setQuery($query);
					$updated = $this->db->execute();

					$query->clear()
						->update($this->db->quoteName('#__emundus_setup_emails'))
						->set('published = ' . $this->db->quote($enabled))
						->where('lbl LIKE ' . $this->db->quote('ranking_locked'))
						->orWhere('lbl LIKE ' . $this->db->quote('ask_lock_ranking'));
					$this->db->setQuery($query);
					$updated = $this->db->execute();

					$query->clear()
						->select('value')
						->from($this->db->quoteName('#__emundus_setup_config'))
						->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('ranking'));

					$this->db->setQuery($query);
					$params = json_decode($this->db->loadResult(), true);

					$params['enabled'] = $enabled;
					$query->clear()
						->update($this->db->quoteName('#__emundus_setup_config'))
						->set($this->db->quoteName('value') . ' = ' . $this->db->quote(json_encode($params)))
						->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('ranking'));

					$this->db->setQuery($query);
					$updated = $this->db->execute();
				case 'import':
					// (Un)Publish emails
					$query->clear()
						->update($this->db->quoteName('#__emundus_setup_emails'))
						->set('published = ' . $this->db->quote($enabled))
						->where('lbl IN (' . $this->db->quote('import_account_created') . ', ' . $this->db->quote('import_file_created')  . ', ' . $this->db->quote('import_file_updated') . ')');
					$this->db->setQuery($query);
					$updated = $this->db->execute();

					// (Un)Publish import action
					$query->clear()
						->update($this->db->quoteName('#__emundus_setup_actions'))
						->set($this->db->quoteName('status') . ' = ' . $this->db->quote($enabled))
						->where($this->db->quoteName('name') . ' = ' . $this->db->quote('import'));
					$this->db->setQuery($query);
					$updated = $this->db->execute();

					$query->clear()
						->select('value')
						->from($this->db->quoteName('#__emundus_setup_config'))
						->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('import'));

					$this->db->setQuery($query);
					$params = json_decode($this->db->loadResult(), true);

					$params['enabled'] = $enabled;
					$query->clear()
						->update($this->db->quoteName('#__emundus_setup_config'))
						->set($this->db->quoteName('value') . ' = ' . $this->db->quote(json_encode($params)))
						->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('import'));

					$this->db->setQuery($query);
					$updated = $this->db->execute();

					break;
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $updated;
	}

	public function setupMessenger($setup)
	{
		$updated = false;

		try
		{
			$emConfig = ComponentHelper::getParams('com_emundus');

			// Extract all value key from $setup->messenger_notify_groups
			if (!empty($setup->messenger_notify_groups))
			{
				$setup->messenger_notify_groups = array_map(function ($group) {
					return $group->value;
				}, $setup->messenger_notify_groups);

				$setup->messenger_notify_groups = implode(',', $setup->messenger_notify_groups);
			}
			else
			{
				$setup->messenger_notify_groups = '';
			}
			if (!empty($setup->messenger_notify_users))
			{
				$setup->messenger_notify_users = array_map(function ($user) {
					return $user->value;
				}, $setup->messenger_notify_users);

				$setup->messenger_notify_users = implode(',', $setup->messenger_notify_users);
			}
			else
			{
				$setup->messenger_notify_users = '';
			}

			$emConfig->set('messenger_anonymous_coordinator', $setup->messenger_anonymous_coordinator);
			$emConfig->set('messenger_notifications_on_send', $setup->messenger_notifications_on_send);
			$emConfig->set('messenger_notify_users_programs', $setup->messenger_notify_users_programs);
			$emConfig->set('messenger_notify_groups', $setup->messenger_notify_groups);
			$emConfig->set('messenger_notify_users', $setup->messenger_notify_users);
			$emConfig->set('messenger_notify_frequency', $setup->messenger_notify_frequency);
			if ($setup->messenger_notify_frequency === 'custom')
			{
				$type     = 'daily';
				$times    = 1;
				$position = strrpos($setup->messenger_notify_frequency_custom, "daily");
				if ($position === false)
				{
					$position = strrpos($setup->messenger_notify_frequency_custom, "weekly");
				}

				if ($position !== false)
				{
					// Extract the first part (everything before "daily")
					$times = trim(substr($setup->messenger_notify_frequency_custom, 0, $position));
					if (!empty($times))
					{
						$emConfig->set('messenger_notify_frequency_times', $times);
					}

					// Extract the second part ("daily" and everything after it)
					$type = trim(substr($setup->messenger_notify_frequency_custom, $position));
					if (!empty($type))
					{
						$emConfig->set('messenger_notify_frequency_type', $type);
					}
				}
			}

			$componentid = ComponentHelper::getComponent('com_emundus')->id;

			$update  = [
				'extension_id' => $componentid,
				'params'       => $emConfig->toString()
			];
			$update  = (object) $update;
			$updated = $this->db->updateObject('#__extensions', $update, 'extension_id');

			// Update scheduler task
			$query = $this->db->getQuery(true);

			$query->select('id,execution_rules,cron_rules,next_execution')
				->from($this->db->quoteName('#__scheduler_tasks'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plg_task_emundusmessenger_task_get'));
			$this->db->setQuery($query);
			$task = $this->db->loadAssoc();

			if (!empty($task))
			{
				$execution_rules = json_decode($task['execution_rules'], true);
				$cron_rules      = json_decode($task['cron_rules'], true);

				switch ($setup->messenger_notify_frequency)
				{
					case 'daily':
						unset($execution_rules['interval-hours']);
						unset($execution_rules['interval-minutes']);

						$execution_rules['rule-type']     = 'interval-days';
						$execution_rules['interval-days'] = 1;

						$cron_rules['type'] = 'interval';
						$cron_rules['exp']  = 'P1D';

						$task['next_execution'] = $this->calcNextExecution();
						break;
					case 'weekly':
						unset($execution_rules['interval-hours']);
						unset($execution_rules['interval-minutes']);

						$execution_rules['rule-type']     = 'interval-days';
						$execution_rules['interval-days'] = 7;

						$cron_rules['type'] = 'interval';
						$cron_rules['exp']  = 'P7D';

						$task['next_execution'] = $this->calcNextExecution(7);
						break;
					case 'custom':
						if ($type == 'daily' && $times == 1)
						{
							unset($execution_rules['interval-hours']);
							unset($execution_rules['interval-minutes']);

							$execution_rules['rule-type']     = 'interval-days';
							$execution_rules['interval-days'] = $times;

							$cron_rules['type'] = 'interval';
							$cron_rules['exp']  = 'P1D';

							$task['next_execution'] = $this->calcNextExecution();
						}

						if ($type == 'weekly')
						{
							unset($execution_rules['interval-hours']);
							unset($execution_rules['interval-minutes']);

							$each_days                        = round(7 / $times);
							$execution_rules['rule-type']     = 'interval-days';
							$execution_rules['interval-days'] = $each_days;

							$cron_rules['type'] = 'interval';
							$cron_rules['exp']  = 'P' . $each_days . 'D';

							$task['next_execution'] = $this->calcNextExecution($each_days);
						}

						if ($type == 'daily')
						{
							unset($execution_rules['interval-days']);
							unset($execution_rules['interval-minutes']);

							$each_hours                        = round(24 / $times);
							$execution_rules['rule-type']      = 'interval-hours';
							$execution_rules['interval-hours'] = $each_hours;

							$cron_rules['type'] = 'interval';
							$cron_rules['exp']  = 'PT' . $each_hours . 'H';

							$task['next_execution'] = $this->calcNextExecution($each_hours, 'hour');
						}
						break;
				}

				$update_task = [
					'id'              => $task['id'],
					'execution_rules' => json_encode($execution_rules),
					'cron_rules'      => json_encode($cron_rules),
					'next_execution'  => $task['next_execution']
				];
				$update_task = (object) $update_task;
				$updated     = $this->db->updateObject('#__scheduler_tasks', $update_task, 'id');
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $updated;
	}

	private function calcNextExecution($times = 1, $cron_type = 'day')
	{
		$next_execution = null;

		$now = new DateTime();

		// Create a date for now at 12:00
		if ($cron_type === 'day')
		{
			$todayAtNoon = (clone $now)->setTime(12, 0);
		}
		else
		{
			$todayAtNoon = (clone $now);
		}

		// If it's before 12:00, we take today at 12:00
		if ($now < $todayAtNoon && $cron_type === 'day')
		{
			$nextNoon = $todayAtNoon;
		}
		else
		{
			// If it's after 12:00, we take future date at 12:00
			$nextNoon = (clone $todayAtNoon)->modify('+' . $times . ' ' . $cron_type);
		}

		$next_execution = $nextNoon->format('Y-m-d H:i:s');

		return $next_execution;
	}

	public function getEvents($search_query, $limit = 100, $user_id = 0)
	{
		$events = [];

		if (empty($user_id))
		{
			$user_id = $this->app->getIdentity()->id;
		}

		$query = $this->db->getQuery(true);

		try
		{
			require_once(JPATH_SITE . '/components/com_emundus/models/programme.php');
			$m_programme = new EmundusModelProgramme;
			$programs    = $m_programme->getUserPrograms($user_id);

			if (!empty($programs))
			{
				$event_ids = [];
				$query->clear()
					->select('esc.id')
					->from($this->db->quoteName('#__emundus_setup_campaigns', 'esc'))
					->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esp.code') . ' = ' . $this->db->quoteName('esc.training'))
					->where($this->db->quoteName('esp.code') . ' IN (' . implode(',', $this->db->quote($programs)) . ')');
				$this->db->setQuery($query);
				$campaigns = $this->db->loadColumn();

				if (!empty($campaigns))
				{
					$query->clear()
						->select('event')
						->from($this->db->quoteName('#__emundus_setup_events_repeat_campaign'))
						->where($this->db->quoteName('campaign') . ' IN (' . implode(',', $campaigns) . ')')
						->group($this->db->quoteName('event'));
					$this->db->setQuery($query);
					$event_ids = $this->db->loadColumn();
				}

				$query->clear()
					->select('event')
					->from($this->db->quoteName('#__emundus_setup_events_repeat_program', 'eserp'))
					->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esp.id') . ' = ' . $this->db->quoteName('eserp.programme'))
					->where($this->db->quoteName('esp.code') . ' IN (' . implode(',', $this->db->quote($programs)) . ')');
				$this->db->setQuery($query);
				$event_ids = array_merge($this->db->loadColumn(), $event_ids);

				if (!empty($event_ids))
				{
					$query->clear()
						->select('ese.id as value, ese.name')
						->from($this->db->quoteName('#__emundus_setup_events', 'ese'))
						->where($this->db->quoteName('ese.id') . ' IN (' . implode(',', $event_ids) . ')');
					if (!empty($search_query))
					{
						$query->where($this->db->quoteName('ese.name') . ' LIKE ' . $this->db->quote('%' . $search_query . '%'));
					}
					$query->group([$this->db->quoteName('ese.id'), $this->db->quoteName('ese.name')]);
					$query->order($this->db->quoteName('ese.name') . ' ASC');
					$this->db->setQuery($query, 0, $limit);
					$events = $this->db->loadObjectList();
				}
			}
		}

		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $events;
	}

	public function setupYousign($app, $setup)
	{
		$updated = false;

		$base_url = $setup->mode == 0 ? 'https://api-sandbox.yousign.app/v3' : 'https://api.yousign.app/v3';

		if (empty($app->config) || $app->config == '{}')
		{
			$config = [
				'base_url'                      => $base_url,
				'mode'                          => $setup->mode,
				'create_webhook'                => $setup->create_webhook,
				'signature_level'               => $setup->signature_level,
				'signature_authentication_mode' => $setup->signature_authentication_mode,
				'authentication'                => [
					'type'          => 'bearer',
					'token_storage' => 'database',
					'token'         => EmundusHelperFabrik::encryptDatas($setup->token)
				]
			];

			if (!empty($setup->expiration_date) && $setup->expiration_date != 'Invalid Date')
			{
				$config['expiration_date'] = $setup->expiration_date;
			}
			else
			{
				$config['expiration_date'] = null;
			}
		}
		else
		{
			$config = json_decode($app->config, true);

			$token = $setup->token;
			// If token have only * characters do not update it
			if (preg_match('/^\*+$/', $token))
			{
				$token = $config['authentication']['token'];
			}
			else
			{
				$token = EmundusHelperFabrik::encryptDatas($token);
			}

			$config['base_url']                      = $base_url;
			$config['authentication']['token']       = $token;
			$config['create_webhook']                = $setup->create_webhook;
			$config['signature_level']               = $setup->signature_level;
			$config['signature_authentication_mode'] = $setup->signature_authentication_mode;
			$config['mode']                          = $setup->mode;

			if (!empty($setup->expiration_date) && $setup->expiration_date != 'Invalid Date')
			{
				$config['expiration_date'] = $setup->expiration_date;
			}
			else
			{
				$config['expiration_date'] = null;
			}
		}

		try
		{
			$query = $this->db->getQuery(true);

			$query->update($this->db->quoteName('#__emundus_setup_sync'))
				->set($this->db->quoteName('config') . ' = ' . $this->db->quote(json_encode($config)))
				->set($this->db->quoteName('enabled') . ' = 1')
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($app->id));
			$this->db->setQuery($query);
			$updated = $this->db->execute();

			// Create scheduled task if not exists
			$query->clear()
				->select('id')
				->from($this->db->quoteName('jos_scheduler_tasks'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('yousign.api'));
			$this->db->setQuery($query);
			$task_id = $this->db->loadResult();

			if (empty($task_id))
			{
				$execution_rules = [
					'rule-type'        => 'interval-minutes',
					'interval-minutes' => 30,
					'exec-day'         => 01,
					'exec-time'        => '12:00'
				];
				$cron_rules      = [
					'type' => 'interval',
					'exp'  => 'PT30M'
				];

				if (!class_exists('EmundusHelperUpdate'))
				{
					require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
				}
				EmundusHelperUpdate::createSchedulerTask('Yousign', 'yousign.api', $execution_rules, $cron_rules);
			}
			else
			{
				// Just enable it
				$query->clear()
					->update($this->db->quoteName('#__scheduler_tasks'))
					->set($this->db->quoteName('state') . ' = 1')
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($task_id));
				$this->db->setQuery($query);
				$this->db->execute();
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $updated;
	}

	public function getAddonStatus(string $addon_type): array
	{
		try
		{
			$query = $this->db->getQuery(true);

			$query->select($this->db->quoteName('value'))
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = :namekey')
				->bind(':namekey', $addon_type);
			$this->db->setQuery($query);
			$result = $this->db->loadResult();

			if (!empty($result))
			{
				$result = json_decode($result, true);
				if (isset($result['enabled']))
				{
					$result['enabled']   = (bool) $result['enabled'];
					$result['displayed'] = (bool) $result['displayed'];
					$result['params']    = (array) $result['params'];
				}
			}
			else
			{
				$result = [
					'enabled'   => false,
					'displayed' => false,
					'params'    => []
				];
			}

			return $result;
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			throw $e;
		}
	}

	public function getFileInfosFromUploadId(int $upload_id): array
	{
		$infos = [];

		try
		{
			// Get file path
			$query = $this->db->getQuery(true);

			$query->select('eu.id, eu.filename, ecc.applicant_id, eu.thumbnail')
				->from($this->db->quoteName('#__emundus_uploads', 'eu'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.fnum') . ' = ' . $this->db->quoteName('eu.fnum'))
				->where($this->db->quoteName('eu.id') . ' = :upload_id')
				->bind(':upload_id', $upload_id, ParameterType::INTEGER);
			$this->db->setQuery($query);
			$file = $this->db->loadObject();

			if (!empty($file->filename))
			{
				$file_path = JPATH_ROOT . '/images/emundus/files/' . $file->applicant_id . '/' . $file->filename;

				// Check if file exists
				if (file_exists($file_path))
				{
					if (empty($file->thumbnail))
					{
						try
						{
							$thumbnail_width = 200;

							if (!extension_loaded('imagick'))
							{
								throw new Exception('Imagick extension is not loaded.');
							}

							$imagick = new Imagick();
							$imagick->setResolution(150, 150);
							$imagick->readImage($file_path . '[0]');
							$imagick->setImageFormat('png');
							$imagick->thumbnailImage($thumbnail_width, 0);

							// Convert image to base64
							$thumbnailData   = $imagick->getImageBlob();
							$base64Thumbnail = base64_encode($thumbnailData);

							$file->thumbnail = $base64Thumbnail;

							// Move to tmp directory instead of database or redis
							$this->db->updateObject('#__emundus_uploads', $file, 'id');
						}
						catch (Exception $e)
						{
							Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
							$base64Thumbnail = '';
						}

					}
					else
					{
						$base64Thumbnail = $file->thumbnail;
					}

					$infos['thumbnail'] = $base64Thumbnail;

					// If pdf, get text content
					if (pathinfo($file->filename, PATHINFO_EXTENSION) === 'pdf')
					{
						$parser                = new Parser();
						$pdf                   = $parser->parseFile($file_path);
						$infos['pages_length'] = count($pdf->getPages());
						$infos['text']         = $pdf->getText();
					}

					return $infos;
				}
				else
				{
					throw new Exception('File not found: ' . $file_path);
				}
			}
			else
			{
				throw new Exception('File not found for upload ID: ' . $upload_id);
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			throw $e;
		}
	}

	public function updateWebhook($type, $secret_key): bool
	{
		try
		{
			$query = $this->db->getQuery(true);

			$query->select('id, config')
				->from($this->db->quoteName('#__emundus_setup_sync'))
				->where($this->db->quoteName('type') . ' = :type')
				->bind(':type', $type);
			$this->db->setQuery($query);
			$sync = $this->db->loadObject();

			if (!empty($sync->id))
			{
				$config               = json_decode($sync->config, true);
				$config['secret_key'] = EmundusHelperFabrik::encryptDatas($secret_key);
				$sync->config         = json_encode($config);

				return $this->db->updateObject('#__emundus_setup_sync', $sync, 'id');
			}
			else
			{
				throw new Exception('Sync not found for type: ' . $type);
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			throw $e;
		}
	}

	public function swith2faMethods($methods): bool
	{
		try
		{
			$available_methods = [$this->db->quote('email'), $this->db->quote('totp')];
			$query = $this->db->getQuery(true);

			$query->select('extension_id, element, enabled')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
				->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('multifactorauth'))
				->where($this->db->quoteName('element') . ' IN ('.implode(',',$available_methods).')');
			$this->db->setQuery($query);
			$methods_db = $this->db->loadObjectList('element');

			foreach ($methods_db as $method)
			{
				if(in_array($method->element, $methods) && $method->enabled == 0)
				{
					$method->enabled = 1;

					$this->db->updateObject('#__extensions', $method, 'extension_id');
				}
				elseif(!in_array($method->element, $methods) && $method->enabled == 1)
				{
					$method->enabled = 0;

					$this->db->updateObject('#__extensions', $method, 'extension_id');
				}
			}

			return true;
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	public function update2faConfig(int $force, array $profiles): bool
	{
		try
		{
			$query = $this->db->getQuery(true);
			$query->select('extension_id, element, params')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
				->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'))
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('emundus'));
			$this->db->setQuery($query);
			$emundus_plugin = $this->db->loadObject();

			if($force === 1)
			{
				$params = json_decode($emundus_plugin->params, true);

				$params['2faForceForProfiles'] = $profiles;
				$emundus_plugin->params = json_encode($params);

				$this->db->updateObject('#__extensions', $emundus_plugin, 'extension_id');
			}
			else {
				$params = json_decode($emundus_plugin->params, true);

				$params['2faForceForProfiles'] = ['0'];
				$emundus_plugin->params = json_encode($params);

				$this->db->updateObject('#__extensions', $emundus_plugin, 'extension_id');
			}

			return true;
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			return false;
		}
	}

	public function get2faParameters(): array
	{
		$parameters = [];

		try
		{
			$query = $this->db->getQuery(true);
			$query->select('params')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
				->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'))
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('emundus'));
			$this->db->setQuery($query);
			$emundus_plugin = $this->db->loadResult();

			if (!empty($emundus_plugin))
			{
				$parameters = json_decode($emundus_plugin, true);
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $parameters;
	}
}
