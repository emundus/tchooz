<?php

/**
 * @package         Emundus.Plugin
 * @subpackage      System.emunduspublicaccess
 *
 * @copyright       Copyright (C) 2005-2026 eMundus - All rights reserved.
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\EmundusPublicAccess\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Application\AfterRouteEvent;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Event\User\LogoutEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Tchooz\Entities\Automation\EventContextEntity;
use Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

/**
 * Plugin système qui intercepte les requêtes contenant un token d'accès public.
 *
 * Quand un utilisateur invité accède à une URL avec un paramètre `access_token` et un `fnum`,
 * ce plugin :
 * 1. Valide le token contre la table jos_emundus_file_access
 * 2. Vérifie que le dossier est public et que le token n'est pas expiré
 * 3. Injecte temporairement l'utilisateur système dans la session
 * 4. Initialise la session eMundus scopée au fnum du dossier, pas à l'utilisateur système global
 *
 * Gestion de la concurrence :
 * Chaque navigateur/invité possède sa propre session PHP (cookie distinct).
 * Le system_public_user_id est utilisé uniquement comme identité Joomla injectée,
 * mais la session eMundus est construite et scopée exclusivement au fnum validé par le token.
 * Ainsi, deux invités avec deux tokens différents ne voient que leur propre dossier,
 * même s'ils partagent le même user_id système sous le capot.
 *
 * @since 1.0.0
 */
