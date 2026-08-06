<?php

namespace Tchooz\Services\Automation;

use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\RedirectIntent;

/**
 * Collecteur request-scoped de l'intention de redirection courante.
 *
 * Une action redirigeante n'appelle jamais $app->redirect() : elle enregistre son URL ici via
 * request(). Le point d'entrée HTTP (controller fetch ou plugin système pleine page) lit puis vide
 * l'intent via consume() et décide du transport.
 *
 * Cycle de vie = la requête HTTP, VOLONTAIREMENT découplé de AutomationExecutionContext dont
 * endProcessing() réinitialise l'état à la fin de chaque chaîne d'automations — sinon l'intent
 * serait détruit avant que le point d'entrée puisse le relire.
 */
class RedirectIntentRegistry
{
	private static ?RedirectIntent $pending = null;

	/**
	 * Enregistre une demande de redirection. Politique : premier arrivé gagne. Toute demande
	 * ultérieure est ignorée et journalisée (jamais de drop silencieux).
	 */
	public static function request(RedirectIntent $intent): void
	{
		if (empty($intent->getUrl()))
		{
			return;
		}

		if (self::$pending !== null)
		{
			self::log('Redirect intent "' . $intent->getSource() . '" (' . $intent->getUrl() . ') dropped, "' . self::$pending->getSource() . '" already requested');

			return;
		}

		self::$pending = $intent;
	}

	public static function peek(): ?RedirectIntent
	{
		return self::$pending;
	}

	/**
	 * Lit et vide l'intent courant. Appelé par le transport.
	 */
	public static function consume(): ?RedirectIntent
	{
		$intent        = self::$pending;
		self::$pending = null;

		return $intent;
	}

	public static function clear(): void
	{
		self::$pending = null;
	}

	/**
	 * Réinitialise l'état. Utile pour les tests et les workers CLI qui enchaînent plusieurs items
	 * dans un même process.
	 */
	public static function reset(): void
	{
		self::$pending = null;
	}

	private static function log(string $message): void
	{
		Log::addLogger(['text_file' => 'com_emundus.action.log.php'], Log::ALL, ['com_emundus.action']);
		Log::add($message, Log::WARNING, 'com_emundus.action');
	}
}
