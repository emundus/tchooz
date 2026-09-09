<?php
/**
 * @package     Tchooz\Factories\Language
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Language;

use Joomla\CMS\Application\WebApplication;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Languages\Administrator\Helper\LanguagesHelper;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Language\LanguageEntity;
use Tchooz\Enums\StatusEnum;
use Tchooz\Factories\DBFactory;
use Tchooz\Repositories\Language\LanguageRepository;
use Tchooz\Services\Language\ObjectsRegistry;

class LanguageFactory implements DBFactory
{
	private static ?string $pinnedLanguageCode = null;

	/** @var array<string, Language> */
	private static array $languages = [];

	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): LanguageEntity
	{
		if (is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		return new LanguageEntity(
			tag: $dbObject->tag,
			langCode: $dbObject->lang_code,
			override: $dbObject->override,
			originalText: $dbObject->original_text ?? '',
			type: $dbObject->type ?? 'override',
			createdBy: $dbObject->created_by ? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject->created_by) : null,
			createdDate: ($dbObject->created_date && $dbObject->created_date !== '0000-00-00 00:00:00') ? new \DateTime($dbObject->created_date) : null,
			referenceId: $dbObject->reference_id ?? 0,
			referenceTable: $dbObject->reference_table ?? '',
			referenceField: $dbObject->reference_field ?? '',
			modifiedBy: $dbObject->modified_by ? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject->modified_by) : null,
			modifiedDate: ($dbObject->modified_date && $dbObject->modified_date !== '0000-00-00 00:00:00') ? new \DateTime($dbObject->modified_date) : null,
			id: $dbObject->id,
			published: StatusEnum::tryFrom($dbObject->published) ?? StatusEnum::UNPUBLISHED,
		);
	}

	public static function replaceAccents($value): string
	{
		$unwanted_array = array('Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
		                        'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
		                        'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
		                        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
		                        'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', '!' => '', '?' => '', '*' => '', '%' => 'y', '^' => '', '€' => '', '+' => '', '=' => '',
		                        ';' => '', ',' => '', '&' => '', '@' => '', '#' => '', '`' => '', '¨' => '', '§' => '', '"' => '', '\'' => '', '\\' => '', '/' => '', '(' => '', ')' => '', '[' => '', ']' => '', ' ' => '_');

		return strtr($value, $unwanted_array);
	}

	public static function translate(
		string  $key,
		array   $values,
		?string $reference_table = '',
		?int    $reference_id = 0,
		?string $reference_field = '',
		?int    $user_id = 0,
		?string $original_text = null
	): string
	{
		$languageRepository = new LanguageRepository();
		$creator            = empty($user_id) ? Factory::getApplication()->getIdentity() : Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id);

		$languages = LanguageHelper::getLanguages();

		foreach ($languages as $language)
		{
			if (!empty($values) && (isset($values[$language->sef]) || isset($values[$language->lang_code])))
			{
				$override = $values[$language->sef] ?? $values[$language->lang_code];
			}
			else {
				// Create it to avoid orphans on other languages
				$override = reset($values);
			}

			$languageRepository->setLangCode($language->lang_code);
			$languageEntity = $languageRepository->getByTag($key);

			if (empty($languageEntity))
			{
				$tag            = self::cleanTag($key);
				$languageEntity = new LanguageEntity(
					$tag,
					$language->lang_code,
					$override,
					$original_text ?? $override,
					'override',
					$creator,
					null,
					$reference_id,
					$reference_table,
					$reference_field
				);
			}
			elseif(isset($values[$language->lang_code]) || isset($values[$language->sef]))
			{
				$languageEntity->setOverride($override);
				$languageEntity->setModifiedBy($creator);
				$languageEntity->setModifiedDate(new Date());
			}

			$languageEntity->setPublished(StatusEnum::PUBLISHED);

			$languageRepository->flush($languageEntity);
		}

		return $key;
	}

	public static function deleteTranslation(string $key): bool
	{
		$deleted = false;

		$languageRepository = new LanguageRepository();

		$platformLanguages = $languageRepository->getPlatformLanguages();
		foreach ($platformLanguages as $language)
		{
			$languageRepository->setLangCode($language);
			$languageEntity = $languageRepository->getByTag($key);

			if ($languageEntity)
			{
				$languageEntity->setPublished(StatusEnum::TRASHED);

				$deleted = $languageRepository->flush($languageEntity);
			}
			else
			{
				$deleted = true;
			}
		}

		return $deleted;
	}

	public static function getTranslation(string $key, ?string $langCode = null, ?LanguageRepository $languageRepository = null): ?string
	{
		if(empty($languageRepository))
		{
			$languageRepository = new LanguageRepository();
		}

		if ($langCode)
		{
			$languageRepository->setLangCode($langCode);
		}

		$languageEntity = $languageRepository->getByTag($key);

		if ($languageEntity)
		{
			return $languageEntity->getOverride();
		}

		return null;
	}

	/**
	 * Replace the translation keys embedded in a text by their translation.
	 * Legacy labels often mix free text and keys, ex: '2025 - FORM_EVALUATION'.
	 * Keys without a translation are left untouched.
	 */
	public static function resolveTranslationKeys(string $text, ?string $langCode = null, ?LanguageRepository $languageRepository = null): string
	{
		if (empty($text))
		{
			return $text;
		}

		if (empty($languageRepository))
		{
			$languageRepository = new LanguageRepository();
		}

		return preg_replace_callback(
			'/[A-Z][A-Z0-9]*(?:_[A-Z0-9]+)+/',
			static function (array $matches) use ($langCode, $languageRepository) {
				$translation = self::getTranslation($matches[0], $langCode, $languageRepository);

				return empty($translation) ? $matches[0] : $translation;
			},
			$text
		);
	}

	public static function getJoomlaTranslations(array $keys): array
	{
		$translations = [];

		if (!empty($keys))
		{
			foreach ($keys as $key)
			{
				$translations[] = Text::_($key);
			}
		}

		return $translations;
	}

	public static function getDefaultLanguageCode(): string
	{
		return ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
	}

	/**
	 * Language tag to use when resolving multilingual content ({shortlang} placeholders, translated columns...).
	 * An execution context, an automated action for instance, can pin it through runWithLanguage().
	 * Otherwise the language of the application is used, except in CLI where the application is built with the
	 * locale stored in configuration.php: that one is the installation language and not necessarily the language
	 * of the site, so the site default language is used instead.
	 */
	public static function getCurrentLanguageCode(): string
	{
		if (!empty(self::$pinnedLanguageCode))
		{
			return self::$pinnedLanguageCode;
		}

		$app = Factory::getApplication();

		if ($app->isClient('cli'))
		{
			return self::getDefaultLanguageCode();
		}

		return $app->getLanguage()->getTag();
	}

	public static function getCurrentShortLang(): string
	{
		return substr(self::getCurrentLanguageCode(), 0, 2);
	}

	/**
	 * Execute a callback with $langCode as the language of the platform: Joomla translations (Text::_),
	 * language overrides and every multilingual content resolved through getCurrentLanguageCode().
	 * The previous language is always restored afterwards.
	 */
	public static function runWithLanguage(string $langCode, callable $callback): mixed
	{
		$previousLanguageCode = self::$pinnedLanguageCode;
		$previousAppLanguage  = Factory::getApplication()->getLanguage();
		$previousTextLanguage = Factory::$language;

		self::$pinnedLanguageCode = $langCode;
		self::applyLanguage(self::createLanguage($langCode));

		try
		{
			return $callback();
		}
		finally
		{
			self::$pinnedLanguageCode = $previousLanguageCode;
			self::applyLanguage($previousAppLanguage);
			// The application and Text::_ languages can differ (CLI), restore the exact previous state.
			Factory::$language = $previousTextLanguage;
		}
	}

	private static function applyLanguage(?Language $language): void
	{
		if ($language === null)
		{
			return;
		}

		$app = Factory::getApplication();

		if ($app instanceof WebApplication)
		{
			$app->loadLanguage($language);
		}

		// Text::_ only reads the language through the deprecated Factory::getLanguage(), and the console
		// application has no way to swap its own language: setting the global is the only option left.
		// Keep it here, nowhere else, so that it is the single spot to revisit when Joomla drops it.
		Factory::$language = $language;
	}

	private static function createLanguage(string $langCode): Language
	{
		// A task run can generate a lot of documents in a row, keep the loaded languages around.
		if (isset(self::$languages[$langCode]))
		{
			return self::$languages[$langCode];
		}

		$container = Factory::getContainer();

		$languageFactory = $container->has(DbLanguageFactory::class) ? $container->get(DbLanguageFactory::class) : new DbLanguageFactory();
		$language        = $languageFactory->createLanguage($langCode, false);
		$language->load('com_emundus', JPATH_SITE, $langCode, true);

		self::$languages[$langCode] = $language;

		return $language;
	}

	public static function cleanTag($tag): string
	{
		$helper = new LanguagesHelper();

		return $helper::filterKey($tag);
	}

	public static function getTranslationsObjects(): array
	{
		$translationsObjects = array();

		$objectRegistry = new ObjectsRegistry();
		$objects        = $objectRegistry->getObjects();

		foreach ($objects as $object)
		{
			$translationsObjects[] = [
				'type'        => $object->getType(),
				'name'        => $object->getName(),
				'description' => $object->getDescription(),
				'table'       => $object->getDefinition()->getTable()->__serialize(),
				'fields'      => $object->getDefinition()->getFields()->__serialize(),
			];
		}

		return $translationsObjects;
	}

	public static function getShortLangCode(string $langCode): string
	{
		$parts = explode('-', $langCode);
		return $parts[0];
	}

	public static function cleanCache()
	{
		$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController('output', ['defaultgroup' => 'com_emundus.translations']);
		$languages = LanguageHelper::getLanguages();

		foreach ($languages as $language)
		{
			$key = 'emundus_translations_' . self::getShortLangCode($language->lang_code);
			$cache->unlock($key);
		}

		$cache->clean();
	}
}