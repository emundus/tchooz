<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Router;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\DI\Exception\KeyNotFoundException;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Route handling class
 *
 * @since  1.7.0
 */
class Route
{
    /**
     * No change, use the protocol currently used.
     *
     * @since  3.9.7
     */
    public const TLS_IGNORE = 0;

    /**
     * Make URI secure using http over TLS (https).
     *
     * @since  3.9.7
     */
    public const TLS_FORCE = 1;

    /**
     * Make URI unsecure using plain http (http).
     *
     * @since  3.9.7
     */
    public const TLS_DISABLE = 2;

    /**
     * The route object so we don't have to keep fetching it.
     *
     * @var    Router[]
     * @since  3.0.1
     */
    private static $_router = [];

    /**
     * Translates an internal Joomla URL to a humanly readable URL. This method builds links for the current active client.
     *
     * @param   string   $url       Absolute or Relative URI to Joomla resource.
     * @param   boolean  $xhtml     Replace & by &amp; for XML compliance.
     * @param   integer  $tls       Secure state for the resolved URI. Use Route::TLS_* constants
     *                                0: (default) No change, use the protocol currently used in the request
     *                                1: Make URI secure using global secure site URI.
     *                                2: Make URI unsecure using the global unsecure site URI.
     * @param   boolean  $absolute  Return an absolute URL
     *
     * @return  string  The translated humanly readable URL.
     *
     * @since   1.7.0
     */
    public static function _($url, $xhtml = true, $tls = self::TLS_IGNORE, $absolute = false)
    {
        try {
            /**
             * @deprecated  3.9 int conversion will be removed in 6.0
             *              Before 3.9.7 this method silently converted $tls to integer
             */
            if (!\is_int($tls)) {
                @trigger_error(
                    __METHOD__ . '() called with incompatible variable type on parameter $tls.',
                    E_USER_DEPRECATED
                );

                $tls = (int) $tls;
            }

            /**
             * @deprecated  3.9 -1 as valid value will be removed in 6.0
             *              Before 3.9.7 this method accepted -1.
             */
            if ($tls === -1) {
                $tls = self::TLS_DISABLE;
            }

            $app    = Factory::getApplication();
            $client = $app->getName();

            return static::link($client, $url, $xhtml, $tls, $absolute);
        } catch (\RuntimeException) {
            /**
             * @deprecated  3.9 this method will not fail silently from 6.0
             *              Before 3.9.0 this method failed silently on router error. This B/C will be removed in Joomla 6.0
             */
            return null;
        }
    }

    /**
     * Translates an internal Joomla URL to a humanly readable URL.
     * NOTE: To build link for active client instead of a specific client, you can use <var>Route::_()</var>
     *
     * @param   string   $client    The client name for which to build the link.
     * @param   string   $url       Absolute or Relative URI to Joomla resource.
     * @param   boolean  $xhtml     Replace & by &amp; for XML compliance.
     * @param   integer  $tls       Secure state for the resolved URI. Use Route::TLS_* constants
     *                                0: (default) No change, use the protocol currently used in the request
     *                                1: Make URI secure using global secure site URI.
     *                                2: Make URI unsecure using the global unsecure site URI.
     * @param   boolean  $absolute  Return an absolute URL
     *
     * @return  string  The translated humanly readable URL.
     *
     * @throws  \RuntimeException
     *
     * @since   3.9.0
     */
    public static function link($client, $url, $xhtml = true, $tls = self::TLS_IGNORE, $absolute = false)
    {
        // If we cannot process this $url exit early.
        if (!\is_array($url) && (!str_starts_with($url, '&')) && (!str_starts_with($url, 'index.php'))) {
            return $url;
        }

        // Get the router instance, only attempt when a client name is given.
        if ($client && !isset(self::$_router[$client])) {
            try {
                self::$_router[$client] = Factory::getContainer()->get(ucfirst($client) . 'Router') ?: Factory::getApplication()::getRouter($client);
            } catch (KeyNotFoundException) {
                self::$_router[$client] = Factory::getApplication()::getRouter($client);
            }
        }

        // Make sure that we have our router
        if (!isset(self::$_router[$client])) {
            throw new \RuntimeException(Text::sprintf('JLIB_APPLICATION_ERROR_ROUTER_LOAD', $client), 500);
        }

        // Build route.
        $uri    = self::$_router[$client]->build($url);
        $scheme = ['path', 'query', 'fragment'];

        /*
         * Get the secure/unsecure URLs.
         *
         * If the first 5 characters of the BASE are 'https', then we are on an ssl connection over
         * https and need to set our secure URL to the current request URL, if not, and the scheme is
         * 'http', then we need to do a quick string manipulation to switch schemes.
         */
        if ($tls === self::TLS_FORCE) {
            $uri->setScheme('https');
        } elseif ($tls === self::TLS_DISABLE) {
            $uri->setScheme('http');
        }

        // Set scheme if requested or
        if ($absolute || $tls > 0) {
            static $scheme_host_port;

            if (!\is_array($scheme_host_port)) {
                $uri2             = Uri::getInstance();
                $scheme_host_port = [$uri2->getScheme(), $uri2->getHost(), $uri2->getPort()];
            }

            if (\is_null($uri->getScheme())) {
                $uri->setScheme($scheme_host_port[0]);
            }

            $uri->setHost($scheme_host_port[1]);
            $uri->setPort($scheme_host_port[2]);

            $scheme = array_merge($scheme, ['host', 'port', 'scheme']);
        }

        $url = $uri->toString($scheme);

        // Replace spaces.
        $url = preg_replace('/\s/u', '%20', $url);

        if ($xhtml) {
            $url = htmlspecialchars($url, ENT_COMPAT, 'UTF-8');
        }

        return $url;
    }
}
