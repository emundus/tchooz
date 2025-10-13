<?php
/**
 * @package    eMundus
 * @subpackage Components
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

require_once(JPATH_LIBRARIES . '/emundus/vendor/autoload.php');
include_once(JPATH_SITE . '/components/com_emundus/helpers/date.php');
require_once(JPATH_SITE . '/components/com_emundus/helpers/cache.php');

use Dompdf\Dompdf;
use Dompdf\Options;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use Joomla\CMS\Language\Text;
use Tchooz\Enums\Fabrik\ElementPlugin;
use Tchooz\Enums\ValueFormat;
use Tchooz\Factories\TransformerFactory;

/**
 * Emundus Fabrik Helper
 * @package     Emundus
 */
class EmundusHelperFabrik
{

	static function updateParam(array $params, $attribute, $value): array
	{
		$params[$attribute] = strval($value);

		return $params;
	}

	static function prepareListParams(): array
	{
		return array(
			'show-table-filters'                => '1',
			'advanced-filter'                   => '0',
			'advanced-filter-default-statement' => '=',
			'search-mode'                       => '0',
			'search-mode-advanced'              => '0',
			'search-mode-advanced-default'      => 'all',
			'search_elements'                   => '',
			'list_search_elements'              => 'null',
			'search-all-label'                  => 'All',
			'require-filter'                    => '0',
			'filter-dropdown-method'            => '0',
			'toggle_cols'                       => '0',
			'empty_data_msg'                    => '',
			'outro'                             => '',
			'list_ajax'                         => '0',
			'show-table-add'                    => '1',
			'show-table-nav'                    => '1',
			'show_displaynum'                   => '1',
			'showall-records'                   => '0',
			'show-total'                        => '0',
			'sef-slug'                          => '',
			'show-table-picker'                 => '1',
			'admin_template'                    => '',
			'show-title'                        => '1',
			'pdf'                               => '',
			'pdf_template'                      => '',
			'pdf_orientation'                   => 'portrait',
			'pdf_size'                          => 'a4',
			'pdf_include_bootstrap'             => '1',
			'bootstrap_stripped_class'          => '1',
			'bootstrap_bordered_class'          => '0',
			'bootstrap_condensed_class'         => '0',
			'bootstrap_hover_class'             => '1',
			'responsive_elements'               => '',
			'responsive_class'                  => '',
			'list_responsive_elements'          => 'null',
			'tabs_field'                        => '',
			'tabs_max'                          => '10',
			'tabs_all'                          => '1',
			'list_ajax_links'                   => '0',
			'actionMethod'                      => 'default',
			'detailurl'                         => '',
			'detaillabel'                       => '',
			'list_detail_link_icon'             => 'search',
			'list_detail_link_target'           => '_self',
			'editurl'                           => '',
			'editlabel'                         => '',
			'list_edit_link_icon'               => 'edit',
			'checkboxLocation'                  => 'end',
			'hidecheckbox'                      => '1',
			'addurl'                            => '',
			'addlabel'                          => '',
			'list_add_icon'                     => 'plus',
			'list_delete_icon'                  => 'delete',
			'popup_width'                       => '',
			'popup_height'                      => '',
			'popup_offset_x'                    => '',
			'popup_offset_y'                    => '',
			'note'                              => '',
			'alter_existing_db_cols'            => 'default',
			'process-jplugins'                  => '1',
			'cloak_emails'                      => '0',
			'enable_single_sorting'             => 'default',
			'collation'                         => 'utf8mb4_general_ci',
			'force_collate'                     => '',
			'list_disable_caching'              => '0',
			'distinct'                          => '1',
			'group_by_raw'                      => '1',
			'group_by_access'                   => '1',
			'group_by_order'                    => '',
			'group_by_template'                 => '',
			'group_by_template_extra'           => '',
			'group_by_order_dir'                => 'ASC',
			'group_by_start_collapsed'          => '0',
			'group_by_collapse_others'          => '0',
			'group_by_show_count'               => '1',
			'menu_module_prefilters_override'   => '1',
			'prefilter_query'                   => '',
			'join-display'                      => 'default',
			'delete-joined-rows'                => '0',
			'show_related_add'                  => '0',
			'show_related_info'                 => '0',
			'rss'                               => '0',
			'feed_title'                        => '',
			'feed_date'                         => '',
			'feed_image_src'                    => '',
			'rsslimit'                          => '150',
			'rsslimitmax'                       => '2500',
			'csv_import_frontend'               => '10',
			'csv_export_frontend'               => '10',
			'csvfullname'                       => '2',
			'csv_export_step'                   => '100',
			'newline_csv_export'                => 'nl2br',
			'csv_clean_html'                    => 'leave',
			'csv_multi_join_split'              => ',',
			'csv_custom_qs'                     => '',
			'csv_frontend_selection'            => '0',
			'incfilters'                        => '0',
			'csv_format'                        => '0',
			'csv_which_elements'                => 'selected',
			'show_in_csv'                       => '',
			'csv_elements'                      => 'null',
			'csv_include_data'                  => '1',
			'csv_include_raw_data'              => '0',
			'csv_include_calculations'          => '0',
			'csv_filename'                      => '',
			'csv_encoding'                      => 'UTF-8',
			'csv_double_quote'                  => '1',
			'csv_local_delimiter'               => '',
			'csv_end_of_line'                   => 'n',
			'open_archive_active'               => '0',
			'open_archive_set_spec'             => '',
			'open_archive_timestamp'            => '',
			'open_archive_license'              => 'http://creativecommons.org/licenses/by-nd/2.0/rdf',
			'dublin_core_type'                  => 'dc:description.abstract',
			'raw'                               => '0',
			'open_archive_elements'             => 'null',
			'search_use'                        => '0',
			'search_title'                      => '',
			'search_description'                => '',
			'search_date'                       => '',
			'search_link_type'                  => 'details',
			'dashboard'                         => '0',
			'dashboard_icon'                    => '',
			'allow_view_details'                => '11',
			'allow_edit_details'                => '11',
			'allow_edit_details2'               => '',
			'allow_add'                         => '11',
			'allow_delete'                      => '10',
			'allow_delete2'                     => '',
			'allow_drop'                        => '10',
			'menu_access_only'                  => '0',
			'isview'                            => '0',
		);
	}

	static function prepareFormParams(bool $init_plugins = true, ?string $type = ''): array
	{
		$params = array(
			'outro'                              => '',
			'copy_button'                        => '0',
			'copy_button_label'                  => 'Save as copy',
			'copy_button_class'                  => '',
			'copy_icon'                          => '',
			'copy_icon_location'                 => 'before',
			'reset_button'                       => '0',
			'reset_button_label'                 => 'Remise à zéro',
			'reset_button_class'                 => 'btn-warning',
			'reset_icon'                         => '',
			'reset_icon_location'                => 'before',
			'apply_button'                       => '0',
			'apply_button_label'                 => 'Appliquer',
			'apply_button_class'                 => '',
			'apply_icon'                         => '',
			'apply_icon_location'                => 'before',
			'goback_button'                      => $type == 'eval' ? '0' : '1',
			'goback_button_label'                => 'GO_BACK',
			'goback_button_class'                => 'goback-btn',
			'goback_icon'                        => '',
			'goback_icon_location'               => 'before',
			'submit_button'                      => '1',
			'submit_button_label'                => 'SAVE_CONTINUE',
			'save_button_class'                  => 'btn-primary save-btn sauvegarder',
			'save_icon'                          => '',
			'save_icon_location'                 => 'after',
			'submit_on_enter'                    => '0',
			'delete_button'                      => '0',
			'delete_button_label'                => 'GO_BACK',
			'delete_button_class'                => 'btn-danger',
			'delete_icon'                        => '',
			'delete_icon_location'               => 'before',
			'ajax_validations'                   => '0',
			'ajax_validations_toggle_submit'     => '0',
			'submit-success-msg'                 => '',
			'suppress_msgs'                      => '0',
			'show_loader_on_submit'              => '0',
			'spoof_check'                        => '1',
			'multipage_save'                     => '0',
			'note'                               => '',
			'labels_above'                       => '1',
			'labels_above_details'               => '1',
			'pdf_template'                       => '',
			'pdf_orientation'                    => 'portrait',
			'pdf_size'                           => 'letter',
			'pdf_include_bootstrap'              => '1',
			'admin_form_template'                => '',
			'admin_details_template'             => '',
			'show-title'                         => '1',
			'print'                              => '',
			'email'                              => '',
			'pdf'                                => '',
			'show-referring-table-releated-data' => '0',
			'tiplocation'                        => 'above'
		);

		$plugins = [];
		if ($init_plugins)
		{
			if ($type == 'eval')
			{
				$plugins = [
					'curl_code'             => [
						1 => 'use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
$app = Factory::getApplication();
$input = $app->getInput();

$student_id = $input->getInt("student_id", null);
$student = isset($student_id) ? JUser::getInstance($student_id) : JUser::getInstance("{jos_emundus_evaluations___student_id}");


echo "<h2>".$student->name."</h2>";
HTMLHelper::styleSheet(JURI::base() . "media/jui/css/chosen.css");
HTMLHelper::stylesheet(JURI::Base()."media/com_fabrik/css/fabrik.css");'
					],
					'only_process_curl'     => [
						1 => 'onLoad'
					],
					'form_php_file'         => [
						1 => '-1'
					],
					'form_php_require_once' => [
						1 => '0'
					],
					'process-jplugins'      => '2',
					'plugins'               => array('emundusstepevaluation', 'php'),
					'plugin_state'          => array('1', '1'),
					'plugin_locations'      => array('both', 'both'),
					'plugin_events'         => array('both', 'both'),
					'plugin_description'    => array('Gestion d\'accès à la phase d\'évaluation', 'css'),
				];
			}
			else
			{
				$plugins = [
					'process-jplugins'   => '2',
					'plugins'            => array("emundustriggers"),
					'plugin_state'       => array("1"),
					'plugin_locations'   => array("both"),
					'plugin_events'      => array("both"),
					'plugin_description' => array("emundus_events"),
				];
			}
		}

		return array_merge($params, $plugins);
	}

	function prepareSubmittionPlugin(array $params): array
	{
		$params['submit_button_label'] = 'SUBMIT';
		$params['submit-success-msg']  = 'APPLICATION_SENT';

		return $params;
	}

	static function prepareGroupParams(): array
	{
		return array(
			'split_page'                 => '0',
			'list_view_and_query'        => '1',
			'access'                     => '1',
			'intro'                      => '',
			'outro'                      => '',
			'repeat_group_button'        => '0',
			'repeat_template'            => 'repeatgroup',
			'repeat_max'                 => '',
			'repeat_min'                 => '',
			'repeat_num_element'         => '',
			'repeat_error_message'       => '',
			'repeat_no_data_message'     => '',
			'repeat_intro'               => '',
			'repeat_add_access'          => '1',
			'repeat_delete_access'       => '1',
			'repeat_delete_access_user'  => '',
			'repeat_copy_element_values' => '0',
			'group_columns'              => '1',
			'group_column_widths'        => '',
			'repeat_group_show_first'    => '-1',
			'random'                     => '0',
			'labels_above'               => '-1',
			'labels_above_details'       => '-1',
		);
	}

