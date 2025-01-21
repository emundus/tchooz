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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use PHPMailer\PHPMailer\Exception as phpMailerException;
use Symfony\Component\Yaml\Yaml;

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

				if ($files > 0)
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
				->update('#__emundus_setup_action_tag')
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote($label))
				->set($this->db->quoteName('class') . ' = ' . $this->db->quote('label-' . $color))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($tag));
			$this->db->setQuery($query);

			return $this->db->execute();
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
				->andWhere($this->db->quoteName('published') . ' = 1');
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


		$settings_general            = file_get_contents(JPATH_ROOT . '/components/com_emundus/src/assets/data/settings/sections/site-settings.js');
		$settings_applicants         = file_get_contents(JPATH_ROOT . '/components/com_emundus/src/assets/data/settings/sections/file-settings.js');
		$settings_mail_server_custom = file_get_contents(JPATH_ROOT . '/components/com_emundus/src/assets/data/settings/emails/custom.js');
		$settings_mail_base          = file_get_contents(JPATH_ROOT . '/components/com_emundus/src/assets/data/settings/emails/global.js');
		$settings_mail_value         = file_get_contents(JPATH_ROOT . '/components/com_emundus/src/assets/data/settings/emails/values.js');

		$settings_applicants         = json_decode(str_replace('export default', '', $settings_applicants), true);
		$settings_general            = json_decode(str_replace('export default', '', $settings_general), true);
		$settings_mail_base          = json_decode(str_replace('export default', '', $settings_mail_base), true);
		$settings_mail_server_custom = json_decode(str_replace('export default', '', $settings_mail_server_custom), true);
		$settings_mail_value         = json_decode(str_replace('export default', '', $settings_mail_value), true);

		$emundus_parameters = ComponentHelper::getParams('com_emundus');

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

		return $params;
	}

	public function getEmailParameters()
	{
		$params   = [];
		$emConfig = ComponentHelper::getComponent('com_emundus')->getParams();

		$params['mailonline']              = $this->app->get('mailonline');
		$params['replyto']                 = $this->app->get('replyto', '');
		$params['replytoname']             = $this->app->get('replytoname', '');
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

						$updated = EmundusHelperUpdate::updateConfigurationFileOld($config, $param, $value);

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
			$this->userID   = Factory::getUser()->id;
			$this->userName = Factory::getUser()->name;
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
				} else {
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
			$this->db->setQuery($query, 0, $limit);
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

			$this->db->setQuery($query, 0, $limit);
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

			if (empty($search_query)) {
				$query->select('esp.id as value, esp.label as name')
					->from($this->db->quoteName('#__emundus_setup_programmes', 'esp'));
			} else {
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

			$this->db->setQuery($query, 0, $limit);
			$programs = $this->db->loadObjectList();

			$programs = array_map(function($program) {
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

		if (empty($user))
		{
			$user = $mainframe->getIdentity();
		}

		if (!empty($app_id) && !empty($setup))
		{
			$query = $this->db->getQuery(true);

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
			}
			catch (Exception $e)
			{
				Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $updated;
	}

	public function getSAMLSettings()
	{
		$config = [];
		$query  = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select('idp_name,metadata_url')
				->from($this->db->quoteName('#__miniorange_saml_config'));
			$this->db->setQuery($query);
			$config = $this->db->loadAssoc();

			if (!empty($config) && !empty($config['metadata_url']))
			{
				$config['metadata_url'] = Uri::base() . '?morequest=sso&idp=' . $config['metadata_url'];
			}
		}
		catch (Exception $e)
		{
			Log::add('Error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $config;
	}

}
