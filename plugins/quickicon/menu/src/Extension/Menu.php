<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Quickicon.eos
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Quickicon\Menu\Extension;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Module\Quickicon\Administrator\Event\QuickIconsEvent;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla! end of support notification plugin
 *
 * @since 4.4.0
 */
final class Menu extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

	/** @var CMSApplication */
	protected $app;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since 4.4.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onGetIcons' => 'onGetIcons',
        ];
    }

	/**
	 * Display Tchooz backend icon
	 *
	 * @param string $context
	 * @return array|null
	 */
	public function onGetIcons(QuickIconsEvent $event)
	{
		$context = $event->getContext();
		$user = $this->app->getIdentity();

		if($context !== $this->params->get('context', 'mod_quickicon')
			|| !$user || !$user->authorise('core.manage', 'com_emundus')) {
			return null;
		}

		$menutype = $this->params->get('menutype', 'coordinatormenu');

		$db     = $this->getDatabase();
		$query  = $db->getQuery(true);
		$query->select('title')
			->from($db->quoteName('#__menu_types'))
			->where($db->quoteName('menutype') . ' = ' . $db->quote($menutype));
		$db->setQuery($query);
		$title = $db->loadResult();

		$result = $event->getArgument('result', []);

		$result[] = [
			[
				'link' => Route::_('/administrator/index.php?option=com_menus&view=items&menutype='.$menutype),
				'image' => 'fa fa-bars',
				'text' => $title,
				'group' => 'MOD_QUICKICON_EXTENSIONS',
			]
		];

		$event->setArgument('result', $result);
	}
}