	static function prepareElementParameters(string $plugin, ?bool $notempty = true, ?int $attachementId = 0): array
	{

		$plugin_no_required = ['display', 'panel'];
		$plugin_to_setup    = '';
		if ($plugin == 'nom' || $plugin == 'prenom' || $plugin == 'email')
		{
			$plugin_to_setup = $plugin;
			$plugin          = 'field';
		}

		$params = array(
			'show_in_rss_feed'        => '0',
			'bootstrap_class'         => 'input-large',
			'show_label_in_rss_feed'  => '0',
			'use_as_rss_enclosure'    => '0',
			'rollover'                => '',
			'tipseval'                => '0',
			'tiplocation'             => 'top-left',
			'labelindetails'          => '0',
			'labelinlist'             => '0',
			'comment'                 => '',
			'edit_access'             => '1',
			'edit_access_user'        => '',
			'view_access'             => '1',
			'view_access_user'        => '',
			'list_view_access'        => '1',
			'encrypt'                 => '0',
			'store_in_db'             => '1',
			'default_on_copy'         => '0',
			'can_order'               => '0',
			'alt_list_heading'        => '',
			'custom_link'             => '',
			'custom_link_target'      => '',
			'custom_link_indetails'   => '1',
			'use_as_row_class'        => '0',
			'include_in_list_query'   => '1',
			'always_render'           => '0',
			'icon_folder'             => '0',
			'icon_hovertext'          => '1',
			'icon_file'               => '',
			'icon_subdir'             => '',
			'filter_length'           => '20',
			'filter_access'           => '1',
			'full_words_only'         => '0',
			'filter_required'         => '0',
			'filter_build_method'     => '0',
			'filter_groupby'          => 'text',
			'inc_in_adv_search'       => '1',
			'filter_class'            => 'input-medium',
			'filter_responsive_class' => '',
			'tablecss_header_class'   => '',
			'tablecss_header'         => '',
			'tablecss_cell_class'     => '',
			'tablecss_cell'           => '',
			'sum_on'                  => '0',
			'sum_label'               => 'Sum',
			'sum_access'              => '1',
			'sum_split'               => '',
			'avg_on'                  => '0',
			'avg_label'               => 'Average',
			'avg_access'              => '1',
			'avg_round'               => '0',
			'avg_split'               => '',
			'median_on'               => '0',
			'median_label'            => 'Median',
			'median_access'           => '1',
			'median_split'            => '',
			'count_on'                => '0',
			'count_label'             => 'Count',
			'count_condition'         => '',
			'count_access'            => '1',
			'count_split'             => '',
			'custom_calc_on'          => '0',
			'custom_calc_label'       => 'Custom',
			'custom_calc_query'       => '',
			'custom_calc_access'      => '1',
			'custom_calc_split'       => '',
			'custom_calc_php'         => '',
			'validations'             => array(),
			'alias'                   => '',
		);

		if ($notempty && !in_array($plugin, $plugin_no_required))
		{
			$params['validations']                   = array(
				'plugin'           => array(
					"notempty",
				),
				'plugin_published' => array(
					"1",
				),
				'validate_in'      => array(
					"both",
				),
				'validation_on'    => array(
					"both",
				),
				'validate_hidden'  => array(
					"0",
				),
				'must_validate'    => array(
					"0",
				),
				'show_icon'        => array(
					"1",
				),
			);
			$params['notempty-message']              = array();
			$params['notempty-validation_condition'] = array();
		}

		if ($plugin == 'date')
		{
			$params['bootstrap_class']            = 'input-xlarge';
			$params['date_showtime']              = '0';
			$params['date_time_format']           = 'H:i';
			$params['date_which_time_picker']     = 'wicked';
			$params['date_show_seconds']          = '0';
			$params['date_24hour']                = '1';
			$params['bootstrap_time_class']       = 'input-medium';
			$params['placeholder']                = 'dd/mm/yyyy';
			$params['date_store_as_local']        = '1';
			$params['date_table_format']          = 'd\/m\/Y';
			$params['date_form_format']           = 'd/m/Y';
			$params['date_defaulttotoday']        = '1';
			$params['date_alwaystoday']           = '0';
			$params['date_firstday']              = '1';
			$params['date_allow_typing_in_field'] = '1';
			$params['date_csv_offset_tz']         = '0';
			$params['date_advanced']              = '0';
			$params['date_allow_func']            = '';
			$params['date_allow_php_func']        = '';
			$params['date_observe']               = '';
		}

		if ($plugin == 'jdate')
		{
			$params['bootstrap_class']             = 'col-sm-8';
			$params['jdate_showtime']              = '0';
			$params['jdate_time_format']           = 'H:i';
			$params['jdate_time_24']               = '1';
			$params['jdate_show_week_numbers']     = '0';
			$params['placeholder']                 = 'dd/mm/yyyy';
			$params['jdate_store_as_local']        = '1';
			$params['jdate_table_format']          = 'd\/m\/Y';
			$params['jdate_form_format']           = 'd/m/Y';
			$params['jdate_defaulttotoday']        = '1';
			$params['jdate_alwaystoday']           = '0';
			$params['jdate_allow_typing_in_field'] = '1';
			$params['jdate_csv_offset_tz']         = '0';
		}

		if ($plugin == 'databasejoin')
		{
			$params['bootstrap_class']                    = 'span12';
			$params['database_join_display_type']         = 'dropdown';
			$params['join_db_name']                       = '';
			$params['join_key_column']                    = '';
			$params['join_val_column']                    = '';
			$params['join_conn_id']                       = '1';
			$params['database_join_where_sql']            = '';
			$params['database_join_where_access']         = '1';
			$params['database_join_where_when']           = '3';
			$params['databasejoin_where_ajax']            = '0';
			$params['database_join_filter_where_sql']     = '';
			$params['database_join_show_please_select']   = '1';
			$params['database_join_noselectionvalue']     = '0';
			$params['database_join_noselectionlabel']     = '';
			$params['placeholder']                        = '';
			$params['databasejoin_popupform']             = '0';
			$params['fabrikdatabasejoin_frontend_add']    = '0';
			$params['join_popupwidth']                    = '';
			$params['databasejoin_readonly_link']         = '0';
			$params['fabrikdatabasejoin_frontend_select'] = '0';
			$params['advanced_behavior']                  = '0';
			$params['dbjoin_options_per_row']             = '1';
			$params['dbjoin_multiselect_max']             = '0';
			$params['dbjoin_multilist_size']              = '6';
			$params['dbjoin_autocomplete_size']           = '20';
			$params['dbjoin_autocomplete_rows']           = '10';
			$params['dabase_join_label_eval']             = '';
			$params['join_desc_column']                   = '';
			$params['dbjoin_autocomplete_how']            = 'contains';
			$params['join_val_column_concat']             = '';
			$params['clean_concat']                       = '0';

			$ref_tables = ['data_nationality', 'data_country', 'data_departements'];
			foreach ($ref_tables as $table)
			{
				$db = Factory::getContainer()->get('DatabaseDriver');
				$db->setQuery("SHOW TABLES LIKE " . $db->quote('data_nationality'));
				$tableExists = $db->loadResult();

				if (!empty($tableExists))
				{
					$params['join_db_name'] = $table;

					if (in_array($table, ['data_nationality', 'data_country']))
					{
						$params['join_key_column'] = 'id';
						$params['join_val_column'] = 'label_fr';
					}
					else
					{
						if ($table == 'data_departements')
						{
							$params['join_key_column'] = 'departement_id';
							$params['join_val_column'] = 'departement_nom';
						}
					}
					break;
				}
			}
		}

		if ($plugin == 'user')
		{
			$params['my_table_data']                  = 'id';
			$params['update_on_edit']                 = '0';
			$params['update_on_copy']                 = '0';
			$params['user_use_social_plugin_profile'] = '0';
			$params['user_noselectionlabel']          = '';
		}

		if ($plugin == 'field')
		{
			$params['placeholder']                 = '';
			$params['password']                    = 0;
			$params['maxlength']                   = 255;
			$params['disable']                     = 0;
			$params['readonly']                    = 0;
			$params['autocomplete']                = 1;
			$params['speech']                      = 0;
			$params['advanced_behavior']           = 0;
			$params['text_format']                 = 'text';
			$params['integer_length']              = 11;
			$params['decimal_length']              = 2;
			$params['field_use_number_format']     = 0;
			$params['field_thousand_sep']          = ',';
			$params['field_decimal_sep']           = '.';
			$params['text_format_string']          = '';
			$params['field_format_string_blank']   = 1;
			$params['text_input_mask']             = '';
			$params['text_input_mask_autoclear']   = 0;
			$params['text_input_mask_definitions'] = '';
			$params['render_as_qrcode']            = '0';
			$params['scan_qrcode']                 = '0';
			$params['guess_linktype']              = '0';
			$params['link_target_options']         = 'default';
			$params['rel']                         = '';
			$params['link_title']                  = '';
			$params['link_attributes']             = '';

			if ($plugin_to_setup == 'email')
			{
				$params['password'] = 3;

				$params['validations']['plugin'][]           = 'isemail';
				$params['validations']['plugin_published'][] = '1';
				$params['validations']['validate_in'][]      = 'both';
				$params['validations']['validation_on'][]    = 'both';
				$params['validations']['validate_hidden'][]  = '0';
				$params['validations']['must_validate'][]    = '0';
				$params['validations']['show_icon'][]        = '0';

				$params['isemail-message']              = array('', '');
				$params['isemail-validation_condition'] = array('', '');
				$params['isemail-allow_empty']          = array('', '1');
				$params['isemail-check_mx']             = array('', '0');
			}
		}

		if ($plugin == 'textarea')
		{
			$params['textarea_placeholder']    = '';
			$params['height']                  = '6';
			$params['use_wysiwyg']             = '0';
			$params['maxlength']               = '255';
			$params['textarea-showmax']        = '0';
			$params['width']                   = '60';
			$params['wysiwyg_extra_buttons']   = '1';
			$params['textarea_field_type']     = 'TEXT';
			$params['textarea_limit_type']     = 'char';
			$params['textarea-tagify']         = '0';
			$params['textarea_tagifyurl']      = '';
			$params['textarea-truncate-where'] = '0';
			$params['textarea-truncate-html']  = '0';
			$params['textarea-truncate']       = '0';
			$params['textarea-hover']          = '1';
			$params['textarea_hover_location'] = 'top';
			$params['bootstrap_class']         = 'input-xxlarge';
		}

		if ($plugin == 'dropdown' || $plugin == 'checkbox' || $plugin == 'radiobutton')
		{
			$params['sub_options']       = array(
				'sub_values'            => array(),
				'sub_labels'            => array(),
				'sub_initial_selection' => array(),
			);
			$params['options_split_str'] = '';
			$params['dropdown_populate'] = '';
		}

		if ($plugin == 'dropdown')
		{
			$params['multiple']                     = '0';
			$params['dropdown_multisize']           = '3';
			$params['allow_frontend_addtodropdown'] = '0';
			$params['dd-allowadd-onlylabel']        = '0';
			$params['dd-savenewadditions']          = '0';
		}

		if ($plugin == 'checkbox')
		{
			$params['ck_options_per_row']           = '1';
			$params['sub_default_value']            = '';
			$params['sub_default_label']            = '';
			$params['allow_frontend_addtocheckbox'] = '0';
			$params['chk-allowadd-onlylabel']       = '0';
			$params['chk-savenewadditions']         = '0';
		}

		if ($plugin == 'radiobutton')
		{
			$params['options_per_row']        = 1;
			$params['btnGroup']               = 0;
			$params['rad-allowadd-onlylabel'] = 0;
			$params['rad-savenewadditions']   = 0;
		}

		if ($plugin == 'birthday')
		{
			$params['birthday_daylabel']   = '';
			$params['birthday_monthlabel'] = '';
			$params['birthday_yearlabel']  = '';
			$params['birthday_yearopt']    = '';
			$params['birthday_yearstart']  = '1950';
			$params['birthday_forward']    = '0';
			$params['details_date_format'] = 'd.m.Y';
			$params['details_dateandage']  = '0';
			$params['list_date_format']    = 'd.m.Y';
			$params['list_age_format']     = 'no';
			$params['empty_is_null']       = '1';
		}

		if ($plugin == 'years')
		{
			$params['birthday_yearopt']   = 'number';
			$params['birthday_forward']   = '0';
			$params['birthday_yearstart'] = '100';
		}

		if ($plugin == 'display')
		{
			$params['display_showlabel'] = '1';
		}

		if ($plugin == 'emundus_fileupload')
		{
			$params['size']                 = '10485760';
			$params['attachmentId']         = $attachementId;
			$params['can_submit_encrypted'] = '2';
		}

		if ($plugin == 'yesno')
		{
			$params['yesno_default']   = '0';
			$params['yesno_icon_yes']  = '';
			$params['yesno_icon_no']   = '';
			$params['options_per_row'] = '4';
			$params['toggle_others']   = '0';
			$params['toggle_where']    = '';
		}

		if ($plugin == 'currency')
		{

			$object                                                      = (object) [
				'iso3'               => 'EUR',
				'minimal_value'      => '0.00',
				'maximal_value'      => '1000000.00',
				'thousand_separator' => ' ',
				'decimal_separator'  => ',',
				'decimal_numbers'    => '2'
			];
			$params['all_currencies_options']['all_currencies_options0'] = $object;
		}

		if ($plugin == 'emundus_phonenumber')
		{
			$params['default_country'] = 'FR';
		}

		if ($plugin == 'panel')
		{
			$params['type']        = '1';
			$params['accordion']   = '0';
			$params['title']       = '';
			$params['store_in_db'] = 0;
		}

		if ($plugin == 'iban')
		{
			$params['encrypt_datas'] = '1';
		}

		if ($plugin == 'average')
		{
			$params['average_multiple_element']  = '';
			$params['average_multiple_weight']   = '1';
			$params['average_multiple_elements'] = json_encode([
				'average_multiple_element' => [''],
				'average_multiple_weight'  => ['1'],
			]);
			$params['used_as_total']             = 0;
		}

		return $params;
	}

	static function getDBType(string $plugin): string
	{
		$dbtype = 'TEXT';

		if ($plugin == 'birthday')
		{
			$dbtype = 'DATE';
		}
		if (in_array($plugin, ['date', 'jdate']))
		{
			$dbtype = 'DATETIME';
		}

		return $dbtype;
	}

