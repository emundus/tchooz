<?php
/**
 * @package     Tchooz\Services\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Addons;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Services\Handlers\HandlerInterface;

abstract class AbstractAddonHandler implements HandlerInterface
{
	protected AddonEntity $addon;

	protected ?EmundusAddonConfiguration $configuration;

	public function __construct(
		AddonEntity $addon,
		?EmundusAddonConfiguration $configuration)
	{
		$this->addon = $addon;
		$this->configuration = $configuration;
	}

	public function onSetup(object $setup, ?AddonRepository $repository = null): bool
	{
		if (empty($this->configuration))
		{
			return false;
		}

		$config = $this->addon->getParams();

		foreach ($setup as $groupName => $group)
		{
			foreach ($group as $fieldName => $value)
			{
				$parameter = $this->configuration->getParameter($fieldName);

				if ($parameter === null)
				{
					continue;
				}

				if ($parameter->isRequired() && empty($value))
				{
					throw new \InvalidArgumentException(
						Text::sprintf('COM_EMUNDUS_SETTINGS_APP_PARAMETER_REQUIRED', $parameter->getName())
					);
				}

				if ($parameter instanceof PasswordField)
				{
					// If value is only asterisks, the user did not change it — skip
					if (preg_match('/^\*+$/', $value))
					{
						continue;
					}

					$value = $this->encrypt($value);
				}

				$config[$groupName][$fieldName] = $value;
			}
		}

		$this->addon->setParams($config);

		$repository = $repository ?? new AddonRepository();

		return $repository->flush($this->addon);
	}

	protected function encrypt(string $value): string
	{
		if (!class_exists('EmundusHelperFabrik'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
		}

		return \EmundusHelperFabrik::encryptDatas($value);
	}

	public function getConfiguration(): ?EmundusAddonConfiguration
	{
		return $this->configuration;
	}
}

