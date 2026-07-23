<?php
/**
 * @package     Unit\Architecture
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Architecture;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Attributes\PublicAccessAttribute;
use Tchooz\Controller\EmundusController;

/**
 * Pipeline guard: every public action method on a Tchooz controller must declare
 * an access check via #[AccessAttribute] (method or class level) or be explicitly
 * marked #[PublicAccessAttribute].
 *
 * @coversNothing
 */
class ControllerAccessAttributeTest extends TestCase
{
	private const CONTROLLER_DIR = JPATH_SITE . '/components/com_emundus/classes/Controller';

	/**
	 * @dataProvider controllerClassProvider
	 */
	public function testEveryPublicActionHasAccessAttribute(string $fqcn): void
	{
		$reflection = new ReflectionClass($fqcn);

		$classHasAccess = !empty($reflection->getAttributes(AccessAttribute::class));

		$missing = [];

		foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
		{
			if ($method->getDeclaringClass()->getName() !== $reflection->getName())
			{
				continue;
			}

			if ($method->isConstructor() || $method->isDestructor() || $method->isStatic())
			{
				continue;
			}

			$hasMethodAccess = !empty($method->getAttributes(AccessAttribute::class));
			$hasPublicMarker = !empty($method->getAttributes(PublicAccessAttribute::class));

			if ($hasMethodAccess || $hasPublicMarker || $classHasAccess)
			{
				continue;
			}

			$missing[] = $method->getName();
		}

		$this->assertEmpty(
			$missing,
			sprintf(
				"Controller %s has public methods without #[AccessAttribute] or #[PublicAccessAttribute]: %s",
				$fqcn,
				implode(', ', $missing)
			)
		);
	}

	public static function controllerClassProvider(): array
	{
		$cases = [];

		foreach (glob(self::CONTROLLER_DIR . '/*.php') as $file)
		{
			require_once $file;

			$className = 'Tchooz\\Controller\\' . basename($file, '.php');

			if (!class_exists($className))
			{
				continue;
			}

			$reflection = new ReflectionClass($className);

			if ($reflection->isAbstract() || !$reflection->isSubclassOf(EmundusController::class))
			{
				continue;
			}

			$cases[$className] = [$className];
		}

		return $cases;
	}
}
