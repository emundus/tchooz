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

				if ($this->callAccessActionMethod($action['id'], $action['mode']->value, $this->user->id))
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

		return self::$accessAttributeCache[$key] = [
			'method' => $methodAttributes,
			'class'  => $classAttributes,
		];
	}

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function setUser(?User $user): void
	{
		$this->user = $user;
	}

	public function setInput(Input $input): void
	{
		$this->input = $input;
	}

	public function getBaseUri(): string
	{
		return Uri::base();
	}
}