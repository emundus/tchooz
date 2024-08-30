<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_version
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\EmundusOauth2\Administrator\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_emundus_oauth2.
 *
 * @since  5.1.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   5.1.0
     */
    protected function getLayoutData()
    {
	    require_once (JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'helpers'.DS.'menu.php');

        $data = parent::getLayoutData();

		$helper = $this->getHelperFactory()->getHelper('EmundusOauth2Helper');
		$data['active_directories'] = $helper->getActiveDirectories();

	    $data['state'] = bin2hex(random_bytes(128/8));
	    $data['nonce'] = \EmundusHelperMenu::getNonce();

        return $data;
    }
}
