<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_articles_category
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Emundus\Module\BrowserCompatibility\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Environment\Browser;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_emundus_internet_explorer.
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

	    $data['compatible'] = $this->isCompatible();

	    $data['message'] = $params->get('message', Text::_('TEXT_DEFAULT'));

        return $data;
    }

	/**
	 * Check if the browser is compatible with eMundus
	 * @return bool
	 */
	public function isCompatible(): bool
	{
		$browser = Browser::getInstance();
		$agent   = $browser->getAgentString();

		if ($browser->isBrowser('msie') || $browser->isBrowser('ie')) {
			return false;
		}

		// Sur iOS, Firefox et Edge s'appuient sur le WebKit du systeme. Leur UA ne
		// porte pas de "Version/x", Browser les identifie donc en safari 0.0.
		if (preg_match('/(FxiOS|EdgiOS)\//', $agent)) {
			return true;
		}

		// Opera mobile a sa propre numerotation, sans rapport avec le desktop :
		// 86 sur Android quand le desktop est a 116, pour le meme Chromium.
		if ($browser->isBrowser('opera') && preg_match('/Mobile|Android/i', $agent)) {
			return $browser->getMajor() >= 76;
		}

		$minVersions = [
			'chrome' => 117,
			'firefox' => 116,
			'edge' => 117,
			'opera' => 102,
			'safari' => 17
		];

		foreach ($minVersions as $name => $min) {
			if ($browser->isBrowser($name)) {
				return $browser->getMajor() >= $min;
			}
		}

		return true;
	}
}
