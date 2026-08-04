<?php
/**
 * Déclarations utilisées uniquement par l'analyse PHPStan des fragments de code Fabrik.
 */

/**
 * Représente une balise Fabrik du type {table___element}.
 * Fabrik y injecte une valeur à l'exécution : elle est donc inconnue au moment de l'analyse.
 *
 * @param   string  $name  Nom de la balise, sans les accolades
 *
 * @return string
 */
function fabrikPlaceholder(string $name): string
{
	return $name;
}
