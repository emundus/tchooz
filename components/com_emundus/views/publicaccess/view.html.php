<?php
/**
 * @package     com_emundus
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2005-2026 eMundus - All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Router\Route;
use Joomla\Plugin\System\EmundusPublicAccess\Extension\EmundusPublicAccess;

/**
 * View for public access token entry.
 * Allows a guest user to enter their access token to resume working on a public application file.
 *
 * @since 1.0.0
 */
class EmundusViewPublicaccess extends HtmlView
{
	protected string $fnum = '';
	protected bool $hasError = false;
	protected string $errorMessage = '';
	protected bool $isAlreadyAuthenticated = false;

	public function display($tpl = null): void
	{
		$app   = Factory::getApplication();
		$input = $app->getInput();

		$this->fnum = $input->getString('fnum', '');

		// Check if the user already has a valid public session
		if (EmundusPublicAccess::isPublicAccessSession())
		{
			$this->isAlreadyAuthenticated = true;
		}

		// Check for error from a failed authentication attempt
		$this->hasError     = (bool) $input->getInt('error', 0);
		$this->errorMessage = $input->getString('error_msg', '');

		parent::display($tpl);
	}
}

