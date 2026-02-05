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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Tchooz\Entities\Emails\Modifiers\ChoiceStatusModifier;
use Tchooz\Entities\Emails\Modifiers\IndexModifier;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;
use Tchooz\Enums\Emails\TagTypeEnum;
use Tchooz\Interfaces\TagModifierInterface;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;

class TagEntity
{
	private string|int $name;

	private string|int $fullName;

	private ?string $description;

	private ?string $request = '';

	private string|int|null $value = '';

	/**
	 * @var TagModifierInterface[]
	 */
	private array $modifiers;

	private ?TagTypeEnum $type;

	public function __construct(string|int $name, ?string $description = '', array $modifiers = [], ?TagTypeEnum $type = TagTypeEnum::STANDARD)
	{
		$this->fullName = $name;

		// Check if the name has not a modifier
		if(str_contains($name, ':')) {
			$parts = explode(':', $name);
			$name = $parts[0];
			unset($parts[0]);

			foreach ($parts as $part) {
				$modifierWithParams = $this->parseModifierWithParams($part);
				if (!empty($modifierWithParams['modifier'])) {
					$modifiers[] = [
						'modifier' => $modifierWithParams['modifier'],
						'params' => $modifierWithParams['params']
					];
				}
			}
		}

		$this->name  = strip_tags($name);
		$this->description = $description;
		$this->modifiers = $modifiers;
		$this->type = $type;
	}

	private function parseModifierWithParams(string $part): array
	{
		$modifierName = $part;
		$params = [];

		/*if (str_contains($part, '(') && str_contains($part, ')')) {
			$modifierName = substr($part, 0, strpos($part, '('));

			$paramsStr = substr($part, strpos($part, '(') + 1, -1);
			// Interpret html quotes
			$paramsStr = html_entity_decode($paramsStr);

			preg_match_all('/"([^"]*)"/', $paramsStr, $matches);
			$params = $matches[1];
		}*/

		if (preg_match('/^([^(]+)\((.*)\)$/', $part, $matches)) {
			$modifierName = $matches[1];
			$paramsStr = $matches[2];
			$paramsStr = html_entity_decode($paramsStr, ENT_QUOTES | ENT_HTML5);

			preg_match_all(
				'/"([^"]*)"|([^,\s]+)/',
				$paramsStr,
				$matchesParams
			);

			$params = array_filter(
				array_merge($matchesParams[1], $matchesParams[2]), function($value)
				{
					return $value !== '' && $value !== null;
				}
			);

			// array values cleanup
			$params = array_map(function($value) {
				// Keep single space params as is (to allow space params)
				return $value !== " " ? trim($value) : $value;
			}, $params);
			$params = array_values($params);
		}

		$modifier = TagModifierRegistry::get($modifierName);
		if ($modifier) {
			$modifier->setParams($params);
		}

		return ['modifier' => $modifier, 'params' => $params];
	}


	public function getName(): string|int
	{
		return $this->name;
	}

	public function getFullName(): string
	{
		/*if (!empty($this->modifiers)) {
			$modifiers = array_map(
				fn($m) => $m['modifier']->getName() .
					(!empty($m['params']) ?
						'("' . implode('","', $m['params']) . '")' : ''),
				$this->modifiers
			);
			$modifiers = ':' . implode(':', $modifiers);
		} else {
			$modifiers = '';
		}
		return $this->name . $modifiers;*/

		return $this->fullName;
	}

	public function getPatternName(): string
	{
		if($this->type === TagTypeEnum::FABRIK) {
			return '/\$\{' . $this->name . '\}/';
		}

		return '/\[' . $this->name . '\]/';
	}

	public function getFullPatternName(): string
	{
		if (!empty($this->modifiers)) {
			$modifiers = array_map(
				fn($m) => $m['modifier']->getName() . (!empty($m['params']) ? '\([^)]*\)' : ''),
				$this->modifiers
			);
			$modifiers = ':' . implode(':', $modifiers);
		} else {
			$modifiers = '';
		}
		if ($this->type === TagTypeEnum::FABRIK) {
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
		$modified_value = $this->value;

		if (!empty($this->modifiers)) {
			foreach ($this->modifiers as $m) {
				$modified_value = $m['modifier']->transform($modified_value, $m['params']);
			}
		}

		return $modified_value;
	}

	public function setValue(string|int|null $value): void
	{
		if (is_null($value)) {
			$value = '';
		}

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

	public function addModifier(TagModifierInterface $modifier, array $params = []): void
	{
		$this->modifiers[] = ['modifier' => $modifier, 'params' => $params];
	}

	public function getType(): ?TagTypeEnum
	{
		return $this->type;
	}

	public function setType(?TagTypeEnum $type): void
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

	public function calculateValue(?int $user_id = 0, bool $base64 = false, ?string $fnum = ''): void
	{
		$result = '';
		$db = Factory::getContainer()->get('DatabaseDriver');

		if (empty($user_id))
		{
			$user_id = !empty(Factory::getApplication()->getIdentity()) ? Factory::getApplication()->getIdentity()->id : 0;
			if (empty($user_id))
			{
				$user_id = ComponentHelper::getParams('com_emundus')->get('automated_task_user', 1);
			}
		}

		// TOOD: Ugly fix for VOEU tag need to be refactored
		if ($this->name === 'VOEU')
		{
			$this->request = 'VOEU';
			$this->type = TagTypeEnum::STANDARD;
		}

		if(!empty($this->request))
		{
			if ($this->type === TagTypeEnum::STANDARD)
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
					elseif ($this->name === 'VOEU')
					{
						$result = '';
						$applicationChoices = [];
						if(!empty($fnum))
						{
							$applicationChoicesRepository = new ApplicationChoicesRepository();
							$applicationChoices = $applicationChoicesRepository->getChoicesByFnum($fnum);
						}

						if(!empty($applicationChoices))
						{
							$applicationChoiceState = null;
							$index = null;
							if (!empty($this->modifiers))
							{
								// Apply Status modifier now
								foreach ($this->modifiers as $modifier)
								{
									if ($modifier['modifier'] instanceof ChoiceStatusModifier)
									{
										$applicationChoiceState = ChoicesStateEnum::isValidState($modifier['params'][0] ?? '');
									}

									if($modifier['modifier'] instanceof IndexModifier)
									{
										$index = (int)($modifier['params'][0] ?? -1);
										if(isset($applicationChoices[$index-1]))
										{
											$applicationChoices = [$applicationChoices[$index-1]];
										}
										else
										{
											$applicationChoices = [];
										}
									}
								}
							}

							foreach ($applicationChoices as $key => $choice)
							{
								if (!empty($applicationChoiceState))
								{
									if ($choice->getState() !== $applicationChoiceState)
									{
										continue;
									}
								}

								if(sizeof($applicationChoices) > 1 || empty($index))
								{
									$index = $key + 1;
								}

								$result .= Text::sprintf('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_NO', $index) . ' : ' . $choice->getCampaign()->getLabel();
							}
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

		if(is_array($result))
		{
			if(isset($result['value']))
			{
				$result = $result['value'];
			}
			else
			{
				$result = implode(',', $result);
			}
		}

		$this->value = $result;
	}
}