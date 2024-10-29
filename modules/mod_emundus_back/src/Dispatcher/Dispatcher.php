<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_articles_category
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Emundus\Module\BackButton\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_emundus_back
 *
 * @since  4.4.0
 */
class Dispatcher extends AbstractModuleDispatcher
{

    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   4.4.0
     */
    protected function getLayoutData(): array
    {
        $data   = parent::getLayoutData();
        $params = $data['params'];

		$data['back_link'] = Uri::base();
		if($params->get('back_type') == 'previous') {
			$data['back_link'] = "history.go(-1)";
		} else if ($params->get('back_type') == 'link') {
			$menu_id = $params->get('link', 0);
			if(!empty($menu_id)) {
				$data['back_link'] = Factory::getApplication()->getMenu()->getItem($menu_id)->alias;
			}
		}

        return $data;
    }
}
