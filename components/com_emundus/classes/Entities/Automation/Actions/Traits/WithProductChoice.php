<?php

namespace Tchooz\Entities\Automation\Actions\Traits;

use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Repositories\Payment\ProductRepository;
use Tchooz\Services\Field\FieldOptionProvider;

/**
 * Mixin pour les actions d'automatisation qui ont besoin d'un sélecteur de produits.
 */
trait WithProductChoice
{
	protected function buildProductChoiceField(
		string $name = 'product',
		string $label = '',
		bool $required = false,
		bool $multiple = false,
		array $dependencies = []
	): ChoiceField
	{
		$field = new ChoiceField($name, $label, [], $required, $multiple);
		$field->setOptionsProvider(new FieldOptionProvider(
			controller: 'payment',
			methodName: 'getproductoptions',
			dependencies: $dependencies,
			repository: new ProductRepository(),
			repositoryMethod: 'getProducts',
			repositoryMethodArgs: [0],
			labelMethod: 'getLabel',
			valueMethod: 'getId',
		));
		$field->provideOptions();

		return $field;
	}
}