	static function initLabel(?string $plugin): array
	{
		return match ($plugin)
		{
			'nom' => array(
				'fr' => 'Nom',
				'en' => 'Name',
			),
			'prenom' => array(
				'fr' => 'Prénom',
				'en' => 'First name',
			),
			'email' => array(
				'fr' => 'Adresse e-mail',
				'en' => 'E-mail address',
			),
			'emundus_phonenumber' => array(
				'fr' => 'Numéro de téléphone',
				'en' => 'Phone number',
			),
			default => array(
				'fr' => '',
				'en' => '',
			),
		};
	}

	static function prepareFabrikMenuParams(): array
	{
		return [
			'rowid'                 => '',
			'usekey'                => '',
			'random'                => '0',
			'fabriklayout'          => '',
			'extra_query_string'    => '',
			'menu-anchor_title'     => '',
			'menu-anchor_css'       => '',
			'menu_image'            => '',
			'menu_image_css'        => '',
			'menu_text'             => '1',
			'menu_show'             => '1',
			'page_title'            => '',
			'show_page_heading'     => '0',
			'page_heading'          => '',
			'pageclass_sfx'         => 'applicant-form',
			'menu-meta_description' => '',
			'menu-meta_keywords'    => '',
			'robots'                => '',
			'secure'                => '0',
		];
	}

