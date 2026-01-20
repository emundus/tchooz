<?php

namespace Tchooz\Services\Mapping;

use EmundusHelperCache;
use Tchooz\Enums\Mapping\MappingTransformersEnum;
use Tchooz\Transformers\Mapping\MappingTranformer;

class MappingTransformationsRegistry
{
	private CONST TRANSFORMERS_DIRECTORY = JPATH_ROOT . '/components/com_emundus/classes/Transformers';

	private array $transformers = [];

	private EmundusHelperCache $cache;


	public function __construct()
	{
		$this->cache = new EmundusHelperCache();
		$this->autoRegisterTransformers();
	}

	public function autoRegisterTransformers(): void
	{
		$transformers = $this->cache->get('mapping_transformers');

		if (empty($transformers))
		{
			$files = glob(self::TRANSFORMERS_DIRECTORY . '/Mapping/*.php');
			if ($files) {
				foreach ($files as $file) {
					$className = 'Tchooz\\Transformers\\Mapping\\' . pathinfo($file, PATHINFO_FILENAME);
					if (class_exists($className)) {
						$reflection = new \ReflectionClass($className);
						if (!$reflection->isAbstract() && $reflection->isSubclassOf(MappingTranformer::class)) {
							$instance = $reflection->newInstance();
							$this->registerTransformer($instance);
						}
					}
				}

				$this->cache->set('mapping_transformers', $this->transformers);
			}
		}
		else
		{
			$this->transformers = $transformers;
		}
	}

	public function registerTransformer(MappingTranformer $transformer): void
	{
		$this->transformers[$transformer->getType()->value] = $transformer;
	}

	public function getTransformer(MappingTransformersEnum $transformerType): ?MappingTranformer
	{
		return $this->transformers[$transformerType->value] ?? null;
	}

	public function getTransformersSchemas(): array
	{
		$schemas = [];
		foreach ($this->transformers as $transformer)
		{
			assert($transformer instanceof MappingTranformer);
			$schemas[] = $transformer->serialize();
		}
		return $schemas;
	}
}