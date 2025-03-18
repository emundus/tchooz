<?php
/**
 * Dashboard model used for the new dashboard in homepage.
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

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\ListModel;

class EmundusModelGallery extends ListModel
{
	protected $_db;

	public function __construct($config = array())
	{
		parent::__construct($config);

		require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';

		$this->_db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function getGalleryByList(int $listid): object
	{
		if(!class_exists('EmundusHelperCache'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/helpers/cache.php');
		}

		$cache   = new EmundusHelperCache('com_emundus');
		$cacheId = 'gallery_' . $listid;

		$gallery = $cache->get($cacheId);

		try {
			$query = $this->_db->getQuery(true);

			if (empty($gallery)) {
				$query->select('*')
					->from($this->_db->quoteName('#__emundus_setup_gallery'))
					->where($this->_db->quoteName('list_id') . ' = ' . $this->_db->quote($listid));
				$this->_db->setQuery($query);
				$gallery = $this->_db->loadObject();

				if (!empty($gallery)) {
					$query->clear()
						->select('title,fields')
						->from($this->_db->quoteName('#__emundus_setup_gallery_detail_tabs'))
						->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($gallery->id));
					$this->_db->setQuery($query);
					$gallery->tabs = $this->_db->loadObjectList();
				}

				$cache->set($cacheId, $gallery);
			}
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/gallery | Error when try to get gallery by list : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
		}

		return $gallery;
	}
}
