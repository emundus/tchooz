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
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FirewallconfigModel;
use Joomla\Component\Users\Administrator\Model\UserModel;
use Joomla\CMS\User\User;
use Joomla\CMS\Mail\MailerFactoryInterface;

class Securitycheckpro extends CMSPlugin 
{
    private $pro_plugin = null;
	
	private $dbtype = "mysql";
	
	private $scan_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;	
	
		
	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
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
        
        
    /* Funci�n para borrar logs */
    function delete_logs()
    {
		// Si la variable "pro_plugin" est� vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
		
        $db = Factory::getDBO();
        
        (int) $track_actions_delete_period = $this->pro_plugin->getValue('delete_period', 0, 'pro_plugin');
        (int) $scp_delete_period = $this->pro_plugin->getValue('scp_delete_period', 60, 'pro_plugin');
        
        // Borramos los logs de Track Actions si el par�metro est� establecido as�
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
            }catch (Exception $e)
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
            }catch (Exception $e)
            {
                
            }
        }
                
    }
    
    /* Funci�n para grabar los logs en la BBDD */
    function grabar_log($logs_attacks,$ip,$tag_description,$description,$type,$uri,$original_string,$username,$component)
    {
        
        if ($logs_attacks) {
			$this->pro_plugin = new BaseModel();
            $db = Factory::getDBO();
            
            /* El par�metro 'blacklist_email' indica si se manda un correo cuando una ip aparece en la lista negra. Inicialmente lo forzamos a '1' para que siempre se mande un email, excepto cuando el par�metro '$tag_description' sea igual a 'IP_BLOCKED', que se cuando se comprueba y, en su caso, modifica este valor */
            $blacklist_email = 1;
        
            /* El par�metro 'send_email_inspector' indica si hay que mandar un correo en las redirecciones 404 */
            $send_email_inspector = 0;
            
            // Sanitizamos las entradas
            $ip = htmlspecialchars($ip);
            $ip = $db->escape($ip);
			if (!is_null($username)) {
				$username = htmlspecialchars($username);
				$username = $db->escape($username);
			}            
            $tag_description = htmlspecialchars($tag_description);
            $tag_description = $db->escape($tag_description);
            $description = htmlspecialchars($description);
            $description = $db->escape($description);
            $type = htmlspecialchars($type);
            $type = $db->escape($type);
			$type = substr($type,0,50);
            $uri = htmlspecialchars($uri);
            $uri = $db->escape($uri);
			// Truncate the uri string
			$uri = substr($uri,0,100);
            $component = htmlspecialchars($component);            
            $component = $db->escape($component);
			$component = substr($component,0,150);
            // Guardamos el string original en formato base64 para evitar problemas de seguridad; adem�s, lo debemos filtrar en el archivo default.php
            //$original_string = filter_var($original_string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);                
            $original_string = base64_encode($original_string);
        
            // Consultamos el �ltimo log para evitar duplicar entradas
            $query = "SELECT tag_description,original_string,ip from #__securitycheckpro_logs WHERE id=(SELECT MAX(id) from #__securitycheckpro_logs)" ;            
            $db->setQuery($query);
            $row = $db->loadRow();
            
            // Consultamos el n�mero de logs para ver si se supera el l�mite establecido en el apartado 'log_limits_per_ip_and_day'
            (int) $logs_per_ip = $this->pro_plugin->getValue('log_limits_per_ip_and_day', 30, 'pro_plugin');
            try
            {
                $query = "SELECT COUNT(*) from #__securitycheckpro_logs WHERE ip='{$ip}' AND (DATE(NOW()) = DATE(time))" ;
                $db->setQuery($query);
                (int) $logs_recorded = $db->loadResult();
            }catch (Exception $e)
            {
                $logs_recorded = 0;
            }
						
			if (!empty($row))
			{      			
				$result_tag_description = $row['0'];
				$result_original_string = $row['1'];
				$result_ip = $row['2'];
			} else
			{
				$result_tag_description = '---';
				$result_original_string = '---';
				$result_ip = '---';				
			}
			
			           
            /* Cargamos el lenguaje del sitio */
            $lang = Factory::getLanguage();
            $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
            
            		
			// Obtenemos el timezone de Joomla y sobre esa informaci�n calculamos el timestamp
			$config = Factory::getConfig();
			$offset = $config->get('offset');
			
			if (empty($offset))
			{
				$offset = 'UTC';
			}
			
			$date = new \DateTime("now", new \DateTimeZone($offset) );
			$timestamp_joomla_timezone = $date->format('Y-m-d H:i:s');
			            
                        
            if (((!($result_tag_description == $tag_description)) || (!($result_original_string == $original_string)) || (!($result_ip == $ip))) && (($logs_recorded < $logs_per_ip) || ($logs_per_ip == 0))) {
                
                try
                {
                    $sql = "INSERT INTO #__securitycheckpro_logs (ip, username, time, tag_description, description, type, uri, component, original_string) VALUES ('{$ip}', '{$username}', '{$timestamp_joomla_timezone}', '{$tag_description}', '{$description}', '{$type}', '{$uri}', '{$component}', '{$original_string}')";
                    $db->setQuery($sql);
                    $db->execute();
                }catch (Exception $e)
                {
                    return false;
                }
                                
                /* Si el par�metro '$tag_description' es 'IP_BLOCKED', comprobamos el campo 'blacklist_email' para ver si tenemos que mandar un correo 
                electr�nico cuando se bloquea un ip en la lista negra */
                if (($tag_description == 'IP_BLOCKED') || ($tag_description == 'IP_BLOCKED_DINAMIC')) {
                    $blacklist_email = $this->pro_plugin->getValue('blacklist_email', 0, 'pro_plugin');
                }
                
                $send_email_inspector = $this->pro_plugin->getValue('send_email_inspector', 0, 'pro_plugin');
                                
                // �Mandar email?
                $email_active = $this->pro_plugin->getValue('email_active', 0, 'pro_plugin');
                
                /* Cargamos el lenguaje del sitio */
                $lang = Factory::getLanguage();
                $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
        
                if ($email_active) {    
                    if ((($tag_description != 'IP_BLOCKED') && ($tag_description != 'URL_FORBIDDEN_WORDS')) || (($tag_description == 'IP_BLOCKED') && ($blacklist_email))  || (($tag_description == 'URL_FORBIDDEN_WORDS') && ($send_email_inspector))) {
                        $email_subject = $lang->_('COM_SECURITYCHECKPRO_RULE') . $lang->_('COM_SECURITYCHECKPRO_' .$tag_description) . "<br />" . $lang->_('COM_SECURITYCHECKPRO_USERNAME') . $username . "<br />" . "IP: " . $ip;
                        $this->mandar_correo($email_subject);
                    }
                    
                }
            }
        }    
        
    }
    
    /* Determina si un valor est� codificado en base64 */    
    function is_base64($value)
    {
        $res = false; // Determines if any character of the decoded string is between 32 and 126, which should indicate a non valid european ASCII character
    
        $min_len = mb_strlen($value)>7;
                
        if ($min_len) {
            
            $decoded = base64_decode(chunk_split($value));
            $string_caracteres = str_split($decoded); 
            if (empty($string_caracteres)) {
                return false;  // It�s not a base64 string!
            }else
            {
                foreach ($string_caracteres as $caracter)
                {
                    if ((empty($caracter)) || (ord($caracter)<32) || (ord($caracter)>126)) { // Non-valid ASCII value
                        return false; // It�s not a base64 string!
                    }
                }
            }
            
            $res = true; // It�s a base64 string!
        }
        
        return $res;
    }
    
    /* Funci�n que realiza la misma funci�n que mysql_real_escape_string() pero sin necesidad de una conexi�n a la BBDD */
    function escapa_string($value)
    {
    
        $search = array("\x00", "'", "\"", "\x1a");
        $replace = array("\\x00", "\'", "\\\"", "\\\x1a");
		    
        return str_ireplace($search, $replace, $value);
    }
	
	// Check if a string is html
	function isHTML($string){
	 return $string != strip_tags($string) ? true:false;
	}
    
    // Chequea si la extensi�n pasada como argumento es vulnerable
    private function check_extension_vulnerable($option)
    {
        
        // Inicializamos las variables
        $vulnerable = false;
        
        // Creamos el nuevo objeto query
        $db = Factory::getDBO();
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
    
    /* Apply firewall filters */
    function apply_filters($ip,$string,$methods_options,$a,$request_uri,&$modified,$check,$logs_attacks,$option) 
    {
        $string_sanitized='';
        $base64=false;
        $pageoption='';
        $existe_componente = false;
        $username = '---';
        $component = '';
        $extension_vulnerable = false;
        $is_array = false;
                
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
        }else
        {
            $user_agent = 'Not set';
        }
        
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
        }else
        {
            $referer = 'Not set';
        }
		
		$app = Factory::getApplication();
        $is_admin = $app->isClient('administrator');
        
        $user = Factory::getUser();
        if (!$user->guest) {
            $username = $user->username;
        }
        
        $pageoption = $option;
		                
        // Chequeamos si hemos de excluir los componentes vulnerables de las excepciones
        $exclude_exceptions_if_vulnerable = $this->pro_plugin->getValue('exclude_exceptions_if_vulnerable', 1, 'pro_plugin');
        
        // Si hemos podido extraer el componente implicado en la petici�n, vemos si la versi�n instalada es vulnerable
        if ((!empty($option)) && ($exclude_exceptions_if_vulnerable)) {
            $extension_vulnerable = $this->check_extension_vulnerable($option);                                    
        }
                
        /* Excepciones */
        $base64_exceptions = $this->pro_plugin->getValue('base64_exceptions', '', 'pro_plugin');
        $strip_tags_exceptions = $this->pro_plugin->getValue('strip_tags_exceptions', '', 'pro_plugin');
        $duplicate_backslashes_exceptions = $this->pro_plugin->getValue('duplicate_backslashes_exceptions', '', 'pro_plugin');
        $line_comments_exceptions = $this->pro_plugin->getValue('line_comments_exceptions', '', 'pro_plugin');
        $sql_pattern_exceptions = $this->pro_plugin->getValue('sql_pattern_exceptions', '', 'pro_plugin');
        $if_statement_exceptions = $this->pro_plugin->getValue('if_statement_exceptions', '', 'pro_plugin');
        $using_integers_exceptions = $this->pro_plugin->getValue('using_integers_exceptions', '', 'pro_plugin');
        $escape_strings_exceptions = $this->pro_plugin->getValue('escape_strings_exceptions', '', 'pro_plugin');
        $lfi_exceptions = $this->pro_plugin->getValue('lfi_exceptions', '', 'pro_plugin');
        $check_header_referer = $this->pro_plugin->getValue('check_header_referer', 1, 'pro_plugin');
        $strip_all_tags = $this->pro_plugin->getValue('strip_all_tags', 1, 'pro_plugin');
        $tags_to_filter = $this->pro_plugin->getValue('tags_to_filter', 'applet,body,bgsound,base,basefont,embed,frame,frameset,head,html,id,iframe,ilayer,layer,link,meta,name,object,script,style,title,xml,svg,input,a', 'pro_plugin');
        
        /* Base64 check */
        if ($check) {
            /* Chequeamos si el componente est� en la lista de excepciones */
            if (!(strstr($base64_exceptions, $pageoption))) {
                $is_base64 = $this->is_base64($string);
                if ($is_base64) {                    
                    $decoded = base64_decode(chunk_split($string));
                    $base64=true;
                    $string = $decoded;
                }
            }
        }
		
		// Prevent arbitrary strings
		$detect_arbitrary_strings = $this->pro_plugin->getValue('detect_arbitrary_strings', 0, 'pro_plugin');
		if ($detect_arbitrary_strings) {							
			// tipo 'cxDJopwYKHbTcy', 'HXGrIcgvt oFcsEdBAMhYW' pero no si todas las letras est�n en may�scula
			$mixed_case = $string;
			$lower_case = strtolower($mixed_case);
			$similar = similar_text($mixed_case, $lower_case);
				
			$number_of_capital_letters = strlen($mixed_case) - $similar;
			
			$string_with_no_spaces = str_replace(' ', '', $string);
			$string_with_no_spaces_lenght = strlen($string_with_no_spaces);
												
			if ( ($string_with_no_spaces_lenght < 30) && ($string_with_no_spaces_lenght <> $number_of_capital_letters) && ($similar >= 4) && ($number_of_capital_letters > 5) ) {
				$this->grabar_log($logs_attacks, $ip, 'ARBITRARY_STRING', '[' .$methods_options .':' .$a .']', 'SPAM_PROTECTION', $request_uri, $string, $username, $pageoption);
				// Actualizamos la lista negra din�mica
				$this->actualizar_lista_dinamica($ip);
				$this->redirection(403, "", true);
			}
		}
        
		/* Regex checker: https://regex101.com/
			https://www.functions-online.com/preg_match.html
		*/
                                            
        /* XSS Prevention */
        //Strip html and php tags from string
        if ((!(strstr($strip_tags_exceptions, $pageoption)) || $extension_vulnerable) && !(strstr($strip_tags_exceptions, '*'))) {
            // If we are in backend, we must not filter all tags to avoid false positives even when creating/modifying articles.
			if (preg_match("/(\%[a-zA-Z0-9]{2}|0x{4,})/", $string)) {
				// Is this an encoding attack?
				$encoding_array = array("%3C","%253C","%3E","%253E","%2F","%252F","%2525");
				foreach($encoding_array as $encoded_word) {
                    if ((is_string($string)) && (!empty($encoded_word))) {
                        if (substr_count(strtolower($string), strtolower($encoded_word))) {
							$this->grabar_log($logs_attacks, $ip, 'TAGS_STRIPPED', '[' .$methods_options .':' .$a .']', 'XSS_BASE64', $request_uri, $string, $username, $pageoption);
							/* Actualizamos la lista negra din�mica */
							$this->actualizar_lista_dinamica($ip);
							$this->redirection(403, "", true);							
						}
					}
				}
			}
			
            if ($is_admin) {                
                $strip_all_tags = 2;
                $tags_to_filter = 'applet,body,bgsound,base,basefont,embed,frame,frameset,head,html,id,iframe,ilayer,layer,link,meta,name,object,script,xml,svg';
            }
        
            if ($strip_all_tags == 1) {
                // Filtering all tags    
                $string_sanitized = strip_tags($string);                
            }else
            {                        
                // Decoding of html entities (if any) to match patterns (less and more than signs)
                $string = html_entity_decode($string);
                $tags_to_filter_final = array();
                $tags_array = explode(",", $tags_to_filter);                            
                foreach ($tags_array as $tag)
                {
                    $tags_to_filter_final[] = "<" . $tag;
                    $tags_to_filter_final[] = $tag . "/>";                        
                }
					
                
                $string_sanitized = str_ireplace($tags_to_filter_final, "", $string);                    
            }
			
			// Filter string in brackets i.e. [URL=https://www.malicioussite.com]malicious_link[/URL]
			$brackets_to_filter_final = array();
			$brackets_to_filter = 'url';
			$brackets_array = explode(",", $brackets_to_filter);  
			foreach ($brackets_array as $tag)
               {
                $brackets_to_filter_final[] = "[" . $tag;
                $brackets_to_filter_final[] = "[/" . $tag;                        
            }
						
			$string_sanitized = str_ireplace($brackets_to_filter_final, "", $string_sanitized);
            
            if (strcmp($string_sanitized, $string) !== 0) { //Se han eliminado caracteres; escribimos en el log
                if ($base64) {
                    $this->grabar_log($logs_attacks, $ip, 'TAGS_STRIPPED', '[' .$methods_options .':' .$a .']', 'XSS_BASE64', $request_uri, $string, $username, $pageoption);
                }else
                {
                    $angle_position = strpos($string, "<");                    
                    if ($angle_position !== false) {
                        $longitud = strlen($string);                                        
                        $string = substr($string, $angle_position, $longitud - $angle_position);                        
                    }            
                    $this->grabar_log($logs_attacks, $ip, 'TAGS_STRIPPED', '[' .$methods_options .':' .$a .']', 'XSS', $request_uri, $string, $username, $pageoption);
                }
                $string = $string_sanitized;    
                $modified = true;
                /* Hemos de cortar la conexi�n, independientemente de lo que tengamos configurado en el par�metro "redirect_after_attack". Esto es necesario porque algunos ataques XSS llegan a producirse, aunque sean detectados, al haber una redirecci�n */
				/* Actualizamos la lista negra din�mica */
				$this->actualizar_lista_dinamica($ip);
                $this->redirection(403, "", true);
            } else 
            {
                $xss_forbidden_words_array = array("onload","onfocus","autofocus","javascript:","onmouseover","onerror","FSCommand","onAbort","onActivate","onAfterPrint","onAfterUpdate","onBeforeActivate","onBeforeCopy","onBeforeCut","onBeforeDeactivate","onBeforeEditFocus","onBeforePaste","onBeforePrint","onBeforeUnload","onBeforeUpdate","onBegin","onBlur","onBounce","onCellChange","onChange","onClick","onContextMenu","onControlSelect","onCopy","onCut","onDataAvailable","onDataSetChanged","onDataSetComplete","onDblClick","onDeactivate","onDrag","onDragEnd","onDragLeave","onDragEnter","onDragOver","onDragDrop","onDragStart","onDrop","onErrorUpdate","onFilterChange","onFinish","onFocusIn","onFocusOut","onHashChange","onHelp","onInput","onKeyDown","onKeyPress","onKeyUp","onLayoutComplete","onLoseCapture","onMediaComplete","onMediaError","onMessage","onMouseDown","onMouseEnter","onMouseLeave","onMouseMove","onMouseOut","onMouseOut","onMouseUp","onMouseWheel","onMove","onMoveEnd","onMoveStart","onOffline","onOnline","onOutOfSync","onPaste","onPause","onPopState","onProgress","onPropertyChange","onReadyStateChange","onRedo","onRepeat","onReset","onResize","onResizeEnd","onResizeStart","onResume","onReverse","onRowsEnter","onRowExit","onRowDelete","onRowInserted","onScroll","onSeek","onSelect","onSelectionChange","onSelectStart","onStart","onSyncRestored","onSubmit","onTimeError","onTimeError","onUndo","onUnload","onURLFlip","seekSegmentTime");
                                
                foreach($xss_forbidden_words_array as $word) {
                    if ((is_string($string)) && (!empty($word))) {
                        if (substr_count(strtolower($string), strtolower($word))) {
                            $string_sanitized = str_replace($word, "", $string);
                            $this->grabar_log($logs_attacks, $ip, 'TAGS_STRIPPED', '[' .$methods_options .':' .$a .']', 'XSS', $request_uri, $string, $username, $pageoption);
                            $modified = true;                                                    
                            /* Hemos de cortar la conexi�n, independientemente de lo que tengamos configurado en el par�metro "redirect_after_attack". Esto es necesario porque algunos ataques XSS llegan a producirse, aunque sean detectados, al haber una redirecci�n */
							/* Actualizamos la lista negra din�mica */
							$this->actualizar_lista_dinamica($ip);
                            $this->redirection(403, "", true);
                        }
                    }                    
                }                
            }
        }
                        
        /* SQL Injection Prevention */
		/* https://hackersonlineclub.com/sql-injection-cheatsheet/ */
		/* https://www.netsparker.com/blog/web-security/sql-injection-cheat-sheet/ */
        if (!$modified) {
            if ($is_admin) {                
                $duplicate_backslashes_exceptions = "*";
                $line_comments_exceptions = "*";
                $if_statement_exceptions = "*";
                $using_integers_exceptions = "*";                
            }
			
			            
            if (!(strstr($duplicate_backslashes_exceptions, $pageoption)) && !(strstr($duplicate_backslashes_exceptions, '*'))) {
                // Prevents duplicate backslashes
                if (PHP_VERSION_ID < 50400 && get_magic_quotes_gpc())
				{
                    $string_sanitized = stripslashes($string);
                    if (strcmp($string_sanitized, $string) !== 0) { //Se han eliminado caracteres; escribimos en el log
                        if ($base64) {
                            $this->grabar_log($logs_attacks, $ip, 'DUPLICATE_BACKSLASHES', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION_BASE64', $request_uri, $string, $username, $pageoption);
                        }else
                        {
                            $this->grabar_log($logs_attacks, $ip, 'DUPLICATE_BACKSLASHES', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION', $request_uri, $string, $username, $pageoption);
                        }
                                        
                        if (strlen($string_sanitized)>0) {
                              $string = $string_sanitized;
                        }
                    }
                }
            }
                            
            if (!(strstr($line_comments_exceptions, $pageoption)) && !(strstr($line_comments_exceptions, '*')) && ($pageoption != 'com_users') && (!$modified)) {
                // Line Comments
                $lineComments = array("/--/","/[^\=\s]#/","/\/\*/","/\*\//","/(?=(%2f|\/)).+\*\*/i");
                $string_sanitized = preg_replace($lineComments, "", $string);
				                                                    
                if (strcmp($string_sanitized, $string) !== 0) { //Se han eliminado caracteres; escribimos en el log
                    if ($base64) {
                        $this->grabar_log($logs_attacks, $ip, 'LINE_COMMENTS', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION_BASE64', $request_uri, $string, $username, $pageoption);
                    }else
                    {
                        $this->grabar_log($logs_attacks, $ip, 'LINE_COMMENTS', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION', $request_uri, $string, $username, $pageoption);
                    }
                                    
                    $string = $string_sanitized;
                    $modified = true;
                }
            }
			                            
            $sqlpatterns = array("/delete(?=(\s|\+|%20|%u0020|%uff00)).+\b(from)\b(?=(\s|\+|%20|%u0020|%uff00))/i","/update(?=(\s|\+|%20|%u0020|%uff00)).+\b(set)\b(?=(\s|\+|%20|%u0020|%uff00))/i",
            "/drop(?=(\s|\+|%20|%u0020|%uff00)).+\b(database|user|table|index)\b(?=(\s|\+|%20|%u0020|%uff00))/i",
            "/insert((\s|\+|%20|%u0020|%uff00|\/|%2f))+(values|set|select)\b((\s|\+|%20|%u0020|%uff00))*/i", "/union(?=(\s|\+|%20|%u0020|%uff00|\/|%2f)).+(select)\b((\s|\+|%20|%u0020|%uff00))*/i",
            "/select(?=(\s|\+|%20|%u0020|%uff00)).+\b(from|ascii|char|concat|case)\b(?=(\s|\+|%20|%u0020|%uff00))/i","/benchmark\(.*\)/i",
            "/md5\(.*\)/i","/sha1\(.*\)/i","/ascii\(.*\)/i","/concat\(.*\)/i","/char\(.*\)/i",
            "/substring\(.*\)/i","/where(\s|\+|%20|%u0020|%uff00)(or|and)(\s|\+|%20|%u0020|%uff00)(\w+)(=|<|>|<=|>=)(\w+)/i","/(or|and)(\s|\+|%20|%u0020|%uff00)(sleep)/i","/(\s|\+|%20|%u0020|%uff00)(pg_sleep)/i","/waitfor(\s|\+|%20|%u0020|%uff00)(delay)/i","/(\s|\+|%20|%u0020|%uff00)(or|and)(\s|\+|%20|%u0020|%uff00)(\()?((\'|%27)+(\d+)(\'|%27)+(=|%3d)(\'|%27)*\d+|((\')+(\D+)(\')+=(\')*\D+))/i","/=dbms_pipe\.receive_message/i","/order by \d+/i");              
                                            
            if ((!(strstr($sql_pattern_exceptions, $pageoption)) || $extension_vulnerable) && !(strstr($sql_pattern_exceptions, '*')) && (!$modified)) { 
				try {
				    $string_sanitized = preg_replace($sqlpatterns, "", $string);
				} catch (Exception $e)
				{
					return;
				}  
                        
                if (strcmp($string_sanitized, $string) !== 0) { //Se han eliminado caracteres; escribimos en el log    
                    if ($base64) {
                        $this->grabar_log($logs_attacks, $ip, 'SQL_PATTERN', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION_BASE64', $request_uri, $string, $username, $pageoption);
                    }else
                    {
                        $this->grabar_log($logs_attacks, $ip, 'SQL_PATTERN', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION', $request_uri, $string, $username, $pageoption);
                    }
                                    
                    $string = $string_sanitized;
                    $modified = true;
					/* Actualizamos la lista negra din�mica */
					$this->actualizar_lista_dinamica($ip);
                    $this->redirection(403, "", true);
                }    
            }
                            
            //IF Statements
            $ifStatements = array("/if\(.*,.*,.*\)/i");
                                
            if ((!(strstr($if_statement_exceptions, $pageoption)) || $extension_vulnerable) && !(strstr($if_statement_exceptions, '*')) && (!$modified)) {  
				try { 
				    $string_sanitized = preg_replace($ifStatements, "", $string);
				} catch (Exception $e)
				{
					return;
				}  	
                
                        
                if (strcmp($string_sanitized, $string) <> 0) { //Se han eliminado caracteres; escribimos en el log
                    if ($base64) {
                        $this->grabar_log($logs_attacks, $ip, 'IF_STATEMENT', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION_BASE64', $request_uri, $string, $username, $pageoption);
                    }else
                    {
                        $this->grabar_log($logs_attacks, $ip, 'IF_STATEMENT', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION', $request_uri, $string, $username, $pageoption);
                    }                        
                                    
                    $string = $string_sanitized;
                    $modified = true;
                }
            }
                            
            //Using Integers			
            $usingIntegers = array("/select(?=(\s|\+|%20|%u0020|%uff00)).+(0x)/i","/@@/i","/||/i");
                                
            if (!(strstr($using_integers_exceptions, $pageoption)) && !(strstr($using_integers_exceptions, '*')) && (!$modified)) {    
               
				try {
				    $string_sanitized = preg_replace($usingIntegers, "", $string);
				} catch (Exception $e)
				{
					return;
				}  			  
                                
                if (strcmp($string_sanitized, $string) !== 0) { //Se han eliminado caracteres; escribimos en el log
                    if ($base64) {
                          $this->grabar_log($logs_attacks, $ip, 'INTEGERS', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION_BASE64', $request_uri, $string, $username, $pageoption);
                    }else
                    {
                           $this->grabar_log($logs_attacks, $ip, 'INTEGERS', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION', $request_uri, $string, $username, $pageoption);
                    }
                                    
                        $string = $string_sanitized;
                        $modified = true;
                }
            }
			
			$is_html = $this->isHTML($string);
			$is_json = 1;
			
			try {
				$obj = json_decode($string);				
				if (empty($obj) ) {					
					$is_json = 0;
				}				
			} catch (Exception $e)
			{				
				$is_json = 0;
			}  
						            
			if ( (!$is_html) && (!$is_json) ) {
				if (!(strstr($escape_strings_exceptions, $pageoption)) && !(strstr($escape_strings_exceptions, '*')) && (!$modified)) {				
									
					try {
						$string_sanitized = $this->escapa_string($string);
					} catch (Exception $e)
					{
						return;
					}  
				   
								
					if (strcmp($string_sanitized, $string) !== 0) { //Se han a�adido barras invertidas a ciertos caracteres; escribimos en el log                            
						if ($base64) {
							$this->grabar_log($logs_attacks, $ip, 'BACKSLASHES_ADDED', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION_BASE64', $request_uri, $string, $username, $pageoption);
						}else
						{
							$this->grabar_log($logs_attacks, $ip, 'BACKSLASHES_ADDED', '[' .$methods_options .':' .$a .']', 'SQL_INJECTION', $request_uri, $string, $username, $pageoption);
						}
										
						if (strlen($string_sanitized)>0) {
							$string = $string_sanitized;
						}
						$modified = true;
					}
				}
			}
                                
        }    
		                        
        /* LFI Prevention */
        $lfiStatements = array("/\.\.\//","/\?\?\?/");
        if ((!(strstr($lfi_exceptions, $pageoption)) || $extension_vulnerable) && !(strstr($lfi_exceptions, '*'))) {
            if (!$modified) {                        
                $string_sanitized = preg_replace($lfiStatements, '', $string);
				if (strcmp($string_sanitized, $string) !== 0) { //Se han eliminado caracteres; escribimos en el log
                    if ($base64) {
                             $this->grabar_log($logs_attacks, $ip, 'LFI', '[' .$methods_options .':' .$a .']', 'LFI_BASE64', $request_uri, $string, $username, $pageoption);
                    }else
                    {
                            $this->grabar_log($logs_attacks, $ip, 'LFI', '[' .$methods_options .':' .$a .']', 'LFI', $request_uri, $string, $username, $pageoption);
                    }
                                    
                    $string = $string_sanitized;
                    $modified = true;
                }
            }
        }
                        
                /* Header and user-agent check */
        if ((!$modified) && ($check_header_referer)) {
            $modified = $this->check_header_and_user_agent($logs_attacks, $user, $user_agent, $referer, $ip, $methods_options, $a, $request_uri, $sqlpatterns, $ifStatements, $usingIntegers, $lfiStatements, $username, $pageoption);
        }
    }
    
    /* Funci�n para 'sanitizar' un string. Devolvemos el string "sanitizado" y modificamos la variable "modified" si se ha modificado el string */
    function cleanQuery($ip,$string,$methods_options,$a,$request_uri,&$modified,$check,$logs_attacks,$option)
    {
                
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
            if ((!(is_array($string))) && (mb_strlen($string)>0) && ($pageoption != '')) {
                $this->apply_filters($ip, $string, $methods_options, $a, $request_uri, $modified, $check, $logs_attacks, $option);
            }                
        }
        
        return $string;
    }
    
    /* Funci�n que chequea el 'Header' y el 'user-agent' en busca de ataques */
    function check_header_and_user_agent($logs_attacks,$user,$user_agent,$referer,$ip,$methods_options,$a,$request_uri,$sqlpatterns,$ifStatements,$usingIntegers,$lfiStatements,$username,$pageoption)
    {
        $modified = false; 
        
        if ($user->guest) {
            /****** User-agent checks *****/
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                /* XSS Prevention in USER_AGENT*/
                //Strip html and php tags from string
                $header_sanitized = strip_tags($user_agent);
                                
                if (strcmp($header_sanitized, $user_agent) !== 0) { //Se han eliminado caracteres; escribimos en el log
                    $this->grabar_log($logs_attacks, $ip, 'TAGS_STRIPPED', '[' .$methods_options .':' .$a .']', 'USER_AGENT_MODIFICATION', $request_uri, $user_agent, $username, $pageoption);
                    
                    $modified = true;
                }
                /* SQL Injection in USER_AGENT*/
                $header_sanitized = preg_replace($sqlpatterns, "", $user_agent);
                if (strcmp($header_sanitized, $user_agent) !== 0) { //Se han eliminado caracteres; escribimos en el log
                    $this->grabar_log($logs_attacks, $ip, 'SQL_PATTERN', '[' .$methods_options .':' .$a .']', 'USER_AGENT_MODIFICATION', $request_uri, $user_agent, $username, $pageoption);
                    
                    $modified = true;
                }
                /* SQL Injection in USER_AGENT*/
                $header_sanitized = preg_replace($ifStatements, "", $user_agent);
                if (strcmp($header_sanitized, $user_agent) !== 0) { //Se han eliminado caracteres; escribimos en el log
                    $this->grabar_log($logs_attacks, $ip, 'IF_STATEMENT', '[' .$methods_options .':' .$a .']', 'USER_AGENT_MODIFICATION', $request_uri, $user_agent, $username, $pageoption);
                    
                    $modified = true;
                } 
                /* SQL Injection in USER_AGENT*/
                $header_sanitized = preg_replace($usingIntegers, "", $user_agent);
                if (strcmp($header_sanitized, $user_agent) !== 0) { //Se han eliminado caracteres; escribimos en el log
                    $this->grabar_log($logs_attacks, $ip, 'INTEGERS', '[' .$methods_options .':' .$a .']', 'USER_AGENT_MODIFICATION', $request_uri, $user_agent, $username, $pageoption);
                    
                    $modified = true;
                } 
                /* LFI in USER_AGENT*/
                $header_sanitized = preg_replace($lfiStatements, '', $user_agent);
                if (strcmp($header_sanitized, $user_agent) !== 0) { //Se han eliminado caracteres; escribimos en el log
                    $this->grabar_log($logs_attacks, $ip, 'LFI', '[' .$methods_options .':' .$a .']', 'USER_AGENT_MODIFICATION', $request_uri, $user_agent, $username, $pageoption);
                    
                    $modified = true;
                }
            }
            /*****
    
       * Referer checks 
*****/
            if (!$modified) {
                if (isset($_SERVER['HTTP_REFERER'])) {
                    /* XSS Prevention in REFERER*/
                    //Strip html and php tags from string
                    $header_sanitized = strip_tags($referer);
                    if (strcmp($header_sanitized, $referer) !== 0) { //Se han eliminado caracteres; escribimos en el log
                        $this->grabar_log($logs_attacks, $ip, 'TAGS_STRIPPED', '[' .$methods_options .':' .$a .']', 'REFERER_MODIFICATION', $request_uri, $referer, $username, $pageoption);
                    
                        $modified = true;
                    } 
                    /* SQL Injection in REFERER*/
                    $header_sanitized = preg_replace($sqlpatterns, "", $referer);
                    if (strcmp($header_sanitized, $referer) !== 0) { //Se han eliminado caracteres; escribimos en el log
                        $this->grabar_log($logs_attacks, $ip, 'SQL_PATTERN', '[' .$methods_options .':' .$a .']', 'REFERER_MODIFICATION', $request_uri, $referer, $username, $pageoption);
                    
                        $modified = true;
                    }
                    /* SQL Injection in REFERER*/
                    $header_sanitized = preg_replace($ifStatements, "", $referer);
                    if (strcmp($header_sanitized, $referer) !== 0) { //Se han eliminado caracteres; escribimos en el log
                        $this->grabar_log($logs_attacks, $ip, 'IF_STATEMENT', '[' .$methods_options .':' .$a .']', 'REFERER_MODIFICATION', $request_uri, $referer, $username, $pageoption);
                    
                        $modified = true;
                    } 
                    /* LFI in REFERER*/
                    $header_sanitized = preg_replace($lfiStatements, '', $referer);
                    if (strcmp($header_sanitized, $referer) !== 0) { //Se han eliminado caracteres; escribimos en el log
                        $this->grabar_log($logs_attacks, $ip, 'LFI', '[' .$methods_options .':' .$a .']', 'REFERER_MODIFICATION', $request_uri, $referer, $username, $pageoption);
                    
                        $modified = true;
                    }
                }
            }
        }
        return $modified;
    }
    
    /* Funci�n para contar el n�mero de palabras "prohibidas" de un string*/
    function second_level($request_uri,$string,$a,&$found,$option)
    {
        $occurrences=0;
        $string_sanitized=$string;
        $application = Factory::getApplication();
        $user = Factory::getUser();
        $dbprefix = $application->getCfg('dbprefix');
        $pageoption='';
        $existe_componente = false;
        $extension_vulnerable = false;
        
        $app = Factory::getApplication();
        $is_admin = $app->isClient('administrator');
        
        // Consultamos si hemos de aplicar las reglas al usuario en funci�n de su pertenencia a grupos.
        $user = Factory::getUser();
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
                    if (PHP_VERSION_ID < 50400 && get_magic_quotes_gpc())
					{
                        $string_sanitized = stripslashes($string);
                    }
                    // Line Comments
                    $lineComments = array("/--/","/[^\=]#/","/\/\*/","/\*\//");
                    $string_sanitized = preg_replace($lineComments, "", $string_sanitized);
                
                    $string_sanitized = $this->escapa_string($string);
                                                            
                    $suspect_words = explode(',', $second_level_words);
                    foreach ($suspect_words as $word)
                    {
                        if ((is_string($string_sanitized)) && (!empty($word)) && (!empty($string_sanitized))) {
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
        
        
    /* Funci�n para chequear si una ip pertenece a una lista din�mica almacenada en una BBDD */
    function chequear_ip_en_lista_dinamica($ip,$blacklist_counter)
    {
        // Creamos el nuevo objeto query
        $db = Factory::getDBO();
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
            } catch (Exception $e)
            {
                return false;
            }            
                    
            if ($result) {
                return true;
            } else
            {
                return false;
            }
        } else {
            return false;
        }	
        
    }
    
    /* Si el tiempo transcurrido desde que se grab� la entrada supera el establecido en el plugin, eliminamos esa entrada de la base de datos */
    function pasar_a_historico($counter_time)
    {
    
        // Creamos el nuevo objeto query
        $db = Factory::getDBO();
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
    
    /* Funci�n que a�ade una IP a la lista negra din�mica */
    function actualizar_lista_dinamica($attack_ip)
    {
		// Creamos el nuevo objeto query
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
		
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
        
        $dynamic_blacklist = $this->pro_plugin->getValue('dynamic_blacklist', 1, 'pro_plugin');
        
        // Chequeamos si la IP tiene un formato v�lido
        $ip_valid = filter_var($attack_ip, FILTER_VALIDATE_IP);
        
        // Sanitizamos la entrada
        $attack_ip = $db->escape($attack_ip);
                
        // Validamos si el valor devuelto es una direcci�n IP v�lida y la lista negra din�mica est� habilitada
        if ((!empty($attack_ip)) && ($ip_valid) && ($dynamic_blacklist)) {
            try 
            {     
				if (strstr($this->dbtype,"mysql")) {
					 $query = "INSERT INTO #__securitycheckpro_dynamic_blacklist (ip, timeattempt) VALUES ('{$attack_ip}', NOW()) ON DUPLICATE KEY UPDATE timeattempt = NOW(), counter = counter + 1;";
				} else if (strstr($this->dbtype,"pgsql")) {
					$query = "INSERT INTO #__securitycheckpro_dynamic_blacklist (ip, timeattempt) VALUES ('{$attack_ip}', NOW()) ON CONFLICT (ip) DO UPDATE SET timeattempt = NOW(), counter = #__securitycheckpro_dynamic_blacklist.counter + 1;";
				}              
                
                $db->setQuery($query);        
                $result = $db->execute();
                
                $firewall_model = new FirewallconfigModel();
                
                // Chequeamos si hemos de a�adir la ip al fichero que ser� consumido por el plugin 'connect'
                $control_center_enabled = $firewall_model->control_center_enabled();
            
                if ($control_center_enabled) {
                    $firewall_model->a�adir_info_control_center($attack_ip, 'dynamic_blacklist');
                }
            } catch (Exception $e)
            {                
            }            
            
        } else
        {
            return false;
        }
    }
    
    /* Funci�n que chequea la sesi�n para usar la funcionalidad otp de Securitycheck Pro */
    private function check_environment($session_username)
    {
        $is_ok = true;
        
        $app = Factory::getApplication();
        
        //Si no estamos en el backend salimos
        if (!$app->isClient('administrator')) {            
            $is_ok = false;
        }
        
        // El usuario logado debe coincidir con el almacenado en la sesi�n o ser el invitado (antes de logarse en el backend)
        $currentUser = Factory::getUser();
                
        if (!$currentUser->guest && (strtoupper($currentUser->username) != strtoupper($session_username))) {
            $is_ok = false;
        }
        
        return $is_ok;
    }
    
    /* Funci�n que obtiene el id de un usuario a trav�s de la variable pasada como argumento. El usuario no puede estar bloqueado. */
    private function get_user_id($username)
    {
        
        if (empty($username)) {
            return null;
        }
        
        try
        {
            // Get a database object
            $db    = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select('id')
                ->from('#__users')
                ->where('username=' . $db->quote($username))
                ->where($db->qn('block') . ' = ' . $db->q(0));

            $db->setQuery($query);
            $userID = $db->setQuery($query)->loadResult();
        } catch (Exception $e)
        {
            return null;
        }
        
        return $userID;
        
    }
	
	/**
	 * Check if the code passed by the user matches with Verification or Yubikey code.
	 *
	 * @param   userId  The user id
	 * @code    verification code or yubikey code passed in the url
	 *
	 * @return  boolean  True is the verification code or yubikey code is valid
	 */
	private function check_mfa_status($userId,$code)
	{
		$check = false;
		$multifactor_method = "";
		
		$user_methods = \Joomla\Component\Users\Administrator\Helper\Mfa::getUserMfaRecords($userId);
		
		foreach ($user_methods as $user_method)
		{
			if ($user_method->method == 'totp')
			{
				if ( (is_array($user_method->options)) && (array_key_exists('key',$user_method->options)) )
				{
					$key = $user_method->options['key'];
				} else {
					return false;
				}				
				$totp    = new Totp;
				$check = $totp->checkCode($key, $code);								
				break;
			}else if ($user_method->method == 'yubikey')
			{
				$check = \Joomla\Plugin\Multifactorauth\Yubikey\Extension\Yubikey::validateYubikeyOtp($code);								
				break;
			}
			
		}
						
		return $check;		
	}
    
    /* Funci�n que chequea si la url usa la funci�n otp de Securitycheck Pro para desbloquear el acceso */
    private function check_otp_params()
    {
        $is_otp = false;
        
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $otp_enabled = $params->get('otp', 1);
        
        // Si la funcionalidad OTP est� habilitada realizamos las secuencia
        if ($otp_enabled) {        
            $session = Factory::getSession();                
            $session_username = $session->get('otp_username', '', 'com_securitycheckpro');
			                    
            if (empty($session_username)) {
                    
                $jinput = Factory::getApplication()->input;
                $url_username = trim($jinput->get('username', '', 'username'));
                $url_otp = $jinput->get('otp', '', 'string');
                        
                $userID = self::get_user_id($url_username);
				                   
                if (!empty($userID)) {                
                    $user = Factory::getUser($userID);
                    if ($user->authorise('core.admin')) {
						if (version_compare(JVERSION, '4.2.0', 'gt')) {
							$check = self::check_mfa_status($userID, $url_otp); 
						} else {
							$check = self::match_otps($userID, $url_otp);  
						}
                        if ($check) {                    
                            $session->set('otp_username', $url_username, 'com_securitycheckpro');
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
    
    /* Funci�n que chequea si el otp introducido por el usuario coincide con el de Verificaci�n o con la clave Yubikey */
    private function match_otps($userID,$url_otp)
    {
        			
        if (empty($url_otp)) {
            // The url_otp is empty
            return false;
        }
        
        $methods = AuthenticationHelper::getTwoFactorMethods();

        if (count($methods) <= 1) {
            // No two factor authentication method is enabled
            return false;
        }
		
        $model = new UserModel(array('ignore_request' => true));
                                   
        $otpConfig = $model->getOtpConfig($userID);
        
        // Check if the user has enabled two factor authentication
        if (empty($otpConfig->method) || ($otpConfig->method === 'none')) {
            return false;
        } else
        {
            if ($otpConfig->method === 'totp') {
                // El usuario tiene configurado Google Authenticator TOTP Plugin como 2FA
                // Create a new TOTP class with Google Authenticator compatible settings
                $totp = new FOFEncryptTotp(30, 6, 10);
                $code = $totp->getCode($otpConfig->config['code']);
                                    
                $check = $code === $url_otp;
                
                /*
                * If the check fails, test the previous 30 second slot. This allow the
                * user to enter the security code when it's becoming red in Google
                * Authenticator app (reaching the end of its 30 second lifetime)
                */
                if (!$check) {
                    $time = time() - 30;
                    $code = $totp->getCode($otpConfig->config['code'], $time);
                    $check = $code === $url_otp;
                }

                /*
                * If the check fails, test the next 30 second slot. This allows some
                * time drift between the authentication device and the server
                */
                if (!$check) {
                    $time = time() + 30;
                    $code = $totp->getCode($otpConfig->config['code'], $time);
                    $check = $code === $url_otp;
                }
                
                return $check;
            } else if ($otpConfig->method === 'yubikey') {
                // El usuario tiene configurado Yubikey como 2FA
                // Check if the Yubikey starts with the configured Yubikey user string
                $yubikey_valid = $otpConfig->config['yubikey'];
                $yubikey       = substr($url_otp, 0, -32);

                $check = $yubikey === $yubikey_valid;

                if ($check) {
                    $yubimodel = new \Joomla\Plugin\Multifactorauth\Yubikey\Extension\Yubikey();
                    $check = $yubimodel->validateYubikeyOtp($url_otp);
                }
                return $check;                
            }                
        }                                
                
    }
    
    /* Acciones a realizar si la IP est� en la lista negra din�mica*/
    function acciones_lista_negra_dinamica($dynamic_blacklist_time,$attack_ip,$dynamic_blacklist_counter,$logs_attacks,$request_uri,$not_applicable)
    {
        /* Cargamos el lenguaje del sitio */
        $lang = Factory::getLanguage();
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
    
    /* Acciones a realizar si la ip est� en la lista negra*/
    function acciones_lista_negra($logs_attacks,$attack_ip,$access_attempt,$request_uri,$not_applicable)
    {
        /* Cargamos el lenguaje del sitio */
        $lang = Factory::getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
        $not_applicable = $lang->_('COM_SECURITYCHECKPRO_NOT_APPLICABLE');
        
        $add_access_attempts_logs = $this->pro_plugin->getValue('add_access_attempts_logs', 0, 'pro_plugin');
        
        // Url con otp de Securitycheck Pro?
        $is_otp = self::check_otp_params();
                
        if (!$is_otp) {                
            // Grabamos una entrada en el log con el intento de acceso de la ip prohibida si est� seleccionada la opci�n para ello
            if ($add_access_attempts_logs) {
                $access_attempt = $lang->_('COM_SECURITYCHECKPRO_ACCESS_ATTEMPT');
                $this->grabar_log($logs_attacks, $attack_ip, 'IP_BLOCKED', $access_attempt, 'IP_BLOCKED', $request_uri, $not_applicable, '---', '---');
            }
                
            // Redirecci�n a nuestra p�gina de "Prohibido"
            $error_403 = $lang->_('COM_SECURITYCHECKPRO_403_ERROR');
            $this->redirection(403, $error_403, true, $attack_ip);    
        }
    }
    
    /* Opciones de redirecci�n: p�gina de error (de Joomla o personalizada) o rechazar la conexi�n. El par�metro blacklist indica si venimos de una lista negra; en ese caso, no podemos hacer la redirecci�n ya que entrar�amos en un bucle infinito. Lo que hacemos es mostrar el c�digo que haya establecido el administrador */
    function redirection($code,$message,$blacklist=false,$ip=null,$time=null)
    {
		// Si la variable "pro_plugin" est� vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
		
        $redirect_after_attack = $this->pro_plugin->getValue('redirect_after_attack', 1, 'pro_plugin');
        $redirect_options = $this->pro_plugin->getValue('redirect_options', 1, 'pro_plugin');
        $redirect_url = $this->pro_plugin->getValue('redirect_url', '', 'pro_plugin');
        $custom_code = $this->pro_plugin->getValue('custom_code', 'The webmaster has forbidden your access to this site', 'pro_plugin');
		$dynamic_blacklist = $this->pro_plugin->getValue('dynamic_blacklist', 1, 'pro_plugin');
				
		$lang = Factory::getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
		
		if (!is_null($ip)) {
			// Let's add the IP to the message shown
			$custom_code .= "<br/>" . Text::sprintf($lang->_('COM_SECURITYCHECKPRO_YOUR_IP'),$ip);			
		}
		
		if (!is_null($time)) {
			// Let's add the time to be unblocked of dynamic blacklist to the message shown
			$custom_code .= "<br/>" . Text::sprintf($lang->_('COM_SECURITYCHECKPRO_COME_BACK_IN'),$time/60);			
		}
        
        $app = Factory::getApplication();
        $is_admin = $app->isClient('administrator');
		                
        if ($redirect_after_attack) {            
            // Tenemos que redigir
            if (!$blacklist ) {
                // Si estamos en la parte administrativa nunca hemos de hacer la redirecci�n para evitar vulnerabilidades. Si la opci�n annadir a la lista negra din�mica est� deshabilitada, tambi�n tenemos que cortar la conexi�n para evitar que el ataque siga adelante.
                if (($is_admin) || !($dynamic_blacklist) ) {				
                    // Mostramos el c�digo establecido por el administrador, una cabecera de Forbidden y salimos 					
                    header('HTTP/1.1 403 Forbidden');
					die($custom_code);
                }                
                if ($redirect_options == 1) {
                    // Redirigimos a la p�gina de error de Joomla
                    Factory::getApplication()->enqueueMessage($message, 'error');
                } else if ($redirect_options == 2) {
                    // Redirigimos a la p�gina establecida por el administrador
                    Factory::getApplication()->redirect(Uri::root() . $redirect_url);    
                }
                    
            } else 
            {
                // Mostramos el c�digo establecido por el administrador, una cabecera de Forbidden y salimos                    
                header('HTTP/1.1 403 Forbidden');
				die($custom_code);
            }            
        } else 
        { // Rechazamos la conexi�n mostrando el c�digo establecido por el administrador, una cabecera de Forbidden y salimos
            header('HTTP/1.1 403 Forbidden');
			die($custom_code);
        }
    
    }
    
    /* Acciones a realizar si la ip est� no est� en ninguna de las listas*/
    function acciones_no_listas($methods,$attack_ip,$methods_options,$request_uri,$check_base_64,$logs_attacks,$secondlevel,$mode)
    {
        /* Cargamos el lenguaje del sitio */
        $lang = Factory::getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
		
		// Si la variable "pro_plugin" est� vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
        
        // Obtenemos los valores del plugin para la protecci�n de sesi�n del usuario
        $session_hijack_protection = $this->pro_plugin->getValue('session_hijack_protection', 1, 'pro_plugin');
        $session_protection_active = $this->pro_plugin->getValue('session_protection_active', 1, 'pro_plugin');
                
        /* Protecci�n de la sesi�n del usuario y contra secuestros de sesi�n */
        if ($session_protection_active || $session_hijack_protection) {
            $this->sesiones_activas($logs_attacks, $attack_ip, $request_uri, $session_protection_active, $session_hijack_protection);
        }
        // Consultamos si hemos de aplicar las reglas al usuario en funci�n de su pertenencia a grupos.
        $user = Factory::getUser();
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
                    
					$entradas = Factory::getApplication()->input;
					$option = $entradas->get('option','com_notfound');
                   
                    $req = $this->cleanQuery($attack_ip, $req, $methods_options, $a, $request_uri, $modified, $check_base_64, $logs_attacks, $option);
					                    
                    if ($modified) {
                        /* Actualizamos la lista negra din�mica */
                        $this->actualizar_lista_dinamica($attack_ip);
						                            
                        if ($mode) { // Modo estricto: redireccion
                            /* Redirecci�n a nuestra p�gina de "Hacking Attempt" */                            
                            $error_400 = $lang->_('COM_SECURITYCHECKPRO_400_ERROR');
                            $this->redirection(400, $error_400);                                            
                        } // Modo alerta: no hacemos redirecci�n
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
    
    /*  Funci�n para mandar correos electr�nicos */
    function mandar_correo($alerta)
    {
		// Si la variable "pro_plugin" est� vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
		
        // Variables del correo electr�nico  y l�mite de correos a enviar cada d�a
        $subject = $this->pro_plugin->getValue('email_subject', '', 'pro_plugin');
        $body = $this->pro_plugin->getValue('email_body', '', 'pro_plugin');
        $email_add_applied_rule = $this->pro_plugin->getValue('email_add_applied_rule', 1, 'pro_plugin');
        $email_to = $this->pro_plugin->getValue('email_to', '', 'pro_plugin');
        $to = explode(',', $email_to);
        $email_from_domain = $this->pro_plugin->getValue('email_from_domain', '', 'pro_plugin');
        $email_from_name = $this->pro_plugin->getValue('email_from_name', '', 'pro_plugin');
        $from = array($email_from_domain,$email_from_name);
        $email_limit = $this->pro_plugin->getValue('email_max_number', 20, 'pro_plugin');
        $today = date("Y-m-d");
        $send = true;
        
        // Consultamos el n�mero de correos mandados
        $db = Factory::getDBO();
        
        $query = "UPDATE #__securitycheckpro_emails SET envoys=0, send_date='{$today}' WHERE (send_date < '{$today}')";
        $db->setQuery($query);
        $db->execute();
        
        
        $query = "SELECT envoys FROM #__securitycheckpro_emails WHERE (send_date = '{$today}')";
        $db->setQuery($query);
        (int) $envoys = $db->loadResult();
        
        if ($envoys < $email_limit) {  // No se ha alcanzado el l�mite m�ximo de emails por d�a
            /* Cargamos el lenguaje del sitio */
            $lang = Factory::getLanguage();
            $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
                            
            // A�adimos la regla aplicada al cuerpo del correo
            if ($email_add_applied_rule) {
                $body = $body . '<br />' . $alerta;
            }
        
            try 
            {
                $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
                // Emisor
                $mailer->setSender($from);
                // Destinatario -- es una array de direcciones
                $mailer->addRecipient($to);
                // Asunto
                $mailer->setSubject($subject);
                // Cuerpo
                $mailer->setBody($body);
                // Opciones del correo
                $mailer->isHTML(true);
                $mailer->Encoding = 'base64';
                // Enviamos el mensaje
                $send = $mailer->Send();
            } catch (\Throwable $e)
            {
                $send = false;
            }
                        
            if ($send !== true) {              
            }else
            {
                $db = Factory::getDBO();
                $query = "UPDATE `#__securitycheckpro_emails` SET envoys=envoys+1 WHERE (send_date = '{$today}')";
                $db->setQuery($query);
                $db->execute();
            }
        }
    }
    
    /* Chequea la direcci�n ip y el user-agent de una sesi�n activa para comprobar que no ha habido ninguna modificaci�n */
    protected function chequeo_suplantacion($user_id)
    {
        // Obtenemos los valores necesarios
        $changed = false;
        $ip = $this->get_ip();
		
		$session_hijack_protection_what_to_check = $this->pro_plugin->getValue('session_hijack_protection_what_to_check', 1, 'pro_plugin');
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        $db = Factory::getDBO();
        
        // Obtenemos el id del usuario logado
        $query = "SELECT * FROM #__securitycheckpro_sessions WHERE (userid = '{$user_id}')";
        $db->setQuery($query);
        $user_data = $db->loadRow();        
                                
        if (!is_null($user_data)) {
			if ( $session_hijack_protection_what_to_check == 1 )
			{
				if ((strcmp($user_data[3], $ip) !== 0) || (strcmp($user_data[4], $user_agent) !== 0)) {
					 // Han cambiado la direcci�n IP o el User-agent
					$changed = true;
				}
			} else if ( $session_hijack_protection_what_to_check == 2 )
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
    
    /*  Funci�n que chequea el n�mero de sesiones activas del usuario y, si existe m�s de una, toma el comportamiento pasado como argumento*/
    protected function sesiones_activas($logs_attacks,$attack_ip,$request_uri,$session_protection_active,$session_hijack_protection)
    {
        
        /* Cargamos el lenguaje del sitio */
        $lang = Factory::getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
		
		// Si la variable "pro_plugin" est� vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
        
        // Chequeamos si la opci�n de compartir sesiones est� activa; en este caso no aplicaremos esta opci�n para evitar una denegaci�n de entrada
        $params = Factory::getConfig();        
        $shared_session_enabled = $params->get('shared_session');
        
        if ($shared_session_enabled) {
            return;
        }
        
        // Cargamos los grupos a los que se ha de aplicar la protecci�n; por defecto se aplica al grupo Super Users, con un id igual a 8 (el valor por defecto debe estar en un array)
        $session_protection_groups = $this->pro_plugin->getValue('session_protection_groups', array('0' => '8'), 'pro_plugin');
		$dynamic_blacklist_on = $this->pro_plugin->getValue('dynamic_blacklist', 1, 'pro_plugin');
                
        // Variable que indicar� si el usuario logado pertenece a un grupo al que haya que aplicar la protecci�n
        $apply_to_user = false;
                
        $user = Factory::getUser();
        $user_id = (int) $user->id;
        $user_groups = $user->groups;
                
        // Creamos el nuevo objeto query
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        
                
        if ($user->guest) {
            /* El usuario no se ha logado; no hacemos nada */                        
        } else 
        {            
            /* En alg�n caso no se pueden determinar los grupos a los que pertenece el usuario. Controlamos que la variable no est� vac�a */
            if (!is_null($user_groups)) {
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
                        
            if (($result > 1) && ($apply_to_user)) {  // Ya existe m�s de una sesi�n activa del usuario y el usuario est� incluido en un grupo al que hay que aplicar la protecci�n                
                if ($session_protection_active) {
                    /*Cerramos todas las sesiones activas del usuario, tanto del frontend (clientid->0) como del backend (clientid->1); este c�digo es necesario porque no queremos modificar los archivos de Joomla , pero esta comprobaci�n podr�a incluirse en la funci�n onUserLogin*/
                    $mainframe= Factory::getApplication();
                    $mainframe->logout($user_id, array("clientid" => 0));
                    $mainframe->logout($user_id, array("clientid" => 1));
                    
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
            } else if (($result == 1) && ($apply_to_user)) {
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
    }
    
    /* Complementa la funci�n original de Joomla a�adiendo a la tabla `#__securitycheckpro_sessions` informaci�n sobre la sesi�n del usuario */
    function onUserLogin($user, $options = array())
    {
        // Obtenemos un manejador a la BBDD
        $db = Factory::getDBO();
		
		// Si la variable "pro_plugin" est� vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
		
		// Chequeamos los ids de los grupos 'Public' y 'Guest'
        $query = "SELECT id FROM #__usergroups WHERE title='Public'";
        $db->setQuery($query);
        (int) $public_group_id = $db->loadResult();
        
        $query = "SELECT id FROM #__usergroups WHERE title='Guest'";
        $db->setQuery($query);
        (int) $guest_acl_security = $db->loadResult();        
        
        // Obtenemos la longitud de la clave que tenemos que generar
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $check_acl_security = $params->get('check_acl_security', 1);

        if ($check_acl_security == 1) {
            $app = Factory::getApplication();    
            
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
                if ($guest_acl) {
                    if (in_array($app->getName(), array('administrator','admin'))) {
                        $app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_INSECURE_ACL_CONFIG_DETECTED', Text::_('COM_SECURITYCHECKPRO_GUEST'), Text::_($value)), 'error');
                    }
                }
            }            
        }                
        
        // Limpiamos las sesiones no v�lidas
        $this->chequeo_sesiones();
        
        // Obtenemos las entradas
        $username = $db->Quote($db->escape($user['username']));
        $name = $user['username'];
		// La variable session_name estar� vacia enlas peticiones a la API
        if (!empty($_COOKIE[session_name()])) {
			$session_id = $db->Quote($db->escape($_COOKIE[session_name()]));
		} else {
			$session_id = $db->Quote($db->escape(Factory::getSession()->getId()));			
		}
        $ip = $this->get_ip();
        $user_agent = $db->Quote($db->escape($_SERVER['HTTP_USER_AGENT']));
        
        // Obtenemos el id del usuario logado
        $query = "SELECT id FROM #__users WHERE (username = {$username})";
        $db->setQuery($query);
        $userid = $db->loadResult();
        
        // Insertamos los datos en la tabla 'securitycheckpro_sessions' ignorando los errores de entradas duplicadas
		if (strstr($this->dbtype,"mysql")) {
			$query = "INSERT IGNORE INTO #__securitycheckpro_sessions (userid,  session_id, username, ip, user_agent) VALUES ('{$userid}', {$session_id}, {$username}, '{$ip}', {$user_agent})";
		} else if (strstr($this->dbtype,"pgsql")) {
			$query = "INSERT INTO #__securitycheckpro_sessions (userid,  session_id, username, ip, user_agent) VALUES ('{$userid}', {$session_id}, {$username}, '{$ip}', {$user_agent}) ON CONFLICT DO NOTHING";
		}
        
        $db->setQuery($query);
        $db->execute();
        
        /* Controlamos el acceso de los administradores al backend */        
        /* Cargamos el lenguaje del sitio */
        $lang = Factory::getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
        $not_applicable = $lang->_('COM_SECURITYCHECKPRO_NOT_APPLICABLE');
        
        $email_on_admin_login = $this->pro_plugin->getValue('email_on_admin_login', 0, 'pro_plugin');
        $forbid_admin_frontend_login = $this->pro_plugin->getValue('forbid_admin_frontend_login', 0, 'pro_plugin');
                        
        // Controlamos el acceso al backend
        $app = Factory::getApplication();
        if (in_array($app->getName(), array('administrator','admin'))) {
            
            // Borramos los logs no necesarios
            $this->delete_logs();
            
            if ($email_on_admin_login) {            
                // Extraemos los datos que se mandar�n por correo
                $ip = $this->get_ip();                               
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
                                    
                // Instanciamos un nuevo objeto usuario con la id del usuario logado para obtnere los grupos a los que pertenece
                $user = Factory::getUser($userid);
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
                                    
                    $attack_ip = $this->get_ip();        
                    $request_uri = $_SERVER['REQUEST_URI'];
                    $logs_attacks = $this->pro_plugin->getValue('logs_attacks', 1, 'pro_plugin');                    
                    $fordib_frontend_login_description = $lang->_('COM_SECURITYCHECKPRO_FRONTEND_LOGIN_FORBIDDEN');
                    $username_string = $lang->_('COM_SECURITYCHECKPRO_USERNAME');
                                        
                    $mainframe= Factory::getApplication();
                    // Cerramos la sesi�n del frontend
                    $mainframe->logout($userid, array("clientid" => 0));                    
                    
                    // Grabamos el log correspondiente...
                    $this->grabar_log($logs_attacks, $attack_ip, 'SESSION_PROTECTION', $fordib_frontend_login_description, 'SESSION_PROTECTION', $request_uri, $username_string .$name, $name, '---');
                                                            
                    // ... y redirigimos la petici�n para realizar las acciones correspondientes
                    $this->redirection(403, $fordib_frontend_login_description);
                    
                }                
            }
        }    
        
    }
    
    /* Complementa la funci�n original de Joomla eliminando de la tabla `#__securitycheckpro_sessions` informaci�n sobre la sesi�n del usuario */
    function onUserLogout($user, $options = array())
    {
        
        // Obtenemos un manejador a la BBDD
        $db = Factory::getDBO();
        
        // Nombre del usuario logado
        $username = $db->Quote($db->escape($user['username']));
                                    
        // Borramos el usuario de la tabla
        $query = "DELETE FROM #__securitycheckpro_sessions WHERE (username = {$username})";
        $db->setQuery($query);
        $db->execute();
        
        // Limpiamos las sesiones no v�lidas
        $this->chequeo_sesiones();
    }
    
    /* Funci�n que chequea si existen sesiones de usuario en la tabla `#__securitycheckpro_sessions` que ya no son v�lidas. Esto sucede, por ejemplo, cuando la sesi�n del usuario se cierra por inactividad */
    protected function chequeo_sesiones()
    {
        // Variables que usamos en la funci�n
        $user = Factory::getUser();
        $user_id = (int) $user->id;
        $db = Factory::getDBO();
        
        if (!$user->guest) {
        
            $session_id = $db->Quote($db->escape($_COOKIE[session_name()]));
                            
            // Consultamos si existe alguna sesi�n en `#__session` con el mismo 'session_id' que las de la cookie. Eso significa que la sesi�n est� activa
            $query = "SELECT session_id FROM #__session WHERE (session_id = {$session_id})";
            $db->setQuery($query);
            $result = $db->loadResult();
                        
            // Si la cookie ya no existe en la tabla  `#__session, significa que no es v�lida. Borramos la entrada en la tabla `#__securitycheckpro_sessions`
            if (is_null($result)) {
				if (strstr($this->dbtype,"mysql")) {
					$query = "DELETE IGNORE FROM #__securitycheckpro_sessions WHERE (session_id = {$session_id})";
				} else if (strstr($this->dbtype,"pgsql")) {
					$query = "DELETE FROM #__securitycheckpro_sessions WHERE (session_id = {$session_id})";					
				}
                
                $db->setQuery($query);
                $db->execute();
            } else
            { 
                /* La cookie existe, por lo que la sesi�n es v�lida. Debemos chequear si la ip de origen y el user-agent de la petici�n actual son los mismos que los almacenados al iniciar la sesi�n.  Lo hacemos en la funci�n sesiones_activas() para evitar lanzarlo cuando no se ha iniciado ninguna sesi�n*/
                
            }
        }
        
        /* Sessions garbage collector */
        // Consultamos todas las sesiones creadas por el plugin.
        $query = "SELECT userid FROM #__securitycheckpro_sessions";
        $db->setQuery($query);
        $userids_array = $db->loadColumn();
        
        // Existen sesiones en la tabla `#__securitycheckpro_sessions`. Comprobamos si est�n activas en la tabla `#__sessions`
        if (!(is_null($userids_array))) {
            foreach ($userids_array as $id)
            {
                // Consultamos si existe alguna sesi�n del usuario activa en `#__session`.
                $query = "SELECT session_id FROM #__session WHERE (userid = {$id})";
                $db->setQuery($query);
                $result = $db->loadResult();
                // Si no existen sesiones, significa que las existentes en la tabla `#__securitycheckpro_sessions` no son v�lidas. Las borramos.
                if (is_null($result)) {
					if (strstr($this->dbtype,"mysql")) {
						$query = "DELETE IGNORE FROM #__securitycheckpro_sessions WHERE (userid = {$id})";
					} else if (strstr($this->dbtype,"pgsql")) {
						$query = "DELETE FROM #__securitycheckpro_sessions WHERE (userid = {$id})";					
					}
                    
                    $db->setQuery($query);
                    $db->execute();
                }                
            }
        }
    }
    
    /* Funci�n que chequea si las reglas han de aplicarse al usuario pasado como argumento. Se comprobar� la pertenencia a grupos y se aplicar� la configuraci�n de la tabla "#__securitycheckpro_rules" */
    protected function check_rules($user_object)
    {
        
        $apply = false;
        
        if ($user_object->guest) {
            $apply = true;
        } else {
            // Consultamos la variable de sesi�n "apply_rules", que nos indicar� si hay que aplicar las reglas al usuario.
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
    
    
    /* Funci�n para establecer en la sesi�n del usuario si hay que aplicarle las reglas del firewall */
    function set_session_rules()
    {
        $apply = "yes";
        
        $db = Factory::getDBO();
        $user = Factory::getUser();
                
        foreach ($user->groups as $grupo)
        {
            // Consultamos si hay que aplicar la regla al grupo
            $query = "SELECT rules_applied FROM #__securitycheckpro_rules WHERE (group_id = {$grupo})";
            $db->setQuery($query);
            $apply_rule_to_group = $db->loadResult();
                                    
            // Si hay que aplicar la regla, actualizamos la variable '$apply' y abandonamos el bucle
             if ( !is_null($apply_rule_to_group) && ($apply_rule_to_group == 0) ) {
                $apply = "no";
                $this->actualizar_rules_log($user, $grupo);
                break;
            }
        }
        
        // Creamos la variable en el entorno del usuario
        $mainframe = Factory::getApplication();
        $mainframe->SetUserState("apply_rules", $apply);        
    }
    
    /* Funci�n para actualizar los logs de las reglas del firewall */
    function actualizar_rules_log($user,$grupo)
    {
        
        // Inicializamos las variables necesarias
        $ip = 'Not set';
        
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        
        // Obtenemos el t�tulo del grupo al que se le aplica la excepci�n
        $query = "SELECT title FROM #__usergroups WHERE (id = {$grupo})";
        $db->setQuery($query);
        $group_title = $db->loadResult();
        
        // Obtenemos la IP del cliente
        $ip = $this->get_ip();
                
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
	   
        
    function onAfterRoute()
    {
		$plugin_enabled = false;
        $tables_locked = false;
        
        $db = Factory::getDBO();
        
        try 
        {
            $query = "SELECT enabled from #__extensions WHERE element='Securitycheckpro' and type='plugin'";            
            $db->setQuery($query);
            $plugin_enabled= $db->loadResult();
        } catch (Exception $e)
        {
            
        }            

        try 
        {
            $query = "SELECT storage_value from #__securitycheckpro_storage WHERE storage_key = 'locked'";        
            $db->setQuery($query);
            $tables_locked= $db->loadResult();
        } catch (Exception $e)
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
			$extensions_blacklist = $this->pro_plugin->getValue('extensions_blacklist', 'php,js,exe,xml', 'pro_plugin');
			$delete_files = $this->pro_plugin->getValue('delete_files', 1, 'pro_plugin');
			$actions_upload_scanner = $this->pro_plugin->getValue('actions_upload_scanner', 0, 'pro_plugin');
			
			// Si el esc�ner est� habilitado y existen archivos subidos, los comprobamos
			if (($upload_scanner_enabled) && ($_FILES)) {
				foreach ($_FILES as $file)
				{ 
					$this->check_file($check_multiple_extensions, $extensions_blacklist, $delete_files, $file, $actions_upload_scanner);            
				}
				
			}
    
            // Cargamos el lenguaje del sitio
            $lang = Factory::getLanguage();
            $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
            $not_applicable = $lang->_('COM_SECURITYCHECKPRO_NOT_APPLICABLE');
            $access_attempt = $lang->_('COM_SECURITYCHECKPRO_ACCESS_ATTEMPT');
						
            $methods = $this->pro_plugin->getValue('methods', 'GET,POST,REQUEST', 'pro_plugin');
            $logs_attacks = $this->pro_plugin->getValue('logs_attacks', 1, 'pro_plugin');
            $mode = $this->pro_plugin->getValue('mode', 1, 'pro_plugin');
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
			$config = Factory::getConfig();
			$this->dbtype = $config->get('dbtype');			
            
            $attack_ip = $this->get_ip();        
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
            
            
            if (!$aparece_lista_blanca) {
                // La IP no se encuentra en ninguna lista
                $this->acciones_no_listas($methods, $attack_ip, $methods, $request_uri, $check_base_64, $logs_attacks, $secondlevel, $mode);
            }       
        }
        // Si las tablas est�n bloqueadas prohibimos el acceso a 'com_installer'
        if ($tables_locked) {
            $app = Factory::getApplication();
            $is_admin = $app->isClient('administrator');
            
            if ($is_admin) {
                $app = Factory::getApplication();
                $option = $app->input->get('option');
                if (($option == "com_installer") || ($option == "com_joomlaupdate")) {
                    $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INSTALLER_ACCESS_FORBIDDEN'), 'error');
                    // Redirigimos a la p�gina establecida por el administrador
                    Factory::getApplication()->redirect(Uri::base());    
                }
            }
            
        }
		// Are we updating Joomla?
		$object = Factory::getApplication()->input;
		$option = $object->getString('option');
		$task = $object->getString('task');
		if ($option == "com_joomlaupdate") {
			if ($task == "update.install") {				
				// Let's write a file to tell securitycheck that Joomla core has been updated. This is needed by /com_securitycheckpro/backend/models/securitycheckpros.php		
				$this->write_file(); 
			}
		}
    } 

    
    public function onAfterDispatch()
    {
        // �Tenemos que eliminar el meta tag?
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $remove_meta_tag = $params->get('remove_meta_tag', 1);
        
        $code  = Factory::getDocument();
        if ($remove_meta_tag) {
            $code->setGenerator('');
        }
		
    }
    
        
    /* Funci�n que chequea si un fichero tiene m�ltiples extensiones o pertenece a una lista de extensiones prohibidas. Seg�n el valor de la variable $delete_files, el fichero ser� borrado */
    protected function check_file($check_multiple_extensions,$extensions_blacklist,$delete_files,$file,$actions_upload_scanner)
    {
		// Si la variable "pro_plugin" est� vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
		
        // Inicializamos variables
        $safe = true;
        $malware_type = '';
        $malware_description = '';
        $logs_attacks = $this->pro_plugin->getValue('logs_attacks', 1, 'pro_plugin');
		$mimetypes_blacklist = $this->pro_plugin->getValue('mimetypes_blacklist','application/x-dosexec,application/x-msdownload ,text/x-php,application/x-php,application/x-httpd-php,application/x-httpd-php-source,application/javascript,application/xml', 'pro_plugin');
        $attack_ip = $this->get_ip();
        $request_uri = $_SERVER['REQUEST_URI'];
        $tag_description = '';
		
		/* Cargamos el lenguaje del sitio */
        $lang = Factory::getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
        $not_applicable = $lang->_('COM_SECURITYCHECKPRO_NOT_APPLICABLE');
        $access_attempt = $lang->_('COM_SECURITYCHECKPRO_ACCESS_ATTEMPT');
        $action = $lang->_('COM_SECURITYCHECKPRO_FILE_DELETED');
		$custom_code = $this->pro_plugin->getValue('custom_code', 'The webmaster has forbidden your access to this site', 'pro_plugin');
		
		// Check file properties. If it's an array, let's convert it.
		$tmp_name = $file['tmp_name'];
		$file_name = $file['name'];
		$file_size = $file['size'];
		
		if (is_array($file['tmp_name'])) {
			if (!array_key_exists(0,$file['tmp_name'])) {
				return;	
			}
			$tmp_name = $file['tmp_name'][0];
		} 
		
		if (is_array($file['name'])) {
			$file_name = $file['name'][0];
		} 
		
		if (is_array($file['size'])) {
			$file_size = $file['size'][0];
		} 
		
		// Obtenemos el mime-type del archivo temporal
		if ( (function_exists('mime_content_type')) && (file_exists($tmp_name)) )  {
			$mime_type = strtolower(mime_content_type($tmp_name));
		} else {
			$mime_type = false;
		}		
		
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
                
                // Si est� marcada la opci�n, a�adimos la IP a la lista negra din�mica
                if ($actions_upload_scanner == 1) {
                    $this->actualizar_lista_dinamica($attack_ip);                    
                }
				$this->grabar_log($logs_attacks, $attack_ip, 'UPLOAD_SCANNER', $action, $type, $request_uri, $file_name . PHP_EOL . $malware_description, $user->username, $component);
				$error_403 = $lang->_('COM_SECURITYCHECKPRO_403_ERROR');
				header('HTTP/1.1 403 Forbidden');
				die($custom_code);
			}
		}		       
                
        // Extensiones de ficheros que ser�n analizadas
        // Eliminamos los espacios en blanco
        $extensions_blacklist = str_ireplace(' ', '', $extensions_blacklist);
        $ext = explode(',', $extensions_blacklist);
        
        // Obtenemos el usuario
        $user = Factory::getUser();
        
        // Obtenemos el componente de la petici�n
		$component = Factory::getApplication()->input->get('option','com_notfound');
            
        if ((!empty($file_name)) && (is_string($file_name))) {
            
            // Buscamos extensiones m�ltiples
            if ($check_multiple_extensions) {        
                
                // Buscamos la verdadera extensi�n del fichero (esto es, buscamos archivos tipo .php.xxx o .php.xxx.yyy)
                $explodedName = explode('.', $file_name);
                array_reverse($explodedName);
                                                
                if((count($explodedName) > 3) && (strtolower($explodedName[1]) == 'php')) {  // Archivo tipo .php.xxx.yyy
                    $malware_description = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_EXTENSION') . $explodedName[2] . "." . $explodedName[3] ;
                    $tag_description = 'MULTIPLE_EXTENSIONS';
                    $safe = false;
                } else if ((count($explodedName) > 2) && (strtolower($explodedName[1]) == 'php')) {  // Archivo tipo .php.xxx
                    $malware_description = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_EXTENSION') . $explodedName[2];
                    $type = 'MULTIPLE_EXTENSIONS';
                    $safe = false;                    
                } 
            }
            
            // Buscamos si la extensi�n est� en la lista de las extensiones prohibidas
            if ((!empty($extensions_blacklist)) && ($safe)) {
                            
                if (in_array(pathinfo($file_name, PATHINFO_EXTENSION), $ext) && ($file_size > 0)) {
                    // Archivo en la lista de extensiones prohibidas
                    $type = 'FORBIDDEN_EXTENSION';
                    $malware_description = $lang->_('COM_SECURITYCHECKPRO_TITLE_FORBIDDEN_EXTENSION');
                    $safe = false;
                }
            }
            
            // Si alguna de las dos comprobaciones es positiva, borramos el fichero subido (si as� est� marcado)
            if (!$safe) {
                if ($delete_files) {                    
                    @unlink($tmp_name);                    
                } else {
                    $action = $lang->_('COM_SECURITYCHECKPRO_FILE_NOT_DELETED');
                }
                
                // Si est� marcada la opci�n, a�adimos la IP a la lista negra din�mica
                if ($actions_upload_scanner == 1) {
                    $this->actualizar_lista_dinamica($attack_ip);                    
                }
                        
                $this->grabar_log($logs_attacks, $attack_ip, 'UPLOAD_SCANNER', $action, $type, $request_uri, $file_name . PHP_EOL . $malware_description, $user->username, $component);
                $error_403 = $lang->_('COM_SECURITYCHECKPRO_403_ERROR');
                header('HTTP/1.1 403 Forbidden');
				die($custom_code);     
            }
        }
    }
       
    
    /* Auditamos las entradas fallidas de los usuarios */
    public function onUserLoginFailure($response)
    {
		// Si la variable "pro_plugin" est� vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
		
        // Extraemos la configuraci�n del plugin
        $track_failed_logins = $this->pro_plugin->getValue('track_failed_logins', 1, 'pro_plugin');
        $write_log = $this->pro_plugin->getValue('write_log', 1, 'pro_plugin');
        $logins_to_monitorize = $this->pro_plugin->getValue('logins_to_monitorize', 2, 'pro_plugin');
        $actions_failed_login = $this->pro_plugin->getValue('actions_failed_login', 1, 'pro_plugin');
        
        $logs_attacks = $this->pro_plugin->getValue('logs_attacks', 1, 'pro_plugin');
        $attack_ip = $this->get_ip();
        $request_uri = $_SERVER['REQUEST_URI'];
                        
        /* Cargamos el lenguaje del sitio */
        $lang = Factory::getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
        $not_applicable = $lang->_('COM_SECURITYCHECKPRO_NOT_APPLICABLE');
                        
        if($track_failed_logins) {
            $login_info = $this->trackFailedLogin();
            // Controlamos el acceso al backend
            $app = Factory::getApplication();
            if (in_array($app->getName(), array('administrator','admin'))) {
                // Escribimos un log si se produce un intento de acceso fallido    al backend                
                if ($logins_to_monitorize != 1) {
                    $description = $lang->_('COM_SECURITYCHECKPRO_USERNAME') . $login_info[0];
                    if($write_log) {
                        $this->grabar_log($write_log, $attack_ip, 'FAILED_LOGIN_ATTEMPT_LABEL', $lang->_('COM_SECURITYCHECKPRO_FAILED_ADMINISTRATOR_LOGIN_ATTEMPT_LABEL'), 'SESSION_PROTECTION', $request_uri, $description, $login_info[0], '---');                        
                    }
                    // Si est� marcada la opci�n, a�adimos la IP a la lista negra din�mica
                    if ($actions_failed_login == 1) {
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
                    // Si est� marcada la opci�n, a�adimos la IP a la lista negra din�mica
                    if ($actions_failed_login == 1) {
                        $this->actualizar_lista_dinamica($attack_ip);                    
                    }
                }
            }    
            
        }
        
        // Limpiamos las sesiones no v�lidas
        $this->chequeo_sesiones();
    }
    
    /* Funci�n que recoje los datos de los intentos de acceso fallidos */
    private function trackFailedLogin()
    {
            
        $input = Factory::getApplication()->input;
		$user = $input->get('username', null);	
        
        $extraInfo = array();
        if(!empty($user)) {    
            $extraInfo[] = $user;        
        }
        
        return $extraInfo;        
    }
      
    
    /* Obtiene la IP remota que realiza las peticiones */
    public function get_ip()
    {
        // Inicializamos las variables 
        $clientIpAddress = 'Not set';
        $ip_valid = false;
        
        // �C�mo determinamos la IP?
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $avoid_proxies = $params->get('avoid_proxies', 1);
                
        if ($avoid_proxies) {
            // Ignoramos todas las cabeceras; usamos s�lo "remote_addr" para determinar la direcci�n ip
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $clientIpAddress = $_SERVER['REMOTE_ADDR'];            
            }
            
            $ip_valid = filter_var($clientIpAddress, FILTER_VALIDATE_IP);
            // Si la ip no es v�lida entonces bloqueamos la petici�n y mostramos un error 403
            if (!$ip_valid) {
                // Cargamos el lenguaje del sitio 
                $lang = Factory::getLanguage();
                $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
                $error_403 = $lang->_('COM_SECURITYCHECKPRO_403_ERROR');
                $this->redirection(403, $error_403, true);                
            } else 
            {
                return $clientIpAddress;
            }        
        } else 
        {    
			// Contribution of George Acu - thanks!
			if (isset($_SERVER['HTTP_TRUE_CLIENT_IP']))
			{
				# CloudFlare specific header for enterprise paid plan, compatible with other vendors
				$clientIpAddress = $_SERVER['HTTP_TRUE_CLIENT_IP']; 
			} elseif (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
			{
				# another CloudFlare specific header available in all plans, including the free one
				$clientIpAddress = $_SERVER['HTTP_CF_CONNECTING_IP']; 
			} elseif (isset($_SERVER['HTTP_INCAP_CLIENT_IP'])) 
			{
				// Users of Incapsula CDN
				$clientIpAddress = $_SERVER['HTTP_INCAP_CLIENT_IP']; 
			} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) 
			{
				# specific header for proxies
				$clientIpAddress = $_SERVER['HTTP_X_FORWARDED_FOR']; 
				$result_ip_address = explode(', ', $clientIpAddress);
                $clientIpAddress = $result_ip_address[0];
			} elseif (isset($_SERVER['REMOTE_ADDR']))
			{
				# this one would be used, if no header of the above is present
				$clientIpAddress = $_SERVER['REMOTE_ADDR']; 
			}
            
            $ip_valid = filter_var($clientIpAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            
            // Si la ip no es v�lida intentamos extraer la direcci�n IP remota
            if (!$ip_valid) {
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    $clientIpAddress = $_SERVER['REMOTE_ADDR'];            
                }
                
                $ip_valid = filter_var($clientIpAddress, FILTER_VALIDATE_IP);
                // Si la ip no es v�lida entonces bloqueamos la petici�n y mostramos un error 403
                if (!$ip_valid) {
                    // Cargamos el lenguaje del sitio
                    $lang = Factory::getLanguage();
                    $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
                    $error_403 = $lang->_('COM_SECURITYCHECKPRO_403_ERROR');
                    $this->redirection(403, $error_403, true);                
                }            
            }
            
            // Devolvemos el resultado
            return $clientIpAddress;            
        }
    }
    
    // Chequeamos los usuarios administradores/super usuarios
    private function forbid_new_admins()
    {
		// Si la variable "pro_plugin" est� vac�a la instanciamos
		if (empty($this->pro_plugin)) {
			$this->pro_plugin = new BaseModel();
		}
		
        // Inicializamos las variables
        $admin_groups = array();        
        $logs_attacks = $this->pro_plugin->getValue('logs_attacks', 1, 'pro_plugin');
        $forbid_new_admins = $this->pro_plugin->getValue('forbid_new_admins', 0, 'pro_plugin');
        $db = Factory::getDBO();
        
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
                    $backend = Access::checkGroup($group, 'core.login.admin');
                    if(is_null($backend)) { $backend = Access::checkGroup($group, 'core.admin');
                    }
                                
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
			}catch (Exception $e)
            {
                return;
            }
                        
            // Consultamos el n�mero previo de usuarios pertenencientes al grupo super-users
            try
            {
                $query = "SELECT contador from #__securitycheckpro_users_control WHERE id='1'" ;
                $db->setQuery($query);
                (int) $previous_admins = $db->loadResult();
            } catch (Exception $e)
            {
                if (strstr($e->getMessage(), "doesn't exist")) {
                    $previous_admins = null;
                }
            }
                            
            if (is_null($previous_admins)) { // No hay datos almacenados (o es la primera vez que se lanza o se ha desactivado esta opci�n y ahora est� activa)
                // Extraemos los ids de los usuarios con permisos de administraci�n
				try 
				{
					$query = "SELECT user_id from #__user_usergroup_map WHERE group_id IN (" . implode(',', array_map('intval', $admin_groups)) . ")" ;
					$db->setQuery($query);
					$actual_admins = $db->loadColumn();
				}catch (Exception $e)
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
                        
                } catch (Exception $e) {    
                    
                }
            } else if ($actual_admins > $previous_admins) {
                // Se ha a�adido un nuevo usuario con permisos de administraci�n
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
                } catch (Exception $e)
                {    
                    if (strstr($e->getMessage(), "doesn't exist")) {
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
							
					} catch (Exception $e) {    
						return;
					}
				}
                                            
                foreach ($new_user_added as $new_user)
                {                        
                    // Creamos una instancia del usuario
                    $instance = User::getInstance($new_user);
                    $username = $instance->username;
                                
                    if ($instance) {
                        // Borramos el usuario
                        $instance->delete();
                        $this->grabar_log($logs_attacks, '---', 'SESSION_PROTECTION', Text::_('COM_SECURITYCHECKPRO_FORBID_NEW_ADMINS_LABEL'), 'SESSION_PROTECTION', Text::_('COM_SECURITYCHECKPRO_NOT_APPLICABLE'), Text::_('COM_SECURITYCHECKPRO_USER_DELETED'), $username, '---');
                        // Si hay alguien logado al backend, mostramos un mensaje de error
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
            // Consultamos el n�mero de logs para ver si se supera el l�mite establecido en el apartado 'log_limits_per_ip_and_day'
            try 
            {
                $query = "DELETE from #__securitycheckpro_users_control WHERE id='1'" ;
                $db->setQuery($query);
                $db->execute();
            } catch (Exception $e)
            {
                if (strstr($e->getMessage(), "doesn't exist")) {                    
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
		
		$lang = Factory::getLanguage();
		$db = Factory::getDBO();
		
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
     * @param   AfterJoomlaUpdateEvent $event  The event instance.
     *
     * @return  void
     *
     * @since   1.0.0
     *
     */	
	private function update_installs_securitycheckpro_storage($table,$name,$type) {
		$installs = null;
        $empty = true;
		$control_center_enabled = false;
        
        $db = Factory::getDBO();
		
		if ($table == "installs_remote") {
			// Check if controlcenter is enabled
			try {                        
				// Comprobamos si hay alg�n dato a�adido o la tabla es null; dependiendo del resultado haremos un 'update' o un 'insert'
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
							
				// Comprobamos si hay alg�n dato a�adido o la tabla es null; dependiendo del resultado haremos un 'update' o un 'insert'
				$query = $db->getQuery(true)
					->select(array('storage_value'))
					->from($db->quoteName('#__securitycheckpro_storage'))
					->where($db->quoteName('storage_key').' = '.$db->quote($table));
				$db->setQuery($query);
				$installs = $db->loadResult();		
									   
				if (!empty($installs)) {
					$empty = false;
					$installs_array = json_decode($installs, true);
					
					// Obtenemos s�lo el array de nombre para comprobar si ya hemos a�adido la extensi�n            
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
										
				// Instanciamos un objeto para almacenar los datos que ser�n sobreescritos/a�adidos
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
    public function onJoomlaAfterUpdate(Event $event): void
    {
        $name = "Joomla!";
        $type = "core";
		
		$this->update_installs_securitycheckpro_storage("installs",$name,$type);
		$this->update_installs_securitycheckpro_storage("installs_remote",$name,$type);
		
	}	
	
    /*  Chequeamos las extensions instaladas/actualizadas para usar esa info en la gesti�n de integridad de los archivos     
    *
    * @name   string  Nombre de la extensi�n extraido del manifest (i.e [string] name "Akeeba Backup package")
    *
    * @type  string  Tipo de extensi�n (component, package...)
    *
    * $files    array  array de los ficheros incluidos en el paquete (i.e [string] 1 = "plg_quickicon_akeebabackup.zip" [string] 2 = "plg_system_akeebaupdatecheck.zip")
    *
    *
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
	
	// Writes a file into the scan folder to know that we must update the vulnerabilities database
	function write_file()
    {
				
		$file_manag = @fopen($this->scan_path."update_vuln_table.php", 'ab');		
		
		if (empty($file_manag)) {
            return;
        }
	
		@fclose($file_manag);
    }
	
	function onExtensionAfterUninstall($installer, $eid)
    {		
		// Let's write a file to tell securitycheck that a new extension has been uninstalled. This is needed by /com_securitycheckpro/backend/models/securitycheckpros.php		
		$this->write_file();
	}
	
	function onExtensionAfterUpdate($installer, $eid)
    {		
		// Let's write a file to tell securitycheck that a new extension has been updated. This is needed by /com_securitycheckpro/backend/models/securitycheckpros.php		
		$this->write_file(); 
	}
        
}
