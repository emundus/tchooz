<?php
/**
 * @Securitycheckpro plugin
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\System\Securitycheckpro\Extension;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Access\Access;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\CMSApplication;
use Joomla\String\StringHelper;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Encrypt\Totp;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use ReflectionMethod;
use Joomla\CMS\Helper\AuthenticationHelper;
use Joomla\Component\Users\Administrator\Model\UserModel;
use Joomla\CMS\User\User;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Installer\Adapter\InstallerAdapter;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Event\MultiFactor\Validate as MfaValidate;
use Joomla\Component\Users\Administrator\Helper\Mfa;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FirewallconfigModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\IpModel;
use Joomla\Plugin\System\Securitycheckpro\Helper\SecuritycheckProHelper;

class Securitycheckpro extends CMSPlugin 
{
	/**
     * Contendr� los parámetros del plugin
     *
     * @var BaseModel|null
     */
    private $pro_plugin = null;
	
	/**
     * Tipo de BBDD
     *
     * @var string
     */
	private $dbtype = "mysql";
	
	/**
     * Ip Model
     *
     * @var IpModel
     */
	private $ipmodel;
	
	/**
     * Path al directorio de los escaneos
     *
     * @var string
     */	
	private $scan_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;

	public function __construct()
    {
		$this->ipmodel = new IpModel();         		
    }		
		
	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array<string,mixed>
	 *
	 * @since   9.0.0
	 */
	public static function getSubscribedEvents(): array
	{	
		
		// Register all public onSomething methods as event handlers
		$events   = [];
		$refClass = new \ReflectionClass(self::class);
		$methods  = $refClass->getMethods(ReflectionMethod::IS_PUBLIC);

		foreach ($methods as $method)
		{
			$name = $method->getName();

			if (substr($name, 0, 2) != 'on')
			{
				continue;
			}

			$events[$name] = $name;
		}

		return $events;
	}	
        
    /**
     * Función para borrar logs
     *
     * @return  void
     *     
     */    
    function delete_logs()
    {
		// Si la variable "pro_plugin" está vacía la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
		
		$sql = '';
		
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        
        (int) $track_actions_delete_period = $this->pro_plugin->getValue('delete_period', 0, 'pro_plugin');
        (int) $scp_delete_period = $this->pro_plugin->getValue('scp_delete_period', 60, 'pro_plugin');
        
        // Borramos los logs de Track Actions si el parámetro está establecido así
        if ($track_actions_delete_period > 0) {
            try
            {
				if (strstr($this->dbtype,"mysql")) {
					$sql = "DELETE FROM #__securitycheckpro_trackactions WHERE log_date < NOW() - INTERVAL '{$track_actions_delete_period}' DAY";
				} else if (strstr($this->dbtype,"pgsql")) {
					$sql = "DELETE FROM #__securitycheckpro_trackactions WHERE log_date < NOW() - INTERVAL '{$track_actions_delete_period} DAY';";					
				}
                
                $db->setQuery($sql);
                $db->execute();
            }catch (\Exception $e)
            {
                
            }
        }
        
        // Borramos los logs capturados por el firewall
        if ($scp_delete_period > 0) {
            try
            {
				if (strstr($this->dbtype,"mysql")) {
					$sql = "DELETE FROM #__securitycheckpro_logs WHERE time < NOW() - INTERVAL '{$scp_delete_period}' DAY";
				} else if (strstr($this->dbtype,"pgsql")) {
					$sql = "DELETE FROM #__securitycheckpro_logs WHERE time < NOW() - INTERVAL '{$scp_delete_period} DAY';";					
				}
                
                $db->setQuery($sql);
                $db->execute();
            }catch (\Exception $e)
            {
                
            }
        }
                
    }
    
   /**
     * Función para grabar los logs en la BBDD
     *
     * @param   string|bool        $logs_attacks    	Tells if logs must be stored
	 * @param   string             $ip    				The IP of the attacker
	 * @param   string             $tag_description     Tag description of the attack
	 * @param   string             $description    		Description of the attack
	 * @param   string             $type   				Type of attack
	 * @param   string             $uri   				The uri where the attack has been stopped
	 * @param   string             $original_string     Original string involved in the attack
	 * @param   string             $username    		Username used in the attack
	 * @param   string             $component   		Extension involved in the attack
     *
     * @return void|bool
     *     
     */
    public function grabar_log(
		$logs_attacks,
		$ip,
		$tag_description,
		$description,
		$type,
		$uri,
		$original_string,
		$username,
		$component
	) {
		SecuritycheckProHelper::grabarLog($logs_attacks, $ip, $tag_description, $description, $type, $uri, $original_string, $username, $component);
	}

   
	/**
     * Determina si un valor esta codificado en base64
     *
     * @param   string       $value    	The string to check
	 *
     * @return bool
     *     
     */    
    function is_base64($value)
    {
        $res = false; // Determines if any character of the decoded string is between 32 and 126, which should indicate a non valid european ASCII character
    
        $min_len = mb_strlen($value)>7;

        if ($min_len) {

            // Verify the string contains only valid base64 characters before attempting decode
            if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $value)) {
                return false;
            }

            $decoded = base64_decode(chunk_split($value));
            $string_caracteres = str_split($decoded); 
            if (empty($string_caracteres)) {
                return false;  // It is not a base64 string!
            }else
            {
                foreach ($string_caracteres as $caracter)
                {
                    if ((empty($caracter)) || (ord($caracter)<32) || (ord($caracter)>126)) { // Non-valid ASCII value
                        return false; // It is not a base64 string!
                    }
                }
            }
            
            $res = true; // It is a base64 string!
        }
        
        return $res;
    }
    
    /**
     * Funcion que realiza la misma funcion que mysql_real_escape_string() pero sin necesidad de una conexion a la BBDD
     *
     * @param   string       $value    	The string to check
	 *
     * @return string
     *     
     */ 
    function escapa_string($value)
    {
    
        $search = array("\x00", "'", "\"", "\x1a");
        $replace = array("\\x00", "\'", "\\\"", "\\\x1a");
		    
        return str_ireplace($search, $replace, $value);
    }
	
	/**
     * Checks if a string is html
     *
     * @param   string       $string    	The string to check
	 *
     * @return bool
     *     
     */ 
	function isHTML($string){
		return $string != strip_tags($string) ? true:false;
	}
    
    /**
     * Chequea si la extensión pasada como argumento es vulnerable
     *
     * @param   string       $option    	The extension to check
	 *
     * @return bool
     *     
     */
    private function check_extension_vulnerable($option)
    {
        
        // Inicializamos las variables
        $vulnerable = false;
        
        // Creamos el nuevo objeto query
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        
        // Sanitizamos el argumento
        $sanitized_option = $db->Quote($db->escape($option));
    
        // Construimos la consulta
        $query = "SELECT COUNT(*) from #__securitycheckpro_db WHERE (vuln_type = {$sanitized_option})" ;        
                
        $db->setQuery($query);
        $result = $db->loadResult();
        
        if ($result > 0) {
            $vulnerable = true;
        } 
        
        // Devolvemos el resultado
        return $vulnerable;
    
    }   
	
	private function isLikelyJson(string $s): bool
	{
		$s = trim($s);
		if ($s === '' || (!str_starts_with($s, '{') && !str_starts_with($s, '[') && !str_starts_with($s, '"'))) {
			return false;
		}
		json_decode($s);
		return json_last_error() === JSON_ERROR_NONE;
	}

	private function isLikelyHtml(string $s): bool
	{
		// mas robusto que buscar "<" a pelo
		return $s !== strip_tags($s);
	}

	private function looksLikeUrlOrEmail(string $s): bool
	{
		$s = trim($s);
		if (filter_var($s, FILTER_VALIDATE_URL)) return true;
		if (filter_var($s, FILTER_VALIDATE_EMAIL)) return true;
		// hashes, anchors, etc.
		if (preg_match('~^[a-z]+://~i', $s)) return true;
		if (preg_match('~^[/#?][^\s]+$~', $s)) return true;
		return false;
	}

	/**
	 * Señal de 'contexto SQL' (palabras clave cercanas a operadores o comillas)
	 */
	private function sqlContextScore(string $s): int
	{
		$score = 0;
		$low = mb_strtolower($s);

		// palabras clave con limites de palabra
		$kw = ['select','union','update','insert','delete','drop','where','and','or','sleep','benchmark','waitfor','case','when'];
		foreach ($kw as $w) {
			if (preg_match('~\b' . preg_quote($w, '~') . '\b~i', $s)) $score++;
		}
		// operadores/comillas frecuentes en inyeccion
		if (preg_match("~['\"`]=|=['\"`]|\b(or|and)\b\s+['\"]|--\s|/\*|\*/|;~i", $s)) $score++;
		// presencia de () + palabra clave
		if (preg_match('~\b(select|concat|substring|ascii|char|md5|sha1)\s*\(~i', $s)) $score++;

		return $score; // 0..N
	}

	/**
	 * Gating general: evita filtrar si claramente es JSON/HTML/URL o texto "normal".
	 */
	private function shouldInspectForSql(string $s): bool
	{
		if ($this->isLikelyJson($s)) return false;
		if ($this->isLikelyHtml($s)) return true; // HTML sí interesa para XSS, pero no para SQL débil
		if ($this->looksLikeUrlOrEmail($s)) return false;

		// No tiene sentido si es corto y alfanumérico
		if (preg_match('~^[a-z0-9 _.-]{1,20}$~i', $s)) return false;

		// Sólo pasa si hay cierta "pinta SQL"
		return $this->sqlContextScore($s) >= 1;
	}
	
	/**
	 * Señales 'fuertes' y combinación de 'débiles'.
	 * Solo si esto devuelve true aplicaremos escapa_string() y bloqueo.
	 */
	private function isLikelySQLi(string $s): bool
	{
		
		// Normaliza solo espacios
		$sNorm = preg_replace('/\s+/u', ' ', $s);
		
		// ------- FUERTES (1 match basta) -------

		// Tautología clásica OR/AND 1=1 (muy tolerante en bordes)
		if (preg_match('/(?:^|\W)(?:or|and)\s*1\s*=\s*1(?:\W|$)/ui', $sNorm)) {	
			return true;
		}

		// Combo HEX + OR/AND en cualquier orden (permitimos hasta ~200 chars entre medias)
		if (preg_match('/(?:\b0x[0-9a-f]{2,}\b.{0,200}\b(?:or|and)\b|\b(?:or|and)\b.{0,200}\b0x[0-9a-f]{2,}\b)/ui', $sNorm)) {
			return true;
		}

		// Resto de 'fuertes' estándar
		$strong = [
			'/\bunion\s+all\s+select\b/ui',
			'/\bunion\s+select\b/ui',
			'/\bselect\b.+\bfrom\b/ui',
			'/\binformation_schema\b/ui',
			'/\bsleep\s*\(\s*\d+\s*\)/ui',
			'/\bbenchmark\s*\(\s*\d+\s*,/ui',
			'/\bload_file\s*\(/ui',
			'/\binto\s+outfile\b/ui',
			'/\bupdate\b.+\bset\b/ui',
			'/\binsert\b.+\binto\b/ui',
			'/\bdelete\b.+\bfrom\b/ui',
			'/\bwaitfor\s+delay\b/ui',
			'/\bxp_cmdshell\b/ui',
			'/\bcast\s*\(.+?\bas\s*char\b/ui',
			'/\bconvert\s*\(.+?\bchar\b/ui',
		];
		foreach ($strong as $rx) {
			if (preg_match($rx, $sNorm)) {
				return true;
			}
		}

		// ------- DÉBILES (requiere combinación) -------
		$weak = 0;
		if (preg_match('/(?<!\w)(?:or|and)\s+[^\s]{1,30}\s*=\s*[^\s]{1,30}/ui', $sNorm)) $weak++;
		if (preg_match('/[\'"]\s*(?:or|and)\s+/ui', $sNorm)) $weak++;
		if (preg_match('/\b(?:like|regexp)\b|!=|<>/ui', $sNorm)) $weak++;
		if (preg_match('/\b0x[0-9a-f]+\b|\b0b[01]+\b/ui', $sNorm)) $weak++;

		return $weak >= 2;
	}

    
    /**
     * Apply firewall filters
     *
     * @param   string             $ip    				The IP of the attacker
	 * @param   mixed              $string     			The string to check
	 * @param   string             $methods_options    	Method used in the attack (GET,POST...)
	 * @param   string             $a   				Type of attack
	 * @param   string             $request_uri   		The uri of the query
	 * @param   bool               $modified     		Tell us if any filtere has been applied
	 * @param   bool               $check    			Check if the string is base64
	 * @param   bool               $logs_attacks   		Tells if a log must be stored
	 * @param   string             $option   			Page option
     *
     * @return void|null
     *     
     */
    function apply_filters($ip, $string, $methods_options, $a, $request_uri, &$modified, $check, $logs_attacks, $option) 
	{
		$string_sanitized = '';
		$base64 = false;
		$pageoption = '';
		$extension_vulnerable = false;

		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
		} else {
			$user_agent = 'Not set';
		}

		if (isset($_SERVER['HTTP_REFERER'])) {
			$referer = $_SERVER['HTTP_REFERER'];
		} else {
			$referer = 'Not set';
		}
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();
		$is_admin = $app->isClient('administrator');

		$user = $app->getIdentity();
		$username = (!$user->guest) ? $user->username : '---';

		$pageoption = $option;

		// Heurística: acumuladores
		$signals  = 0;  // señales 'débiles'
		$hardHits = 0;  // golpes fuertes

		// Política de excepciones/flags
		$exclude_exceptions_if_vulnerable = $this->pro_plugin->getValue('exclude_exceptions_if_vulnerable', 1, 'pro_plugin');

		if ((!empty($option)) && ($exclude_exceptions_if_vulnerable)) {
			$extension_vulnerable = $this->check_extension_vulnerable($option);
		}

		/* Excepciones */
		$base64_exceptions                = $this->pro_plugin->getValue('base64_exceptions', '', 'pro_plugin');
		$strip_tags_exceptions            = $this->pro_plugin->getValue('strip_tags_exceptions', '', 'pro_plugin');
		$duplicate_backslashes_exceptions = $this->pro_plugin->getValue('duplicate_backslashes_exceptions', '', 'pro_plugin');
		$line_comments_exceptions         = $this->pro_plugin->getValue('line_comments_exceptions', '', 'pro_plugin');
		$sql_pattern_exceptions           = $this->pro_plugin->getValue('sql_pattern_exceptions', '', 'pro_plugin');
		$if_statement_exceptions          = $this->pro_plugin->getValue('if_statement_exceptions', '', 'pro_plugin');
		$using_integers_exceptions        = $this->pro_plugin->getValue('using_integers_exceptions', '', 'pro_plugin');
		$escape_strings_exceptions        = $this->pro_plugin->getValue('escape_strings_exceptions', '', 'pro_plugin');
		$lfi_exceptions                   = $this->pro_plugin->getValue('lfi_exceptions', '', 'pro_plugin');
		$check_header_referer             = $this->pro_plugin->getValue('check_header_referer', 1, 'pro_plugin');
		$strip_all_tags                   = $this->pro_plugin->getValue('strip_all_tags', 1, 'pro_plugin');
		$tags_to_filter                   = $this->pro_plugin->getValue('tags_to_filter', 'applet,body,bgsound,base,basefont,embed,frame,frameset,head,html,id,iframe,ilayer,layer,link,meta,name,object,script,style,title,xml,svg,input,a', 'pro_plugin');

		// Patterns 'fuertes'
		$sqlpatterns = array(
			"/delete(?=(\s|\+|%20|%u0020|%uff00))(.\b){1,3}(from)\b(?=(\s|\+|%20|%u0020|%uff00))/i",
			"/update(?=(\s|\+|%20|%u0020|%uff00)).+\b(set)\b(?=(\s|\+|%20|%u0020|%uff00))/i",
			"/drop(?=(\s|\+|%20|%u0020|%uff00)).+\b(database|user|table|index)\b(?=(\s|\+|%20|%u0020|%uff00))/i",
			"/insert((\s|\+|%20|%u0020|%uff00|\/|%2f))+(values|set|select)\b((\s|\+|%20|%u0020|%uff00))*/i",
			"/union(?=(\s|\+|%20|%u0020|%uff00|\/|%2f)).+(select)\b((\s|\+|%20|%u0020|%uff00))*/i",
			"/select(?=(\s|\+|%20|%u0020|%uff00))(.\b|.\B){1,3}(from|ascii|char|concat|case)\b(?=(\s|\+|%20|%u0020|%uff00))/i",
			"/benchmark\(.{1,200}?\)/i",
			"/(?:select|union|where|and|or)\b.{0,40}?\bmd5\(.{1,100}?\)/i",
			"/(?:select|union|where|and|or)\b.{0,40}?\bsha1\(.{1,100}?\)/i",
			"/ascii\(.{1,100}?\)/i",
			"/concat\(.{1,200}?\)/i",
			"/char\((?:\d+|0x[0-9a-f]+)[\d,\s0x a-f]*\)/i",
			"/substring\(.{1,200}?\)/i",
			"/where(\s|\+|%20|%u0020|%uff00)(or|and)(\s|\+|%20|%u0020|%uff00)(\w+)(=|<|>|<=|>=)(\w+)/i",
			"/\b(?:or|and)(?:\s|\+|%20|%u0020|%uff00)+sleep\s*\(/i",
			"/(?:\s|\+|%20|%u0020|%uff00)+pg_sleep\s*\(/i",
			"/waitfor(\s|\+|%20|%u0020|%uff00)(delay)/i",
			"/(\s|\+|%20|%u0020|%uff00)(or|and)(\s|\+|%20|%u0020|%uff00)(\()?((\'|%27)+(\d+)(\'|%27)+(=|%3d)(\'|%27)*\d+|((\')+(\D+)(\')+=(\')*\D+))/i",
			"/=dbms_pipe\.receive_message/i",
			"/\border\s+by\s+\d+(?:\s*,\s*\d+)*\s*(?:--|;|$|\b)/i",
			"/\bxp_cmdshell\b/i",
			"/\binto\s+(?:outfile|dumpfile)\b/i",
			"/\bload_file\s*\(/i",
			"/\binformation_schema\b/i",
			"/\bcast\s*\(.{1,200}?\bas\s+char\b/i",
			"/\bconvert\s*\(.{1,200}?\bchar\b/i",
			"/(?:^|\W)(?:or|and)\s+\d+\s*=\s*\d+(?:\W|$)/i",
			// DDL / DCL
			"/\balter\s+table\b/i",
			"/\bcreate\s+(?:table|user|database|function|procedure)\b/i",
			"/\btruncate\s+table\b/i",
			"/\bgrant\s+.*\bon\b/i",
			"/\brevoke\s+.*\bfrom\b/i",
			"/\brename\s+table\b/i",
			// Error-based SQLi (MySQL)
			"/\bextractvalue\s*\(/i",
			"/\bupdatexml\s*\(/i",
			"/\bexp\s*\(\s*~\s*\(/i",
			// Exfiltracion
			"/\bgroup_concat\s*\(/i",
			// MSSQL avanzado
			"/\bdeclare\s+@/i",
			"/\bexec\s*\(/i",
			"/\bexec(?:ute)?\s+(?:sp_|xp_)/i",
			"/\bopenrowset\s*\(/i",
			// HAVING
			"/\bhaving\s+\d+\s*=\s*\d+/i",
			"/\bgroup\s+by\s+.{1,100}\bhaving\b/i"
		);
		$ifStatements   = array("/\bif\s*\(.{1,100},.{1,100},.{1,100}\)/i");
		$lfiStatements  = array(
			"/\.\.\//",
			"/\.\.\\\\/",
			"/\?\?\?/",
			"/(?:php|expect|data|phar|zip|zlib|glob|ssh2|rar|ogg):\/\//i",
			"/php:\/\/(?:filter|input|stdin|memory|temp)/i",
			"/%00/"
		);

		/* Base64 check */
		if ($check) {
			if (!str_contains((string) $base64_exceptions, (string) $pageoption)) {
				$is_base64 = $this->is_base64($string);
				if ($is_base64) {
					$decoded = base64_decode(chunk_split($string));
					// Sanidad básica: exige alta proporción ASCII imprimible
					$printables = preg_match_all('/[[:print:]\s]/', (string) $decoded);
					if ($printables !== false && $printables >= (int)(strlen($decoded) * 0.8)) {
						$base64 = true;
						$string = $decoded;
					}
				}
			}
		}

		/* ========= XSS ========= */
		// si hay encoding sospechoso
		if ((!(str_contains($strip_tags_exceptions, $pageoption)) || $extension_vulnerable) && !(str_contains($strip_tags_exceptions, '*'))) {
			if (preg_match("/(\%[a-zA-Z0-9]{2}|0x{4,})/", (string) $string)) {
				$encoding_array = array("%3C","%253C","%3E","%253E","%2F","%252F","%2525");
				foreach($encoding_array as $encoded_word) {
					if (is_string($string) && substr_count(strtolower($string), strtolower($encoded_word))) {
						$this->grabar_log($logs_attacks, $ip, 'TAGS_STRIPPED', '[' .$methods_options .':' .$a .']', 'XSS_BASE64', $request_uri, $string, $username, $pageoption);
						$this->actualizar_lista_dinamica($ip);
						$modified = true;
						$hardHits++;
						$this->redirection(403, "", true);
					}
				}
			}

			if ($is_admin) {
				$strip_all_tags = 2;
				$tags_to_filter = 'applet,body,bgsound,base,basefont,embed,frame,frameset,head,html,id,ilayer,layer,link,meta,name,object,script,xml,svg';
			}

			if ((int) $strip_all_tags === 1) {
				$string_sanitized = strip_tags($string);
			} else {
				$string = html_entity_decode($string);
				$tags_to_filter_final = array();
				foreach (explode(",", $tags_to_filter) as $tag) {
					$tag = trim($tag);
					if ($tag === '') continue;
					$tags_to_filter_final[] = "<" . $tag;
					$tags_to_filter_final[] = $tag . "/>";
				}
				$string_sanitized = str_ireplace($tags_to_filter_final, "", $string);
			}

			// In admin context, exclude {source}...{/source} blocks from comparison.
			// Content inside these blocks is intentional raw HTML from trusted editors.
			$compareString    = $string;
			$compareSanitized = $string_sanitized;
			if ($is_admin) {
				$srcPattern       = '/\{source\}[\s\S]*?\{\/source\}/si';
				$compareString    = preg_replace($srcPattern, '', $compareString);
				$compareSanitized = preg_replace($srcPattern, '', $compareSanitized);
			}

			if (strcmp($compareSanitized, $compareString) !== 0) {
				if ($base64) {
					$this->grabar_log($logs_attacks, $ip, 'TAGS_STRIPPED', '[' .$methods_options .':' .$a .']', 'XSS_BASE64', $request_uri, $string, $username, $pageoption);
				} else {
					$angle_position = strpos($string, "<");
					if ($angle_position !== false) {
						$string = substr($string, (int)$angle_position);
					}
					$this->grabar_log($logs_attacks, $ip, 'TAGS_STRIPPED', '[' .$methods_options .':' .$a .']', 'XSS', $request_uri, $string, $username, $pageoption);
				}
				$string   = $string_sanitized;
				$modified = true;
				$hardHits++;
				$this->actualizar_lista_dinamica($ip);
				$this->redirection(403, "", true);
			} else {
				$xss_forbidden_words_array = array(
					"onload","onfocus","autofocus","javascript:","vbscript:","data:text/html","onmouseover","onerror","FSCommand",
					"onAbort","onActivate","onAfterPrint","onAfterUpdate","onBeforeActivate","onBeforeCopy","onBeforeCut",
					"onBeforeDeactivate","onBeforeEditFocus","onBeforePaste","onBeforePrint","onBeforeUnload","onBeforeUpdate",
					"onBegin","onBlur","onBounce","onCellChange","onChange","onClick","onContextMenu","onControlSelect","onCopy",
					"onCut","onDataAvailable","onDataSetChanged","onDataSetComplete","onDblClick","onDeactivate","onDrag","onDragEnd",
					"onDragLeave","onDragEnter","onDragOver","onDragDrop","onDragStart","onDrop","onErrorUpdate","onFilterChange",
					"onFinish","onFocusIn","onFocusOut","onHashChange","onHelp","onInput","onKeyDown","onKeyPress","onKeyUp",
					"onLayoutComplete","onLoseCapture","onMediaComplete","onMediaError","onMessage","onMouseDown","onMouseEnter",
					"onMouseLeave","onMouseMove","onMouseOut","onMouseOut","onMouseUp","onMouseWheel","onMove","onMoveEnd",
					"onMoveStart","onOffline","onOnline","onOutOfSync","onPaste","onPause","onPopState","onProgress",
					"onPropertyChange","onReadyStateChange","onRedo","onRepeat","onReset","onResize","onResizeEnd","onResizeStart",
					"onResume","onReverse","onRowsEnter","onRowExit","onRowDelete","onRowInserted","onScroll","onSeek","onSelect",
					"onSelectionChange","onSelectStart","onStart","onSyncRestored","onSubmit","onTimeError","onTimeError","onUndo",
					"onUnload","onURLFlip","seekSegmentTime"
				);
				foreach($xss_forbidden_words_array as $word) {
					if (is_string($string)) {
						if (str_contains($word, ':')) {
							// Protocol-based entries like "javascript:" — match as substring
							$matched = (stripos($string, $word) !== false);
						} else {
							// Event handler attributes — require attribute context (word followed by '=')
							$pattern = '/' . preg_quote($word, '/') . '\s*=/i';
							$matched = (bool) preg_match($pattern, $string);
						}
						if ($matched) {
							$this->grabar_log($logs_attacks, $ip, 'TAGS_STRIPPED', '[' .$methods_options .':' .$a .']', 'XSS', $request_uri, $string, $username, $pageoption);
							$modified = true;
							$hardHits++;
							$this->actualizar_lista_dinamica($ip);
							$this->redirection(403, "", true);
						}
					}
				}
			}
		}

		/* ========= SQLi / Heurística débil ========= */
		if (!$modified) {
			if ($is_admin) {
				$duplicate_backslashes_exceptions = "*";
				$line_comments_exceptions         = "*";
				$if_statement_exceptions          = "*";
				$using_integers_exceptions        = "*";
			}

			// Nuevo gating para reglas 'débiles'
			$inspectSql = $this->shouldInspectForSql($string);

			// Duplicate backslashes (detección, no modificar)
			if ($inspectSql && !(str_contains($duplicate_backslashes_exceptions, $pageoption)) && !(str_contains($duplicate_backslashes_exceptions, '*'))) {
				$dupBackslashes = (bool) preg_match('/\\\\{2,}(?=.*([\'"]|\\bselect\\b|\\binsert\\b|\\bupdate\\b|\\bwhere\\b))/i', $string);
				if ($dupBackslashes) {
					$signals++;
				}
			}

			// Line comments (contextual, sólo señal)
			if (
				$inspectSql
				&& $pageoption !== 'com_users'
				&& !str_contains($line_comments_exceptions, $pageoption)
				&& !str_contains($line_comments_exceptions, '*')
			) {
				$lineComments = [
					'~--(?=\s|$)~',                              // "-- " o fin de línea
					'~(?<!://)#(?=\s|$)~',                       // "#" que no sigue a "://"
					'~/\*.*?\*/~s',                              // /* ... */ (flag s)
				];

				$tmp = preg_replace($lineComments, "", $string, -1, $lcCount);
				if ($lcCount > 0) {
					$signals++;
				}
			}

			// SQL pattern 'fuerte' (modifica y bloquea)
			if (
				($extension_vulnerable || !str_contains($sql_pattern_exceptions, $pageoption))
				&& !str_contains($sql_pattern_exceptions, '*')
			) {
				try {
					$string_sanitized = preg_replace($sqlpatterns, "", $string);
				} catch (\Exception $e) {
					return;
				}
				if (strcmp($string_sanitized, $string) !== 0) {
					if ($base64) {
						$this->grabar_log($logs_attacks, $ip, 'SQL_PATTERN', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION_BASE64', $request_uri, $string, $username, $pageoption);
					} else {
						$this->grabar_log($logs_attacks, $ip, 'SQL_PATTERN', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION', $request_uri, $string, $username, $pageoption);
					}
					$string   = $string_sanitized;
					$modified = true;
					$hardHits++;
					$this->actualizar_lista_dinamica($ip);
					$this->redirection(403, "", true);
				}
			}

			// IF(...) (fuerte)
			if ((!(str_contains($if_statement_exceptions, $pageoption)) || $extension_vulnerable) && !(str_contains($if_statement_exceptions, '*')) && (!$modified)) {
				try {
					$string_sanitized = preg_replace($ifStatements, "", $string);
				} catch (\Exception $e) {
					return;
				}
				if (strcmp($string_sanitized, $string) !== 0) {
					if ($base64) {
						$this->grabar_log($logs_attacks, $ip, 'IF_STATEMENT', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION_BASE64', $request_uri, $string, $username, $pageoption);
					} else {
						$this->grabar_log($logs_attacks, $ip, 'IF_STATEMENT', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION', $request_uri, $string, $username, $pageoption);
					}
					$string   = $string_sanitized;
					$modified = true;
					$hardHits++;
				}
			}

			// Using integers (contextual, sólo señal)
			if ($inspectSql && !(str_contains($using_integers_exceptions, $pageoption)) && !(str_contains($using_integers_exceptions, '*')) && (!$modified)) {
				$usingIntegers = [
					// 0xHEX contextual (sin lookbehind)
					'~(?:^|[=,(])\s*0x[0-9a-f]{2,}\b(?=[^#&;]{0,40}\b(select|and|or|where|union)\b)~i',
					// @@var contextual
					'~@@[a-z_]+\b(?=[^#&;]{0,40}\b(select|and|or|where|union|version)\b)~i',
					// operador OR "||" evitando esquemas tipo "http://"
					// Opción A (lookbehind fijo, válido):
					'~(?<!://)\|\|~',
					// Opción B (sin lookbehind, un poco más permisiva):
					// '~(?:^|[^:])\|\|~'
					'/(?:^|\W)(?:or|and)\s*1\s*=\s*1(?:\W|$)/ui',
				];

				$tmp = preg_replace($usingIntegers, "", $string, -1, $uiCount);
				if ($uiCount > 0) {
					$signals++;
				}
			}			

			// HTML / JSON
			$is_html = $this->isHTML($string);
			$obj     = json_decode($string, false);
			$is_json = (json_last_error() === JSON_ERROR_NONE) ? 1 : 0;

			if (!$is_html && !$is_json && !$modified) {
			if (!(str_contains($escape_strings_exceptions, $pageoption)) && !(str_contains($escape_strings_exceptions, '*'))) {

				// Nuevo gating robusto
				if ($this->isLikelySQLi($string)) {
					try {
						$string_sanitized = $this->escapa_string($string);
					} catch (\Exception $e) {
						return;
					}

					// Solo si realmente cambia tras escapar
					if (strcmp($string_sanitized, $string) !== 0) {
						$this->grabar_log($logs_attacks, $ip, 'BACKSLASHES_ADDED', '['.$methods_options.':'.$a.']', $base64 ? 'SQL_INJECTION_BASE64' : 'SQL_INJECTION', $request_uri, $string, $username, $pageoption);
						if ($string_sanitized !== '') {
							$string = $string_sanitized;
						}
						$modified = true;
						$hardHits++; // lo consideramos hit 'fuerte' por decisión final
					}
				}
			}
		}

			// Decisión por umbral de señales débiles si aún no hubo golpe fuerte ni modificación
			if (!$modified && $hardHits === 0 && $signals >= 2) {
				$this->grabar_log($logs_attacks, $ip, 'HEURISTIC_SQL', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION', $request_uri, $string, $username, $pageoption);
				$this->actualizar_lista_dinamica($ip);
				$modified = true;
				$this->redirection(403, "", true);
			}
		}

		/* ========= LFI ========= */
		if ((!(str_contains($lfi_exceptions, $pageoption)) || $extension_vulnerable) && !(str_contains($lfi_exceptions, '*'))) {
			if (!$modified) {
				$string_sanitized = preg_replace($lfiStatements, '', $string);
				if (strcmp($string_sanitized, $string) !== 0) {
					if ($base64) {
						$this->grabar_log($logs_attacks, $ip, 'LFI', '[' .$methods_options .':' .$a .']', 'LFI_BASE64', $request_uri, $string, $username, $pageoption);
					} else {
						$this->grabar_log($logs_attacks, $ip, 'LFI', '[' .$methods_options .':' .$a .']', 'LFI', $request_uri, $string, $username, $pageoption);
					}
					$string   = $string_sanitized;
					$modified = true;
					$hardHits++;
				}
			}
		}

		/* ========= Command Injection ========= */
		if (!$modified && !$is_admin) {
			$cmdPatterns = [
				'/(?:^|[;&|`])\s*(?:cat|ls|dir|whoami|id|uname|wget|curl|nc|ncat|bash|sh|cmd|powershell|ping|nslookup|traceroute|netstat|ifconfig|ipconfig)\b/i',
				'/\$\([^)]+\)/',                    // $(command)
				'/`\s*(?:cat|ls|dir|whoami|id|uname|wget|curl|nc|ncat|bash|sh|cmd|powershell|ping|nslookup|traceroute|netstat|ifconfig|ipconfig)\b[^`]*`/i', // `command`
				'/\|\s*(?:cat|ls|dir|whoami|id|uname|wget|curl|nc|bash|sh|cmd|powershell)\b/i',
				'/;\s*(?:cat|ls|dir|whoami|id|uname|wget|curl|nc|bash|sh|cmd|powershell)\b/i',
				'/&&\s*(?:cat|ls|dir|whoami|id|uname|wget|curl|nc|bash|sh|cmd|powershell)\b/i',
				'/\|\|\s*(?:cat|ls|dir|whoami|id|uname|wget|curl|nc|bash|sh|cmd|powershell)\b/i',
			];
			foreach ($cmdPatterns as $cmdRx) {
				if (preg_match($cmdRx, $string)) {
					$this->grabar_log($logs_attacks, $ip, 'CMD_INJECTION', '[' .$methods_options .':' .$a .']', 'CMD_INJECTION', $request_uri, $string, $username, $pageoption);
					$modified = true;
					$hardHits++;
					$this->actualizar_lista_dinamica($ip);
					$this->redirection(403, "", true);
				}
			}
		}

		/* ========= CRLF Injection ========= */
		if (!$modified) {
			if (preg_match('/%0[da]|\\r|\\n/i', $string)) {
				if (preg_match('/%0[da].{0,20}(?:Set-Cookie|Location|Content-Type|HTTP\/)/i', $string)) {
					$this->grabar_log($logs_attacks, $ip, 'CRLF_INJECTION', '[' .$methods_options .':' .$a .']', 'CRLF_INJECTION', $request_uri, $string, $username, $pageoption);
					$modified = true;
					$hardHits++;
					$this->actualizar_lista_dinamica($ip);
					$this->redirection(403, "", true);
				}
			}
		}

		/* ========= Cabeceras ========= */
		if ((!$modified) && ($check_header_referer)) {
			$modified = $this->check_header_and_user_agent(
				$logs_attacks, $user, $user_agent, $referer, $ip, $methods_options, $a, $request_uri,
				$sqlpatterns, $ifStatements, // reusa patrones fuertes
				// usingIntegers/lfiStatements para cabeceras: aplica pero recuerda que check_header_and_user_agent ya hace sus propias comprobaciones
				array('/(?<=\=|\(|,)\s*0x[0-9a-f]{2,}\b/i','/@@[a-z_]+\b/i','/\|\|/'),
				$lfiStatements,
				$username, $pageoption
			);
		}
	}

    
	/**
     * Función para 'sanitizar' un string. Devolvemos el string "sanitizado" y modificamos la variable "modified" si se ha modificado el string
     *
     * @param   string             							$ip    				The IP of the attacker
	 * @param 	string|array<string>|array<array<mixed>>	$string     		The string to check
	 * @param   string           							$methods_options  	Method used in the attack (GET,POST...)
	 * @param   string             							$a   				Type of attack
	 * @param   string             							$request_uri   		The uri of the query
	 * @param   bool               							$modified     		Tells us if any filtere has been applied
	 * @param   bool	          							$check    			Check if the string is base64
	 * @param   bool	          							$logs_attacks   	Tells if a log must be stored
	 * @param   string             							$option   			Page option
     *
     * @return string
     *     
     */
    function cleanQuery($ip,$string,$methods_options,$a,$request_uri,&$modified,$check,$logs_attacks,$option)
    {
        /** @var \Joomla\CMS\Application\CMSApplication $app */       
        $app = Factory::getApplication();
        $is_admin = $app->isClient('administrator');
                
        $pageoption = $option;
		
		if (is_array($string)) {                
            // Get all values of the array
			$strings_in_array = array();			
			foreach ($string as $item) {				
				if (is_array($item)) {
					$strings_in_array = array_merge($strings_in_array, array_values($item));
				} else {
					$strings_in_array[] = $item;
				}
				
			}
            foreach ($strings_in_array as $string) {                       
                if ((!(is_array($string))) && (mb_strlen($string)>0) && ($pageoption != '')) {                    
                    $this->apply_filters($ip, $string, $methods_options, $a, $request_uri, $modified, $check, $logs_attacks, $option);                        
                }
            }
        } else
        {
            if ((mb_strlen($string)>0) && ($pageoption != '')) {
                $this->apply_filters($ip, $string, $methods_options, $a, $request_uri, $modified, $check, $logs_attacks, $option);
            }                
        }
        
        return $string;
    }
    
   /**
     * Función que chequea el 'Header' y el 'user-agent' en busca de ataques
     *
     * @param   bool        					      $logs_attacks   		Tells if a log must be stored
	 * @param   \Joomla\CMS\User\User|null            $user   				User
	 * @param   string            					  $user_agent    		User-agent of the query
	 * @param   string            					  $referer     			Referer of the query
	 * @param   string           					  $ip    				The IP of the attacker
	 * @param   string            					  $methods_options    	Method used in the attack (GET,POST...)
	 * @param   string            					  $a   					Type of attack
	 * @param   string             					  $request_uri   		The uri of the query
	 * @param   array<mixed>     					  $sqlpatterns   		Patterns of the sql injection filter
	 * @param   array<mixed>      					  $ifStatements   		Patterns of the if statement filter
	 * @param   array<mixed>       					  $usingIntegers  	 	Patterns of the using integers filter
	 * @param   array<mixed>       					  $lfiStatements   		Patterns of the LFI filter
	 * @param   string            					  $username   			The username
	 * @param   string             	  				  $pageoption   		Page option
     *
     * @return bool
     *     
     */
    function check_header_and_user_agent(
		$logs_attacks,
		$user,
		$user_agent,
		$referer,
		$ip,
		$methods_options,
		$a,
		$request_uri,
		$sqlpatterns,     // patrones "fuertes"
		$ifStatements,    // patrón fuerte IF(...)
		$usingIntegers,   // no usado directamente
		$lfiStatements,   // LFI fuerte
		$username,
		$pageoption
	) {
		$modified = false;
		$signals  = 0;
		$hardHits = 0;
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app      = Factory::getApplication();
		$is_admin = $app->isClient('administrator');

		$enableSqlWeakSignals = !$is_admin;

		// Comentarios en línea (señales débiles)
		$lineComments = [
			'~--(?=\s|$)~',
			'~(?<!://)#(?=\s|$)~',
			'~/\*.*?\*/~s',
		];

		// Patrones 'using integers' contextuales (señales débiles)
		$usingIntegersCtx = [
			'~(?:^|[=,(])\s*0x[0-9a-f]{2,}\b(?=[^#&;]{0,40}\b(select|and|or|where|union)\b)~i',
			'~@@[a-z_]+\b(?=[^#&;]{0,40}\b(select|and|or|where|union|version)\b)~i',
			'~(?<!://)\|\|~',
		];

		// Helper seguro para preg_replace con recuento
		$safePregReplaceCount = static function ($patterns, string $subject, &$countOut) {
			$countOut = 0;
			if (empty($patterns)) {
				return $subject;
			}
			// Acepta string o array; si es array, filtra vacíos
			if (is_array($patterns)) {
				$patterns = array_values(array_filter($patterns, static function ($p) {
					return is_string($p) && $p !== '';
				}));
				if (!$patterns) {
					return $subject;
				}
			} elseif (!is_string($patterns)) {
				return $subject;
			}

			try {
				return preg_replace($patterns, '', $subject, -1, $countOut);
			} catch (\Throwable $e) {
				// Si algún patrón es inválido, no contamos nada
				$countOut = 0;
				return $subject;
			}
		};

		// Closure que escanea una cabecera concreta
		$scanHeader = function (string $label, $rawValue) use (
			&$signals, &$hardHits, $logs_attacks, $ip, $methods_options, $a, $request_uri,
			$sqlpatterns, $ifStatements, $lfiStatements, $usingIntegersCtx, $lineComments,
			$username, $pageoption, $enableSqlWeakSignals, $safePregReplaceCount
		) {
			// Normaliza a string
			$value = (string)($rawValue ?? '');
			if ($value === '' || $value === 'Not set') {
				return false;
			}

			// 1) XSS fuerte (strip tags)
			$sanitized = strip_tags($value);
			if (strcmp($sanitized, $value) !== 0) {
				$this->grabar_log($logs_attacks, $ip, 'TAGS_STRIPPED', '[' . $methods_options . ':' . $a . ']', $label . '_MODIFICATION', $request_uri, $value, $username, $pageoption);
				$hardHits++;
				$this->actualizar_lista_dinamica($ip);
				$this->redirection(403, "", true);
				return true;
			}

			// 2) SQL fuerte
			$safePregReplaceCount($sqlpatterns, $value, $strongSqlCount);
			if (!empty($strongSqlCount)) {
				$this->grabar_log($logs_attacks, $ip, 'SQL_PATTERN', '[' . $methods_options . ':' . $a . ']', $label . '_MODIFICATION', $request_uri, $value, $username, $pageoption);
				$hardHits++;
				$this->actualizar_lista_dinamica($ip);
				$this->redirection(403, "", true);
				return true;
			}

			// 3) IF(...) fuerte
			$safePregReplaceCount($ifStatements, $value, $ifCount);
			if (!empty($ifCount)) {
				$this->grabar_log($logs_attacks, $ip, 'IF_STATEMENT', '[' . $methods_options . ':' . $a . ']', $label . '_MODIFICATION', $request_uri, $value, $username, $pageoption);
				$hardHits++;
				$this->actualizar_lista_dinamica($ip);
				$this->redirection(403, "", true);
				return true;
			}

			// 4) LFI fuerte
			$safePregReplaceCount($lfiStatements, $value, $lfiCount);
			if (!empty($lfiCount)) {
				$this->grabar_log($logs_attacks, $ip, 'LFI', '[' . $methods_options . ':' . $a . ']', $label . '_MODIFICATION', $request_uri, $value, $username, $pageoption);
				$hardHits++;
				$this->actualizar_lista_dinamica($ip);
				$this->redirection(403, "", true);
				return true;
			}

			// 5) SQL 'débil': sólo si pasa el gating
			if ($enableSqlWeakSignals && $this->shouldInspectForSql($value)) {
				$safePregReplaceCount($lineComments, $value, $lcCount);
				if (!empty($lcCount)) {
					$signals++;
				}
				$safePregReplaceCount($usingIntegersCtx, $value, $uiCount);
				if (!empty($uiCount)) {
					$signals++;
				}
			}

			return false;
		};

		// Sólo para invitados (tu l�gica)
		if ($user && $user->guest) {
			if (isset($_SERVER['HTTP_USER_AGENT'])) {
				if ($scanHeader('USER_AGENT', $user_agent)) {
					return true;
				}
			}
			if (isset($_SERVER['HTTP_REFERER'])) {
				if ($scanHeader('REFERER', $referer)) {
					return true;
				}
			}

			if ($hardHits === 0 && $signals >= 2) {
				$this->grabar_log($logs_attacks, $ip, 'HEURISTIC_SQL', '[' . $methods_options . ':' . $a . ']', 'HEADER_HEURISTIC', $request_uri, '[UA/REF]', $username, $pageoption);
				$this->actualizar_lista_dinamica($ip);
				$this->redirection(403, "", true);
				return true;
			}
		}

		return $modified;
	}

    
    /**
     * Función para contar el número de palabras "prohibidas" de un string
     *
     * @param   string             $request_uri   		The uri of the query
	 * @param   string|array<mixed>		       $string   			The string
	 * @param   string             $a   				Type of attack
	 * @param   string		       $found   			Words found
	 * @param   string             $option   			Page option
     *
     * @return int
     *     
     */
    function second_level($request_uri,$string,$a,&$found,$option)
    {
        $occurrences=0;
        $string_sanitized=$string;
		/** @var \Joomla\CMS\Application\CMSApplication $application */
        $application = Factory::getApplication();
        $user = $application->getIdentity();
        $dbprefix = $application->getCfg('dbprefix');
        $pageoption='';
        $existe_componente = false;
        $extension_vulnerable = false;
        
        $is_admin = $application->isClient('administrator');
        
        // Consultamos si hemos de aplicar las reglas al usuario en Función de su pertenencia a grupos.
        $apply_rules_to_user = $this->check_rules($user);
        
        $pageoption = $option;
        
        // Chequeamos si hemos de excluir los componentes vulnerables de las excepciones
        $exclude_exceptions_if_vulnerable = $this->pro_plugin->getValue('exclude_exceptions_if_vulnerable', 1, 'pro_plugin');
        
        // Si hemos podido extraer el componente implicado en la peticion, vemos si la versin instalada es vulnerable
        if ((!empty($option)) && ($exclude_exceptions_if_vulnerable)) {
            $extension_vulnerable = $this->check_extension_vulnerable($option);                                        
        }
        
        /* Excepciones */
        $second_level_exceptions = $this->pro_plugin->getValue('second_level_exceptions', '', 'pro_plugin');
        
        /* Lista de palabras sospechosas */
        $second_level_words = $this->pro_plugin->getValue('second_level_words', '', 'pro_plugin');
        
        // Desde la versi�n 3.1.6 la lista de palabras sospechosas se codifica en base64 para evitar problemas con una regla de mod_security.
        if (substr_count($second_level_words, ",") < 2) {    
            $second_level_words = base64_decode($second_level_words);
        }
                    
        if ($apply_rules_to_user) { 
            if ((!($is_admin)) && ($pageoption != '') && !(is_array($string))) {  // No estamos en la parte administrativa
                if (!(strstr($second_level_exceptions, $pageoption)) || $extension_vulnerable) {
                    /* SQL Injection Prevention */
                    // Prevents duplicate backslashes
                    $string_sanitized = stripslashes($string);
                   
                    // Line Comments
                   	$lineComments = array("/--/","/[^\=\s]#/","/\/\*/","/\*\//","/(?=(%2f|\/)).+\*\*/i");
                    $string_sanitized = preg_replace($lineComments, "", $string_sanitized);
                
                    $string_sanitized = $this->escapa_string($string);
                                                            
                    $suspect_words = explode(',', $second_level_words);
                    foreach ($suspect_words as $word)
                    {
                        if ((!empty($word)) && (!empty($string_sanitized))) {
                            if (substr_count(strtolower($string_sanitized), strtolower($word))) {
                                if (empty($found)) {
                                    $found .= $word;
                                } else
                                {
                                    $found .= ', ' .$word;
                                }                                
                                $occurrences++;
                            }
                        }
                    }
                }
            }
        }
        return $occurrences;        
    }
        
    /**
     * Función para chequear si una ip pertenece a una lista din�mica almacenada en una BBDD
     *
     * @param   string             $ip   				The IP
	 * @param   int		      	   $blacklist_counter   The number of occurences
     *
     * @return bool
     *     
     */
    function chequear_ip_en_lista_dinamica($ip,$blacklist_counter)
    {
        // Creamos el nuevo objeto query
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
    
        // Chequeamos si la IP tiene un formato v�lido
        $ip_valid = filter_var($ip, FILTER_VALIDATE_IP);
        
        // Sanitizamos las entradas
        $ip = $db->escape($ip);
                        
        // Validamos si el valor devuelto es una direcci�n v�lida
        if ((!empty($ip)) && ($ip_valid)) {
            // Construimos la consulta
            try 
            {
                $query = "SELECT COUNT(*) from #__securitycheckpro_dynamic_blacklist WHERE (ip = '{$ip}' AND counter >= {$blacklist_counter})" ; 								
                $db->setQuery($query);
                $result = $db->loadResult();                
            } catch (\Exception $e)
            {
                return false;
            }            
                    
            if ($result) {
                return true;
            } else
            {
                return false;
            }
        }        
		return false;        
    }
    
    /**
     * Si el tiempo transcurrido desde que se grab� la entrada supera el establecido en el plugin, eliminamos esa entrada de la base de datos
     *
     * @param   int		      	   $counter_time   The time set in the counter
     *
     * @return void
     *     
     */
    function pasar_a_historico($counter_time)
    {
    
        // Creamos el nuevo objeto query
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        
        // Sanitizamos la entrada
        (int) $counter_time = $db->escape($counter_time);
        
        if (is_numeric($counter_time)) {			
			if (strstr($this->dbtype,"mysql")) {
				$query = "DELETE FROM #__securitycheckpro_dynamic_blacklist WHERE (DATE_ADD(timeattempt, INTERVAL {$counter_time} SECOND)) < NOW();";
			} else if (strstr($this->dbtype,"pgsql")) {
				$query = "DELETE FROM #__securitycheckpro_dynamic_blacklist WHERE timeattempt < NOW() - INTERVAL '{$counter_time} second';";
			}			
			$db->setQuery($query);
            $db->execute();   
        }       
        
    }
   
   /**
     * Función que a�ade una IP a la lista negra din�mica
     *
     * @param   string	   $attack_ip   The IP to add
     *
     * @return void|bool
     *     
     */
    function actualizar_lista_dinamica($attack_ip)
    {
		SecuritycheckProHelper::actualizarListaDinamica((string) $attack_ip);
    }
    
    /**
     * Función que chequea la sesi�n para usar la funcionalidad otp de Securitycheck Pro
     *
     * @param   string	   $session_username   The username of the session
     *
     * @return bool
     *     
     */
    private function check_environment($session_username)
    {
        $is_ok = true;
        /** @var \Joomla\CMS\Application\CMSApplication $app */
        $app = Factory::getApplication();
        
        //Si no estamos en el backend salimos
        if (!$app->isClient('administrator')) {            
            $is_ok = false;
        }
        
        // El usuario logado debe coincidir con el almacenado en la sesi�n o ser el invitado (antes de logarse en el backend)
        $currentUser = $app->getIdentity();
                
        if (!$currentUser->guest && (strtoupper($currentUser->username) != strtoupper($session_username))) {
            $is_ok = false;
        }
        
        return $is_ok;
    }
    
   /**
     * Función que obtiene el id de un usuario a trav�s de la variable pasada como argumento. El usuario no puede estar bloqueado.
     *
     * @param   string	   $username   The username
     *
     * @return int|null
     *     
     */
    private function get_user_id($username)
    {
        if (empty($username)) {
            return null;
        }
        
        try
        {
            // Get a database object
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('id')
                ->from('#__users')
                ->where('username=' . $db->quote($username))
                ->where($db->qn('block') . ' = ' . $db->q(0));

            $db->setQuery($query);
            $userID = $db->setQuery($query)->loadResult();
        } catch (\Exception $e)
        {
            return null;
        }
        
        return $userID;
        
    }
	
	/**
	 * Check if the code passed by the user matches with any of the mfa methods.
	 *
	 * @param   int			$userId  The user id
	 * @param 	string		$code    verification code or yubikey code passed in the url
	 *
	 * @return  boolean  True is the verification code is valid
	 */
	private function check_mfa_status(int $userId, string $code): bool
	{
		$code = trim($code);
		if ($code === '') {
			return false;
		}

		// Asegura que los plugins MFA están cargados (totp, yubikey, webauthn, �)
		PluginHelper::importPlugin('multifactorauth');

		$app        = Factory::getApplication();
		$dispatcher = $app->getDispatcher();
		$user       = Factory::getUser($userId);

		// Registros MFA del usuario (cada uno es un MfaTable)
		/** @var list<object> $userMethods */
		// @phpstan-ignore-next-line
		$userMethods = Mfa::getUserMfaRecords($userId);

		if ($userMethods === []) {
			return false;
		}

		foreach ($userMethods as $record) {
			// Construye el evento tipado que esperan los plugins
			$event = new MfaValidate($record, $user, $code);

			// Despachamos y recuperamos el array de resultados acumulado por los plugins
			$results = (array) $dispatcher
				->dispatch('onUserMultifactorValidate', $event)
				->getArgument('result', []);

			if (\in_array(true, $results, true)) {
				return true;
			}
		}

		return false;
	}
    
   /**
     * Función que chequea si la url usa la Función otp de Securitycheck Pro para desbloquear el acceso
     *
     *
     * @return bool
     *     
     */
    private function check_otp_params()
    {
        $is_otp = false;
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app       = Factory::getApplication();
		
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $otp_enabled = $params->get('otp', 1);
        
        // Si la funcionalidad OTP está habilitada realizamos las secuencia
        if ($otp_enabled) {        
            $session = $app->getSession();               
            $session_username = $session->get('otp_username', '');
			                    
            if (empty($session_username)) {
                    
                $jinput = $app->getInput();
                $url_username = trim($jinput->get('username', '', 'username'));
                $url_otp = $jinput->get('otp', '', 'string');
                        
                $userID = self::get_user_id($url_username);
				                   
                if (!empty($userID)) {                
                    /** @var UserFactoryInterface $userFactory */
					$userFactory = Factory::getContainer()->get(UserFactoryInterface::class);

					$user = $userFactory->loadUserById((int) $userID);
                    if ($user->authorise('core.admin')) {
						$check = self::check_mfa_status($userID, $url_otp); 
						
                        if ($check) {                    
                            $session->set('otp_username', $url_username);
							$is_otp = true;
                        }
                    }
                }
            } else 
            {
                $is_ok = self::check_environment($session_username);                
                if ($is_ok) {
                    $is_otp = true;
                }
            }
        }
        
        return $is_otp;
    
    }
            
    /**
     * Acciones a realizar si la IP está en la lista negra din�mica
     *
     * @param   string|int             $dynamic_blacklist_time   	 The time to dinamically block an IP
	 * @param   string	               $attack_ip				 	 The IP
	 * @param   string|int	           $dynamic_blacklist_counter	 The number of attacks to dinamically block an IP
	 * @param   string	               $logs_attacks				 Tell us if a log must be stored
	 * @param   string	               $request_uri				  	 The url
	 * @param   string	               $not_applicable				 Tell us if this is applicable or not
     *
     * @return  void
     *     
     */
    function acciones_lista_negra_dinamica($dynamic_blacklist_time,$attack_ip,$dynamic_blacklist_counter,$logs_attacks,$request_uri,$not_applicable)
    {
        /* Cargamos el lenguaje del sitio */
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
        $lang = $app->getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
        
        /* Actualizamos la lista din�mica */
        $this->pasar_a_historico($dynamic_blacklist_time);
        
        $aparece_lista_negra_dinamica = $this->chequear_ip_en_lista_dinamica($attack_ip, $dynamic_blacklist_counter);
        $add_access_attempts_logs = $this->pro_plugin->getValue('add_access_attempts_logs', 0, 'pro_plugin');
                        
        if ($aparece_lista_negra_dinamica) {
            
            // Url con otp de Securitycheck Pro?
            $is_otp = self::check_otp_params();
                
            if (!$is_otp) {            
                // Grabamos una entrada en el log con el intento de acceso de la ip prohibida
                if ($add_access_attempts_logs) {
                    $access_attempt = $lang->_('COM_SECURITYCHECKPRO_ACCESS_ATTEMPT');
                    $this->grabar_log($logs_attacks, $attack_ip, 'IP_BLOCKED_DINAMIC', $access_attempt, 'IP_BLOCKED_DINAMIC', $request_uri, $not_applicable, '---', '---');
                }
                                
                // Redirecci�n a nuestra p�gina de "Prohibido" 
                $error_403 = $lang->_('COM_SECURITYCHECKPRO_403_ERROR');
                $this->redirection(403, $error_403, true, $attack_ip, $dynamic_blacklist_time);
            }
        }
    }
    
   /**
     * Acciones a realizar si la IP está en la lista negra
     *
     * @param   string	               $logs_attacks				 Tell us if a log must be stored
	 * @param   string	               $attack_ip				 	 The IP
	 * @param   string	               $access_attempt				 The string
	 * @param   string	               $request_uri				  	 The url
	 * @param   string	               $not_applicable				 Tell us if this is applicable or not
     *
     * @return  void
     *     
     */
    function acciones_lista_negra($logs_attacks,$attack_ip,$access_attempt,$request_uri,$not_applicable)
    {
        /* Cargamos el lenguaje del sitio */
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
        $lang = $app->getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
        $not_applicable = $lang->_('COM_SECURITYCHECKPRO_NOT_APPLICABLE');
        
        $add_access_attempts_logs = $this->pro_plugin->getValue('add_access_attempts_logs', 0, 'pro_plugin');
        
        // Url con otp de Securitycheck Pro?
        $is_otp = self::check_otp_params();
                
        if (!$is_otp) {                
            // Grabamos una entrada en el log con el intento de acceso de la ip prohibida si está seleccionada la opci�n para ello
            if ($add_access_attempts_logs) {
                $access_attempt = $lang->_('COM_SECURITYCHECKPRO_ACCESS_ATTEMPT');
                $this->grabar_log($logs_attacks, $attack_ip, 'IP_BLOCKED', $access_attempt, 'IP_BLOCKED', $request_uri, $not_applicable, '---', '---');
            }
                
            // Redirecci�n a nuestra p�gina de "Prohibido"
            $error_403 = $lang->_('COM_SECURITYCHECKPRO_403_ERROR');
            $this->redirection(403, $error_403, true, $attack_ip);    
        }
    }
    
	/**
     * Opciones de redirecci�n: p�gina de error (de Joomla o personalizada) o rechazar la conexi�n. El parámetro blacklist indica si venimos de una lista negra; en ese caso, no podemos hacer la 
	 * redirecci�n ya que entrar�amos en un bucle infinito. Lo que hacemos es mostrar el c�digo que haya establecido el administrador
     *
     * @param   int	     	           $code				 The code to show
	 * @param   string	               $message				 The message to show
	 * @param   bool	               $blacklist			 Tell us if we are
	 * @param   string|null            $ip				  	 The IP
	 * @param   int|null  	           $time				 The time is minutes
     *
     * @return  void
     *     
     */
    function redirection($code,$message,$blacklist=false,$ip=null,$time=null)
    {
		SecuritycheckProHelper::redirection((int) $code, (string) $message, (bool) $blacklist, $ip !== null ? (string) $ip : null, $time !== null ? (int) $time : null);
    }
    
   /**
     * Acciones a realizar si la ip está no está en ninguna de las listas
     *
     * @param   string		           $methods				 The methods to inspect
	 * @param   string	               $attack_ip			 The IP
	 * @param   string	               $methods_options		 The options
	 * @param   string		           $request_uri			 The IP
	 * @param   bool	  	           $check_base_64		 Check is the string is base64
	 * @param   bool	  	           $logs_attacks		 Tell us if it has to write a log entry
	 * @param   bool	  	           $secondlevel			 Apply the second level filter
     *
     * @return  void
     *
     */
    function acciones_no_listas($methods,$attack_ip,$methods_options,$request_uri,$check_base_64,$logs_attacks,$secondlevel)
    {
        /* Cargamos el lenguaje del sitio */
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
        $lang = $app->getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
		
		// Si la variable "pro_plugin" está vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}

		// Registramos en el log de Joomla las peticiones con métodos HTTP no inspeccionados por el firewall
		$request_method = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');
		if (in_array($request_method, ['PUT', 'PATCH', 'DELETE', 'HEAD', 'TRACE'], true)) {
			Log::add(
				'SecurityCheck Pro: método HTTP ' . $request_method . ' recibido desde ' . $attack_ip . ' — URI: ' . $request_uri,
				Log::WARNING,
				'securitycheckpro'
			);
		}
        
        // Obtenemos los valores del plugin para la protección de sesión del usuario
        $session_hijack_protection = $this->pro_plugin->getValue('session_hijack_protection', 1, 'pro_plugin');
        $session_protection_active = $this->pro_plugin->getValue('session_protection_active', 1, 'pro_plugin');
                
        /* Protección de la sesión del usuario y contra secuestros de sesión */
        if ($session_protection_active || $session_hijack_protection) {
            $this->sesiones_activas($logs_attacks, $attack_ip, $request_uri, $session_protection_active, $session_hijack_protection);
        }
        // Consultamos si hemos de aplicar las reglas al usuario en Función de su pertenencia a grupos.
        $user = $app->getIdentity();
        $apply_rules_to_user = $this->check_rules($user);
                
        if ( ($apply_rules_to_user) && (!empty($methods)) ) {            
            foreach(explode(',', $methods) as $methods_options)
            {
                switch ($methods_options)
                {
                case 'GET':
                    $method = $_GET;
                    break;
                case 'POST':
                    $method = $_POST;
                    break;
                case 'REQUEST':
                    $method = $_REQUEST;
                    break; 
				default:
					return;
                }
                
                foreach($method as $a => &$req)
                {
                
                    if(is_numeric($req)) { continue;
                    }
                                        
                    $modified = false;
                    
					$entradas = $app->getInput();
					$option = $entradas->get('option','com_notfound');

					// Config-save POST for this component legitimately contains SQL-like patterns
					// (e.g. second_level_words). CSRF token protects the form; GET requests
					// (e.g. a malicious link) are still inspected.
					if ($app->isClient('administrator')
						&& $option === 'com_securitycheckpro'
						&& strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST') {
						continue;
					}

                    $req = $this->cleanQuery($attack_ip, $req, $methods_options, $a, $request_uri, $modified, $check_base_64, $logs_attacks, $option);
					                    
                    if ($modified) {
                        $this->actualizar_lista_dinamica($attack_ip);
                        $error_400 = $lang->_('COM_SECURITYCHECKPRO_400_ERROR');
                        $this->redirection(400, $error_400);
                    } else if ($secondlevel) {  // Second level protection
                        // N� m�ximo de palabras sospechosas
                        $second_level_limit_words = intval($this->pro_plugin->getValue('second_level_limit_words', 3, 'pro_plugin'));
                        $words_found='';
                        $num_keywords = $this->second_level($request_uri, $req, $a, $words_found, $option);
                        if ($num_keywords >= $second_level_limit_words) {
                              /* Actualizamos la lista negra din�mica */
                              $this->actualizar_lista_dinamica($attack_ip);                        
                              $this->grabar_log($logs_attacks, $attack_ip, 'FORBIDDEN_WORDS', $words_found, 'SECOND_LEVEL', $request_uri, $req, $user->username, $option);
                                
                              $error_401 = $lang->_('COM_SECURITYCHECKPRO_401_ERROR');
                              $this->redirection(401, $error_401);
                        }
                    }
                }
            }
        }
    }
    
   /**
     * Función para mandar correos electr�nicos
     *
     * @param   string	               $alerta			 	 The message to send in the body
     *
     * @return  void
     *     
     */
    function mandar_correo($alerta)
    {
		SecuritycheckProHelper::mandarCorreo((string) $alerta);
    }
    
    /**
     * Chequea la direcci�n ip y el user-agent de una sesi�n activa para comprobar que no ha habido ninguna modificaci�n
     *
     * @param   int	               $user_id			 	 The user id
     *
     * @return  bool
     *     
     */
    protected function chequeo_suplantacion($user_id)
    {
        // Obtenemos los valores necesarios
        $changed = false;
		
        $ip = $this->ipmodel->getClientIpForSecuritycheckPro();
		
		$session_hijack_protection_what_to_check = $this->pro_plugin->getValue('session_hijack_protection_what_to_check', 1, 'pro_plugin');
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        
        // Obtenemos el id del usuario logado
        $query = "SELECT * FROM #__securitycheckpro_sessions WHERE (userid = '{$user_id}')";
        $db->setQuery($query);
        $user_data = $db->loadRow();        
                                
        if (!is_null($user_data)) {
			if ( (int) $session_hijack_protection_what_to_check === 1 )
			{
				if ((strcmp($user_data[3], $ip) !== 0) || (strcmp($user_data[4], $user_agent) !== 0)) {
					 // Han cambiado la direcci�n IP o el User-agent
					$changed = true;
				}
			} else if ( (int) $session_hijack_protection_what_to_check === 2 )
			{
				if ((strcmp($user_data[3], $ip) !== 0) && (strcmp($user_data[4], $user_agent) !== 0)) {
					 // Han cambiado tanto la direcci�n IP como el User-agent                
					$changed = true;
				}
			}	
            
        } else { //No hay datos (esto, en teor�a, no deber�a ser posible); devolvemos el valor 'false' para evitar falsos positivos
            $changed = false;
        }
        
        return $changed;
        
    }
    
   /**
     * Función que chequea el n�mero de sesiones activas del usuario y, si existe m�s de una, toma el comportamiento pasado como argumento
     *
     * @param   bool		           $logs_attacks				 Tells if store the log
	 * @param   string	               $attack_ip			 		 The IP
	 * @param   string		           $request_uri					 The IP
	 * @param   bool	  	           $session_protection_active	 Apply session protection
	 * @param   bool	  	           $session_hijack_protection	 Apply hijack protection
	 *
     * @return  void
     *     
     */
    protected function sesiones_activas($logs_attacks,$attack_ip,$request_uri,$session_protection_active,$session_hijack_protection)
    {
        
        /* Cargamos el lenguaje del sitio */
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
        $lang = $app->getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
		
		// Creamos el nuevo objeto query
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
		
		// Si la variable "pro_plugin" está vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
        
        // Chequeamos si la opci�n de compartir sesiones está activa; en este caso no aplicaremos esta opci�n para evitar una denegaci�n de entrada
        $params = $app->getConfig();        
        $shared_session_enabled = $params->get('shared_session');
        
        if ($shared_session_enabled) {
            return;
        }
        
        // Cargamos los grupos a los que se ha de aplicar la protecci�n; por defecto se aplica al grupo Super Users, con un id igual a 8 (el valor por defecto debe estar en un array)
        $session_protection_groups = $this->pro_plugin->getValue('session_protection_groups', array('0' => '8'), 'pro_plugin');
		$dynamic_blacklist_on = $this->pro_plugin->getValue('dynamic_blacklist', 1, 'pro_plugin');
                
        // Variable que indicar� si el usuario logado pertenece a un grupo al que haya que aplicar la protecci�n
        $apply_to_user = false;
		
		$user = $app->getIdentity();
        if ($user === null || $user->guest) {
			// Usuario no logado; no hacemos nada
			return;
		}

		$user_id = (int) $user->id;

		/** @var int[] $user_groups */
		$user_groups = $user->getAuthorisedGroups();  // alternativo: $user->get('groups', [])
        
                
        // Si se pudieron determinar grupos, continuamos. Si el array está vac�o no
		if ($user_groups !== []) {
            // Chequeamos si el usuario pertenece a un grupo al que haya que aplicar la protecci�n
            foreach ($session_protection_groups as $group)
            {
                $included = in_array($group, $user_groups);
                if ($included) {
                    $apply_to_user = true;
                    break;
                }            
            }
        }
                                    
        // Construimos la consulta
        $query = "SELECT COUNT(*) from #__session WHERE (userid = {$user_id})" ;            
        $db->setQuery($query);
        $result = $db->loadResult();
                        
        if (($result > 1) && ($apply_to_user)) {  // Ya existe m�s de una sesi�n activa del usuario y el usuario está incluido en un grupo al que hay que aplicar la protecci�n                
            if ($session_protection_active) {
                /*Cerramos todas las sesiones activas del usuario, tanto del frontend (clientid->0) como del backend (clientid->1); este c�digo es necesario porque no queremos modificar los archivos de Joomla , pero esta comprobaci�n podr�a incluirse en la Función onUserLogin*/
                $app->logout($user_id, array("clientid" => 0));
                $app->logout($user_id, array("clientid" => 1));
                    
                $session_protection_description = $lang->_('COM_SECURITYCHECKPRO_SESSION_PROTECTION_DESCRIPTION');
                $username = $lang->_('COM_SECURITYCHECKPRO_USERNAME');
					
				if ($dynamic_blacklist_on) {
					$this->actualizar_lista_dinamica($attack_ip);
				}
                  
                // Grabamos el log correspondiente...
                $this->grabar_log($logs_attacks, $attack_ip, 'SESSION_PROTECTION', $session_protection_description, 'SESSION_PROTECTION', $request_uri, $username .$user->username, $user->username, '---');
                    
                // ... y redirigimos la petici�n para realizar las acciones correspondientes
                $session_protection_error = $lang->_('COM_SECURITYCHECKPRO_SESSION_PROTECTION_ERROR');
                $this->redirection(403, $session_protection_error);
            }    
        } else if (((int) $result === 1) && ($apply_to_user)) {
            //Existe una sesi�n activa del usuario; comprobamos que no ha sido suplantada
            if ($session_hijack_protection) {
                $session_hijacked = $this->chequeo_suplantacion($user_id);                    
                if ($session_hijacked) {                        
                    $session_hijack_attempt_description = $lang->_('COM_SECURITYCHECKPRO_SESSION_HIJACK_ATTEMPT_DESCRIPTION');
                    $username = $lang->_('COM_SECURITYCHECKPRO_USERNAME');
						
					if ($dynamic_blacklist_on) {
						$this->actualizar_lista_dinamica($attack_ip);
					}
                    
                    // Grabamos el log correspondiente...
                    $this->grabar_log($logs_attacks, $attack_ip, 'SESSION_PROTECTION', $session_hijack_attempt_description, 'SESSION_HIJACK_ATTEMPT', $request_uri, $username .$user->username, $user->username, '---');
                    
                    // ... y redirigimos la petici�n para realizar las acciones correspondientes
                    $session_protection_error = $lang->_('COM_SECURITYCHECKPRO_SESSION_PROTECTION_ERROR');
                    $this->redirection(403, $session_protection_error);
                }
            }
        }
       
    }
    
   /**
     * Complementa la Función original de Joomla a�adiendo a la tabla `#__securitycheckpro_sessions` informaci�n sobre la sesi�n del usuario
     *
     * @param   array<string,mixed>      $user				 The user info
	 * @param   array<string,mixed>	     $options			 The options
	 *
     * @return  void
     *     
     */
    function onUserLogin($user, $options = array())
    {
        // Obtenemos un manejador a la BBDD
        $db = Factory::getContainer()->get(DatabaseInterface::class);
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
		
		// Si la variable "pro_plugin" está vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
		
		// Chequeamos los ids de los grupos 'Public' y 'Guest'
        $query = "SELECT id FROM #__usergroups WHERE title='Public'";
        $db->setQuery($query);
        $public_group_id = (int) $db->loadResult();
        
        $query = "SELECT id FROM #__usergroups WHERE title='Guest'";
        $db->setQuery($query);
        $guest_acl_security = (int) $db->loadResult();        
        
        // Obtenemos la longitud de la clave que tenemos que generar
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $check_acl_security = $params->get('check_acl_security', 1);

        if ((int) $check_acl_security === 1) {
            //core.login.site, core.login.admin, core.login.offline, core.admin, core.manage, core.create, core.delete, core.edit, core.edit.state, core.edit.own
            $permissions_to_check = array (
            'core.login.site'    => 'JACTION_LOGIN_SITE',
            'core.login.admin'    => 'JACTION_LOGIN_ADMIN',
            'core.login.offline'    =>    'JACTION_LOGIN_OFFLINE',
            'core.admin'    =>    'JACTION_ADMIN_GLOBAL',
            'core.manage'    =>    'JACTION_MANAGE',
            'core.create'    =>    'JACTION_CREATE',
            'core.delete'    =>    'JACTION_DELETE',
            'core.edit'    =>    'JACTION_EDIT',
            'core.edit.state'    =>    'JACTION_EDITSTATE',
            'core.edit.own'    =>    'JACTION_EDITOWN');
            foreach ($permissions_to_check as $key => $value)
            {
                $public_acl = Access::checkGroup($public_group_id, $key);
                if ($public_acl) {
                    if (in_array($app->getName(), array('administrator','admin'))) {
                        $app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_INSECURE_ACL_CONFIG_DETECTED', Text::_('COM_SECURITYCHECKPRO_PUBLIC'), Text::_($value)), 'error');
                    }
                }
                
                $guest_acl = Access::checkGroup($guest_acl_security, $key);
                if (
					$guest_acl
					&& in_array($app->getName(), ['administrator', 'admin'], true)
				){
                    $app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_INSECURE_ACL_CONFIG_DETECTED', Text::_('COM_SECURITYCHECKPRO_GUEST'), Text::_($value)), 'error');                   
                }
            }            
        }                
        
        // Limpiamos las sesiones no v�lidas
        $this->chequeo_sesiones();
        
        // Normalización de datos
		$username = isset($user['username']) && is_string($user['username'])
			? $user['username']
			: '';

		$name = $username;
		
		$session_id = (string) $app->getSession()->getId();

		$ip = (string) $this->ipmodel->getClientIpForSecuritycheckPro();
        $user_agent = '';
		
		if (isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT'])) {
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
		}

		// Obtenemos el ID del usuario logado
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName('username') . ' = ' . $db->quote($username));

		$db->setQuery($query);
		$userid = (int) $db->loadResult();

		if ($userid > 0) {
			// Comprobamos si ya existe fila para este userid
			$query = $db->getQuery(true)
				->select('1')
				->from($db->quoteName('#__securitycheckpro_sessions'))
				->where($db->quoteName('userid') . ' = ' . $userid);

			$db->setQuery($query);
			$exists = (int) $db->loadResult() === 1;

			if ($exists) {
				// UPDATE: mantenemos la fila del usuario actualizada
				$query = $db->getQuery(true)
					->update($db->quoteName('#__securitycheckpro_sessions'))
					->set($db->quoteName('session_id') . ' = ' . $db->quote($session_id))
					->set($db->quoteName('username') . ' = ' . $db->quote($username))
					->set($db->quoteName('ip') . ' = ' . $db->quote($ip))
					->set($db->quoteName('user_agent') . ' = ' . $db->quote($user_agent))
					->where($db->quoteName('userid') . ' = ' . $userid);

				$db->setQuery($query);
				$db->execute();
			} else {
				// INSERT: primera sesi�n registrada para este usuario
				$query = $db->getQuery(true)
					->insert($db->quoteName('#__securitycheckpro_sessions'))
					->columns([
						$db->quoteName('userid'),
						$db->quoteName('session_id'),
						$db->quoteName('username'),
						$db->quoteName('ip'),
						$db->quoteName('user_agent'),
					])
					->values(implode(', ', [
						(string) $userid,
						$db->quote($session_id),
						$db->quote($username),
						$db->quote($ip),
						$db->quote($user_agent),
					]));

				$db->setQuery($query);
				$db->execute();
			}
		}
        
        /* Controlamos el acceso de los administradores al backend */        
        /* Cargamos el lenguaje del sitio */
        $lang = $app->getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
        $not_applicable = $lang->_('COM_SECURITYCHECKPRO_NOT_APPLICABLE');
        
        $email_on_admin_login = $this->pro_plugin->getValue('email_on_admin_login', 0, 'pro_plugin');
        $forbid_admin_frontend_login = $this->pro_plugin->getValue('forbid_admin_frontend_login', 0, 'pro_plugin');
                        
        // Controlamos el acceso al backend
        if (in_array($app->getName(), array('administrator','admin'))) {
            
            // Borramos los logs no necesarios
            $this->delete_logs();
            
            if ($email_on_admin_login) {            
                // Extraemos los datos que se mandar�n por correo
                $ip = $this->ipmodel->getClientIpForSecuritycheckPro();                               
                $email_subject = $lang->_('COM_SECURITYCHECKPRO_RULE') . $lang->_('COM_SECURITYCHECKPRO_ADMIN_LOGIN_TO_BACKEND') . "<br />" . $lang->_('COM_SECURITYCHECKPRO_USERNAME') . $username . "<br />" . "IP: " . $ip;
                $this->mandar_correo($email_subject);                                        
            }        
        } else 
        {
            // Controlamos el acceso al frontend de los Super usuarios
            if ($forbid_admin_frontend_login) {    
                // El grupo Super Users tiene un id igual a 8 (el valor por defecto debe estar en un array)
                $forbidden_groups = array('0' => '8');
                
                $apply_to_user = false;
                                    
                // Instanciamos un nuevo objeto usuario con la id del usuario logado para obtener los grupos a los que pertenece
                $user = $app->getIdentity();
                $user_groups = $user->groups;
                                
                // Chequeamos si el usuario pertenece a un grupo al que haya que aplicar la protecci�n
                foreach ($user_groups as $group)
                {
                    $included = in_array($group, $forbidden_groups);
                    if ($included) {
                        $apply_to_user = true;
                        break;
                    }            
                }
                
                if ($apply_to_user) {
                                    
                    $attack_ip = $this->ipmodel->getClientIpForSecuritycheckPro();        
                    $request_uri = $_SERVER['REQUEST_URI'];
                    $logs_attacks = $this->pro_plugin->getValue('logs_attacks', 1, 'pro_plugin');                    
                    $fordib_frontend_login_description = $lang->_('COM_SECURITYCHECKPRO_FRONTEND_LOGIN_FORBIDDEN');
                    $username_string = $lang->_('COM_SECURITYCHECKPRO_USERNAME');
                    
                    // Cerramos la sesi�n del frontend
                    $app->logout($userid, array("clientid" => 0));                    
                    
                    // Grabamos el log correspondiente...
                    $this->grabar_log($logs_attacks, $attack_ip, 'SESSION_PROTECTION', $fordib_frontend_login_description, 'SESSION_PROTECTION', $request_uri, $username_string .$name, $name, '---');
                                                            
                    // ... y redirigimos la petici�n para realizar las acciones correspondientes
                    $this->redirection(403, $fordib_frontend_login_description);
                    
                }                
            }
        }    
        
    }
    
    /**
     * Complementa la Función original de Joomla eliminando a la tabla `#__securitycheckpro_sessions` informaci�n sobre la sesi�n del usuario
     *
     * @param   array<string,mixed>      $user				 The user info
	 * @param   array<string,mixed>	     $options			 The options
	 *
     * @return  void
     *     
     */
    function onUserLogout($user, $options = array())
    {
        
        // Obtenemos un manejador a la BBDD
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        
        // Nombre del usuario logado
        $username = $db->Quote($db->escape($user['username']));
                                    
        // Borramos el usuario de la tabla
        $query = "DELETE FROM #__securitycheckpro_sessions WHERE (username = {$username})";
        $db->setQuery($query);
        $db->execute();
        
        // Limpiamos las sesiones no v�lidas
        $this->chequeo_sesiones();
    }
    
   	/**
	 * Comprueba la validez de la sesi�n actual del usuario autenticado y
	 * elimina sesiones hu�rfanas de #__securitycheckpro_sessions.
	 *
	 * @return void
	 */
	protected function chequeo_sesiones(): void
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();

		$user = $app->getIdentity();

		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// 1) Si el usuario autenticado tiene cookie de sesi�n, comprobamos
		//    que esa sesi�n siga existiendo en #__session.
		if (!$user->guest && (int) $user->id > 0) {
			$cookieSessionName = session_name();
			$sessionId = '';

			if ($cookieSessionName !== false) {
				$cookieValue = $_COOKIE[$cookieSessionName] ?? null;

				if (is_string($cookieValue) && $cookieValue !== '') {
					$sessionId = $cookieValue;
				}
			}

			if ($sessionId !== '') {
				$query = $db->getQuery(true)
					->select($db->quoteName('session_id'))
					->from($db->quoteName('#__session'))
					->where($db->quoteName('session_id') . ' = ' . $db->quote($sessionId));

				$db->setQuery($query);
				$existingSessionId = $db->loadResult();

				// Si la sesi�n de la cookie ya no existe en #__session,
				// eliminamos su rastro en #__securitycheckpro_sessions.
				if ($existingSessionId === null) {
					$query = $db->getQuery(true)
						->delete($db->quoteName('#__securitycheckpro_sessions'))
						->where($db->quoteName('session_id') . ' = ' . $db->quote($sessionId));

					$db->setQuery($query);
					$db->execute();
				}
			}
		}

		// 2) Garbage collector:
		//    eliminamos de #__securitycheckpro_sessions todos los usuarios que
		//    ya no tengan ninguna sesi�n activa en #__session.
		$subQuery = $db->getQuery(true)
			->select('1')
			->from($db->quoteName('#__session', 's'))
			->where(
				's.' . $db->quoteName('userid') . ' = ' .
				$db->quoteName('#__securitycheckpro_sessions') . '.' . $db->quoteName('userid')
			);

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__securitycheckpro_sessions'))
			->where('NOT EXISTS (' . (string) $subQuery . ')');

		$db->setQuery($query);
		$db->execute();
	}
    
   	/**
     * Función que chequea si las reglas han de aplicarse al usuario pasado como argumento. Se comprobar� la pertenencia a grupos y se aplicar� la configuraci�n de la tabla "#__securitycheckpro_rules"
     *
     *
	 * @param   User       $user_object    The user object
	 *
     * @return  bool
     *     
     */
    protected function check_rules($user_object)
    {
        
        $apply = false;
        
        if ($user_object->guest) {
            $apply = true;
        } else {
            // Consultamos la variable de sesi�n "apply_rules", que nos indicar� si hay que aplicar las reglas al usuario.
			/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
            $mainframe = Factory::getApplication();
            $apply_rules = $mainframe->getUserState("apply_rules", 'not_set');        
            
            switch ($apply_rules)
            {
            case "not_set": // Si no se ha establecido la variable, lanzamos el procedimiento "set_session_rules", que se encargar� de establecerla.                
                $this->set_session_rules();
                $apply_rules = $mainframe->getUserState("apply_rules", 'not_set');                    
                switch ($apply_rules)
                {
                case "yes":
                    $apply = true;
                    break;
                case "no":
                    $apply = false;
                    break;
                }
                case "yes":
                    $apply = true;
                break;
            case "no":
                $apply = false;
                break;
            }
        }
        
        return $apply;
    }
    
    /**
     * Función para establecer en la sesi�n del usuario si hay que aplicarle las reglas del firewall
     *
     *
     * @return  void
     *     
     */
    function set_session_rules()
    {
        $apply = "yes";
        
        $db = Factory::getContainer()->get(DatabaseInterface::class);
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
        $user = $app->getIdentity();
                
        foreach ($user->groups as $grupo)
        {
            // Consultamos si hay que aplicar la regla al grupo
            $query = "SELECT rules_applied FROM #__securitycheckpro_rules WHERE (group_id = {$grupo})";
            $db->setQuery($query);
            $apply_rule_to_group = $db->loadResult();
                                    
            // Si hay que aplicar la regla, actualizamos la variable '$apply' y abandonamos el bucle
             if ( !is_null($apply_rule_to_group) && ((int) $apply_rule_to_group === 0) ) {
                $apply = "no";
                $this->actualizar_rules_log($user, $grupo);
                break;
            }
        }
        
        // Creamos la variable en el entorno del usuario
        $app->SetUserState("apply_rules", $apply);        
    }
    
    /**
     * Función para actualizar los logs de las reglas del firewall
     *
     * @param   \Joomla\CMS\User\User|null     $user    The user object
	 * @param   int           				  $grupo   The group id
	 *
     * @return  void
     *     
     */
    function actualizar_rules_log($user,$grupo)
    {
        
        // Inicializamos las variables necesarias
        $ip = 'Not set';
		$sql = '';
        
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        
        // Obtenemos el t�tulo del grupo al que se le aplica la excepci�n
        $query = "SELECT title FROM #__usergroups WHERE (id = {$grupo})";
        $db->setQuery($query);
        $group_title = $db->loadResult();
        
        // Obtenemos la IP del cliente
        $ip = $this->ipmodel->getClientIpForSecuritycheckPro();
                
        // Obtenemos un timestamp
        $date = Factory::getDate();
        
        // Rellenamos el objeto que vamos a insertar en la tabla '#__securitycheckpro_rules_logs'
        $valor = (object) array(
        'ip' => $ip,
        'username' => $user->username,
        'last_entry' => $date->format("Y-m-d H:i:s"),
        'reason' => Text::plural('COM_SECURITYCHECKPRO_RULES_LOGS_REASON', $group_title),
                    );
        $insert_result = $db->insertObject('#__securitycheckpro_rules_logs', $valor, 'id');
        
        // Borramos las entradas con m�s de un mes de antig�edad
		if (strstr($this->dbtype,"mysql")) {
			$sql = "DELETE FROM #__securitycheckpro_rules_logs WHERE (DATE_ADD(last_entry, INTERVAL 1 MONTH)) < NOW();";
		} else if (strstr($this->dbtype,"pgsql")) {
			$sql = "DELETE FROM #__securitycheckpro_rules_logs WHERE last_entry < NOW() - INTERVAL '1 MONTH';";
		}	
        
        $db->setQuery($sql);
        $db->execute();
        
    }
	
	/**
	 * Overwrite the onAfterInitialise method
	 *
	 * @return void
	 *
	 */
	function onAfterInitialise()
	{
		$plugin_enabled = false;
                
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        
        try 
        {
            $query = "SELECT enabled from #__extensions WHERE element='Securitycheckpro' and type='plugin'";            
            $db->setQuery($query);
            $plugin_enabled= $db->loadResult();
        } catch (\Exception $e)
        {
            
        }            
        
        // Is the plugin enabled?
        if ($plugin_enabled) {	
			/* Chequeamos los archivos subidos al servidor usando cabeceras HTTP y m�todo POST. Los archivos son arrays con el siguiente formato:
			[integer] error = 0
			[string] name = "k.txt"
			[integer] size = 4674
			[string] tmp_name = "/tmp/phpkhm2Jz"
			[string] type = "text/plain"
			*/
			
			$this->pro_plugin = new BaseModel();
			
			// Extraemos la configuración del escaner de subidas
			$upload_scanner_enabled = $this->pro_plugin->getValue('upload_scanner_enabled', 1, 'pro_plugin');
			$check_multiple_extensions = $this->pro_plugin->getValue('check_multiple_extensions', 1, 'pro_plugin');
			$extensions_blacklist = $this->pro_plugin->getValue('extensions_blacklist', 'php,phtml,phar,shtml,htaccess,js,exe,xml', 'pro_plugin');
			$delete_files = $this->pro_plugin->getValue('delete_files', 1, 'pro_plugin');
			$actions_upload_scanner = $this->pro_plugin->getValue('actions_upload_scanner', 0, 'pro_plugin');

			// Si el escáner está habilitado y existen archivos subidos, los comprobamos
			if (($upload_scanner_enabled) && ($_FILES)) {
				foreach ($_FILES as $entry) {
					foreach ($this->normalise_files($entry) as $file) {
						$this->check_file($check_multiple_extensions, $extensions_blacklist, $delete_files, $file, $actions_upload_scanner);
					}
				}
			}

            // Cargamos el lenguaje del sitio
			/** @var \Joomla\CMS\Application\CMSApplication $app */
			$app       = Factory::getApplication();
            $lang = $app->getLanguage();
            $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
            $not_applicable = $lang->_('COM_SECURITYCHECKPRO_NOT_APPLICABLE');
            $access_attempt = $lang->_('COM_SECURITYCHECKPRO_ACCESS_ATTEMPT');
						
            $methods = $this->pro_plugin->getValue('methods', 'GET,POST,REQUEST', 'pro_plugin');
            $logs_attacks = $this->pro_plugin->getValue('logs_attacks', 1, 'pro_plugin');
            $blacklist_ips = $this->pro_plugin->getValue('blacklist', 'pro_plugin');
            $dynamic_blacklist_on = $this->pro_plugin->getValue('dynamic_blacklist', 1, 'pro_plugin');
            $dynamic_blacklist_time = $this->pro_plugin->getValue('dynamic_blacklist_time', 60000, 'pro_plugin');
            $dynamic_blacklist_counter = $this->pro_plugin->getValue('dynamic_blacklist_counter', 2, 'pro_plugin');
            $whitelist_ips = $this->pro_plugin->getValue('whitelist', 'pro_plugin');
            $secondlevel = $this->pro_plugin->getValue('second_level', 1, 'pro_plugin');
            $check_base_64 = $this->pro_plugin->getValue('check_base_64', 1, 'pro_plugin');           
            $priority1 = $this->pro_plugin->getValue('priority1', 'Whitelist', 'pro_plugin');
            $priority2 = $this->pro_plugin->getValue('priority2', 'Blacklist', 'pro_plugin');
            $priority3 = $this->pro_plugin->getValue('priority3', 'DynamicBlacklist', 'pro_plugin');
			// Get database type
			$config = $app->getConfig();
			$this->dbtype = $config->get('dbtype');			
            
            $attack_ip = $this->ipmodel->getClientIpForSecuritycheckPro();      
            $request_uri = $_SERVER['REQUEST_URI'];
			
            // Chequeamos los nuevos usuarios administradores/super usuarios
            $this->forbid_new_admins();
            
            // Cargamos las librerias necesarias para realizar comprobaciones
            $model = $this->pro_plugin;
          
			$aparece_lista_negra = $model->chequear_ip_en_lista($attack_ip, "blacklist");
			$aparece_lista_blanca = $model->chequear_ip_en_lista($attack_ip, "whitelist");
                    
            // If priority1 was set to "geoblock" we must set it to other value (i.e Whitelist) or no actions will be taken
			if ($priority1 == "Geoblock") {		
				$priority1 = "Whitelist";
			}
			
            // Prioridad            
            if ($priority1 == "Whitelist") {
                if ($aparece_lista_blanca) {
                    return;
                }            
            } else if ($priority1 == "DynamicBlacklist") {
                // Chequeamos si la ip remota se encuentra en la lista negra din�mica
                if ($dynamic_blacklist_on) {
                    $this->acciones_lista_negra_dinamica($dynamic_blacklist_time, $attack_ip, $dynamic_blacklist_counter, $logs_attacks, $request_uri, $not_applicable);
                }
            } else if ($priority1 == "Blacklist") {
                // Chequeamos si la ip remota se encuentra en la lista negra
                if ($aparece_lista_negra) {
                    $this->acciones_lista_negra($logs_attacks, $attack_ip, $access_attempt, $request_uri, $not_applicable);
                }
            }
            
            
            if ($priority2 == "Whitelist") {
                if ($aparece_lista_blanca) {
                    return;
                }
            }  else if ($priority2 == "DynamicBlacklist") {
                // Chequeamos si la ip remota se encuentra en la lista negra din�mica
                if ($dynamic_blacklist_on) {
                    $this->acciones_lista_negra_dinamica($dynamic_blacklist_time, $attack_ip, $dynamic_blacklist_counter, $logs_attacks, $request_uri, $not_applicable);
                }
            } else if ($priority2 == "Blacklist") {
                // Chequeamos si la ip remota se encuentra en la lista negra
                if ($aparece_lista_negra) {
                    $this->acciones_lista_negra($logs_attacks, $attack_ip, $access_attempt, $request_uri, $not_applicable);
                }
            }
                    
            if ($priority3 == "Whitelist") {
                if ($aparece_lista_blanca) {
                    return;
                }
            }  else if ($priority3 == "DynamicBlacklist") {
                // Chequeamos si la ip remota se encuentra en la lista negra din�mica
                if ($dynamic_blacklist_on) {
                    $this->acciones_lista_negra_dinamica($dynamic_blacklist_time, $attack_ip, $dynamic_blacklist_counter, $logs_attacks, $request_uri, $not_applicable);
                }
            } else if ($priority3 == "Blacklist") {
                // Chequeamos si la ip remota se encuentra en la lista negra
                if ($aparece_lista_negra) {
                    $this->acciones_lista_negra($logs_attacks, $attack_ip, $access_attempt, $request_uri, $not_applicable);
                }
            }
		}
		
	}
	
	/**
     * Sobreescribe la Función original
     *
     * @return  void
     *     
     */	
    function onAfterRoute()
    {
		$plugin_enabled = false;
        $tables_locked = false;

        $db = Factory::getContainer()->get(DatabaseInterface::class);

		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();

        try
        {
            $query = "SELECT enabled from #__extensions WHERE element='Securitycheckpro' and type='plugin'";
            $db->setQuery($query);
            $plugin_enabled= $db->loadResult();
        } catch (\Exception $e)
        {

        }

        try
        {
            $query = "SELECT storage_value from #__securitycheckpro_storage WHERE storage_key = 'locked'";
            $db->setQuery($query);
            $tables_locked= $db->loadResult();
        } catch (\Exception $e)
        {

        }

        // Is the plugin enabled?
        if ($plugin_enabled) {

			/* Chequeamos los archivos subidos al servidor usando cabeceras HTTP y m�todo POST. Los archivos son arrays con el siguiente formato:
			[integer] error = 0
			[string] name = "k.txt"
			[integer] size = 4674
			[string] tmp_name = "/tmp/phpkhm2Jz"
			[string] type = "text/plain"
			*/

			$this->pro_plugin = new BaseModel();

			// Extraemos la configuraci�n del escaner de subidas
			$upload_scanner_enabled = $this->pro_plugin->getValue('upload_scanner_enabled', 1, 'pro_plugin');
			$check_multiple_extensions = $this->pro_plugin->getValue('check_multiple_extensions', 1, 'pro_plugin');
			$extensions_blacklist = $this->pro_plugin->getValue('extensions_blacklist', 'php,phtml,phar,shtml,htaccess,js,exe,xml', 'pro_plugin');
			$delete_files = $this->pro_plugin->getValue('delete_files', 1, 'pro_plugin');
			$actions_upload_scanner = $this->pro_plugin->getValue('actions_upload_scanner', 0, 'pro_plugin');

			// Si el esc�ner está habilitado y existen archivos subidos, los comprobamos
			if (($upload_scanner_enabled) && ($_FILES)) {
				foreach ($_FILES as $entry) {
					foreach ($this->normalise_files($entry) as $file) {
						$this->check_file($check_multiple_extensions, $extensions_blacklist, $delete_files, $file, $actions_upload_scanner);
					}
				}
			}

            $methods = $this->pro_plugin->getValue('methods', 'GET,POST,REQUEST', 'pro_plugin');
            $logs_attacks = $this->pro_plugin->getValue('logs_attacks', 1, 'pro_plugin');
            $secondlevel = $this->pro_plugin->getValue('second_level', 1, 'pro_plugin');
            $check_base_64 = $this->pro_plugin->getValue('check_base_64', 1, 'pro_plugin');

            $attack_ip = $this->ipmodel->getClientIpForSecuritycheckPro();
            $request_uri = $_SERVER['REQUEST_URI'];

            // Cargamos las librerias necesarias para realizar comprobaciones
            $model = $this->pro_plugin;

			$aparece_lista_blanca = $model->chequear_ip_en_lista($attack_ip, "whitelist");

            if (!$aparece_lista_blanca) {
                // La IP no se encuentra en ninguna lista
                $this->acciones_no_listas($methods, $attack_ip, $methods, $request_uri, $check_base_64, $logs_attacks, $secondlevel);
            }
        }
        // Si las tablas están bloqueadas prohibimos el acceso a 'com_installer'
        if ($tables_locked) {           
            $is_admin = $app->isClient('administrator');
            
            if ($is_admin) {                
                $option = $app->getInput()->get('option');
                if (($option == "com_installer") || ($option == "com_joomlaupdate")) {
                    $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INSTALLER_ACCESS_FORBIDDEN'), 'error');
                    // Redirigimos a la p�gina establecida por el administrador
                    $app->redirect(Uri::base());    
                }
            }
            
        }
		// Are we updating Joomla?
		$object = $app->getInput();
		$option = $object->getString('option');
		$task = $object->getString('task');
		if ($option == "com_joomlaupdate") {
			if ($task == "update.install") {				
				// Let's write a file to tell securitycheck that Joomla core has been updated. This is needed by /com_securitycheckpro/backend/models/securitycheckpros.php		
				$this->write_file(); 
			}
		}
    } 

    /**
     * Sobreescribe la Función original para eliminar el meta-tag
     *
     * @return  void
     *     
     */
    public function onAfterDispatch()
    {
        // �Tenemos que eliminar el meta tag?
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $remove_meta_tag = $params->get('remove_meta_tag', 1);
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
        
        $code  = $app->getDocument();
        if ($remove_meta_tag) {
            $code->setGenerator('');
        }
		
    }
    
    /**
     * Normalises a single $_FILES field entry into a flat list of single-file records.
     * Handles scalar (single file), parallel-array (multiple files, <input multiple>),
     * and nested-array (name="a[b][]") shapes. Entries with UPLOAD_ERR_NO_FILE are skipped.
     *
     * @param   array<string,mixed>  $entry  One $_FILES field entry
     *
     * @return  array<int,array{name:string,tmp_name:string,size:int,type:string,error:int}>
     */
    protected function normalise_files(array $entry): array
    {
        $records = [];

        if (!is_array($entry['tmp_name'])) {
            $error = (int) ($entry['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($error !== UPLOAD_ERR_NO_FILE) {
                $records[] = [
                    'name'     => (string) ($entry['name'] ?? ''),
                    'tmp_name' => (string) ($entry['tmp_name'] ?? ''),
                    'size'     => (int)    ($entry['size'] ?? 0),
                    'type'     => (string) ($entry['type'] ?? ''),
                    'error'    => $error,
                ];
            }
            return $records;
        }

        foreach (array_keys($entry['tmp_name']) as $i) {
            $sub = [
                'name'     => $entry['name'][$i] ?? '',
                'tmp_name' => $entry['tmp_name'][$i] ?? '',
                'size'     => $entry['size'][$i] ?? 0,
                'type'     => $entry['type'][$i] ?? '',
                'error'    => $entry['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            ];

            if (is_array($sub['tmp_name'])) {
                foreach ($this->normalise_files($sub) as $record) {
                    $records[] = $record;
                }
            } else {
                $error = (int) $sub['error'];
                if ($error !== UPLOAD_ERR_NO_FILE) {
                    $records[] = [
                        'name'     => (string) $sub['name'],
                        'tmp_name' => (string) $sub['tmp_name'],
                        'size'     => (int)    $sub['size'],
                        'type'     => (string) $sub['type'],
                        'error'    => $error,
                    ];
                }
            }
        }

        return $records;
    }

    /**
     * Función que chequea si un fichero tiene m�ltiples extensiones o pertenece a una lista de extensiones prohibidas. Seg�n el valor de la variable $delete_files, el fichero ser� borrado
     *
	 * @param   int|bool        		 $check_multiple_extensions  	Check multiple extensions?
	 * @param   string          		 $extensions_blacklist    		String with the extensions forbidden
	 * @param   int|bool       			 $delete_files   				Delete uploaded files
	 * @param   array{name:string,tmp_name:string,size:int,type:string,error:int}  $file  The file info (scalar values)
	 * @param   int          			 $actions_upload_scanner   		Actions
	 *
     * @return  void
     *
     */
    protected function check_file($check_multiple_extensions,$extensions_blacklist,$delete_files,$file,$actions_upload_scanner)
    {
		// Si la variable "pro_plugin" está vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
		
        // Inicializamos variables
        $safe = true;
        $malware_type = '';
        $malware_description = '';
        $logs_attacks = $this->pro_plugin->getValue('logs_attacks', 1, 'pro_plugin');
		$mimetypes_blacklist = $this->pro_plugin->getValue('mimetypes_blacklist','application/x-dosexec,application/x-msdownload ,text/x-php,application/x-php,application/x-httpd-php,application/x-httpd-php-source,application/javascript,application/xml', 'pro_plugin');
        $attack_ip = $this->ipmodel->getClientIpForSecuritycheckPro();
        $request_uri = $_SERVER['REQUEST_URI'];
        $tag_description = '';
		$type = '';
		
		/* Cargamos el lenguaje del sitio */
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
        $lang = $app->getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
        $not_applicable = $lang->_('COM_SECURITYCHECKPRO_NOT_APPLICABLE');
        $access_attempt = $lang->_('COM_SECURITYCHECKPRO_ACCESS_ATTEMPT');
        $action = $lang->_('COM_SECURITYCHECKPRO_FILE_DELETED');
		$custom_code = $this->pro_plugin->getValue('custom_code', 'The webmaster has forbidden your access to this site', 'pro_plugin');
		
		$tmp_name  = (string) $file['tmp_name'];
		$file_name = (string) $file['name'];
		$file_size = (int)    $file['size'];
		
		// Obtenemos el mime-type del archivo temporal
		if ( (function_exists('mime_content_type')) && (file_exists($tmp_name)) )  {
			$mime_type = strtolower(mime_content_type($tmp_name));
		} else {
			$mime_type = false;
		}		
		
		// Obtenemos el componente de la petici�n
		$component = $app->getInput()->get('option','com_notfound');
		
		// Obtenemos el usuario
        $user = $app->getIdentity(); 
		
		if ($mime_type) {
			$mimetypes_blacklist_array = explode(",",$mimetypes_blacklist);
			// Convertimos los valores del array a min�sculas para hacer la comparaci�n 'in_array'
			$mimetypes_blacklist_array = array_map('strtolower', $mimetypes_blacklist_array);			
			
			if ( in_array($mime_type,$mimetypes_blacklist_array) ) {
				$malware_description = $lang->_('COM_SECURITYCHECKPRO_FILE_MIMETYPE_NOT_ALLOWED') . $mime_type;
				$type = 'FORBIDDEN_EXTENSION';
				if ($delete_files) {                    
                    @unlink($tmp_name);                    
                } else {
                    $action = $lang->_('COM_SECURITYCHECKPRO_FILE_NOT_DELETED');
                }
                
                // Si está marcada la opci�n, a�adimos la IP a la lista negra din�mica
                if ((int) $actions_upload_scanner === 1) {
                    $this->actualizar_lista_dinamica($attack_ip);                    
                }
				$this->grabar_log($logs_attacks, $attack_ip, 'UPLOAD_SCANNER', $action, $type, $request_uri, $file_name . PHP_EOL . $malware_description, $user->username, $component);
				$error_403 = $lang->_('COM_SECURITYCHECKPRO_403_ERROR');
				header('HTTP/1.1 403 Forbidden');
				die($custom_code);
			}
		}

		// Capa adicional: detectar c�digo PHP dentro de archivos con extensi�n de imagen
		$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
		$fileExtLower = strtolower((string) pathinfo((string) $file_name, PATHINFO_EXTENSION));
		if (in_array($fileExtLower, $imageExtensions, true) && file_exists($tmp_name)) {
			$fh = @fopen($tmp_name, 'rb');
			if ($fh !== false) {
				$header = (string) fread($fh, 512);
				fclose($fh);
				if (strpos($header, '<?') !== false || strpos($header, '<%') !== false) {
					$malware_description = $lang->_('COM_SECURITYCHECKPRO_FILE_MIMETYPE_NOT_ALLOWED') . 'PHP/script content in image file';
					$type = 'FORBIDDEN_EXTENSION';
					if ($delete_files) {
						@unlink($tmp_name);
					} else {
						$action = $lang->_('COM_SECURITYCHECKPRO_FILE_NOT_DELETED');
					}
					if ((int) $actions_upload_scanner === 1) {
						$this->actualizar_lista_dinamica($attack_ip);
					}
					$this->grabar_log($logs_attacks, $attack_ip, 'UPLOAD_SCANNER', $action, $type, $request_uri, $file_name . PHP_EOL . $malware_description, $user->username, $component);
					header('HTTP/1.1 403 Forbidden');
					die($custom_code);
				}
			}
		}

        // Extensiones de ficheros que ser�n analizadas
        // Eliminamos los espacios en blanco y normalizamos a min�sculas para comparaci�n case-insensitive
        $extensions_blacklist = str_ireplace(' ', '', $extensions_blacklist);
        $ext = array_map('strtolower', explode(',', $extensions_blacklist));

        if ($file_name !== '') {

            // Buscamos extensiones m�ltiples: cualquier segmento intermedio que sea extensi�n prohibida
            if ($check_multiple_extensions) {

                $explodedName = explode('.', $file_name);
                $explodedName = array_reverse($explodedName);

                // Comprobamos segmentos intermedios (saltamos [0]=extensi�n final y el �ltimo=nombre base)
                for ($i = 1; $i < count($explodedName) - 1; $i++) {
                    if (in_array(strtolower($explodedName[$i]), $ext, true)) {
                        $malware_description = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_EXTENSION') . $explodedName[$i];
                        $type = 'MULTIPLE_EXTENSIONS';
                        $safe = false;
                        break;
                    }
                }
            }

            // Buscamos si la extensión está en la lista de las extensiones prohibidas (comparaci�n case-insensitive)
            if ((!empty($extensions_blacklist)) && ($safe)) {

                if (in_array(strtolower((string) pathinfo($file_name, PATHINFO_EXTENSION)), $ext, true) && ($file_size > 0)) {
                    // Archivo en la lista de extensiones prohibidas
                    $type = 'FORBIDDEN_EXTENSION';
                    $malware_description = $lang->_('COM_SECURITYCHECKPRO_TITLE_FORBIDDEN_EXTENSION');
                    $safe = false;
                }
            }
            
            // Si alguna de las dos comprobaciones es positiva, borramos el fichero subido (si así está marcado)
            if (!$safe) {
                if ($delete_files) {                    
                    @unlink($tmp_name);                    
                } else {
                    $action = $lang->_('COM_SECURITYCHECKPRO_FILE_NOT_DELETED');
                }
                
                // Si está marcada la opci�n, a�adimos la IP a la lista negra din�mica
                if ((int) $actions_upload_scanner === 1) {
                    $this->actualizar_lista_dinamica($attack_ip);                    
                }
                        
                $this->grabar_log($logs_attacks, $attack_ip, 'UPLOAD_SCANNER', $action, $type, $request_uri, $file_name . PHP_EOL . $malware_description, $user->username, $component);
                $error_403 = $lang->_('COM_SECURITYCHECKPRO_403_ERROR');
                header('HTTP/1.1 403 Forbidden');
				die($custom_code);     
            }
        }
    }
       
    /**
     * Auditamos las entradas fallidas de los usuarios
     *
	 * @param   string        		 $response  	The response of the original method
	 *
     * @return  void
     *     
     */	
    public function onUserLoginFailure($response)
    {
		// Si la variable "pro_plugin" está vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
		
        // Extraemos la configuraci�n del plugin
        $track_failed_logins = $this->pro_plugin->getValue('track_failed_logins', 1, 'pro_plugin');
        $write_log = $this->pro_plugin->getValue('write_log', 1, 'pro_plugin');
        $logins_to_monitorize = $this->pro_plugin->getValue('logins_to_monitorize', 2, 'pro_plugin');
        $actions_failed_login = $this->pro_plugin->getValue('actions_failed_login', 1, 'pro_plugin');
        
        $logs_attacks = $this->pro_plugin->getValue('logs_attacks', 1, 'pro_plugin');
        $attack_ip = $this->ipmodel->getClientIpForSecuritycheckPro();
        $request_uri = $_SERVER['REQUEST_URI'];
                        
        /* Cargamos el lenguaje del sitio */
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
        $lang = $app->getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
        $not_applicable = $lang->_('COM_SECURITYCHECKPRO_NOT_APPLICABLE');
                        
        if($track_failed_logins) {
            $login_info = $this->trackFailedLogin();
            // Controlamos el acceso al backend           
            if (in_array($app->getName(), array('administrator','admin'))) {
                // Escribimos un log si se produce un intento de acceso fallido    al backend                
                if ($logins_to_monitorize != 1) {
                    $description = $lang->_('COM_SECURITYCHECKPRO_USERNAME') . $login_info[0];
                    if($write_log) {
                        $this->grabar_log($write_log, $attack_ip, 'FAILED_LOGIN_ATTEMPT_LABEL', $lang->_('COM_SECURITYCHECKPRO_FAILED_ADMINISTRATOR_LOGIN_ATTEMPT_LABEL'), 'SESSION_PROTECTION', $request_uri, $description, $login_info[0], '---');                        
                    }
                    // Si está marcada la opci�n, a�adimos la IP a la lista negra din�mica
                    if ((int) $actions_failed_login === 1) {
                        $this->actualizar_lista_dinamica($attack_ip);                    
                    }
                }                                        
            } else
            {
                // Escribimos en log si se produce un intento de acceso fallido al frontend
                if ($logins_to_monitorize != 2) {
                    $description = $lang->_('COM_SECURITYCHECKPRO_USERNAME') . $login_info[0];                    
                    if($write_log) {
                        $this->grabar_log($write_log, $attack_ip, 'FAILED_LOGIN_ATTEMPT_LABEL', $lang->_('COM_SECURITYCHECKPRO_FAILED_LOGIN_ATTEMPT_LABEL'), 'SESSION_PROTECTION', $request_uri, $description, $login_info[0], '---');
                    }
                    // Si está marcada la opci�n, a�adimos la IP a la lista negra din�mica
                    if ((int) $actions_failed_login === 1) {
                        $this->actualizar_lista_dinamica($attack_ip);                    
                    }
                }
            }    
            
        }        
        // Limpiamos las sesiones no v�lidas
        $this->chequeo_sesiones();
    }
    
    /**
     * Función que recoje los datos de los intentos de acceso fallidos
     *
	 * @return  array<mixed,mixed>
     *     
     */	
    private function trackFailedLogin()
    {
        /** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
        $input = $app->getInput();
		$user = $input->get('username', null);	
        
        $extraInfo = array();
        if(!empty($user)) {    
            $extraInfo[] = $user;        
        }
        
        return $extraInfo;        
    }
      
        
    /**
     * Chequeamos los usuarios administradores/super usuarios
     *
	 * @return  void|null
     *     
     */	
    private function forbid_new_admins()
    {
		// Si la variable "pro_plugin" está vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
		
		$previous_admins= '';
		
        // Inicializamos las variables
        $admin_groups = array();        
        $logs_attacks = $this->pro_plugin->getValue('logs_attacks', 1, 'pro_plugin');
        $forbid_new_admins = $this->pro_plugin->getValue('forbid_new_admins', 0, 'pro_plugin');
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        
        if ($forbid_new_admins) {
                        
            // Extraemos todos los grupos existentes...
            $query = $db->getQuery(true)
                ->select(array($db->quoteName('group_id')))
                ->from($db->quoteName('#__user_usergroup_map'));        
            $db->setQuery($query);
            $groups = $db->loadColumn();
            
            // ... y chequeamos los que tienen permisos de administraci�n, ya sean propios o heredados
            if(!empty($groups)) { foreach($groups as $group)
                {
                    // First try to see if the group has explicit backend login privileges
                    $backend = Access::checkGroup($group, 'core.login.admin') || Access::checkGroup($group, 'core.admin');
                                
                    // Si el grupo tiene privilegios de administraci�n, lo a�adimos al array 
                    if ($backend) {
                        $admin_groups[] = $group;
                    }                
            }
            }
                        
            // Consultamos el n�mero actual de usuarios con permisos de administraci�n
			try 
            {
				$query = "SELECT COUNT(*) from #__user_usergroup_map WHERE group_id IN (" . implode(',', array_map('intval', $admin_groups)) . ")" ;
				$db->setQuery($query);
				(int) $actual_admins = $db->loadResult();
			}catch (\Exception $e)
            {
                return;
            }
                        
            // Consultamos el n�mero previo de usuarios pertenencientes al grupo super-users
            try
            {
                $query = "SELECT contador from #__securitycheckpro_users_control WHERE id='1'" ;
                $db->setQuery($query);
                (int) $previous_admins = $db->loadResult();
            } catch (\Exception $e)
            {
                if (strstr($e->getMessage(), "doesn't exist")) {
                    $previous_admins = null;
                }
            }
                            
            if (is_null($previous_admins)) { // No hay datos almacenados (o es la primera vez que se lanza o se ha desactivado esta opci�n y ahora está activa)
                // Extraemos los ids de los usuarios con permisos de administraci�n
				try 
				{
					$query = "SELECT user_id from #__user_usergroup_map WHERE group_id IN (" . implode(',', array_map('intval', $admin_groups)) . ")" ;
					$db->setQuery($query);
					$actual_admins = $db->loadColumn();
				}catch (\Exception $e)
				{
					return;
				}
                
                // Instanciamos un objeto para almacenar los datos que ser�n sobreescritos
                $object = new \StdClass();                    
                $object->id = 1;
                $object->users = json_encode($actual_admins);
                $object->contador = count($actual_admins);
                
                try 
                {
                    // A�adimos los datos a la BBDD
                    $res = $db->insertObject('#__securitycheckpro_users_control', $object);    
                        
                } catch (\Exception $e) {    
                    
                }
            } else if ($actual_admins > $previous_admins) {
                // Se ha añadido un nuevo usuario con permisos de administraci�n
                // Extraemos los ids de los usuarios con permisos de administraci�n
                $query = "SELECT user_id from `#__user_usergroup_map` WHERE group_id IN (" . implode(',', array_map('intval', $admin_groups)) . ")" ;
                $db->setQuery($query);
                $actual_admins = $db->loadColumn();
                                
                // Extraemos los ids de los usuarios con permisos de administraci�n anteriores
                try
                {
                    $query = "SELECT users from #__securitycheckpro_users_control" ;
                    $db->setQuery($query);
                    $previous_admins = $db->loadResult();
                } catch (\Exception $e)
                {    
                    if (strstr($e->getMessage(), "doesn't exist")) {
						/** @var \Joomla\CMS\Application\CMSApplication $app */
						$app = Factory::getApplication();                                    
                        $app->enqueueMessage(Text::_('A mandatory table of Securitycheck Pro has not been created. Please, install the extension again and everything should work fine. Please, close this message.'), 'error');
                    }
                }
                
                // Decodificamos el array, que vendr� en formato json
                $previous_admins = json_decode($previous_admins, true);
				
				if (!is_null($previous_admins)) {
					// Extraemos el id del nuevo usuario creado
					$new_user_added = array_diff($actual_admins, $previous_admins);
				} else {
					// Something went wrong decoding the json to extract previous admins. Let's create an empty array
					$new_user_added = array();
					// Instanciamos un objeto para almacenar los datos que ser�n sobreescritos
					$object = new \StdClass();                    
					$object->id = 1;
					$object->users = json_encode($actual_admins);
					$object->contador = count($actual_admins);
					
					try 
					{
						// A�adimos los datos a la BBDD
						$db->updateObject('#__securitycheckpro_users_control', $object, 'id');    
							
					} catch (\Exception $e) {    
						return;
					}
				}
                                            
                foreach ($new_user_added as $new_user)
                {                        
                    // Creamos una instancia del usuario. Si $new_user no existe en la base de datos, getInstance() devuelve un objeto User "guest" con id=0
                    $instance = User::getInstance($new_user);
                    $username = $instance->username;

                    if ($instance->id) {
                        // Borramos el usuario
                        $instance->delete();
                        $this->grabar_log($logs_attacks, '---', 'SESSION_PROTECTION', Text::_('COM_SECURITYCHECKPRO_FORBID_NEW_ADMINS_LABEL'), 'SESSION_PROTECTION', Text::_('COM_SECURITYCHECKPRO_NOT_APPLICABLE'), Text::_('COM_SECURITYCHECKPRO_USER_DELETED'), $username, '---');
                        // Si hay alguien logado al backend, mostramos un mensaje de error
                        /** @var \Joomla\CMS\Application\CMSApplication $app */
                        $app = Factory::getApplication();
                        if (in_array($app->getName(), array('administrator','admin'))) {
                            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_USER_DELETED_EXPLAINED'), 'error');
                        }
                    }
                }
                
                
            }
            
        } else
        {
            // Borramos los datos de la tabla
            // Consultamos el n�mero de logs para ver si se supera el Límite establecido en el apartado 'log_limits_per_ip_and_day'
            try 
            {
                $query = "DELETE from #__securitycheckpro_users_control WHERE id='1'" ;
                $db->setQuery($query);
                $db->execute();
            } catch (\Exception $e)
            {
                if (strstr($e->getMessage(), "doesn't exist")) { 
					/** @var \Joomla\CMS\Application\CMSApplication $app */
                    $app = Factory::getApplication();                                    
                    $app->enqueueMessage(Text::_('A mandatory table of Securitycheck Pro has not been created. Please, install the extension again and everything should work fine. Please, close this message.'), 'error');                
                }
                
            }
        }        
    }
	
	/**
     * Translate an extension name (based on /administrator/components/com_installer/src/Model/InstallerModel.php)
     *
     * @param   string $name  The name of the extension.
	 * @param   string $type  The type of the extension.
     *
     * @return  string 
     *
     *
     */	
	private function translate_name($name,$type) {
		$name_translated = $name;
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
		$lang = $app->getLanguage();
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		
		try {                        
			$query = $db->getQuery(true)
				->select('element,client_id,folder')
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('name').' = '.$db->quote($name));
			$db->setQuery($query);
			$item = $db->loadObject();
		} catch (\Throwable $e) {  
			return $name_translated;
		}
				
		$path = $item->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE;
		
        switch ($type) {
            case 'component':
                $extension = $item->element;
                $source    = JPATH_ADMINISTRATOR . '/components/' . $extension;
                $lang->load("$extension.sys", JPATH_ADMINISTRATOR) || $lang->load("$extension.sys", $source);
				break;
            case 'file':
                $extension = 'files_' . $item->element;
                $lang->load("$extension.sys", JPATH_SITE);
                break;
            case 'library':
                $parts     = explode('/', $item->element);
                $vendor    = (isset($parts[1]) ? $parts[0] : null);
                $extension = 'lib_' . ($vendor ? implode('_', $parts) : $item->element);

                if (!$lang->load("$extension.sys", $path)) {
                    $source = $path . '/libraries/' . ($vendor ? $vendor . '/' . $parts[1] : $item->element);
                    $lang->load("$extension.sys", $source);
                }
                break;
            case 'module':
                $extension = $item->element;
                $source    = $path . '/modules/' . $extension;
                $lang->load("$extension.sys", $path) || $lang->load("$extension.sys", $source);
                break;
            case 'plugin':
                $extension = 'plg_' . $item->folder . '_' . $item->element;
                $source    = JPATH_PLUGINS . '/' . $item->folder . '/' . $item->element;
                $lang->load("$extension.sys", JPATH_ADMINISTRATOR) || $lang->load("$extension.sys", $source);
                break;
            case 'template':
                $extension = 'tpl_' . $item->element;
                $source    = $path . '/templates/' . $item->element;
                $lang->load("$extension.sys", $path) || $lang->load("$extension.sys", $source);
                break;
            case 'package':
            default:
                $extension = $item->element;
                $lang->load("$extension.sys", JPATH_SITE);
                break;
        }

        // Translate the extension name if possible
        $name_translated = Text::_($name);
		
		return $name_translated;
	}
	
	/**
     * On after CMS Update
     *
     * Method is called after user update the CMS.
     *
     * @param   string 		$table  The table to check
	 * @param   string 		$name  	The name of the etension installed
	 * @param   string 		$type  The type of the extension
     *
     * @return  void|bool
     *
     */	
	private function update_installs_securitycheckpro_storage($table,$name,$type) {
		$installs = null;
        $empty = true;
		$control_center_enabled = false;
		$controlcenter_config = null;
        
        $db = Factory::getContainer()->get(DatabaseInterface::class);
		
		if ($table == "installs_remote") {
			// Check if controlcenter is enabled
			try {                        
				// Comprobamos si hay algún dato añadido o la tabla es null; dependiendo del resultado haremos un 'update' o un 'insert'
				$query = $db->getQuery(true)
					->select(array('storage_value'))
					->from($db->quoteName('#__securitycheckpro_storage'))
					->where($db->quoteName('storage_key').' = '.$db->quote("controlcenter"));
				$db->setQuery($query);
				$controlcenter_config = $db->loadResult();
			} catch (\Throwable $e) {                
			}
			if (!is_null($controlcenter_config)){
				$controlcenter_config_array = json_decode($controlcenter_config, true);
				if ( (is_array($controlcenter_config_array)) && (array_key_exists("control_center_enabled",$controlcenter_config_array)) ) {
					$control_center_enabled = $controlcenter_config_array['control_center_enabled'];
				}			
			}
		}
		
		if ( ($table == "installs") || ( ($table == "installs_remote") && ($control_center_enabled == "1") ) ) {
			
			if ($name != "Joomla!") {
				$name = $this->translate_name($name,$type);
			}
        
			try {
							
				// Comprobamos si hay algún dato añadido o la tabla es null; dependiendo del resultado haremos un 'update' o un 'insert'
				$query = $db->getQuery(true)
					->select(array('storage_value'))
					->from($db->quoteName('#__securitycheckpro_storage'))
					->where($db->quoteName('storage_key').' = '.$db->quote($table));
				$db->setQuery($query);
				$installs = $db->loadResult();		
									   
				if (!empty($installs)) {
					$empty = false;
					$installs_array = json_decode($installs, true);
					
					// Obtenemos Sólo el array de nombre para comprobar si ya hemos añadido la extensión            
					$array_names = array_column($installs_array, 'name');
					
					if (!in_array($name, $array_names)) {
						$extension_data = array(
						'name' => $name,
						'type' => $type
						);
						
						$installs_array[] = $extension_data;
					}                
				} else 
				{
					$extension_data = array(
					'name' => $name,
					'type' => $type
					);
							
					$installs_array[] = $extension_data;
				}
				
				// Codificamos el array en formato json
				$installs_array = json_encode($installs_array);
										
				// Instanciamos un objeto para almacenar los datos que serán sobreescritos/añadidos
				$object = new \StdClass();                    
				$object->storage_key = $table;
				$object->storage_value = $installs_array;
				
				if ($empty) {
					$res = $db->insertObject('#__securitycheckpro_storage', $object);
				} else {
					$res = $db->updateObject('#__securitycheckpro_storage', $object, 'storage_key');
				}
				
				// Let's write a file to tell securitycheck that a new extension has been installed. This is needed by /com_securitycheckpro/backend/models/securitycheckpros.php	
				if ($table == "installs") {
					$this->write_file(); 
				}
			} catch (\Throwable $e) {                
				return false;
			}
		}
	}
	
	/**
     * On after CMS Update
     *
     * Method is called after user update the CMS.
     *
     * @param   Event $event  The event instance.
     *
     * @return  void
     *
     * @since   1.0.0
     *
     */
    public function onJoomlaAfterUpdate(Event $event)
    {
        $name = "Joomla!";
        $type = "core";
		
		$this->update_installs_securitycheckpro_storage("installs",$name,$type);
		$this->update_installs_securitycheckpro_storage("installs_remote",$name,$type);
		
	}	
	
    /**
     * Check installed/updated extensions to use this info in file integrity management.
     *
     * @param \Joomla\CMS\Installer\Installer   $installer Installer instance used in the process.
     * @param int|false   						$eid       Extension ID on success; false on failure.
     *
     * @return void
     */
    public function onExtensionAfterInstall($installer, $eid)
    {
		$manifest = $installer->get('manifest');
		
		if ($manifest === null) {
			return;
		}
            
        $name = $manifest->name->__toString();
        $type = $manifest->attributes()['type']->__toString();
		
		$this->update_installs_securitycheckpro_storage("installs",$name,$type);
		$this->update_installs_securitycheckpro_storage("installs_remote",$name,$type);		
        
    }
	
	/**
     * Writes a file into the scan folder to know that we must update the vulnerabilities database
     *
     *
     * @return  void
     *     
     */

	function write_file()
    {
				
		$file_manag = @fopen($this->scan_path."update_vuln_table.php", 'ab');		
		
		if (empty($file_manag)) {
            return;
        }
	
		@fclose($file_manag);
    }
	
	/**  
     *
     * @param \Joomla\CMS\Installer\Installer   $installer Installer instance used in the process.
     * @param int|false   						$eid       Extension ID on success; false on failure.
     *
     * @return void
    */	
	function onExtensionAfterUninstall($installer, $eid)
    {		
		// Let's write a file to tell securitycheck that a new extension has been uninstalled. This is needed by /com_securitycheckpro/backend/models/securitycheckpros.php		
		$this->write_file();
	}
	
	/**  
     *
     * @param \Joomla\CMS\Installer\Installer   $installer Installer instance used in the process.
     * @param int|false   						$eid       Extension ID on success; false on failure.
     *
     * @return void
    */		
	function onExtensionAfterUpdate($installer, $eid)
    {		
		// Let's write a file to tell securitycheck that a new extension has been updated. This is needed by /com_securitycheckpro/backend/models/securitycheckpros.php		
		$this->write_file(); 
	}
        
}