final class EmundusPublicAccess extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;

	/**
	 * Session key used to mark a session as a public access session.
	 */
	public const SESSION_PUBLIC_ACCESS_KEY = 'emundus_public_access';

	/**
	 * Session key used to store the fnum of the public file being accessed.
	 */
	public const SESSION_PUBLIC_FNUM_KEY = 'emundus_public_fnum';

	/**
	 * Session key used to store the plain token for the current public session.
	 */
	public const SESSION_PUBLIC_TOKEN_KEY = 'emundus_public_token';

	/**
	 * Session key used to store the creation timestamp of the public access session.
	 */
	public const SESSION_PUBLIC_CREATED_AT_KEY = 'emundus_public_created_at';

	/**
	 * Session key used to detect if user has already stored the access token in session and avoid infinite redirection loop to storetoken layout
	 */
	public const SESSION_PUBLIC_STORED_ACCESS_KEY = 'emundus_public_stored_access';

	/**
	 * Default maximum duration (in minutes) for a public access session.
	 * After this delay, the session is destroyed regardless of token validity.
	 * Configurable via plugin params (field: session_ttl).
	 */
	private const DEFAULT_SESSION_TTL_MINUTES = 15;

	public static function getSubscribedEvents(): array
	{
		$app = Factory::getApplication();

		return [
			'onAfterRoute'       => 'onAfterRoute',
			'onUserLogout'       => 'onUserLogout',
			'onAfterSubmitFile'  => 'onAfterSubmitFile',
			'onAfterUpdateOwner' => 'onAfterUpdateOwner',
		];
	}

	/**
	 * Intercepte les requêtes après le routage pour vérifier la présence d'un token d'accès public.
	 *
	 * @param   AfterRouteEvent  $event
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterRoute(AfterRouteEvent $event): void
	{
		$app     = $this->getApplication();
		$input   = $app->getInput();
		$session = $app->getSession();

		$option = $input->getCmd('option', '');
		if ($option === 'com_emundus')
		{
			$task = $input->getCmd('task', '');

			// Token authentication is only accepted via POST to avoid token exposure in URLs,
			// browser history, server logs, and Referer headers.
			if ($task === 'authenticatepublicaccess' && $app->getInput()->getMethod() === 'POST')
			{
				// Validate CSRF token
				if (!\Joomla\CMS\Session\Session::checkToken())
				{
					Log::add(
						'Public access attempt with invalid CSRF token.',
						Log::WARNING,
						'plg_system_emunduspublicaccess'
					);
					$app->redirect(Route::_('index.php?option=com_emundus&view=publicaccess&error=1&error_msg=' . urlencode(Text::_('JINVALID_TOKEN')), false));

					return;
				}

				$tokenParamName = $this->params->get('token_param_name', 'access_token');
				$accessToken    = $input->post->getString($tokenParamName, '');
				$fnum           = $input->post->getString('fnum', '');

				if (!empty($accessToken) && !empty($fnum))
				{
					$this->authenticatePublicAccess($accessToken, $fnum);
				}
			}
		}

		// If the session is already marked as a public access session, validate that it is still valid
		if (self::isPublicAccessSession())
		{
			$this->validateExistingPublicSession();

			if (!$session->get(self::SESSION_PUBLIC_STORED_ACCESS_KEY, true))
			{
				$uri = Uri::getInstance();

				if ($uri->getVar('task') === 'markPublicAccessKeyAsStored')
				{
					// do nothing
				}
				else if ($uri->getVar('layout') !== 'storetoken')
				{
					$redirectUrl = Route::_('/index.php?option=com_emundus&view=publicaccess&layout=storetoken', false);
					$app->redirect($redirectUrl);
				}
			}
			else
			{
				$currentMenu = $app->getMenu()->getActive();
				if ($currentMenu->menutype !== 'topmenu' && !str_starts_with($currentMenu->menutype, 'menu-profile'))
				{
					throw new \Exception(Text::_('ACCESS_DENIED'), 403);
				}
			}
		}
	}

	/**
	 * @param   GenericEvent  $event
	 *
	 * @return void
	 */
	public function onAfterSubmitFile(GenericEvent $event): void
	{
		if (self::isPublicAccessSession() && $event->hasArgument('context'))
		{
			assert($event->getArgument('context') instanceof EventContextEntity);
			$this->destroyPublicSession(false, $event->getArgument('context')->getFiles()[0]);
		}
	}

	/**
	 * @param   GenericEvent  $event
	 *
	 * @return void
	 */
	public function onAfterUpdateOwner(GenericEvent $event): void
	{
		Log::addLogger(['text_file' =>  'plg_system_emunduspublicaccess.php'], Log::ALL, ['plg_system_emunduspublicaccess']);

		Log::add('onAfterUpdateOwner event triggered in EmundusPublicAccess plugin.', Log::DEBUG, 'plg_system_emunduspublicaccess');

		if ($event->hasArgument('context'))
		{
			assert($event->getArgument('context') instanceof EventContextEntity);

			$applicationFileRepository = new ApplicationFileRepository();
			$applicationFileAccessRepository = new ApplicationFileAccessRepository();
			$systemUserId = (int) ComponentHelper::getParams('com_emundus')->get('system_public_user_id', 0);

			foreach ($event->getArgument('context')->getFiles() as $fnum)
			{
				$applicationFile = $applicationFileRepository->getByFnum($fnum);

				if ($systemUserId !== $applicationFile->getUser()->id)
				{
					if ($applicationFile->isPublic())
					{
						$applicationFile->setIsPublic(false);
						if (!$applicationFileRepository->flush($applicationFile))
						{
							Log::add('Failed to set file as not public anymore.', Log::ERROR, 'plg_system_emunduspublicaccess');
						}
					}

					if (!$applicationFileAccessRepository->revokeAccess($applicationFile))
					{
						Log::add('Failed to revoke public access for fnum: ' . $fnum . ' after ownership change.', Log::ERROR, 'plg_system_emunduspublicaccess');
					}
				}
				else
				{
					// if owner is system public user, make sure file is public
					if (!$applicationFile->isPublic())
					{
						$applicationFile->setIsPublic(true);
						$applicationFileRepository->flush($applicationFile);
					}
				}
			}
		}
	}

	/**
	 * Before logout, destroy scoped session and revert to guest identity.
	 * Do not let Joomla perform total logout, because other users could be using system user, but with another session
	 *
	 * @param   LogoutEvent  $event
	 *
	 * @return void
	 */
	public function onUserLogout(LogoutEvent $event): void
	{
		if (self::isPublicAccessSession())
		{
			$this->destroyPublicSession();
			$event->stopPropagation();
		}
	}

	/**
	 * Validate a token + fnum pair and set up the public access session.
	 *
	 * @param   string  $accessToken  The plain-text access token from the URL
	 * @param   string  $fnum         The fnum of the application file
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function authenticatePublicAccess(string $accessToken, string $fnum): void
	{
		try
		{
			$applicationFileRepository = new ApplicationFileRepository();
			$applicationFile           = $applicationFileRepository->getByFnum($fnum);

			if (empty($applicationFile))
			{
				Log::add(
					'Public access attempt with unknown fnum: ' . $fnum,
					Log::WARNING,
					'plg_system_emunduspublicaccess'
				);

				return;
			}

			if (!$applicationFile->isPublic())
			{
				Log::add(
					'Public access attempt on non-public file: ' . $fnum,
					Log::WARNING,
					'plg_system_emunduspublicaccess'
				);

				return;
			}

			$accessRepository = new ApplicationFileAccessRepository();
			$isValid          = $accessRepository->verifyAccessToken($accessToken, $applicationFile);

			if (!$isValid)
			{
				Log::add(
					'Public access attempt with invalid or expired token for fnum: ' . $fnum,
					Log::WARNING,
					'plg_system_emunduspublicaccess'
				);

				return;
			}

			// Token is valid — set up the public access session
			$this->getApplication()->getSession()->set(self::SESSION_PUBLIC_STORED_ACCESS_KEY, true);
			$this->initPublicSession($applicationFile, $accessToken);
			$this->getApplication()->redirect('/index.php?option=com_emundus&task=openfile&fnum=' . $applicationFile->getFnum());
		}
		catch (\Exception $e)
		{
			Log::add(
				'Error during public access authentication: ' . $e->getMessage(),
				Log::ERROR,
				'plg_system_emunduspublicaccess'
			);
		}
	}

	/**
	 * Initialise la session publique : injecte l'utilisateur système et configure la session eMundus.
	 *
	 * Chaque session PHP (navigateur) est isolée. L'utilisateur système est injecté comme identité
	 * Joomla, mais la session eMundus est scopée au fnum validé. Cela garantit que deux invités
	 * simultanés ne peuvent pas interférer entre eux.
	 *
	 * @param   \Tchooz\Entities\ApplicationFile\ApplicationFileEntity  $applicationFile
	 * @param   string                                                  $accessToken
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function initPublicSession(
		\Tchooz\Entities\ApplicationFile\ApplicationFileEntity $applicationFile,
		string $accessToken
	): void
	{
		$app     = $this->getApplication();
		$session = $app->getSession();

		$systemUserId = (int) ComponentHelper::getParams('com_emundus')->get('system_public_user_id', 0);

		if (empty($systemUserId))
		{
			Log::add(
				'system_public_user_id is not configured in com_emundus params. Public access cannot proceed.',
				Log::ERROR,
				'plg_system_emunduspublicaccess'
			);

			return;
		}

		$systemUser = $this->getUserFactory()->loadUserById($systemUserId);

		if (empty($systemUser->id))
		{
			Log::add(
				'System public user (ID: ' . $systemUserId . ') not found. Public access cannot proceed.',
				Log::ERROR,
				'plg_system_emunduspublicaccess'
			);

			return;
		}

		// Inject the system user identity into the application.
		// Each browser has its own PHP session, so this does not conflict with other public users.
		if (!class_exists('EmundusModelUsers'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/models/users.php');
		}
		$usersModel = new \EmundusModelUsers();
		$usersModel->login($systemUser->id);

		// Mark the session as a public access session, scoped to this specific fnum
		$session->set(self::SESSION_PUBLIC_ACCESS_KEY, true);
		$session->set(self::SESSION_PUBLIC_FNUM_KEY, $applicationFile->getFnum());
		$session->set(self::SESSION_PUBLIC_TOKEN_KEY, $accessToken);
		$session->set(self::SESSION_PUBLIC_CREATED_AT_KEY, time());

		// Build a scoped emundus session that only contains this fnum.
		// This prevents loading ALL public files belonging to the system user.
		$this->initScopedEmundusSession($applicationFile, $systemUser);

		Log::add(
			'Public access session established for fnum: ' . $applicationFile->getFnum(),
			Log::INFO,
			'plg_system_emunduspublicaccess'
		);
	}

	/**
	 * Construit une session eMundus scopée au dossier public.
	 *
	 * Contrairement à EmundusModelProfile::initEmundusSession() qui charge TOUS les fnums
	 * de l'utilisateur via getApplicantFnums($systemUser->id), cette méthode construit
	 * un objet session limité au seul fnum autorisé par le token.
	 *
	 * C'est la clé de l'isolation : même si 100 dossiers publics partagent le même
	 * system_public_user_id, chaque session PHP ne voit que SON dossier.
	 *
	 * @param   \Tchooz\Entities\ApplicationFile\ApplicationFileEntity  $applicationFile
	 * @param   \Joomla\CMS\User\User                                  $systemUser
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function initScopedEmundusSession(
		\Tchooz\Entities\ApplicationFile\ApplicationFileEntity $applicationFile,
		\Joomla\CMS\User\User $systemUser
	): void
	{
		$app     = $this->getApplication();
		$session = $app->getSession();

		if (!class_exists('EmundusModelProfile'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/profile.php';
		}
		$m_profile = new \EmundusModelProfile();

		$profile = $m_profile->getFullProfileByFnum($applicationFile->getFnum());
		$emundusSession = new \stdClass();

		// Basic user info from system user
		$emundusSession->id         = $systemUser->id;
		$emundusSession->firstname  = 'Public';
		$emundusSession->lastname   = 'USER';
		$emundusSession->emGroups   = [];
		$emundusSession->emProfiles = [$profile];

		// Campaign info scoped to this fnum only
		$campaign = $applicationFile->getCampaign();

		if (!empty($campaign))
		{
			$emundusSession->fnum                   = $applicationFile->getFnum();
			$emundusSession->fnums                  = [$applicationFile->getFnum()];
			$emundusSession->campaign_id            = $campaign->getId();
			$emundusSession->status                 = $applicationFile->getStatus()->getStep();
			$emundusSession->candidature_incomplete = ($applicationFile->getStatus()->getStep() == 0) ? 0 : 1;
			$emundusSession->profile                = !empty($profile['profile_id']) ? $profile['profile_id'] : ($profile['profile'] ?? 0);
			$emundusSession->profile_label          = $profile['label'] ?? '';
			$emundusSession->menutype               = $profile['menutype'] ?? '';
			$emundusSession->university_id          = null;
			$emundusSession->applicant              = 1;
			$emundusSession->start_date             = $campaign->getStartDate()->format('Y-m-d H:i:s');
			$emundusSession->end_date               = $campaign->getEndDate()->format('Y-m-d H:i:s');
			$emundusSession->candidature_start      = $campaign->getStartDate()->format('Y-m-d H:i:s');
			$emundusSession->candidature_end        = $campaign->getEndDate()->format('Y-m-d H:i:s');
			$emundusSession->admission_start_date   = null;
			$emundusSession->admission_end_date     = null;
			$emundusSession->candidature_posted     = (!empty($profile['date_submitted']) && $profile['date_submitted'] !== '0000-00-00 00:00:00') ? 1 : 0;
			$emundusSession->schoolyear             = $campaign->getYear();
			$emundusSession->code                   = $campaign->getProgram() ? $campaign->getProgram()->getCode() : '';
			$emundusSession->campaign_name          = $campaign->getLabel();
		}
		$emundusSession->is_public_access = true;
		$emundusSession->public_fnum      = $applicationFile->getFnum();

		$session->set('emundusUser', $emundusSession);
	}

	/**
	 * Valide qu'une session publique existante est toujours valide.
	 * Si le token a expiré ou que le dossier n'est plus accessible, on détruit la session publique.
	 *
	 * Gère aussi la sécurité : un invité ne peut pas changer de fnum en modifiant l'URL.
	 * Le fnum en session (validé par token) est la seule source de vérité.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function validateExistingPublicSession(): void
	{
		$app     = $this->getApplication();
		$session = $app->getSession();
		$input   = $app->getInput();

		$sessionFnum = $session->get(self::SESSION_PUBLIC_FNUM_KEY, '');
		$savedToken  = $session->get(self::SESSION_PUBLIC_TOKEN_KEY, '');

		if (empty($sessionFnum) || empty($savedToken))
		{
			$this->destroyPublicSession();

			return;
		}

		// Enforce session TTL: destroy the session if it exceeds the configured maximum duration.
		// This is independent of the token expiration (30 days) and the Joomla session lifetime.
		$createdAt  = (int) $session->get(self::SESSION_PUBLIC_CREATED_AT_KEY, 0);
		$ttlMinutes = (int) $this->params->get('session_ttl', self::DEFAULT_SESSION_TTL_MINUTES);
		$ttlSeconds = $ttlMinutes * 60;

		if ($createdAt > 0 && (time() - $createdAt) > $ttlSeconds)
		{
			Log::add(
				'Public access session expired (TTL: ' . $ttlMinutes . ' min) for fnum: ' . $sessionFnum,
				Log::INFO,
				'plg_system_emunduspublicaccess'
			);

			$this->destroyPublicSession(true, $sessionFnum);

			throw new \Exception(Text::_('COM_EMUNDUS_PUBLIC_ACCESS_SESSION_EXPIRED'), 403);
		}



		// Security: if a fnum is in the URL, it MUST match the session fnum.
		// This prevents a public user from navigating to another user's file
		// by simply changing the fnum parameter.
		$requestFnum = $input->getString('fnum', '');
		if (!empty($requestFnum) && $requestFnum !== $sessionFnum)
		{
			Log::add(
				'Public session fnum mismatch. Session: ' . $sessionFnum . ', Request: ' . $requestFnum,
				Log::WARNING,
				'plg_system_emunduspublicaccess'
			);

			$this->destroyPublicSession();

			return;
		}

		try
		{
			$applicationFileRepository = new ApplicationFileRepository();
			$applicationFile           = $applicationFileRepository->getByFnum($sessionFnum);

			if (empty($applicationFile) || !$applicationFile->isPublic())
			{
				$this->destroyPublicSession();

				return;
			}

			$accessRepository = new ApplicationFileAccessRepository();
			$isValid          = $accessRepository->verifyAccessToken($savedToken, $applicationFile);

			if (!$isValid)
			{
				$this->destroyPublicSession(true, $sessionFnum);

				return;
			}

			$this->initPublicSession($applicationFile, $savedToken);
		}
		catch (\Exception $e)
		{
			Log::add(
				'Error validating existing public session: ' . $e->getMessage(),
				Log::ERROR,
				'plg_system_emunduspublicaccess'
			);

			$this->destroyPublicSession();
		}
	}

	/**
	 * Détruit la session d'accès public et réinitialise l'identité à guest.
	 *
	 * @param   bool    $redirect   Si true, redirige l'utilisateur vers la vue publicaccess
	 * @param   string  $fnum       Le fnum à pré-remplir dans le formulaire de ré-authentification
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function destroyPublicSession(bool $redirect = false, string $fnum = ''): void
	{
		$app     = $this->getApplication();
		$session = $app->getSession();

		$session->clear(self::SESSION_PUBLIC_ACCESS_KEY);
		$session->clear(self::SESSION_PUBLIC_FNUM_KEY);
		$session->clear(self::SESSION_PUBLIC_TOKEN_KEY);
		$session->clear(self::SESSION_PUBLIC_CREATED_AT_KEY);
		$session->clear('emundusUser');
		$session->destroy();

		// Restore the guest identity
		$guestUser = $this->getUserFactory()->loadUserById(0);
		$app->loadIdentity($guestUser);

		Log::add(
			'Public access session destroyed.',
			Log::INFO,
			'plg_system_emunduspublicaccess'
		);

		if ($this->getApplication()->isClient('site')) {
			$this->getApplication()->getInput()->cookie->set(
				'joomla_user_state',
				'',
				[
					'expires' => 1,
					'path'    => $this->getApplication()->get('cookie_path', '/'),
					'domain'  => $this->getApplication()->get('cookie_domain', ''),
				]
			);

			if ($redirect)
			{
				$url = 'index.php?option=com_emundus&view=publicaccess&error=1'
					. '&error_msg=' . urlencode(Text::_('COM_EMUNDUS_PUBLIC_ACCESS_SESSION_EXPIRED'));

				if (!empty($fnum))
				{
					$url .= '&fnum=' . urlencode($fnum);
				}

				$app->redirect(Route::_($url, false));
			}
		}
	}

	/**
	 * Vérifie si la session courante est une session d'accès public.
	 *
	 * Méthode statique utile pour les autres composants/plugins qui doivent adapter leur comportement
	 * (ex: masquer des actions, filtrer les données, conditions FormBuilder guest/registered).
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 */
	public static function isPublicAccessSession(): bool
	{
		return (bool) Factory::getApplication()->getSession()->get(self::SESSION_PUBLIC_ACCESS_KEY, false);
	}

	/**
	 * Retourne le fnum du dossier public accédé dans la session courante.
	 *
	 * @return  string|null
	 *
	 * @since   1.0.0
	 */
	public static function getPublicAccessFnum(): ?string
	{
		$session = Factory::getApplication()->getSession();

		if (!$session->get(self::SESSION_PUBLIC_ACCESS_KEY, false))
		{
			return null;
		}

		return $session->get(self::SESSION_PUBLIC_FNUM_KEY, null);
	}
}

