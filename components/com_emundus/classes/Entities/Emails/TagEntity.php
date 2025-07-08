<?php
/**
 * @package     Tchooz\Entities\Emails
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Emails;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Tchooz\Entities\Emails\Modifiers\UppercaseModifier;
use Tchooz\Enums\Emails\TagType;
use Tchooz\Interfaces\TagModifierInterface;

class TagEntity
{
	private string|int $name;

	private ?string $description;

	private ?string $request = '';

	private string|int|null $value = '';

	/**
	 * @var TagModifierInterface[]
	 */
	private array $modifiers;

	private ?TagType $type;

	public function __construct(string|int $name, ?string $description = '', array $modifiers = [], ?TagType $type = TagType::STANDARD)
	{
		// Check if the name has not a modifier
		if(str_contains($name, ':')) {
			$parts = explode(':', $name);
			$name = $parts[0];
			unset($parts[0]);

			foreach ($parts as $part) {
				$modifier = TagModifierRegistry::get($part);
				if (!empty($modifier) && !in_array($modifier, $modifiers, true)) {
					$modifiers[] = $modifier;
				}
			}
		}

		$this->name  = strip_tags($name);
		$this->description = $description;
		$this->modifiers = $modifiers;
		$this->type = $type;
	}

	public function getName(): string|int
	{
		return $this->name;
	}

	public function getFullName(): string
	{
		if(!empty($this->modifiers)) {
			$modifiers = array_map(fn($modifier) => $modifier->getName(), $this->modifiers);
			$modifiers = ':' . implode(':', $modifiers);
		} else {
			$modifiers = '';
		}

		return $this->name . $modifiers;
	}

	public function getPatternName(): string
	{
		if($this->type === TagType::FABRIK) {
			return '/\$\{' . $this->name . '\}/';
		}
		
		return '/\[' . $this->name . '\]/';
	}

	public function getFullPatternName(): string
	{
		if(!empty($this->modifiers)) {
			$modifiers = array_map(fn($modifier) => $modifier->getName(), $this->modifiers);
			$modifiers = ':' . implode(':', $modifiers);
		} else {
			$modifiers = '';
		}

		if($this->type === TagType::FABRIK) {
			return '/\$\{' . $this->name . $modifiers . '\}/';
		}

		return '/\[' . $this->name . $modifiers . '\]/';
	}

	public function setName(string|int $name): void
	{
		$this->name = $name;
	}

	public function getValue(): string|int|null
	{
		return $this->value;
	}

	public function getValueModified(): ?string
	{
		if(empty($this->value)) {
			return null;
		}

		$modified_value = $this->value;

		if(!empty($this->modifiers))
		{
			// Apply all modifiers to the value
			foreach ($this->modifiers as $modifier) {
				if(!($modifier instanceof TagModifierInterface)) {
					continue;
				}
				$modified_value = $modifier->transform($modified_value);
			}
		}

		return $modified_value;
	}

	public function setValue(string|int $value): void
	{
		$this->value = $value;
	}

	public function getModifiers(): array
	{
		return $this->modifiers;
	}

	public function setModifier(array $modifiers): void
	{
		$this->modifiers = $modifiers;
	}

	public function addModifier(TagModifierInterface $modifier): void
	{
		if (!in_array($modifier, $this->modifiers, true)) {
			$this->modifiers[] = $modifier;
		}
	}

	public function getType(): ?TagType
	{
		return $this->type;
	}

	public function setType(?TagType $type): void
	{
		$this->type = $type;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function getRequest(): ?string
	{
		return $this->request;
	}

	public function setRequest(?string $request): void
	{
		$this->request = $request;
	}

	public function calculateValue(int $user_id = 0, bool $base64 = false): void
	{
		$result = '';
		$db = Factory::getContainer()->get('DatabaseDriver');

		if(empty($user_id))
		{
			$user_id = Factory::getApplication()->getIdentity()->id;
			if(empty($user_id))
			{
				$user_id = ComponentHelper::getParams('com_emundus')->get('automated_task_user', 1);
			}
		}

		if(!empty($this->request))
		{
			if ($this->type === TagType::STANDARD)
			{
				$value = $this->request;

				if (!str_contains($value, 'php|'))
				{
					$result = $value;
					$request = explode('|', $value);

					if (count($request) === 3)
					{
						try
						{
							$query = 'SELECT ' . $request[0] . ' FROM ' . $request[1] . ' WHERE ' . $request[2];
							$db->setQuery($query);
							$result = $db->loadResult();

						}
						catch (\Exception $e)
						{
							$error = Uri::getInstance() . ' :: USER ID : ' . $user_id . '\n -> ' . $query;
							Log::add($error, Log::ERROR, 'com_emundus');
						}
					}
					elseif ($this->name == 'PHOTO')
					{
						if (file_exists(EMUNDUS_PATH_REL . $user_id . '/tn_' . $result))
						{
							$result = EMUNDUS_PATH_REL . $user_id . '/tn_' . $result;
						}
						else
						{
							$result = EMUNDUS_PATH_REL . $user_id . '/' . $result;
						}

						if ($base64)
						{
							$type   = pathinfo($result, PATHINFO_EXTENSION);
							$data   = file_get_contents($result);
							$result = 'data:image/' . $type . ';base64,' . base64_encode($data);
						}
					}
				}
				else
				{
					$request = str_replace('php|', '', $value);

					try
					{
						$result = eval("$request");
					}
					catch (\Exception $e)
					{
						Log::add('Error setTags for tag : ' . $this->name . '. Message : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
					}
				}
			}
		}

		$this->value = $result;
	}
}