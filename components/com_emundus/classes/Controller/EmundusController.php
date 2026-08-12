<?php
/**
 * @package     Tchooz\Controller
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Controller;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Input\Input;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Attributes\PublicAccessAttribute;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\EmundusResponse;
use Tchooz\Traits\TraitResponse;

abstract class EmundusController extends BaseController
{
	use TraitResponse;

	protected ?User $user;

	/**
	 * Reflection cache:
	 * [
	 *   'ControllerClass::method' => [
	 *       'method' => AccessAttribute[],
	 *       'class'  => AccessAttribute[],
	 *   ]
	 * ]
	 */
	protected static array $accessAttributeCache = [];

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplicationInterface $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		if (!class_exists('EmundusHelperAccess'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';
		}

		$this->user = $this->app->getIdentity();
	}

	public function execute($task): void
	{
		try
		{
			$this->enforceAccess($this, $task);

			$response = parent::execute($task);
		}
		catch (\Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		} finally
		{
			$this->sendJsonResponse($response);
		}
	}

	// TODO: Allow to inject the user for testing purposes
	// TODO: Allow to pass parameters as fnum
	private function enforceAccess(object $controller, string $method): void
	{
		$attributes = $this->getCachedAccessAttributes($controller, $method);

		// 0 - Explicit public marker → allow
		if (!empty($attributes['public']))
		{
			return;
		}

		// 1 - Method-level rules take priority
		if (!empty($attributes['method']))
		{
			if ($this->passesAnyAccessAttribute($attributes['method']))
			{
				return;
			}

			throw new AccessException(
				Text::_('ACCESS_DENIED'),
				EmundusResponse::HTTP_FORBIDDEN
			);
		}

		// 2 - Fallback to class-level rules
		if (!empty($attributes['class']))
		{
			if ($this->passesAnyAccessAttribute($attributes['class']))
			{
				return;
			}

			throw new AccessException(
				Text::_('ACCESS_DENIED'),
				EmundusResponse::HTTP_FORBIDDEN
			);
		}

		// 3 - No rules at all → allow
	}

	private function passesAnyAccessAttribute(array $attributes): bool
	{
		foreach ($attributes as $attribute)
		{
			assert($attribute instanceof AccessAttribute);
			if ($this->passesAccessAttribute($attribute))
			{
				return true;
			}
		}

		return false;
	}

	protected function callAccessLevelMethod(string $methodName, int $userId)
	{
		return \EmundusHelperAccess::$methodName($userId);
	}

	protected function callAccessActionMethod(string $actionId, string $mode, int $userId): bool
	{
		return \EmundusHelperAccess::asAccessAction($actionId, $mode, $userId);
	}

	private function passesAccessAttribute(AccessAttribute $access): bool
	{
		if (empty($access->accessLevel) && empty($access->actions))
		{
			// If no access level or actions defined, it's weird, so we deny access just in case
			return false;
		}

		// 0 - Guest users are denied access when any access level or action is required
		if ($this->user->guest)
		{
			return false;
		}

		// 1 - Access level check
		if ($access->accessLevel !== null)
		{
			$methodName = $access->accessLevel->getMethodName();

			if (!$this->callAccessLevelMethod($methodName, $this->user->id))
			{
				return false;
			}
		}

		// 2 - Actions check
		if (!empty($access->actions))
		{
			foreach ($access->actions as $action)
			{
				if (
					!isset($action['id'], $action['mode']) ||
					!$action['mode'] instanceof CrudEnum ||
					empty($this->user->id) ||
					$this->user->guest
				)
				{
					continue;
				}

				$resolvedMode = $this->resolveActionMode($action);
				$actionMode   = $resolvedMode->value;
				$actionId     = $action['id'] instanceof ActionEnum ? $action['id']->value : $action['id'];
				if ($this->callAccessActionMethod($actionId, $actionMode, $this->user->id))
				{
					return true;
				}
			}

			return false;
		}

		// 3 - If accessLevel passed and no actions required
		return true;
	}

	protected function getCachedAccessAttributes(
		object $controller,
		string $method
	): array
	{
		$class = get_class($controller);
		$key   = $class . '::' . $method;

		if (isset(self::$accessAttributeCache[$key]))
		{
			return self::$accessAttributeCache[$key];
		}

		$methodReflection = new \ReflectionMethod($controller, $method);
		$classReflection  = new \ReflectionClass($controller);

		$methodAttributes = array_map(
			fn($attr) => $attr->newInstance(),
			$methodReflection->getAttributes(AccessAttribute::class)
		);

		$classAttributes = array_map(
			fn($attr) => $attr->newInstance(),
			$classReflection->getAttributes(AccessAttribute::class)
		);

		$publicAttributes = $methodReflection->getAttributes(PublicAccessAttribute::class);

		return self::$accessAttributeCache[$key] = [
			'method' => $methodAttributes,
			'class'  => $classAttributes,
			'public' => $publicAttributes,
		];
	}

	/**
	 * Resolve the effective CRUD mode for an action declaration.
	 *
	 * Supports CrudEnum::CREATE_OR_UPDATE: reads the request parameter named by
	 * `entityIdParam` (default 'id'). Mode becomes UPDATE when the value > 0,
	 * CREATE otherwise.
	 */
	private function resolveActionMode(array $action): CrudEnum
	{
		$mode = $action['mode'];
		assert($mode instanceof CrudEnum);

		if ($mode !== CrudEnum::CREATE_OR_UPDATE)
		{
			return $mode;
		}

		$entityIdParam = $action['entityIdParam'] ?? 'id';
		$entityId      = (int) $this->input->getInt($entityIdParam, 0);

		return $entityId > 0 ? CrudEnum::UPDATE : CrudEnum::CREATE;
	}

	/**
	 * Enforce a single CRUD access check for the current user on the given action.
	 *
	 * Coordinators bypass the action check by default.
	 *
	 * @param ActionEnum|string|int $actionId
	 * @param CrudEnum              $mode
	 * @param bool                  $bypassForCoordinator
	 *
	 * @throws AccessException When the user does not have the required access.
	 */
	protected function enforceAccessAction(
		ActionEnum|string|int $actionId,
		CrudEnum $mode,
		bool $bypassForCoordinator = true
	): void
	{
		if ($this->user === null || $this->user->guest)
		{
			throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}

		if ($bypassForCoordinator && \EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			return;
		}

		$resolvedActionId = $actionId instanceof ActionEnum ? $actionId->value : $actionId;

		if (!\EmundusHelperAccess::asAccessAction($resolvedActionId, $mode->value, $this->user->id))
		{
			throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}
	}

	/**
	 * Enforce CREATE or UPDATE access depending on whether the entity already exists.
	 *
	 * Use for save endpoints that handle both creation (entityId <= 0) and update (entityId > 0).
	 *
	 * @param ActionEnum|string|int $actionId
	 * @param int                   $entityId
	 * @param bool                  $bypassForCoordinator
	 *
	 * @throws AccessException When the user does not have the required access.
	 */
	protected function enforceCreateOrUpdateAccess(
		ActionEnum|string|int $actionId,
		int $entityId,
		bool $bypassForCoordinator = true
	): void
	{
		$mode = $entityId > 0 ? CrudEnum::UPDATE : CrudEnum::CREATE;
		$this->enforceAccessAction($actionId, $mode, $bypassForCoordinator);
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getUser(): ?User
	{
		return $this->user;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function setUser(?User $user): void
	{
		$this->user = $user;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function setInput(Input $input): void
	{
		$this->input = $input;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getBaseUri(): string
	{
		return Uri::base();
	}
}