<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_version
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\EmundusVersion\Administrator\Helper;

use Joomla\CMS\Version;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_version
 *
 * @since  1.6
 */
class EmundusVersionHelper
{
    /**
     * Get the Joomla version number.
     *
     * @return  string  String containing the current Joomla version.
     *
     * @since  5.1.0
     */
    public function getVersionString()
    {
	    $release_version = '2.0.0';
	    $xmlDoc = new \DOMDocument();
	    if ($xmlDoc->load(JPATH_SITE . '/administrator/components/com_emundus/emundus.xml')) {
		    $release_version = $xmlDoc->getElementsByTagName('version')->item(0)->textContent;
	    }

		return $release_version;
    }

	public function getCurrentGitBranch()
	{
		$gitBranch = 'master';
		$gitBranchFile = JPATH_SITE . '/.git/HEAD';
		if (file_exists($gitBranchFile)) {
			$gitBranch = file_get_contents($gitBranchFile);
			$gitBranch =  explode("/", $gitBranch, 3);
			if(count($gitBranch) > 2) {
				$gitBranch = trim($gitBranch[2]);
			}
		}

		return $gitBranch;
	}

	public function getLastUpdate()
	{
		$last_updated = '';
		$git_file = JPATH_SITE . '/.git/FETCH_HEAD';
		if(is_file($git_file)) {
			$last_updated = date("d/m/Y", filemtime($git_file));
		}

		return $last_updated;
	}
}
