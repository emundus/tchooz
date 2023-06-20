<?php
/**
 * @package     Joomla\CMS\Console
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\CMS\Console;

use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// phpcs:disable PSR1.Files.SideEffects
\defined('JPATH_PLATFORM') or die;
// phpcs:enable PSR1.Files.SideEffects

class TchoozDeploymentRunCommand extends AbstractCommand
{
	use DatabaseAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected static $defaultName = 'tchooz:deployment:run';

    /**
     * @var DatabaseInterface
     * @since 4.0.0
     */
    private $db;

	/**
	 * Command constructor.
	 *
	 * @param   DatabaseInterface  $db  The database
	 *
	 * @since   4.2.0
	 */
	public function __construct(DatabaseInterface $db)
	{
        $this->db = $db;
        parent::__construct();
	}

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 *
	 * @since   4.0.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
        $query = $this->db->getQuery(true);

		$symfonyStyle = new SymfonyStyle($input, $output);

		$symfonyStyle->title('Tchooz deployment running...');

        $symfonyStyle->section('Configure SecuritycheckPro');
        try {
            $query->clear()
                ->update($this->db->quoteName('#__extensions'))
                ->set($this->db->quoteName('enabled') . ' = 1')
                ->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('url_inspector'))
                ->andWhere($this->db->quoteName('enabled') . ' = 0')
                ->andWhere($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('plugin'));
            $this->db->setQuery($query);
            $this->db->execute();

            $query->clear()
                ->select($this->db->quoteName('storage_value'))
                ->from($this->db->quoteName('#__securitycheckpro_storage'))
                ->where($this->db->quoteName('storage_key') . ' LIKE ' . $this->db->quote('pro_plugin'));
            $this->db->setQuery($query);
            $storage_value = $this->db->loadResult();

            if(empty($storage_value)) {
                $new_storage_values = array(
                    "dynamic_blacklist" => "1",
                    "dynamic_blacklist_time" => "600",
                    "dynamic_blacklist_counter" => "5",
                    "blacklist_email" => "0",
                    "priority1" => "Whitelist",
                    "priority2" => "DynamicBlacklist",
                    "priority3" => "Blacklist",
                    "methods" => "GET,POST,REQUEST",
                    "mode" => "1",
                    "logs_attacks" => "1",
                    "scp_delete_period" => "60",
                    "log_limits_per_ip_and_day" => "0",
                    "add_access_attempts_logs" => "0",
                    "redirect_after_attack" => "1",
                    "redirect_options" => "1",
                    "redirect_url" => "",
                    "custom_code" => "<p>The webmaster has forbidden your access to this site<\/p>",
                    "second_level" => "1",
                    "second_level_redirect" => "1",
                    "second_level_limit_words" => "3",
                    "second_level_words" => "ZHJvcCx1cGRhdGUsc2V0LGFkbWluLHNlbGVjdCx1c2VyLHBhc3N3b3JkLGNvbmNhdCxsb2dpbixsb2FkX2ZpbGUsYXNjaWksY2hhcix1bmlvbixmcm9tLGdyb3VwIGJ5LG9yZGVyIGJ5LGluc2VydCx2YWx1ZXMscGFzcyx3aGVyZSxzdWJzdHJpbmcsYmVuY2htYXJrLG1kNSxzaGExLHNjaGVtYSx2ZXJzaW9uLHJvd19jb3VudCxjb21wcmVzcyxlbmNvZGUsaW5mb3JtYXRpb25fc2NoZW1hLHNjcmlwdCxqYXZhc2NyaXB0LGltZyxzcmMsaW5wdXQsYm9keSxpZnJhbWUsZnJhbWUsJF9QT1NULGV2YWwsJF9SRVFVRVNULGJhc2U2NF9kZWNvZGUsZ3ppbmZsYXRlLGd6dW5jb21wcmVzcyxnemluZmxhdGUsc3RydHJleGVjLHBhc3N0aHJ1LHNoZWxsX2V4ZWMsY3JlYXRlRWxlbWVudA==",
                    "email_active" => "0",
                    "email_subject" => "Securitycheck Pro alert!",
                    "email_body" => "Securitycheck Pro has generated a new alert. Please, check your logs.",
                    "email_to" => "admin@emundus.fr",
                    "email_from_domain" => "me@mydomain.com",
                    "email_from_name" => "Your name",
                    "email_add_applied_rule" => "1",
                    "email_max_number" => "20",
                    "exclude_exceptions_if_vulnerable" => "1",
                    "check_header_referer" => "0",
                    "check_base_64" => "1",
                    "base64_exceptions" => "com_hikashop",
                    "strip_all_tags" => "1",
                    "tags_to_filter" => "applet,body,bgsound,base,basefont,embed,frame,frameset,head,html,id,iframe,ilayer,layer,link,meta,name,object,script,style,title,xml,svg,input,a",
                    "strip_tags_exceptions" => "com_jdownloads,com_hikashop,com_phocaguestbook",
                    "duplicate_backslashes_exceptions" => "com_kunena,com_securitycheckprocontrolcenter",
                    "line_comments_exceptions" => "com_comprofiler",
                    "sql_pattern_exceptions" => "",
                    "if_statement_exceptions" => "",
                    "using_integers_exceptions" => "com_dms,com_comprofiler,com_jce,com_contactenhanced,com_securitycheckprocontrolcenter",
                    "escape_strings_exceptions" => "com_kunena,com_jce",
                    "lfi_exceptions" => "",
                    "second_level_exceptions" => "",
                    "session_protection_active" => "0",
                    "session_hijack_protection" => "0",
                    "session_hijack_protection_what_to_check" => "1",
                    "session_protection_groups" => array("8"),
                    "track_failed_logins" => "0",
                    "logins_to_monitorize" => "2",
                    "write_log" => "1",
                    "actions_failed_login" => "0",
                    "email_on_admin_login" => "0",
                    "forbid_admin_frontend_login" => "0",
                    "forbid_new_admins" => "0",
                    "upload_scanner_enabled" => "1",
                    "check_multiple_extensions" => "1",
                    "mimetypes_blacklist" => "application/x-dosexec,application/x-msdownload ,text/x-php,application/x-php,application/x-httpd-php,application/x-httpd-php-source,application/javascript,application/xml",
                    "extensions_blacklist" => "php,js,exe,xml",
                    "delete_files" => "1",
                    "actions_upload_scanner" => "1",
                    "write_log_inspector" => "1",
                    "action_inspector" => "2",
                    "send_email_inspector" => "0",
                    "inspector_forbidden_words" => "wp-login.php,.git,owl.prev,tmp.php,home.php,Guestbook.php,aska.cgi,default.asp,jax_guestbook.php,bbs.cg,gastenboek.php,light.cgi,yybbs.cgi,wsdl.php,wp-content,cache_aqbmkwwx.php,.suspected,seo-joy.cgi,google-assist.php,wp-main.php,sql_dump.php,xmlsrpc.php",
                    "loggable_extensions" => array("com_banners","com_cache","com_categories","com_config","com_contact","com_content","com_installer","com_media","com_menus","com_messages","com_modules","com_newsfeeds","com_plugins","com_redirect","com_tags","com_templates","com_users")
                );

                $query->clear()
                    ->insert($this->db->quoteName('#__securitycheckpro_storage'))
                    ->set($this->db->quoteName('storage_value') . ' = ' . $this->db->quote(json_encode($new_storage_values)))
                    ->set($this->db->quoteName('storage_key') . ' = ' . $this->db->quote('pro_plugin'));
                $this->db->setQuery($query);
                if($this->db->execute()) {
                    $symfonyStyle->success('SecuritycheckPro configured successfully');
                } else {
                    $symfonyStyle->error('Error configuring SecuritycheckPro');
                    return 1;
                }
            }
        } catch (\Exception $e) {
            $symfonyStyle->error('Error configuring SecuritycheckPro: ' . $e->getMessage());
            return 1;
        }

        $symfonyStyle->section('Enable standard plugins');
        try {
            $plugins_to_enabled = [
                'emundus',
                'send_file_archive',
                'setup_category',
                'limit_obtained_alert',
                'custom_event_handler',
                'emundus_hikashop',
                'emunduswaitingroom',
                'emundus_block_user',
                'emundusregistrationredirect',
                'emundus_assign_to_files',
                'emundus_registration_email',
            ];
            $query->clear()
                ->update($this->db->quoteName('#__extensions'))
                ->set($this->db->quoteName('enabled') . ' = 1')
                ->where($this->db->quoteName('element') . ' IN (' . implode(',',$this->db->quote($plugins_to_enabled)) . ')')
                ->andWhere($this->db->quoteName('enabled') . ' = 0')
                ->andWhere($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('plugin'));
            $this->db->setQuery($query);
            if($this->db->execute()){
                $symfonyStyle->success('Standard plugins enabled');
            } else {
                $symfonyStyle->error('Error enabling Standard plugins');
            }
        } catch (\Exception $e) {
            $symfonyStyle->error('Error enabling standard plugins: ' . $e->getMessage());
            return 1;
        }


		$symfonyStyle->success('Tchooz is ready to use!');

		return 0;
	}

	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function configure(): void
	{
		$this->setDescription('Run a Tchooz deployment by installing a vanilla database and running the migrations');
		$this->setHelp(
			<<<EOF
The <info>%command.name%</info> command run a fresh deployment by installing a vanilla database and running the migrations
<info>php %command.full_name%</info>
EOF
		);
	}
}