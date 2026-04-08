<?php
declare(strict_types=1);

/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\Utilities\IpHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;

class IpModel
{
	
	protected function getComponentParams(): Registry
	{
		return ComponentHelper::getParams('com_securitycheckpro');
	}

	/**
	 * Obtiene la IP cliente para el uso en la aplicación (modo seguro para un product usado por clientes).
	 *
	 * Políticas:
	 *  - Por defecto: no permitir overrides (ignora X-Forwarded-For y vendor headers).
	 *  - Si el administrador habilita 'trust_ip_overrides' y proporciona trusted_proxies: se permiten overrides solo si REMOTE_ADDR pertenece a trusted_proxies.
	 *  - Si 'trust_ip_overrides' habilitado + trusted_proxies vacío + allow_overrides_when_remote_is_private=true:
	 *      se permiten overrides únicamente cuando REMOTE_ADDR es una IP privada/loopback (uso en LAN/dev).
	 *
	 * @return string|null IP válida o null si no se puede determinar
	 */
	function getClientIpForSecuritycheckPro(): ?string
	{
		// Lee params del componente
		$params = $this->getComponentParams();

		$trustOverrides = (bool) $params->get('trust_ip_overrides', false);
		$trustedProxiesCsv = (string) $params->get('trusted_proxies', '');
		$allowWhenRemotePrivate = (bool) $params->get('allow_overrides_when_remote_is_private', false);

		$trustedProxies = [];
		if ($trustedProxiesCsv !== '') {
			// normaliza CSV -> array, permite comas y saltos de línea
			$items = preg_split('/[\r\n,]+/', $trustedProxiesCsv, flags: PREG_SPLIT_NO_EMPTY);
			if ($items !== false) {
				$trustedProxies = array_map(static fn($v) => trim((string) $v), $items);
			}
		}

		$server = $_SERVER;
		$remoteAddr = (string) ($server['REMOTE_ADDR'] ?? '');

		// Heurística segura: por defecto no permitimos overrides
		$allowOverridesNow = false;

		if ($trustOverrides) {
			if (!empty($trustedProxies)) {
				// Si hay proxies confiables definidos, sólo permitimos overrides si REMOTE_ADDR está en la lista
				$allowOverridesNow = IpHelper::IPinList($remoteAddr, $trustedProxies);
			} elseif ($allowWhenRemotePrivate) {
				// Si no hay proxies definidos pero admin marcó 'allowWhenRemotePrivate',
				// permitimos overrides solo cuando REMOTE_ADDR es una IP privada/loopback.
				if ($this->isPrivateOrReservedIp($remoteAddr)) {
					$allowOverridesNow = true;
				} else {
					$allowOverridesNow = false;
				}
			} else {
				$allowOverridesNow = false;
			}
		}

		// Configura IpHelper para que tenga o no en cuenta los overrides
		IpHelper::setAllowIpOverrides($allowOverridesNow);

		// Glue para vendors: si cliente habilitó overrides y el servidor recibe headers vendor-specific,
		// podemos mapear CF-Connecting-IP -> HTTP_CLIENT_IP para que IpHelper lo considere.
		// Esto solo se hace si allowOverridesNow === true (sino riesgo de spoofing).
		if ($allowOverridesNow) {
			if (empty($server['HTTP_CLIENT_IP']) && !empty($server['HTTP_CF_CONNECTING_IP'])) {
				// Setear en superglobal para que IpHelper la lea (no persiste fuera de este request)
				$_SERVER['HTTP_CLIENT_IP'] = (string) $server['HTTP_CF_CONNECTING_IP'];
			}
			if (empty($server['HTTP_CLIENT_IP']) && !empty($server['HTTP_TRUE_CLIENT_IP'])) {
				$_SERVER['HTTP_CLIENT_IP'] = (string) $server['HTTP_TRUE_CLIENT_IP'];
			}
		}

		$ip = IpHelper::getIp(); // devuelve string ('' si no hay)
		if ($ip === '') {
			return null;
		}

		// Reglas de validación finales (puedes ajustarlas): rechazamos IPs inválidas o no válidas
		if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
			return null;
		}

		return $ip;
	}

	/**
	 * Comprueba si la IP es privada/loopback/reservada.
	 * @param string $ip
	 * @return bool
	 */
	function isPrivateOrReservedIp(string $ip): bool
	{
		if ($ip === '') {
			return false;
		}

		// Si FILTER_VALIDATE_IP con flags NO_PRIV_RANGE | NO_RES_RANGE falla pero sin flags pasa => es privada/reservada
		$publicValid = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
		if ($publicValid === false && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
			return true;
		}

		return false;
	}
    
}
