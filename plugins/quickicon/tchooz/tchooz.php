<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die;

use Gantry\Component\Filesystem\Streams;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Gantry5\Loader;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Event\DispatcherInterface;

// Quick check to prevent fatal error in unsupported Joomla admin.
if (!class_exists(CMSPlugin::class)) {
	return;
}

/**
 * Class plgQuickiconGantry5
 */
class plgQuickiconTchooz extends CMSPlugin
{
	/** @var CMSApplication */
	protected $app;

	/**
	 * plgQuickiconGantry5 constructor.
	 * @param DispatcherInterface $subject
	 * @param array $config
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->loadLanguage('com_emundus.sys');
	}

	/**
	 * Display Gantry 5 backend icon
	 *
	 * @param string $context
	 * @return array|null
	 */
	public function onGetIcons($context)
	{
		if(!JFactory::getUser()->authorise('core.manage', 'com_emundus')) {
			return;
		}

		$quickicons = array(
			array(
				'link' => Route::_('http://localhost:8383/administrator/index.php?option=com_emundus&view=panel'),
				'image' => 'fa fa-graduation-cap',
				'text' => Text::_('COM_EMUNDUS'),
				'group' => 'MOD_QUICKICON_EXTENSIONS',
				'access' => array('core.manage', 'com_emundus')
			)
		);

		return $quickicons;
	}
}
