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
use Tchooz\Repositories\Campaigns\CampaignRepository;

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

	/** @var string Token d'accès pour le layout storetoken */
	protected string $accessToken = '';

	/** @var string Fnum pour le layout storetoken */
	protected string $storetokenFnum = '';

	/** @var string Clé composite <token>::<fnum> pour le layout storetoken */
	protected string $compositeKey = '';

	/**
	 * @var bool Indique si l'utilisateur est en train de renouveler son token (true) ou s'il vient de le créer pour la première fois (false). Utilisé pour adapter les messages affichés dans le layout storetoken.
	 */
	protected bool $renew = false;

	protected int $campaignId = 0;

	public function display($tpl = null): void
	{
		$app   = Factory::getApplication();
		$input = $app->getInput();
		$layout = $input->getString('layout', '');
		$session = $app->getSession();

		if ($layout === 'storetoken' && $session->get(EmundusPublicAccess::SESSION_PUBLIC_STORED_ACCESS_KEY, true))
		{
			// already stored token, redirect to default view
			$this->setLayout('');
			$layout = '';
			$tpl = null;
		}

		if ($layout === 'storetoken')
		{
			$this->prepareStoreTokenLayout($app);
		}
		else
		{
			$this->prepareDefaultLayout($app, $input);
		}

		parent::display($tpl);
	}

	private function prepareDefaultLayout($app, $input): void
	{
		$this->fnum = $input->getString('fnum', '');

		// Check if the user already has a valid public session
		if (EmundusPublicAccess::isPublicAccessSession())
		{
			$this->isAlreadyAuthenticated = true;
			$app   = Factory::getApplication();
			$this->fnum = $app->getSession()->get(EmundusPublicAccess::SESSION_PUBLIC_FNUM_KEY, $this->fnum);
		}

		// Check for error from a failed authentication attempt
		$this->hasError     = (bool) $input->getInt('error', 0);
		$this->errorMessage = $input->getString('error_msg', '');
	}

	private function prepareStoreTokenLayout($app): void
	{
		$session = $app->getSession();

		$this->accessToken    = $session->get(EmundusPublicAccess::SESSION_PUBLIC_TOKEN_KEY, '');
		$this->storetokenFnum = $session->get(EmundusPublicAccess::SESSION_PUBLIC_FNUM_KEY, '');
		$this->renew          = $session->get(EmundusPublicAccess::RENEW_TOKEN_SESSION_KEY, false);

		if (empty($this->accessToken) || empty($this->storetokenFnum))
		{
			// No token data in session, redirect to default view
			$app->redirect(Route::_('index.php?option=com_emundus&view=publicaccess', false));
		}

		$this->compositeKey = EmundusPublicAccess::buildCompositeKey($this->accessToken, $this->storetokenFnum);
		$document = $app->getDocument();
		$document->addScriptOptions('com_emundus.fnum', $this->storetokenFnum);
		$wa = $document->getWebAssetManager();
		$wa->registerAndUseScript(
			'com_emundus.storetoken',
			'components/com_emundus/assets/js/storetoken.js',
			['version' => 'auto', 'defer' => true]
		);
	}
}

