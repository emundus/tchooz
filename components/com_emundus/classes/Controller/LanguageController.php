<?php

namespace Tchooz\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Component\Languages\Administrator\Helper\LanguagesHelper;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\EmundusResponse;
use Tchooz\Entities\Language\LanguageEntity;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Factories\Language\LanguageFactory;
use Tchooz\Repositories\Language\LanguageRepository;
use Tchooz\Transformers\Language\TranslationListItemTransformer;

class LanguageController extends EmundusController
{
	private LanguageRepository $languageRepository;

	private TranslationListItemTransformer $transformer;

	function __construct($config = array())
	{
		parent::__construct($config);

		$this->languageRepository = new LanguageRepository();
		$this->transformer        = new TranslationListItemTransformer();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::ADMINISTRATOR)]
	public function gettranslations(): EmundusResponse
	{
		$lim       = $this->input->getInt('lim', 0);
		$page      = $this->input->getInt('page', 1);
		$recherche = $this->input->getString('recherche', '');
		$sort      = $this->input->getString('sort', 'ASC');
		$order_by  = $this->input->getString('order_by', 'tag');
		$langCode  = $this->input->getString('lang_code', '');

		$filters = [
			'type'      => 'override',
			'lang_code' => !empty($langCode) ? $langCode : LanguageFactory::getDefaultLanguageCode(),
		];

		$published = $this->input->getString('published', '');
		if ($published !== '')
		{
			$filters['published'] = (int) $published;
		}

		$form = $this->input->getInt('form', 0);
		if (!empty($form))
		{
			$filters['form'] = $form;
		}

		$order = $this->languageRepository->buildOrderBy($order_by, $sort === 'DESC' ? 'DESC' : 'ASC');
		$translations = $this->languageRepository->getList($filters, $lim, $page, [], $order, $recherche);

		return EmundusResponse::ok([
			'datas' => $this->transformer->transformAll($translations->getItems()),
			'count' => $translations->getTotalItems(),
		]);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::ADMINISTRATOR)]
	public function getformsforfilter(): EmundusResponse
	{
		$forms = array_map(
			static fn(object $form) => [
				'value' => (int) $form->id,
				'label' => Text::_($form->label),
			],
			$this->languageRepository->getReferencedForms()
		);

		$collator = new \Collator(Factory::getApplication()->getLanguage()->getTag());
		usort($forms, static fn(array $a, array $b) => $collator->compare($a['label'], $b['label']));

		return EmundusResponse::ok($forms);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::ADMINISTRATOR)]
	public function getplatformlanguages(): EmundusResponse
	{
		$languages = array_values(array_filter(
			$this->languageRepository->getLanguages(),
			static fn(object $language) => (int) $language->published === 1
		));

		return EmundusResponse::ok($languages);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::ADMINISTRATOR)]
	public function addtranslation(): EmundusResponse
	{
		$this->checkToken();

		$tag = LanguagesHelper::filterKey($this->input->getString('tag', ''));
		$overrides = $this->input->get('overrides', [], 'array');

		if (empty($tag))
		{
			return EmundusResponse::fail(
				Text::_('COM_EMUNDUS_LANGUAGES_TRANSLATION_TAG_INVALID'),
				EmundusResponse::HTTP_BAD_REQUEST
			);
		}

		$overrides = array_filter(
			array_map('strval', $overrides),
			static fn(string $override) => $override !== ''
		);

		if (empty($overrides))
		{
			return EmundusResponse::fail(
				Text::_('COM_EMUNDUS_LANGUAGES_TRANSLATION_OVERRIDE_REQUIRED'),
				EmundusResponse::HTTP_BAD_REQUEST
			);
		}

		$publishedLanguages = array_map(
			static fn(object $language) => $language->lang_code,
			array_filter(
				$this->languageRepository->getLanguages(),
				static fn(object $language) => (int) $language->published === 1
			)
		);

		foreach (array_keys($overrides) as $langCode)
		{
			if (!in_array($langCode, $publishedLanguages, true))
			{
				return EmundusResponse::fail(
					Text::sprintf('COM_EMUNDUS_LANGUAGES_TRANSLATION_LANGUAGE_UNKNOWN', $langCode),
					EmundusResponse::HTTP_BAD_REQUEST
				);
			}

			if ($this->languageRepository->tagExists($tag, $langCode))
			{
				return EmundusResponse::fail(
					Text::sprintf('COM_EMUNDUS_LANGUAGES_TRANSLATION_TAG_EXISTS', $tag),
					EmundusResponse::HTTP_CONFLICT
				);
			}
		}

		$created = [];
		foreach ($overrides as $langCode => $override)
		{
			$translation = new LanguageEntity(
				tag: $tag,
				langCode: $langCode,
				override: $override,
				originalText: $override,
				type: 'override',
				createdBy: $this->user
			);

			if (!$this->languageRepository->flush($translation))
			{
				return EmundusResponse::fail(
					Text::_('COM_EMUNDUS_LANGUAGES_TRANSLATION_SAVE_FAILED'),
					EmundusResponse::HTTP_INTERNAL_SERVER_ERROR
				);
			}

			$created[] = $this->transformer->transform($translation->toObject());
		}

		return EmundusResponse::ok($created, Text::_('COM_EMUNDUS_LANGUAGES_TRANSLATION_ADDED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::ADMINISTRATOR)]
	public function savetranslation(): EmundusResponse
	{
		$this->checkToken();

		$id       = $this->input->getInt('id', 0);
		$langCode = $this->input->getString('lang_code', '');
		$override = $this->input->getString('override', '');

		if (empty($id) || empty($langCode))
		{
			return EmundusResponse::fail(
				Text::_('COM_EMUNDUS_LANGUAGES_MISSING_PARAMETERS'),
				EmundusResponse::HTTP_BAD_REQUEST
			);
		}

		$this->languageRepository->setLangCode($langCode);
		$translation = $this->languageRepository->getById($id);

		if (empty($translation))
		{
			return EmundusResponse::fail(
				Text::sprintf('COM_EMUNDUS_LANGUAGES_TRANSLATION_NOT_FOUND', $id),
				EmundusResponse::HTTP_NOT_FOUND
			);
		}

		$translation->setOverride($override);
		$translation->setModifiedBy($this->user);
		$translation->setModifiedDate(new \DateTime());

		if (!$this->languageRepository->flush($translation))
		{
			return EmundusResponse::fail(
				Text::_('COM_EMUNDUS_LANGUAGES_TRANSLATION_SAVE_FAILED'),
				EmundusResponse::HTTP_INTERNAL_SERVER_ERROR
			);
		}

		return EmundusResponse::ok(
			$this->transformer->transform($translation->toObject()),
			Text::_('COM_EMUNDUS_LANGUAGES_TRANSLATION_SAVED')
		);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::ADMINISTRATOR)]
	public function deletetranslation(): EmundusResponse
	{
		$this->checkToken();

		$id = $this->input->getInt('id', 0);

		if (empty($id))
		{
			return EmundusResponse::fail(
				Text::_('COM_EMUNDUS_LANGUAGES_MISSING_PARAMETERS'),
				EmundusResponse::HTTP_BAD_REQUEST
			);
		}

		$translation = $this->languageRepository->getById($id);

		if (empty($translation))
		{
			return EmundusResponse::fail(
				Text::sprintf('COM_EMUNDUS_LANGUAGES_TRANSLATION_NOT_FOUND', $id),
				EmundusResponse::HTTP_NOT_FOUND
			);
		}

		if (!$this->languageRepository->delete($id))
		{
			return EmundusResponse::fail(
				Text::_('COM_EMUNDUS_LANGUAGES_TRANSLATION_DELETE_FAILED'),
				EmundusResponse::HTTP_INTERNAL_SERVER_ERROR
			);
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_LANGUAGES_TRANSLATION_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::ADMINISTRATOR)]
	public function search(): EmundusResponse
	{
		$this->checkToken('get');

		$model = $this->app->bootComponent('com_languages')
			->getMVCFactory()->createModel('Strings', 'Administrator', ['ignore_request' => true]);

		return EmundusResponse::ok($model->search());
	}
}