	static function addOption($eid, $label, $value): bool
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		try
		{
			$query->select('params')
				->from($db->quoteName('#__fabrik_elements'))
				->where($db->quoteName('id') . ' = ' . $db->quote($eid));
			$db->setQuery($query);
			$params = json_decode($db->loadResult(), true);

			$params['sub_options']['sub_values'][] = $value;
			$params['sub_options']['sub_labels'][] = $label;

			$query->clear()
				->update($db->quoteName('#__fabrik_elements'))
				->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
				->where($db->quoteName('id') . ' = ' . $db->quote($eid));
			$db->setQuery($query);

			return $db->execute();
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/helpers/fabrik | Cannot add option for element ' . $eid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	static function addNotEmptyValidation($eid, $message = '', $condition = ''): bool
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		try
		{
			$query->select('params')
				->from($db->quoteName('#__fabrik_elements'))
				->where($db->quoteName('id') . ' = ' . $db->quote($eid));
			$db->setQuery($query);
			$params = json_decode($db->loadResult(), true);

			$params['notempty-message']              = $message;
			$params['notempty-validation_condition'] = $condition;
			if (!isset($params['validations']['plugin']))
			{
				$params['validations'] = array(
					'plugin'           => array(),
					'plugin_published' => array(),
					'validate_in'      => array(),
					'validation_on'    => array(),
					'validate_hidden'  => array(),
					'must_validate'    => array(),
					'show_icon'        => array(),
				);
			}
			$params['validations']['plugin'][]           = 'notempty';
			$params['validations']['plugin_published'][] = '1';
			$params['validations']['validate_in'][]      = 'both';
			$params['validations']['validation_on'][]    = 'both';
			$params['validations']['validate_hidden'][]  = '0';
			$params['validations']['must_validate'][]    = '0';
			$params['validations']['show_icon'][]        = '1';

			$query->clear()
				->update($db->quoteName('#__fabrik_elements'))
				->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
				->where($db->quoteName('id') . ' = ' . $db->quote($eid));
			$db->setQuery($query);

			return $db->execute();
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/helpers/fabrik | Cannot add notempty validation for element ' . $eid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	static function checkFabrikJoins($eid, $name, $plugin, $group_id): bool
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		try
		{
			if ($plugin == 'user')
			{
				$params = array(
					'join-label' => 'id',
					'type'       => 'element',
					'pk'         => '`#__users`.`id`',
				);
				$data   = array(
					'list_id'         => 0,
					'element_id'      => $eid,
					'join_from_table' => '',
					'table_join'      => '#__users',
					'table_key'       => $name,
					'table_join_key'  => 'id',
					'join_type'       => 'left',
					'group_id'        => $group_id,
					'params'          => json_encode($params)
				);

				$query->insert($db->quoteName('#__fabrik_joins'))
					->columns($db->quoteName(array_keys($data)))
					->values(implode(',', $db->quote(array_values($data))));
				$db->setQuery($query);

				return $db->execute();
			}

			return true;
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/helpers/fabrik | Cannot check fabrik joins for element ' . $eid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	static function addJsAction($eid, $action): bool
	{
		$added = false;

		if (!empty($eid) && !empty($action))
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			try
			{
				$query->select('count(id)')
					->from($db->quoteName('#__fabrik_jsactions'))
					->where($db->quoteName('element_id') . ' = ' . $db->quote($eid));
				$db->setQuery($query);
				$assignations = $db->loadResult();

				if (empty($assignations))
				{
					$js     = null;
					$params = [
						'js_e_event'     => '',
						'js_e_trigger'   => '',
						'js_e_condition' => '',
						'js_e_value'     => '',
						'js_published'   => '1',
					];

					$event = 'change';
					if (is_string($action))
					{
						if ($action == 'nom')
						{
							$js    = "this.set(this.get('value').toUpperCase());";
							$event = 'keyup';
						}
						if ($action == 'prenom')
						{
							$js    = "const mySentence = this.get(&#039;value&#039;);if(mySentence !== '') {const words = mySentence.split(&quot; &quot;);for (let i = 0; i &lt; words.length; i++) {words[i] = words[i][0].toUpperCase() + words[i].substr(1);};this.set(words.join(&quot; &quot;));}";
							$event = 'keyup';
						}
					}
					elseif (is_array($action))
					{
						$js    = $action['code'];
						$event = $action['event'];
					}

					if (!empty($js) && !empty($params))
					{
						$query->clear()
							->insert($db->quoteName('#__fabrik_jsactions'))
							->set($db->quoteName('element_id') . ' = ' . $db->quote($eid))
							->set($db->quoteName('action') . ' = ' . $db->quote($event))
							->set($db->quoteName('code') . ' = ' . $db->quote($js))
							->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)));
						$db->setQuery($query);
						$added = $db->execute();
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus/helpers/fabrik | Cannot create JS Action for element ' . $eid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $added;
	}

	static function getTableFromFabrik($id, $object = 'list'): string
	{
		$table_name = '';

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		try
		{
			$where_column = ($object == 'list') ? 'id' : 'form_id';

			$query->select('db_table_name')
				->from($db->quoteName('#__fabrik_lists'))
				->where($db->quoteName($where_column) . ' = ' . $db->quote($id));

			$db->setQuery($query);
			$table_name = $db->loadResult();
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/helpers/fabrik | Cannot get table from fabrik with type ' . $object . ' ' . $id . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $table_name;
	}

	static function createFilterList(&$filters, $eid, $value, $condition = '=', $join = 'AND', $hidden = 0): array
	{
		if (!isset($filters['elementid']))
		{
			$filters['elementid']           = array();
			$filters['value']               = array();
			$filters['condition']           = array();
			$filters['join']                = array();
			$filters['no-filter-setup']     = array();
			$filters['hidden']              = array();
			$filters['key']                 = array();
			$filters['key2']                = array();
			$filters['search_type']         = array();
			$filters['match']               = array();
			$filters['eval']                = array();
			$filters['required']            = array();
			$filters['access']              = array();
			$filters['grouped_to_previous'] = array();
			$filters['raw']                 = array();
			$filters['orig_condition']      = array();
			$filters['sqlCond']             = array();
			$filters['origvalue']           = array();
			$filters['filter']              = array();
		}

		if (!in_array($eid, $filters['elementid']))
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('fl.db_table_name,fe.name')
				->from($db->quoteName('#__fabrik_elements', 'fe'))
				->leftJoin($db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $db->quoteName('ffg.group_id') . ' = ' . $db->quoteName('fe.group_id'))
				->leftJoin($db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $db->quoteName('fl.form_id') . ' = ' . $db->quoteName('ffg.form_id'))
				->where($db->quoteName('fe.id') . ' = ' . $db->quote($eid));
			$db->setQuery($query);
			$element_details = $db->loadObject();

			$filters['elementid'][]           = $eid;
			$filters['value'][]               = $value;
			$filters['condition'][]           = $condition;
			$filters['join'][]                = $join;
			$filters['no-filter-setup'][]     = 0;
			$filters['hidden'][]              = $hidden;
			$filters['key'][]                 = '`' . $element_details->db_table_name . '`.`' . $element_details->name . '`';
			$filters['key2'][]                = '';
			$filters['search_type'][]         = 'querystring';
			$filters['match'][]               = '1';
			$filters['eval'][]                = 3;
			$filters['required'][]            = '0';
			$filters['access'][]              = '1';
			$filters['grouped_to_previous'][] = 0;
			$filters['raw'][]                 = 0;
			$filters['orig_condition'][]      = '=';
			$filters['sqlCond'][]             = ' `' . $element_details->db_table_name . '`.`' . $element_details->name . '` = ' . $value . ' ';
			$filters['origvalue'][]           = $value;
			$filters['filter'][]              = $value;
		}

		return $filters;
	}

	static function getFormattedPhoneNumberValue(string|array|null $phone_number, $format = PhoneNumberFormat::E164): string
	{
		$formattedValue = '';

		if (!empty($phone_number))
		{
			if (is_array($phone_number))
			{
				$phone_number = $phone_number['country_code'] . $phone_number['num_tel'];
			}

			$phone_number = trim($phone_number);
			$phone_number = str_replace(' ', '', $phone_number);

			$iso2Test          = '';
			$phone_number_util = PhoneNumberUtil::getInstance();

			if (preg_match('/^\w{2}/', $phone_number))
			{
				$iso2Test     = substr($phone_number, 0, 2);
				$phone_number = substr($phone_number, 2);
			}

			if (preg_match('/^\+\d+$/', $phone_number))
			{
				try
				{
					$phone_number = $phone_number_util->parse($phone_number);
					$iso2         = $phone_number_util->getRegionCodeForNumber($phone_number);

					if ($iso2 || $iso2 === $iso2Test)
					{
						$formattedValue = $iso2 . $phone_number_util->format($phone_number, $format);
					}
				}
				catch (Exception $e)
				{
					Log::add('EmundusHelperFabrik::getFormattedPhoneNumberValue Phone number lib returned an error for given phone number ' . $phone_number . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
				}
			}
		}

		return $formattedValue;
	}

	public static function getDbTableName($formid): string
	{
		$db_table_name = '';

		if (!empty($formid))
		{
			try
			{
				$db_table_name = self::getTableFromFabrik($formid, 'form');
			}
			catch (Exception $e)
			{
				Log::add('EmundusHelperFabrik::getDbTableName Cannot get table name for form ' . $formid . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $db_table_name;
	}

	static function formatElementValue($elt_name, $raw_value, $groupId = null, $uid = null, $html = false)
	{
		$formatted_value = $raw_value;
		$app             = Factory::getApplication();

		if (!empty($elt_name))
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$element = null;

			$query->select('fe.id,fe.name,fe.params,fe.plugin, fe.label, fe.group_id')
				->from($db->quoteName('#__fabrik_elements', 'fe'))
				->where($db->quoteName('name') . ' = ' . $db->quote($elt_name));

			if (!empty($groupId))
			{
				$query->andWhere($db->quoteName('fe.group_id') . ' = ' . $db->quote($groupId));
			}

			try
			{
				$db->setQuery($query);
				$element = $db->loadObject();
			}
			catch (Exception $e)
			{
				Log::add('components/com_emundus/helpers/fabrik | Error when try to get fabrik elements table data : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
			}

			if (!empty($element))
			{
				$params = json_decode($element->params, true);

				switch ($element->plugin)
				{
					case 'date':
					case 'jdate':
						$date_format = self::getFabrikDateParam($element, 'date_form_format');
						$local       = self::getFabrikDateParam($element, 'date_store_as_local') ? 1 : 0;

						$formatted_value = EmundusHelperDate::displayDate($raw_value, $date_format, $local);
						break;

					case 'birthday':
						preg_match('/([0-9]{4})-([0-9]{1,})-([0-9]{1,})/', $raw_value, $matches);
						if (count($matches) != 0)
						{
							$format = $params['list_date_format'];

							$d = DateTime::createFromFormat($format, $raw_value);
							if ($d && $d->format($format) == $raw_value)
							{
								$formatted_value = $html ? HTMLHelper::_('date', $raw_value, Text::_('DATE_FORMAT_LC')) : EmundusHelperDate::displayDate($raw_value);
							}
							else
							{
								$formatted_value = $html ? HTMLHelper::_('date', $raw_value, $format) : EmundusHelperDate::displayDate($raw_value, $format);
							}
						}
						break;

					case 'emundus_phonenumber':
						$formatted_value = self::getFormattedPhoneNumberValue($raw_value);
						break;

					case 'databasejoin':
						$select = $params['join_val_column'];
						if (!empty($params['join_val_column_concat']))
						{
							$select = 'CONCAT(' . $params['join_val_column_concat'] . ')';
							$select = preg_replace('#{thistable}#', 'jd', $select);
							$select = preg_replace('#{shortlang}#', substr($app->getLanguage()->getTag(), 0, 2), $select);
							if (!empty($uid))
							{
								$select = preg_replace('#{my->id}#', $uid, $select);
							}
						}

						$query->clear()
							->select($select)
							->from($db->quoteName($params['join_db_name'], 'jd'));

						if (($params['database_join_display_type'] == 'checkbox' || $params['database_join_display_type'] == 'multilist') && is_array($raw_value))
						{
							$query->where($db->quoteName('jd.' . $params['join_key_column']) . ' IN (' . implode(',', $raw_value) . ')');
							$db->setQuery($query);
							$res = $db->loadColumn();

							$formatted_value = $html ? "<ul><li>" . implode("</li><li>", $res) . "</li></ul>" : implode(',', $res);
						}
						elseif (!is_array($raw_value))
						{
							$query->where($db->quoteName('jd.' . $params['join_key_column']) . ' = ' . $db->quote($raw_value));
							$db->setQuery($query);

							$formatted_value = $db->loadResult();
						}

						break;

					case 'cascadingdropdown':
						$cascadingdropdown_id    = $params['cascadingdropdown_id'];
						$cascadingdropdown_label = Text::_($params['cascadingdropdown_label']);

						$r1     = explode('___', $cascadingdropdown_id);
						$r2     = explode('___', $cascadingdropdown_label);
						$select = !empty($params['cascadingdropdown_label_concat'] ? "CONCAT(" . $params['cascadingdropdown_label_concat'] . ")" : $r2[1]);
						$from   = $r2[0];
						$where  = $r1[1] . '=' . $db->Quote($raw_value);
						$query  = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
						$query  = preg_replace('#{thistable}#', $from, $query);
						$query  = preg_replace('#{shortlang}#', substr($app->getLanguage()->getTag(), 0, 2), $query);
						if (!empty($uid))
						{
							$query = preg_replace('#{my->id}#', $uid, $query);
						}

						$db->setQuery($query);
						$ret = $db->loadResult();
						if (empty($ret))
						{
							$ret = $raw_value;
						}
						$formatted_value = Text::_($ret);
						break;

					case 'dropdown':
					case 'radiobutton':
						if (isset($params['multiple']) && $params['multiple'] == 1)
						{
							$data = json_decode($raw_value);
							foreach ($data as $key => $value)
							{
								$index = array_search($value, $params['sub_options']['sub_values']);
								if ($index !== false)
								{
									$data[$key] = Text::_($params['sub_options']['sub_labels'][$index]);
								}
							}
							$formatted_value = $html ? "<ul><li>" . implode("</li><li>", $data) . "</li></ul>" : implode(',', $data);
						}
						else
						{
							$index = array_search($raw_value, $params['sub_options']['sub_values']);

							if ($index !== false)
							{
								if ($raw_value == '0')
								{
									$formatted_value = '';
								}
								else
								{
									$formatted_value = Text::_($params['sub_options']['sub_labels'][$index]);
								}
							}
						}
						break;

					case 'checkbox':
						$elm  = array();
						$data = json_decode($raw_value, true);

						if (!empty($data))
						{
							$index = array_intersect($data, $params['sub_options']['sub_values']);
						}
						else
						{
							$index = $params['sub_options']['sub_values'];
						}

						foreach ($index as $sub_value)
						{
							$key   = array_search($sub_value, $params['sub_options']['sub_values']);
							$elm[] = Text::_($params['sub_options']['sub_labels'][$key]);
						}

						$formatted_value = $html ? "<ul>" . implode("</li><li>", $elm) . "</ul>" : implode(',', $elm);
						break;

					case 'yesno':
						$formatted_value = $raw_value == 1 ? Text::_('JYES') : Text::_('JNO');
						break;

					case 'textarea':
						$formatted_value = nl2br($raw_value);
						break;

					case 'field':
						if ($params['password'] == 1)
						{
							$formatted_value = '******';
						}
						elseif ($params['password'] == 3 && $html)
						{
							$formatted_value = '<a href="mailto:' . $raw_value . '" title="' . Text::_($element->label) . '">' . $raw_value . '</a>';
						}
						elseif ($params['password'] == 5 && $html)
						{
							$formatted_value = '<a href="' . $raw_value . '" target="_blank" title="' . Text::_($element->label) . '">' . $raw_value . '</a>';
						}
						break;
					case 'internalid':
						$formatted_value = '';
						break;
					case 'fileupload':
						$live_site = Factory::getApplication()->get('live_site', Uri::root());
						$live_site = rtrim($live_site, '/');
						$formatted_value = $live_site.$raw_value;
						break;

					default:
						break;
				}
			}
		}

		return $formatted_value;
	}

	static function encryptDatas($value, $encryption_key = null, $cipher = 'aes-128-cbc', $iv = null)
	{
		$result = $value;
		$app    = Factory::getApplication();

		//Generate a 256-bit encryption key
		if (empty($encryption_key))
		{
			$encryption_key = $app->getConfig()->get('secret', '');
		}

		if (!empty($encryption_key))
		{
			if (empty($iv))
			{
				$iv_length = openssl_cipher_iv_length($cipher);
				$iv        = openssl_random_pseudo_bytes($iv_length);
			}

			//Data to encrypt
			$contents = $value;
			if (is_string($value) && is_array(json_decode($value)))
			{
				$contents = json_decode($value);
			}

			if (is_array($contents))
			{
				foreach ($contents as $key => $content)
				{
					$encrypted_data = openssl_encrypt($content, $cipher, $encryption_key, 0, $iv);
					if ($encrypted_data !== false)
					{
						$contents[$key] = $encrypted_data . '|' . base64_encode($iv);
					}
				}
				$result = json_encode($contents);
			}
			else
			{
				$val            = $value;
				$encrypted_data = openssl_encrypt($val, $cipher, $encryption_key, 0, $iv);
				if ($encrypted_data !== false)
				{
					$result = $encrypted_data . '|' . base64_encode($iv);
				}
			}
		}

		return $result;
	}

	static function decryptDatas($value, $encryption_key = null, $cipher = 'aes-128-cbc', $plugin = null)
	{
		$result = $value;

		if (empty($encryption_key))
		{
			$encryption_key = Factory::getApplication()->getConfig()->get('secret', '');
		}

		if (!empty($encryption_key))
		{
			$contents = $value;
			if (is_string($value) && is_array(json_decode($value)))
			{
				$contents = json_decode($value);
			}

			if (is_array($contents))
			{
				foreach ($contents as $key => $content)
				{
					$content        = explode('|', $content);
					$decrypted_data = false;

					if (is_array($content))
					{
						$iv = base64_decode($content[1]);

						try
						{
							$decrypted_data = openssl_decrypt($content[0], $cipher, $encryption_key, 0, $iv);
						}
						catch (Exception $e)
						{
							$decrypted_data = false;
						}
					}

					if ($decrypted_data !== false)
					{
						$contents[$key] = $decrypted_data;
					}
					else
					{
						$decrypted_data = self::oldDecryptDatas((is_array($content) ? $content[0] : $content), $encryption_key);
						if ($decrypted_data !== false)
						{
							$contents[$key] = $decrypted_data;
						}
					}
				}

				$result = json_encode($contents);
			}
			else
			{
				list($encrypted_value, $encoded_iv) = explode('|', $value);
				$iv = base64_decode($encoded_iv);

				try
				{
					$decrypted_data = openssl_decrypt($encrypted_value, $cipher, $encryption_key, 0, $iv);
				}
				catch (Exception $e)
				{
					$decrypted_data = false;
				}

				if ($decrypted_data !== false)
				{
					$result = $decrypted_data;
				}
				else
				{
					$decrypted_data = self::oldDecryptDatas((is_array($value) ? $value[0] : $value), $encryption_key, $plugin);
					if ($decrypted_data !== false)
					{
						$result = $decrypted_data;
					}
				}
			}
		}

		return $result;
	}

	public static function oldDecryptDatas($value, $encryption_key = null, $plugin = null)
	{
		$cipher = 'aes-128-cbc';
		$result = $value;

		if (empty($encryption_key))
		{
			$encryption_key = Factory::getApplication()->getConfig()->get('secret', '');
		}

		if (!empty($encryption_key))
		{
			if ($plugin == 'emundus_phonenumber')
			{
				$value = explode('==', $value);
			}

			$contents = $value;
			if (is_string($value) && is_array(json_decode($value)))
			{
				$contents = json_decode($value);
			}

			if (is_array($contents))
			{
				foreach ($contents as $key => $content)
				{
					$decrypted_data = openssl_decrypt($content, $cipher, $encryption_key, 0);
					if ($decrypted_data !== false)
					{
						$contents[$key] = $decrypted_data;
					}
				}

				$result = json_encode($contents);
				if ($plugin == 'emundus_phonenumber')
				{
					$result = implode('', $contents);
				}
			}
			else
			{
				$decrypted_data = openssl_decrypt($value, $cipher, $encryption_key, 0);
				if ($decrypted_data !== false)
				{
					$result = $decrypted_data;
				}
			}
		}

		return $result;
	}

	public static function migrateEncryptDatas($old_cipher, $new_cipher, $old_key, $new_key, $datas, $iv = null)
	{
		foreach ($datas as $key => $data)
		{
			if (is_array(json_decode($data['value'])))
			{
				$contents           = json_decode($data['value']);
				$decrypted_contents = [];
				foreach ($contents as $index => $content)
				{
					$decrypted_contents[$index] = openssl_decrypt($content, $old_cipher, $old_key, 0);
					if ($decrypted_contents[$index] === false)
					{
						$decrypted_contents[$index] = $content;
					}
				}
				$decrypted_data = json_encode($decrypted_contents);
			}
			else
			{
				$decrypted_data = openssl_decrypt($data['value'], $old_cipher, $old_key, 0);
				if ($decrypted_data === false)
				{
					$decrypted_data = $data['value'];
				}
			}

			$datas[$key]['value'] = self::encryptDatas($decrypted_data, $new_key, $new_cipher, $iv);
		}

		return $datas;
	}

	public static function getFabrikDateParam($elt, $param)
	{
		$result = '';

		if (!empty($elt) && !empty($param))
		{
			$params = json_decode($elt->params, true);
			if ($elt->plugin == 'jdate' && isset($params['j' . $param]))
			{
				$result = $params['j' . $param];
			}
			else
			{
				$result = $params[$param];
			}
		}

		return $result;
	}

	static function getGroupsFromFabrikForms(array $form_ids): array
	{
		$groups   = [];
		$form_ids = array_unique($form_ids);

		if (!empty($form_ids))
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->clear()
				->select('jfg.id, jfg.label, jffg.form_id')
				->from('jos_fabrik_groups as jfg')
				->join('inner', 'jos_fabrik_formgroup as jffg ON jfg.id = jffg.group_id')
				->join('inner', 'jos_fabrik_forms as jff ON jffg.form_id = jff.id')
				->where('jffg.form_id IN (' . implode(',', $form_ids) . ')')
				->andWhere('jfg.published = 1');

			try
			{
				$db->setQuery($query);
				$groups = $db->loadAssocList();

				foreach ($groups as $key => $group)
				{
					$groups[$key]['label'] = Text::_($group['label']);
				}
			}
			catch (Exception $e)
			{
				Log::add('Failed to get groups associated to profiles that current user can access : ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
			}
		}

		return $groups;
	}

	static function getElementsFromFabrikForms(array $form_ids, array $excluded_plugins = []): array
	{
		$elements = [];
		$form_ids = array_unique($form_ids);

		if (!empty($form_ids))
		{
			$helper_cache = new EmundusHelperCache();

			if ($helper_cache->isEnabled())
			{
				foreach ($form_ids as $key => $form_id)
				{
					$cache_key      = 'elements_from_form_' . $form_id;
					$cache_elements = $helper_cache->get($cache_key);

					if (!empty($cache_elements))
					{
						$elements = array_merge($elements, $cache_elements);
						unset($form_ids[$key]);
					}
				}
			}

			if (!empty($form_ids))
			{
				$db    = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->getQuery(true);

				$query->clear()
					->select('jfe.id, jfe.plugin, jfe.label, jfe.params, jffg.form_id as element_form_id, jff.label as element_form_label, jfg.label as element_group_label, jfg.id as element_group_id')
					->from('jos_fabrik_elements as jfe')
					->join('inner', 'jos_fabrik_groups as jfg ON jfe.group_id = jfg.id')
					->join('inner', 'jos_fabrik_formgroup as jffg ON jfg.id = jffg.group_id')
					->join('inner', 'jos_fabrik_forms as jff ON jffg.form_id = jff.id')
					->where('jffg.form_id IN (' . implode(',', $form_ids) . ')')
					->andWhere('jfe.published = 1')
					->andWhere('jfe.hidden = 0');

				if (!empty($excluded_plugins))
				{
					$query->andWhere('jfe.plugin NOT IN (' . implode(',', $db->quote($excluded_plugins)) . ')');
				}

				try
				{
					$db->setQuery($query);
					$query_elements = $db->loadAssocList();
					$elements       = array_merge($elements, $query_elements);

					foreach ($elements as $key => $element)
					{
						$elements[$key]['label']               = Text::_($element['label']);
						$elements[$key]['element_form_label']  = Text::_($element['element_form_label']);
						$elements[$key]['element_group_label'] = Text::_($element['element_group_label']);
					}

					if ($helper_cache->isEnabled())
					{
						$elements_by_form = [];
						foreach ($elements as $element)
						{
							if (!isset($elements_by_form[$element['element_form_id']]))
							{
								$elements_by_form[$element['element_form_id']] = [];
							}
							$elements_by_form[$element['element_form_id']][] = $element;
						}

						foreach ($elements_by_form as $form_id => $element_by_form)
						{
							$helper_cache->set('elements_from_form_' . $form_id, $element_by_form);
						}
					}
				}
				catch (Exception $e)
				{
					Log::add('Failed to get elements associated to profiles that current user can access : ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
				}
			}
		}

		return $elements;
	}

	static function getElementsByAlias(string $alias, ?int $form_id = null): array
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$elements = [];

		if (!empty($alias))
		{
			try
			{
				$query->select('fl.db_table_name,fe.name,fe.id, fe.plugin, fe.params, fg.params as group_params, fg.id as group_id, fj.table_join')
					->from($db->quoteName('#__fabrik_elements', 'fe'))
					->leftJoin($db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $db->quoteName('ffg.group_id') . ' = ' . $db->quoteName('fe.group_id'))
					->leftJoin($db->quoteName('#__fabrik_groups', 'fg') . ' ON ' . $db->quoteName('fg.id') . ' = ' . $db->quoteName('ffg.group_id'))
					->leftJoin($db->quoteName('#__fabrik_forms', 'ff') . ' ON ' . $db->quoteName('ff.id') . ' = ' . $db->quoteName('ffg.form_id'))
					->leftJoin($db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $db->quoteName('fl.form_id') . ' = ' . $db->quoteName('ffg.form_id'))
					->leftJoin($db->quoteName('#__fabrik_joins', 'fj') . ' ON ' . $db->quoteName('fl.id') . ' = ' . $db->quoteName('fj.list_id') . ' AND ' . $db->quoteName('fg.id') . ' = ' . $db->quoteName('fj.group_id'))
					->where("JSON_EXTRACT(fe.params, '$.alias') = " . $db->quote($alias))
					->where($db->quoteName('fe.published') . ' = 1')
					->where($db->quoteName('fg.published') . ' = 1')
					->where($db->quoteName('fl.published') . ' = 1')
					->where($db->quoteName('ff.published') . ' = 1');
				if (!empty($form_id))
				{
					$query->where($db->quoteName('fl.form_id') . ' = ' . $db->quote($form_id));
				}
				$db->setQuery($query);
				$elements = $db->loadObjectList();
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus/helpers/fabrik | Cannot retrive elements by alias : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $elements;
	}

	public function getValueByAlias(
		string $alias,
		?string $fnum = null,
		int $user_id = 0,
		string $load_option = 'result',
		int $step_id = null,
		bool $use_evaluation_forms = true
	): array
	{
		$values = [];

		if (!empty($alias) && (!empty($fnum) || !empty($user_id)))
		{
			$elements = self::getElementsByAlias($alias);

			if (!empty($elements))
			{
				$db    = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->getQuery(true);

				if (!empty($fnum))
				{
					// TODO: Aliases from profile forms are removed here, we have to keep them if we want to get values from profile forms

					// ! this means that only applicants forms data will be retrieved !
					require_once(JPATH_SITE . '/components/com_emundus/models/application.php');
					$m_application = new EmundusModelApplication();
					$fnumElements  = $m_application->getFabrikDataByFnum($fnum, 'element', $use_evaluation_forms);

					$elements = array_filter($elements, function ($element) use ($fnumElements) {
						return in_array($element->id, $fnumElements);
					});
				}

				try
				{
					foreach ($elements as $element)
					{
						$columns = array_keys($db->getTableColumns($element->db_table_name));

						if ((!empty($fnum) && in_array('fnum', $columns)) || (!empty($user_id) && in_array('user', $columns) || in_array('user_id', $columns)))
						{
							//TODO: Allow getFabrikElementValue to get values by step_id
							$element      = (array) $element;
							$fabrik_value = $this->getFabrikElementValue($element, $fnum, 0, ValueFormat::BOTH, $user_id);
							if (!empty($fabrik_value[$element['id']]))
							{
								if (!empty($fnum) && !empty($fabrik_value[$element['id']][$fnum]))
								{
									$value['raw'] = $fabrik_value[$element['id']][$fnum]['raw'];
									$value['value'] = $fabrik_value[$element['id']][$fnum]['val'];
								}
								elseif (!empty($user_id) && !empty($fabrik_value[$element['id']][$user_id]))
								{
									$value['raw'] = $fabrik_value[$element['id']][$user_id]['raw'];
									$value['value'] = $fabrik_value[$element['id']][$user_id]['val'];
								}
								else {
									$value['raw'] = '';
									$value['value'] = '';
								}

								if($load_option == 'column') {
									$values[] = $value;
								} else {
									return $value;
								}
							}

							/*$query->clear()
								->select($db->quoteName($element->name))
								->from($db->quoteName($element->db_table_name));

							if (!empty($fnum) && in_array('fnum', $columns))
							{
								$query->where("fnum LIKE " . $db->quote($fnum));
							}

							if (!empty($user_id) && (in_array('user', $columns) || in_array('user_id', $columns)))
							{
								if (in_array('user', $columns))
								{
									$query->where("user = " . $db->quote($user_id));
								}
								elseif (in_array('user_id', $columns))
								{
									$query->where("user_id = " . $db->quote($user_id));
								}
							}

							if (!empty($step_id) && in_array('step_id', $columns))
							{
								$query->where("step_id = " . $db->quote($step_id));
							}

							$query->order('id DESC');
							$db->setQuery($query);


							if ($load_option == 'column')
							{
								$raw_value = $db->loadColumn();
							}
							else
							{
								$raw_value = $db->loadResult();
							}

							if (!empty($raw_value))
							{
								$value['raw']   = $raw_value;
								$value['value'] = EmundusHelperFabrik::formatElementValue($element->name, $raw_value);
								break;
							}*/
						}
					}
				}
				catch (Exception $e)
				{
					Log::add('component/com_emundus/helpers/fabrik | Cannot retrive value by alias : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
				}
			}
		}

		return $values;
	}

	public static function displayPasswordTip()
	{
		$params     = ComponentHelper::getParams('com_users');
		$min_length = $params->get('minimum_length');
		$min_int    = $params->get('minimum_integers');
		$min_sym    = $params->get('minimum_symbols');
		$min_up     = $params->get('minimum_uppercase');
		$min_low    = $params->get('minimum_lowercase');

		return Text::sprintf('USER_PASSWORD_TIP', $min_length, $min_int, $min_sym, $min_up, $min_low);
	}


	static function getFabrikJoins(int $lid): array
	{
		$fabrik_joins = [];

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		try
		{
			$query->select('*')
				->from($db->quoteName('#__fabrik_joins'))
				->where($db->quoteName('list_id') . ' = ' . $lid);
			$db->setQuery($query);
			$fabrik_joins = $db->loadObjectList('table_join');
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/helpers/fabrik | Cannot get fabrik joins : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $fabrik_joins;
	}

	static function createFabrikJoin($join_from_table, $table_join, $lid = 0, $eid = 0, $table_key = 'fnum', $table_join_key = 'fnum', $join_type = 'left', $group_id = 0, $params = [])
	{
		$joined = ['status' => false, 'group_id' => $group_id, 'list_id' => $lid];
		$db     = Factory::getContainer()->get('DatabaseDriver');
		$query  = $db->getQuery(true);

		try
		{
			if (empty($params))
			{
				$params = [
					'pk' => '`' . $table_join . '`' . '`id`'
				];
			}

			if (empty($group_id) && !empty($lid))
			{
				$query->select('label,form_id')
					->from($db->quoteName('#__fabrik_lists'))
					->where($db->quoteName('id') . ' = ' . $db->quote($lid));
				$db->setQuery($query);
				$list          = $db->loadObject();
				$datas         = [
					'name'    => $list->label . '- [' . $table_join . ']',
					'is_join' => 1
				];
				$created_group = EmundusHelperFabrik::addFabrikGroup($datas, [], 1, true);
				if ($created_group)
				{
					$group_id           = $created_group['id'];
					$joined['group_id'] = $group_id;

					EmundusHelperFabrik::joinFormGroup($list->form_id, [$group_id]);
				}
			}

			$data = array(
				'list_id'         => $lid,
				'element_id'      => $eid,
				'join_from_table' => $join_from_table,
				'table_join'      => $table_join,
				'table_key'       => $table_key,
				'table_join_key'  => $table_join_key,
				'join_type'       => $join_type,
				'group_id'        => $group_id,
				'params'          => json_encode($params)
			);

			$query->clear()
				->insert($db->quoteName('#__fabrik_joins'))
				->columns($db->quoteName(array_keys($data)))
				->values(implode(',', $db->quote(array_values($data))));
			$db->setQuery($query);

			$joined['status'] = $db->execute();
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/helpers/fabrik | Cannot check fabrik joins for element ' . $eid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $joined;
	}

	public static function addFabrikGroup($datas, $params = [], $published = 1, $no_label = false, $user = null)
	{
		$result = ['status' => false, 'message' => '', 'id' => 0];

		if (empty($datas['name']))
		{
			$result['message'] = 'INSERTING FABRIK GROUP : Please indicate a name.';

			return $result;
		}

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('id')
			->from($db->quoteName('#__fabrik_groups'))
			->where($db->quoteName('name') . ' LIKE ' . $db->quote($datas['name']));
		$db->setQuery($query);
		$is_existing = $db->loadResult();

		if (!$is_existing)
		{
			require_once(JPATH_SITE . '/components/com_emundus/helpers/fabrik.php');

			$default_params = EmundusHelperFabrik::prepareGroupParams();
			$params         = array_merge($default_params, $params);

			if ($no_label)
			{
				$datas['label'] = '';
			}
			else
			{
				if (empty($datas['label']))
				{
					$datas['label'] = $datas['name'];
				}
			}

			try
			{
				$inserting_datas = [
					'name'             => $datas['name'],
					'css'              => !empty($datas['css']) ? $datas['css'] : '',
					'label'            => $datas['label'],
					'created'          => date('Y-m-d H:i:s'),
					'created_by'       => !empty($user) ? $user->id : 62,
					'created_by_alias' => !empty($user) ? $user->username : 'admin',
					'modified'         => date('Y-m-d H:i:s'),
					'modified_by'      => 0,
					'checked_out'      => 0,
					'checked_out_time' => date('Y-m-d H:i:s'),
					'published'        => $published,
					'is_join'          => !empty($datas['is_join']) ? $datas['is_join'] : 0,
					'params'           => json_encode($params)
				];

				$query->clear()
					->insert($db->quoteName('#__fabrik_groups'))
					->columns($db->quoteName(array_keys($inserting_datas)))
					->values(implode(',', $db->quote(array_values($inserting_datas))));
				$db->setQuery($query);
				$db->execute();

				$result['id'] = $db->insertid();
			}
			catch (Exception $e)
			{
				$result['message'] = 'INSERTING FABRIK GROUP : Error : ' . $e->getMessage();

				return $result;
			}
		}
		else
		{
			$result['id'] = $is_existing;
		}

		$result['status'] = true;

		return $result;
	}

	public static function joinFormGroup($form_id, $groups_id)
	{
		$result = ['status' => false, 'message' => ''];

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		try
		{
			foreach ($groups_id as $group)
			{
				$query->clear()
					->select('id')
					->from($db->quoteName('#__fabrik_formgroup'))
					->where($db->quoteName('form_id') . ' = ' . $form_id)
					->andWhere($db->quoteName('group_id') . ' = ' . $group);
				$db->setQuery($query);
				$is_existing = $db->loadResult();

				if (!$is_existing)
				{
					$query->clear()
						->insert($db->quoteName('#__fabrik_formgroup'))
						->set($db->quoteName('form_id') . ' = ' . $db->quote($form_id))
						->set($db->quoteName('group_id') . ' = ' . $db->quote($group));
					$db->setQuery($query);
					$db->execute();
				}
			}
		}
		catch (Exception $e)
		{
			$result['message'] = 'JOIN FABRIK FORM WITH GROUPS : Error : ' . $e->getMessage();

			return $result;
		}

		$result['status'] = true;

		return $result;
	}

	public static function createPrefilterList($lid, $elt_name, $value, $condition = 'equals', $eval = 0, $grouped = 0, $access = 1)
	{
		$created = false;

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('fl.params,fl.db_table_name')
			->from($db->quoteName('#__fabrik_lists', 'fl'))
			->where($db->quoteName('fl.id') . ' = ' . $db->quote($lid));
		$db->setQuery($query);
		$list = $db->loadObject();

		if (!empty($list))
		{
			$params = json_decode($list->params, true);

			$params['filter-fields'][]     = $list->db_table_name . '.' . $elt_name;
			$params['filter-conditions'][] = $condition;
			$params['filter-eval'][]       = $eval;
			$params['filter-value'][]      = $value;
			$params['filter-access'][]     = $access;
			$params['filter-grouped'][]    = $grouped;

			$query->clear()
				->update($db->quoteName('#__fabrik_lists'))
				->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
				->where($db->quoteName('id') . ' = ' . $db->quote($lid));
			$db->setQuery($query);
			$created = $db->execute();
		}

		return $created;
	}

	public static function getFabrikFormsList(): array
	{
		$forms = [];

		try
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('id, label')
				->from($db->quoteName('#__fabrik_forms'))
				->where($db->quoteName('published') . ' = 1');

			$db->setQuery($query);
			$forms = $db->loadAssocList();

			$forms = array_map(function ($form) {
				$form['label'] = Text::_($form['label']);

				return $form;
			}, $forms);
		}
		catch (Exception $e)
		{
			JLog::add('Failed to get forms associated to profiles that current user can access : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.filters.error');
		}

		return $forms;
	}

	public static function getAllFabrikAliases(): array
	{
		$aliases = [];

		$h_cache        = new EmundusHelperCache();
		$cached_aliases = $h_cache->get('fabrik_aliases');

		if (!empty($cached_aliases))
		{
			$aliases = $cached_aliases;
		}
		else
		{
			try
			{
				$db    = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->getQuery(true);

				$query->select('params')
					->from($db->quoteName('#__fabrik_elements'))
					->where($db->quoteName('published') . ' = 1');
				$db->setQuery($query);
				$elements = $db->loadColumn();

				// Extract aliases from element params
				$aliases = array_map(function ($params) {
					$params = json_decode($params, true);

					return !empty($params['alias']) ? $params['alias'] : '';
				}, $elements);
				// Filter out empty aliases
				$aliases = array_filter($aliases, function ($alias) {
					return !empty($alias);
				});
				// Remove duplicates
				$aliases = array_values(array_unique($aliases));

				$h_cache->set('fabrik_aliases', $aliases);
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus/helpers/fabrik | Cannot get all fabrik aliases : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $aliases;
	}

	public static function getAllFabrikAliasesGrouped($lim = 25, $page = 1, $search = '', string $profile = '', string $order_by = '', string $sort = 'ASC', int $user_id = 0): array
	{
		$datas         = ['datas' => [], 'count' => 0];

		$h_cache        = new EmundusHelperCache();
		$aliases = $h_cache->get('fabrik_aliases_grouped');

		if(empty($user_id))
		{
			$user = Factory::getApplication()->getIdentity();
			if(!empty($user))
			{
				$user_id = $user->id;
			}
		}

		try
		{
			if (empty($lim) || $lim == 'all')
			{
				$limit = '';
			}
			else
			{
				$limit = $lim;
			}

			if (empty($page) || empty($limit))
			{
				$offset = 0;
			}
			else
			{
				$offset = ($page - 1) * $limit;
			}

			if(empty($aliases))
			{
				$db    = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->getQuery(true);

				// TODO: Display only evaluation forms if user has access to them
				$query->select('esws.label, esws.form_id')
					->from($db->quoteName('#__emundus_setup_workflows_steps', 'esws'))
					->leftJoin($db->quoteName('#__emundus_setup_workflows', 'esw') . ' ON ' . $db->quoteName('esw.id') . ' = ' . $db->quoteName('esws.workflow_id'))
					->where($db->quoteName('esws.form_id') . ' IS NOT NULL')
					->where($db->quoteName('esw.published') . ' = 1');
				$db->setQuery($query);
				$evaluation_form_ids = $db->loadAssocList('form_id');

				$query->clear()
					->select('fe.name, fe.label, fe.params, ff.label as form_label, fg.label as group_label, ff.id as form_id')
					->from($db->quoteName('#__fabrik_elements', 'fe'))
					->leftJoin($db->quoteName('#__fabrik_groups', 'fg') . ' ON ' . $db->quoteName('fg.id') . ' = ' . $db->quoteName('fe.group_id'))
					->leftJoin($db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $db->quoteName('ffg.group_id') . ' = ' . $db->quoteName('fe.group_id'))
					->leftJoin($db->quoteName('#__fabrik_forms', 'ff') . ' ON ' . $db->quoteName('ff.id') . ' = ' . $db->quoteName('ffg.form_id'))
					->where($db->quoteName('fe.published') . ' = 1')
					->where('JSON_EXTRACT(fe.params, \'$.alias\') IS NOT NULL');
				$db->setQuery($query);
				$elements = $db->loadObjectList();

				// First filter aliases to have only those that match applicants or evaluation forms (Use cache for this part)
				$profiles = [];

				if(!class_exists('EmundusModelForm'))
				{
					require_once(JPATH_SITE . '/components/com_emundus/models/form.php');
				}
				$m_form = new EmundusModelForm();
				$available_profiles = $m_form->getAllForms('', '', '', 0, 0, $user_id);
				$available_profiles = array_keys(array_column($available_profiles['datas'], null, 'id'));

				$aliases = array_map(function ($element) use ($db, $query, $evaluation_form_ids, &$profiles, $available_profiles) {
					$params = json_decode($element->params, true);
					if (!empty($params['alias']))
					{
						// Get group label, form label and profile label for element context
						$profile = null;
						if(in_array($element->form_id, array_keys($profiles)))
						{
							$profile = $profiles[$element->form_id];
						}

						if(empty($profile))
						{
							$query->clear()
								->select('esp.id, esp.label')
								->from($db->quoteName('#__emundus_setup_profiles', 'esp'))
								->leftJoin($db->quoteName('#__menu', 'm') . ' ON ' . $db->quoteName('m.menutype') . ' = ' . $db->quoteName('esp.menutype'))
								->where($db->quoteName('m.link') . ' = ' . $db->quote('index.php?option=com_fabrik&view=form&formid=' . $element->form_id));
							$db->setQuery($query);
							$profile = $db->loadObject();

							$profiles[$element->form_id] = $profile;
						}

						// If profile id is not in available profiles, we skip it
						if(!empty($profile) && !in_array($profile->id, $available_profiles))
						{
							return null;
						}

						$profile_label = !empty($profile) ? Text::_($profile->label) : '';
						$profile_id    = !empty($profile) ? ('applicant_' . $profile->id) : 0;

						if (in_array($element->form_id, array_keys($evaluation_form_ids)))
						{
							$profile_label = !empty($evaluation_form_ids[$element->form_id]['label']) ? Text::_($evaluation_form_ids[$element->form_id]['label']) : Text::_('COM_EMUNDUS_EVALUATION_FORM');
							$profile_id    = 'evaluation_' . $element->form_id;
						}

						if (empty($profile_label))
						{
							return null;
						}

						return [
							'name'          => $element->name,
							'label'         => $element->label,
							'profile_id'    => $profile_id,
							'profile_label' => $profile_label,
							'alias'         => $params['alias'],
							'form_label'    => $element->form_label,
							'group_label'   => $element->group_label,
						];
					}
					else
					{
						return null;
					}
				}, $elements);
				// Filter out empty aliases
				$aliases = array_filter($aliases, function ($alias) {
					return !empty($alias);
				});
				//

				$aliases = array_map(function ($element) {
					$path = $element['profile_label'] . ' > ';
					if (!empty(Text::_($element['form_label'])))
					{
						$path .= Text::_($element['form_label']) . ' > ';
					}
					if (!empty(Text::_($element['group_label'])))
					{
						$path .= Text::_($element['group_label']) . ' > ';
					}

					return [
						'name'  => $element['name'],
						'path'  => $path,
						'profile_id'    => $element['profile_id'],
						'label' => Text::_($element['label']),
						'alias' => $element['alias'],
					];
				}, $aliases);

				// Grouped by alias
				$aliases = array_reduce($aliases, function ($carry, $item) {
					$carry[$item['alias']]['name']       = $item['alias'];
					$carry[$item['alias']]['elements'][] = $item;

					return $carry;
				}, []);

				$h_cache->set('fabrik_aliases_grouped', $aliases);
			}

			// Apply profile filter
			if (!empty($profile) && $profile != 'all')
			{
				$aliases = array_filter($aliases, function ($alias) use ($profile) {
					foreach ($alias['elements'] as $element) {
						if ($element['profile_id'] == $profile) {
							return true;
						}
					}

					return false;
				});
			}

			// Then apply search filter
			if (!empty($search))
			{
				$search = strtolower($search);
				$aliases = array_filter($aliases, function ($alias) use ($search) {
					// Search in alias name only
					if(strpos(strtolower($alias['name']), $search) !== false) {
						return true;
					}

					// Search in element labels and paths
					foreach ($alias['elements'] as $element) {
						if (strpos(strtolower($element['label']), $search) !== false) {
							return true;
						}
					}

					return false;
				});
			}

			// Then apply limit and offset
			$datas['count'] = count($aliases);
			// Apply order
			if (!empty($order_by))
			{
				$aliases = array_values($aliases);
				$sort    = strtoupper($sort) == 'DESC' ? SORT_DESC : SORT_ASC;

				if ($order_by == 'label')
				{
					$names = array_column($aliases, 'name');
					array_multisort($names, $sort, $aliases);
				}
				elseif ($order_by == 'count')
				{
					$counts = array_map(function ($alias) {
						return count($alias['elements']);
					}, $aliases);
					array_multisort($counts, $sort, $aliases);
				}
			}

			if (!empty($limit))
			{
				$datas['datas'] = array_slice($aliases, $offset, $limit);
			}
			else {
				$datas['datas'] = $aliases;
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/helpers/fabrik | Cannot get all fabrik aliases : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $datas;
	}

	public static function clearFabrikAliasesCache()
	{
		if(!class_exists('EmundusHelperCache'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/cache.php';
		}
		$h_cache = new EmundusHelperCache();
		$h_cache->set('fabrik_aliases', []);
		$h_cache->set('fabrik_aliases_grouped', []);
	}

	/**
	 * Fill form data from aliases
	 *
	 * @param   FabrikFEModelForm  $form_model
	 * @param   string             $table
	 * @param   string             $fnum
	 * @param   int                $user_id
	 *
	 * @return void
	 */
	public static function fillFormFromAliases(FabrikFEModelForm $form_model, string $table, string $fnum, int $user_id = 0): void
	{
		if (!empty($fnum))
		{
			$elements = array();
			$groups   = $form_model->getGroupsHiarachy();
			foreach ($groups as $group)
			{
				$elements = array_merge($group->getPublishedElements(), $elements);
			}

			if (!empty($elements))
			{
				$elements = array_filter($elements, function ($element) use ($table) {
					return $element->getElement()->name !== 'parent_id';
				});

				foreach ($elements as $elt)
				{
					if (!empty($elt->getParams()) && !empty($elt->getParams()->get('alias')))
					{
						$h_fabrik    = new EmundusHelperFabrik();
						$alias_value = $h_fabrik->getValueByAlias($elt->getParams()->get('alias'), $fnum, $user_id, 'result', null, false);

						if (!empty($alias_value['raw']))
						{
							$form_model->data[$elt->getFullName()]          = $alias_value['raw'];
							$form_model->data[$elt->getFullName() . '_raw'] = $alias_value['raw'];
						}
					}
				}
			}
		}
	}

	/**
	 * Extract numeric value from currency fields
	 *
	 * @param   mixed  $value
	 *
	 * @return float
	 */
	public static function extractNumericValue(mixed $value): float
	{
		// Manage raw values when save
		$rawValue = explode(',', $value);
		if (count($rawValue) === 3)
		{
			$value = $rawValue[1];
		}

		// Step 1: Extract the first number-like segment (with digits and optional commas/dots)
		if (!preg_match('/-?\d(?:[\s00A0]?\d|[.,])*/', $value, $matches))
		{
			return 0.0; // No valid number found
		}

		$number = $matches[0];

		// Step 2: Normalize separators (dots and commas)
		$commaPos = strrpos($number, ',');
		$dotPos   = strrpos($number, '.');

		if ($commaPos !== false && $dotPos !== false)
		{
			if ($commaPos > $dotPos)
			{
				// European: "1.234,56"
				$number = str_replace(['.', ','], ['', '.'], $number); // remove thousand dots and convert decimal comma
			}
			else
			{
				// US: "1,234.56"
				$number = str_replace(',', '', $number);     // remove thousand commas
			}
		}
		elseif ($commaPos !== false)
		{
			// Assume comma is decimal separator
			$number = str_replace(',', '.', $number);
		}
		else
		{
			// Only dot or plain digits
			if (substr_count($number, '.') > 1)
			{
				// Too many dots? Likely thousand separators → remove all
				$number = str_replace('.', '', $number);
			}
		}

		// Finally, remove spaces used as thousand separators
		$number = str_replace(' ', '', $number);

		return (float) $number;
	}

	public static function generatePdf($form, $groups, $params)
	{
		/* GET LOGO */
		if (!class_exists('EmundusHelperEmails'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/emails.php';
		}
		$logo = EmundusHelperEmails::getLogo(false, null, true);

		$type        = pathinfo($logo, PATHINFO_EXTENSION);
		$data        = file_get_contents($logo);
		$logo_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
		/* END LOGO */

		$htmldata = '<html>
				<head>
				<title>' . $form->label . '</title>
				  <meta name="author" content="eMundus">
				</head>
				<body>';
		$htmldata .= '<header><table style="width: 100%"><tr><td><img src="' . $logo_base64 . '" width="auto" height="60"/><hr/></td></tr></table></header>';

		$htmldata .= '<h2 class="pdf-page-title">' . $form->label . '</h2>';
		foreach ($groups as $group)
		{
			if (str_contains($group->css, 'display:none'))
			{
				continue;
			}

			$htmldata .= '<h3 class="group">' . $group->title . '</h3>';

			$htmldata .= '<table class="pdf-forms">';

			foreach ($group->elements as $element)
			{
				if (!$element->hidden)
				{
					if ($element->plugin === 'textarea')
					{
						$htmldata .= '</table>';
						$htmldata .= '<div style="width: 93.5%;padding: 8px 16px;">';
						$htmldata .= '<div style="width: 100%; padding: 4px 8px;background-color: #F3F3F3;color: #000000;border: solid 1px #A4A4A4;border-bottom: unset;font-size: 12px">' . (!empty(Text::_($element->label)) ? Text::_($element->label) . ' : ' : '') . '</div>';
						$htmldata .= '<div style="width: 100%; padding: 4px 8px;color: #000000;border: solid 1px #A4A4A4;font-size: 12px;word-break:break-word; hyphens:auto;">' . $element->element . '</div>';
						$htmldata .= '</div>';
						$htmldata .= '<table class="pdf-forms">';
					}
					else
					{
						$htmldata .= '<tr>';
						$htmldata .= '<td colspan="1" style="background-color: var(--neutral-200);"><span style="color: #000000;">' . (!empty(Text::_($element->label)) ? Text::_($element->label) . ' : ' : '') . '</span></td>';

						if (!empty($element->element))
						{
							// Remove img tags from the element value
							$element->element = preg_replace('/<img[^>]+\>/i', '', $element->element);

							$htmldata .= '<td> ' . $element->element . '</td>';
						}

						$htmldata .= '</tr>';
					}
				}
			}

			$htmldata .= '</table>';
		}

		$htmldata .= "
			<style>
					@page { 
						margin: 130px 25px; 
						font-family: Helvetica, Arial, sans-serif;
					}
					header { position: fixed; top: -120px; left: 0px; right: 0px; }
					header hr {
						border: none;
						height: 1px;
						background-color: #A4A4A4;
						margin-top: 12px;
					}
					.page-break { page-break-before: always; }
					hr {
						border: solid 1px black;
					}
					h2 {
						font-size: 18px;
						line-height: 16px;
						margin-top: 4px;
						margin-bottom: 0;
					}
					h2.pdf-page-title{
					    background-color: #EAEAEA;
					    padding: 10px 12px;
					    border-radius: 2px;
					    margin-right: 16px;
					}
					h3 {
					  font-style: normal;
					  font-weight: 600;
					  font-size: 16px;
					  line-height: 14px;
					  margin-bottom: 8px;
                    }
                    h3.group{
                      padding-left: 16px;
                    }
                    td{
                    	font-size: 12px;
                    }
                    .pdf-forms{
                   	   border-spacing: 0;
                    }
                    .pdf-repeat-count{
                       margin-top: 12px;
                       margin-bottom: 6px;
                       padding-left: 16px; 
                    }
                    .pdf-forms th{
                       font-size: 12px;
                       font-weight: 400;
                    }
                    .pdf-forms th.background{
                       background-color: #EDEDED;
                       border-top: solid 1px #A4A4A4;
                       border-left: solid 1px #A4A4A4;
                       border-right: solid 1px #A4A4A4;
                    }
                    table.pdf-forms{
                       width: 100%;
                       page-break-inside:auto;
                       padding: 0 16px;
                    }
                    .pdf-forms tr{
                       page-break-inside:avoid; 
                       page-break-after:auto
                    }
                    .pdf-forms td{
                       border-collapse: collapse;
                       padding: 8px;
                       width: 100%;
                       border-left: solid 1px #A4A4A4;
  					   border-top: solid 1px #A4A4A4;
                    }
                    .pdf-forms tr td:first-child {
  					   width: 30%;
					}
                    .pdf-forms tr td:nth-child(2){
                       width:70%; 
                       border-right: solid 1px #A4A4A4;
                    }
                    .pdf-forms td.background-light{
                       width: auto;
                    }
                    .pdf-forms tr td[colspan='2']{
                       border-right: solid 1px #A4A4A4;
                    }
                    .pdf-forms tr:last-child td{
                       border-bottom: solid 1px #A4A4A4;
                    }
                    .pdf-forms tr:last-child td.background-light{
                       border-right: solid 1px #A4A4A4 !important;
                    }
                    .pdf-attachments{
                       font-size: 14px;
                    }
                    .pdf-attachments li {
                       margin-bottom: 6px;
                    }
                    @media print {
                        .breaker{
                            page-break-before: always;
                        }
                    }
			</style>";

		$htmldata .= '<script type="text/php">
			        if ( isset($pdf) ) {
			            $x = 570;
			            $y = 760;
			            $text = "{PAGE_NUM} / {PAGE_COUNT}";
			            $font = $fontMetrics->get_font("helvetica", "bold");
			            $size = 8;
			            $color = array(0,0,0);
			            $word_space = 0.0;  //  default
			            $char_space = 0.0;  //  default
			            $angle = 0.0;   //  default
			            $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
			        }
    			</script>';
		$htmldata .= '</body></html>';

		$filename = JPATH_BASE . '/tmp/test.pdf';

		/** DOMPDF */
		$options = new Options();
		$options->set('defaultFont', 'helvetica');
		$options->set('isPhpEnabled', true);
		$dompdf = new Dompdf($options);

		$dompdf->loadHtml($htmldata);
		$dompdf->render();

		return $dompdf->stream($filename, array("Attachment" => false));
	}

	public function getFabrikElementValue(
		array       $fabrik_element,
		?string     $fnum = null,
		int         $row_id = 0,
		ValueFormat $return = ValueFormat::FORMATTED,
		int         $user_id = 0,
	): array
	{
		$isRaw = $return === ValueFormat::RAW;

		$value = [];

		if (empty($fabrik_element))
		{
			return $value;
		}

		$plugin = ElementPlugin::tryFromString($fabrik_element['plugin']);
		if ($plugin === null || empty($fabrik_element['params']) || empty($fabrik_element['group_params']))
		{
			return $value;
		}

		$params      = json_decode($fabrik_element['params']);
		$groupParams = json_decode($fabrik_element['group_params']);

		$fnums = !empty($fnum) ? [$fnum] : null;

		$date_format = null;
		if ($plugin->isDateField())
		{
			$date_format_parameter = $plugin->getDateFormatParameter();
			if (!empty($date_format_parameter))
			{
				$date_format = !empty($params->$date_format_parameter) ? $params->$date_format_parameter : 'Y-m-d H:i:s';
			}
		}

		$isRepeatGroup = !empty($groupParams) && isset($groupParams->repeat_group_button) && $groupParams->repeat_group_button == 1;

		if ($plugin === ElementPlugin::DATABASEJOIN || $isRepeatGroup)
		{
			$value[$fabrik_element['id']] = $this->getFabrikValueRepeat($fabrik_element, $fnums, $params, $isRepeatGroup, $row_id, $return, $date_format, $user_id);
		}
		else
		{
			$value[$fabrik_element['id']] = $this->getFabrikValue($fnums, $fabrik_element['db_table_name'], $fabrik_element['name'], $date_format, $row_id, $return, $user_id);
		}

		// Transform value if needed
		$transformer = TransformerFactory::make($plugin->value, (array)$params, (array)$groupParams);
		foreach ($value[$fabrik_element['id']] as $fnumKey => $val)
		{
			$key = 'val';
			if($plugin === ElementPlugin::CURRENCY){
				$key = 'raw';
			}

			$values = [$val['val']];
			if($isRepeatGroup) {
				$values = explode(',', $val['val']);
			}

			$formatted_values = [];
			foreach ($values as $_value)
			{
				if ($plugin === ElementPlugin::CURRENCY)
				{
					$formatted_values[] = self::extractNumericValue($_value);
				}
				elseif (!$isRaw)
				{
					$formatted_values[] = $transformer->transform($_value);
				}
			}

			if($isRepeatGroup) {
				$value[$fabrik_element['id']][$fnumKey][$key] = implode(', ', $formatted_values);
			} else {
				$value[$fabrik_element['id']][$fnumKey][$key] = $formatted_values[0] ?? '';
			}
		}
		//

		if (!isset($value[$fabrik_element['id']][$fnum]['complex_data']))
		{
			$value[$fabrik_element['id']][$fnum]['complex_data'] = false;
		}

		return $value;
	}

	public function getFabrikValueRepeat(
		array             $elt,
		string|array|null $fnums,
		object            $params,
		bool              $groupRepeat,
		int               $parent_row_id = 0,
		ValueFormat       $return = ValueFormat::FORMATTED,
		?string           $date_format = null,
		int               $user_id = 0
	)
	{
		if (!is_array($fnums) && $fnums !== null)
		{
			$fnums = [$fnums];
		}

		if (empty($fnums) && empty($user_id))
		{
			return [];
		}

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$plugin = ElementPlugin::tryFromString($elt['plugin']);
		if ($plugin === null)
		{
			return [];
		}

		$tableName      = $elt['db_table_name'];
		$tableJoin      = $elt['table_join'];
		$name           = $elt['name'];
		$isFnumsNull    = ($fnums === null || (is_array($fnums) && count($fnums) === 0));
		$isDatabaseJoin = ($plugin === ElementPlugin::DATABASEJOIN);
		$isMulti        = isset($params->database_join_display_type) && ($params->database_join_display_type == 'multilist' || $params->database_join_display_type == 'checkbox');

		$select   = '';
		$from     = '';
		$leftJoin = [];
		$where    = '';
		$group    = '';

		if ($plugin->isDateField())
		{
			$date_form_format = $this->dateFormatToMysql($date_format);

			if ($return === ValueFormat::BOTH)
			{
				$select = 'GROUP_CONCAT(t_repeat.' . $name . '  SEPARATOR ", ") as raw, GROUP_CONCAT(DATE_FORMAT(t_repeat.' . $name . ', ' . $db->quote($date_form_format) . ')  SEPARATOR ", ") as val, t_origin.fnum ';
			}
			elseif ($return === ValueFormat::RAW)
			{
				$select = 'GROUP_CONCAT(t_repeat.' . $name . '  SEPARATOR ", ") as val, t_origin.fnum ';
			}
			else
			{
				$select = 'GROUP_CONCAT(DATE_FORMAT(t_repeat.' . $name . ', ' . $db->quote($date_form_format) . ')  SEPARATOR ", ") as val, t_origin.fnum ';
			}
		}
		else
		{
			// TODO: Manage join_val_column_concat
			if ($isDatabaseJoin)
			{
				// join_key_column = raw, join_val_column = formatted
				if ($groupRepeat)
				{
					if ($return === ValueFormat::BOTH)
					{
						$select = 'GROUP_CONCAT(t_origin.' . $params->join_key_column . '  SEPARATOR ", ") as raw, GROUP_CONCAT(t_origin.' . $params->join_val_column . '  SEPARATOR ", ") as val, t_table.fnum ';
					}
					elseif ($return === ValueFormat::RAW)
					{
						$select = 'GROUP_CONCAT(t_origin.' . $params->join_key_column . '  SEPARATOR ", ") as val, t_table.fnum ';
					}
					else
					{
						$select = 'GROUP_CONCAT(t_origin.' . $params->join_val_column . '  SEPARATOR ", ") as val, t_table.fnum ';
					}
				}
				else
				{
					if ($isMulti)
					{
						if ($return === ValueFormat::BOTH)
						{
							$select = 'GROUP_CONCAT(t_origin.' . $params->join_key_column . '  SEPARATOR ", ") as raw, GROUP_CONCAT(t_origin.' . $params->join_val_column . '  SEPARATOR ", ") as val, t_elt.fnum ';
						}
						elseif ($return === ValueFormat::RAW)
						{
							$select = 'GROUP_CONCAT(t_origin.' . $params->join_key_column . '  SEPARATOR ", ") as val, t_elt.fnum ';
						}
						else
						{
							$select = 'GROUP_CONCAT(t_origin.' . $params->join_val_column . '  SEPARATOR ", ") as val, t_elt.fnum ';
						}
					}
					else
					{
						if ($return === ValueFormat::BOTH)
						{
							$select = 't_origin.' . $params->join_key_column . ' as raw, t_origin.' . $params->join_val_column . ' as val, t_elt.fnum ';
						}
						elseif ($return === ValueFormat::RAW)
						{
							$select = 't_origin.' . $params->join_key_column . ' as val, t_elt.fnum ';
						}
						else
						{
							$select = 't_origin.' . $params->join_val_column . ' as val, t_elt.fnum ';
						}
					}
				}
			}
			else
			{
				if ($return === ValueFormat::BOTH)
				{
					$select = 'GROUP_CONCAT(t_repeat.' . $name . '  SEPARATOR ", ") as raw, GROUP_CONCAT(t_repeat.' . $name . '  SEPARATOR ", ") as val, t_origin.fnum ';
				}
				elseif ($return === ValueFormat::RAW)
				{
					$select = 'GROUP_CONCAT(t_repeat.' . $name . '  SEPARATOR ", ") as val, t_origin.fnum ';
				}
				else
				{
					$select = 'GROUP_CONCAT(t_repeat.' . $name . '  SEPARATOR ", ") as val, t_origin.fnum ';
				}
			}
		}

		if ($isDatabaseJoin)
		{
			if ($groupRepeat)
			{
				$tableName2 = $tableJoin;
				if ($isMulti)
				{
					$from       = $db->quoteName($params->join_db_name, 't_origin');
					$leftJoin[] = $db->quoteName($tableName . '_repeat_' . $name, 't_repeat') . ' ON t_repeat.' . $name . ' = t_origin.' . $params->join_key_column;
					$leftJoin[] = $db->quoteName($tableName2, 't_elt') . ' ON t_elt.id = t_repeat.parent_id';
					$leftJoin[] = $db->quoteName($tableName, 't_table') . ' ON t_table.id = t_elt.parent_id';
				}
				else
				{
					$from       = $db->quoteName($params->join_db_name, 't_origin');
					$leftJoin[] = $db->quoteName($tableName2, 't_elt') . ' ON t_elt.' . $name . " = t_origin." . $params->join_key_column;
					$leftJoin[] = $db->quoteName($tableName, 't_table') . ' ON t_table.id = t_elt.parent_id';
				}
			}
			else
			{
				if ($isMulti)
				{
					$from       = $db->quoteName($params->join_db_name, 't_origin');
					$leftJoin[] = $db->quoteName($tableName . '_repeat_' . $name, 't_repeat') . ' ON t_repeat.' . $name . ' = t_origin.' . $params->join_key_column;
					$leftJoin[] = $db->quoteName($tableName, 't_elt') . ' ON t_elt.id = t_repeat.parent_id';
				}
				else
				{
					$from       = $db->quoteName($params->join_db_name, 't_origin');
					$leftJoin[] = $db->quoteName($tableName, 't_elt') . ' ON t_elt.' . $name . " = t_origin." . $params->join_key_column;
				}
			}

		}
		else
		{
			$from       = $db->quoteName($tableJoin, 't_repeat');
			$leftJoin[] = $db->quoteName($tableName, 't_origin') . ' ON t_origin.id = t_repeat.parent_id';
		}

		if ($isMulti || $isDatabaseJoin)
		{
			if (!$isFnumsNull)
			{
				if ($groupRepeat)
				{
					$where = $db->quoteName('t_table.fnum') . ' IN (' . implode(',', $db->quote($fnums)) . ')';
					$group = $db->quoteName('t_table.fnum');
				}
				else
				{
					$where = $db->quoteName('t_elt.fnum') . ' IN (' . implode(',', $db->quote($fnums)) . ')';
					$group = $db->quoteName('t_elt.fnum');
				}
			}
		}
		else
		{
			if (!$isFnumsNull)
			{
				$where = $db->quoteName('t_origin.fnum') . ' IN (' . implode(',', $db->quote($fnums)) . ')';
				$group = $db->quoteName('t_origin.fnum');
			}
		}

		if (!empty($user_id))
		{
			$userColumn = null;
			if ($this->tableHasColumn($tableName, 'user'))
			{
				$userColumn = 'user';
			}
			elseif ($this->tableHasColumn($tableName, 'user_id'))
			{
				$userColumn = 'user_id';
			}

			if ($userColumn !== null)
			{
				$userCondition = $db->quoteName('t_origin.' . $userColumn) . ' = ' . $db->quote($user_id);

				if (!empty($where))
				{
					$where .= ' AND ' . $userCondition;
				}
				else
				{
					$where = $userCondition;
				}
			}
		}

		if (!empty($parent_row_id) && !$isFnumsNull)
		{
			if ($isDatabaseJoin)
			{
				if ($groupRepeat)
				{
					$where .= ' AND t_table.id = ' . $db->quote($parent_row_id);
				}
				else
				{
					$where .= ' AND t_elt.id = ' . $db->quote($parent_row_id);
				}
			}
			else
			{
				$where .= ' AND t_origin.id = ' . $db->quote($parent_row_id);
			}
		}

		$query->select($select)
			->from($from);
		foreach ($leftJoin as $join)
		{
			$query->leftJoin($join);
		}
		$query->where($where)
			->group($group);

		try
		{
			$db->setQuery($query);

			if (!$isFnumsNull)
			{
				$res = $db->loadAssocList('fnum');
			}
			else
			{
				$res = $db->loadAssocList();
			}

			return $res;
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	public function getFabrikValue(
		string|array|null $fnums,
		string            $tableName,
		string            $name,
		?string           $dateFormat = null,
		int               $row_id = 0,
		ValueFormat       $return = ValueFormat::FORMATTED,
		int               $user_id = 0
	): array
	{
		$values = [];

		if (!is_array($fnums) && $fnums !== null)
		{
			$fnums = [$fnums];
		}

		if (empty($fnums) && empty($user_id))
		{
			return $values;
		}

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		// check if column fnum exists
		$fnum_column_existing = $this->tableHasColumn($tableName, 'fnum');
		$user_column           = null;
		if ($this->tableHasColumn($tableName, 'user'))
		{
			$user_column = 'user';
		}
		elseif ($this->tableHasColumn($tableName, 'user_id'))
		{
			$user_column = 'user_id';
		}


		if ($fnum_column_existing && !empty($fnums))
		{
			$query->clear()
				->select('fnum, ' . $db->quoteName($name) . ' as val')
				->from($db->quoteName($tableName))
				->where($db->quoteName('fnum') . ' IN (' . implode(',', $db->quote($fnums)) . ')');

			if (!empty($user_id) && $user_column !== null)
			{
				$query->andWhere($db->quoteName($user_column) . ' = ' . $db->quote($user_id));
			}

			if (!empty($row_id))
			{
				$query->andWhere($db->quoteName('id') . ' = ' . $db->quote($row_id));
			}

			try
			{
				$db->setQuery($query);
				$rows = $db->loadAssocList('fnum');

				if ($return === ValueFormat::BOTH)
				{
					foreach ($rows as $fnumKey => $row)
					{
						$raw       = $row['val'];
						$formatted = $raw;

						if (!empty($dateFormat) && $raw !== null && $raw !== '')
						{
							$formatted = date($dateFormat, strtotime($raw));
						}

						$values[$fnumKey] = [
							'raw'  => $raw,
							'val'  => $formatted,
							'fnum' => $fnumKey
						];
					}
				}
				elseif ($return === ValueFormat::RAW)
				{
					// keep legacy shape: val => raw value
					foreach ($rows as $fnumKey => $row)
					{
						$values[$fnumKey] = [
							'val'  => $row['val'],
							'fnum' => $fnumKey
						];
					}
				}
				else // formatted
				{
					foreach ($rows as $fnumKey => $row)
					{
						$val = $row['val'];
						if (!empty($dateFormat) && $val !== null && $val !== '')
						{
							$val = date($dateFormat, strtotime($val));
						}

						$values[$fnumKey] = [
							'val'  => $val,
							'fnum' => $fnumKey
						];
					}
				}
			}
			catch (Exception $e)
			{
				throw $e;
			}
		}
		else
		{
			if ($user_column !== null && !empty($user_id))
			{
				try
				{
					// Query by user/user_id
					$query->clear()
						->select($db->quoteName($name) . ' as val, ' . $db->quoteName($user_column) . ' as user_val')
						->from($db->quoteName($tableName))
						->where($db->quoteName($user_column) . ' = ' . $db->quote($user_id));

					if (!empty($row_id))
					{
						$query->andWhere($db->quoteName('id') . ' = ' . $db->quote($row_id));
					}

					$db->setQuery($query);

					// We don't have fnum key; use user id as key to keep associative results stable
					$row = $db->loadAssoc();

					if ($row !== null)
					{
						$raw = $row['val'];
						if ($return === ValueFormat::BOTH)
						{
							$formatted = $raw;
							if (!empty($dateFormat) && $raw !== null && $raw !== '')
							{
								$formatted = date($dateFormat, strtotime($raw));
							}

							$values[$user_id] = [
								'raw'  => $raw,
								'val'  => $formatted,
								'fnum' => $user_id
							];
						}
						elseif ($return === ValueFormat::RAW)
						{
							$values[$user_id] = [
								'val'  => $raw,
								'fnum' => $user_id
							];
						}
						else
						{
							$val = $raw;
							if (!empty($dateFormat) && $val !== null && $val !== '')
							{
								$val = date($dateFormat, strtotime($val));
							}

							$values[$user_id] = [
								'val'  => $val,
								'fnum' => $user_id
							];
						}
					}
				}
				catch (Exception $e)
				{
					throw $e;
				}
			}
			else
			{
				if (!empty($user_column_existing))
				{
					try
					{
						foreach ($fnums as $fnum)
						{
							$applicant_id = EmundusHelperFiles::getApplicantIdFromFnum($fnum);

							$query->clear()
								->select($db->quoteName($name) . ' as val')
								->from($db->quoteName($tableName))
								->where($db->quoteName('user_id') . ' = ' . $db->quote($applicant_id));

							if (!empty($row_id))
							{
								$query->andWhere($db->quoteName('id') . ' = ' . $db->quote($row_id));
							}

							$db->setQuery($query);
							$value = $db->loadResult();

							if ($return === ValueFormat::BOTH)
							{
								$raw       = $value;
								$formatted = $raw;
								if (!empty($dateFormat) && $raw !== null && $raw !== '')
								{
									$formatted = date($dateFormat, strtotime($raw));
								}

								$values[$fnum] = [
									'raw'  => $raw,
									'val'  => $formatted,
									'fnum' => $fnum
								];
							}
							elseif ($return === ValueFormat::RAW)
							{
								$values[$fnum] = [
									'val'  => $value,
									'fnum' => $fnum
								];
							}
							else // formatted
							{
								$val = $value;
								if (!empty($dateFormat) && $val !== null && $val !== '')
								{
									$val = date($dateFormat, strtotime($val));
								}

								$values[$fnum] = [
									'val'  => $val,
									'fnum' => $fnum
								];
							}
						}
					}
					catch (Exception $e)
					{
						throw $e;
					}
				}
			}
		}

		return $values;
	}

	private function dateFormatToMysql($date_format)
	{
		$date_format = str_replace('D', '%D', $date_format);
		$date_format = str_replace('d', '%d', $date_format);
		$date_format = str_replace('M', '%M', $date_format);
		$date_format = str_replace('m', '%m', $date_format);
		$date_format = str_replace('Y', '%Y', $date_format);
		$date_format = str_replace('y', '%y', $date_format);
		$date_format = str_replace('H', '%H', $date_format);
		$date_format = str_replace('h', '%h', $date_format);
		$date_format = str_replace('I', '%I', $date_format);
		$date_format = str_replace('i', '%i', $date_format);
		$date_format = str_replace('S', '%S', $date_format);

		return str_replace('s', '%s', $date_format);
	}

	private function tableHasColumn(string $tableName, string $columnName): bool
	{
		$db  = Factory::getContainer()->get('DatabaseDriver');
		$sql = 'SHOW COLUMNS FROM ' . $db->quoteName($tableName) . ' WHERE ' . $db->quoteName('Field') . ' = ' . $db->quote($columnName);
		$res = $db->setQuery($sql)->loadResult();

		return !empty($res);
	}
}
