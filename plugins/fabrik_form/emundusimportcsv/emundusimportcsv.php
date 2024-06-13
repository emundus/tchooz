<?php
/**
 * @package Fabrik
 * @copyright Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Import data from a csv file in Fabrik tables
 */

// No direct access
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseFactory;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';


require_once (JPATH_ROOT.'/components/com_emundus/models/emails.php');

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juseremundus
 * @since       3.0
 */

class PlgFabrik_FormEmundusimportcsv extends plgFabrik_Form {

	private function getCsvDelimiter($filePath, $checkLines = 3)
	{
		$delimiters =[",", ";", "\t"];

		$default =",";

		$fileObject = new \SplFileObject($filePath);
		$results = [];
		$counter = 0;
		while ($fileObject->valid() && $counter <= $checkLines) {
			$line = $fileObject->fgets();
			foreach ($delimiters as $delimiter) {
				$fields = explode($delimiter, $line);
				$totalFields = count($fields);
				if ($totalFields > 1) {
					if (!empty($results[$delimiter])) {
						$results[$delimiter] += $totalFields;
					} else {
						$results[$delimiter] = $totalFields;
					}
				}
			}
			$counter++;
		}
		if (!empty($results)) {
			$results = array_keys($results, max($results));

			return $results[0];
		}
		return $default;
	}
	
	public function onBeforeStore() {
		
		if (!$this->app->isClient('administrator')) {
			jimport('joomla.log.log');
			Log::addLogger(array('text_file' => 'com_emundus.csvimport.php'), Log::ALL, array('com_emundus.csvimport'));

			$formModel = $this->getModel();

			$csv = $formModel->formData['jos_emundus_setup_csv_import___csv_file_raw'];
			$campaign_id = $formModel->formData['jos_emundus_setup_csv_import___campaign_raw'];
			if(is_array($campaign_id)){
				$campaign_id = $campaign_id[0];
			}
			$create_new_fnum = $formModel->formData['jos_emundus_setup_csv_import___create_new_fnum'];
			$send_email = $formModel->formData['jos_emundus_setup_csv_import___send_email_raw'];
			if(is_array($send_email)){
				$send_email = $send_email[0];
			}

			// Check if the file is a file on the server and in the right format.
			if (!is_file(JPATH_ROOT.$csv)) {
				Log::add('ERROR: Tried to upload something that was not a file.', Log::ERROR, 'com_emundus.csvimport');
				$this->app->enqueueMessage('ERROR: Tried to upload something that was not a file.', 'error');
				return false;
			}

			if (pathinfo($csv, PATHINFO_EXTENSION) !== 'csv') {
				Log::add('ERROR: Tried to upload something that was not a csv file.', Log::ERROR, 'com_emundus.csvimport');
				$this->app->enqueueMessage('ERROR: Tried to upload something that was not a csv file.', 'error');
				return false;
			}

			// auto_detect_line_endings allows PHP to detect MACOS line endings or else things get ugly...
			ini_set('auto_detect_line_endings', TRUE);

			$handle = fopen(JPATH_ROOT.$csv, 'r');
			if (!$handle) {
				Log::add('ERROR: Could not open import file.', Log::ERROR, 'com_emundus.csvimport');
				$this->app->enqueueMessage('ERROR: Could not open import file.', 'error');
				return false;
			}

			PluginHelper::importPlugin('emundus', 'custom_event_handler');
			$this->app->triggerEvent('onCallEventHandler',['onBeforeImportCSV', ['data' => array(
				'csv' => $csv,
				'create_new_fnum' => $create_new_fnum,
				'formData' => $formModel->formData,
			)]]);

			// Prepare data structure for parsing.
			$query = $this->_db->getQuery(true);

			$database_elements = [];
			$bad_columns = [];
			$checked_tables = [];
			$repeat_tables = [];
			
			// Static column
			$campaign_column = null;
			$profile_column = null;
			$status_column = null;
			$group_column = null;
			$email_column = null;
			$username_column = null;
			$lastname_column = null;
			$firstname_column = null;
			$fnum_column = null;
			
			$campaign_row = null;
			$profile_row = null;
			$status_row = null;
			$group_row = null;
			$email_row = null;
			$username_row = null;
			$lastname_row = null;
			$firstname_row = null;
			$fnum_row = null;

			$row = 0;

			$delimiter = $this->getCsvDelimiter(JPATH_ROOT.$csv);

			if (($data = fgetcsv($handle, 0, $delimiter)) !== false) {

				foreach ($data as $column_number => $column) {

					// If the file name is not in the following format : table___element; mark column as bad.
					$column = explode("___", trim(preg_replace('/[^\PC\s]/u', '', $column)));
					
					if (count($column) !== 2) {
						// Special columns such as the campaign ID can be inserted.
						if ($create_new_fnum && $column[0] == 'campaign') 
						{
							$campaign_column = $column_number;
						} 
						else if ($create_new_fnum && $column[0] == 'status') 
						{
							$status_column = $column_number;
						}
						else if ($column[0] == 'group') 
						{
							$group_column = $column_number;
						} 
						else if ($column[0] == 'profile') 
						{
							$profile_column = $column_number;
						} 
						else if ($column[0] == 'email') 
						{
							$email_column = $column_number;
						}
						else if ($column[0] == 'username')
						{
							$username_column = $column_number;
						}
						else if ($column[0] == 'lastname') 
						{
							$lastname_column = $column_number;
						} 
						else if ($column[0] == 'firstname') 
						{
							$firstname_column = $column_number;
						}
						else if ($column[0] == 'fnum')
						{
							$fnum_column = $column_number;
						}

						$bad_columns[] = $column_number;
						continue;
					}

					$table = $column[0];
					$element = $column[1];

					// Test the existence of the table and the fnum column.
					if ($table !== 'jos_emundus_users' && $table !== 'jos_emundus_campaign_candidature') {

						if (!in_array($table, $checked_tables)) {

							// Check if we are dealing with a repeat table.
							if (strpos($table, '_repeat') !== false) {

								// If we are, we check for the presence of the parent_id column.
								$this->_db->setQuery('SHOW COLUMNS FROM '.$this->_db->quoteName($table).' LIKE '.$this->_db->quote('parent_id'));
								try {
									if (empty($this->_db->loadResult())) {
										$bad_columns[] = $column_number;
										continue;
									}
								} catch (Exception $e) {
									$bad_columns[] = $column_number;
									continue;
								}

								// Parse parent table name from repeat table name using a RegEx.
								$parent_table = preg_split('/_\d+_repeat$/', $table);

								// If the result of the preg_split contains 2 elements (meaning the regex found a match) but the second is empty (meaning we correctly split on the end of the string), the table name matches the correct format.
								if (sizeof($parent_table) === 2 && empty($parent_table[1])) {
									$parent_table = $parent_table[0];
								} else {

									// In case our table is not a repeat group, we need to check the case of a repeat element (like a databasejoin with a multi-select)
									$parent_table = preg_split('/_repeat_'.$element.'$/', $table);

									// If the result of the preg_split contains 2 elements (meaning the regex found a match) but the second is empty (meaning we correctly split on the end of the string), the table name matches the correct format.
									if (sizeof($parent_table) === 2 && empty($parent_table[1])) {
										$parent_table = $parent_table[0];
									} else {
										$bad_columns[] = $column_number;
										continue;
									}
								}

								// We add the table to the repeat tables so we can later insert.
								if (!in_array($table, $repeat_tables)) {
									$repeat_tables[$column_number] = new stdClass();
									$repeat_tables[$column_number]->parent = $parent_table;
									$repeat_tables[$column_number]->table = $table;
								}

								// If the parent table is not in the list of known parents, we need to check if it contains an fnum column.
								if (!in_array($parent_table, array_keys($repeat_tables))) {

									// Check for the presence of the fnum in the parent table.
									$this->_db->setQuery('SHOW COLUMNS FROM '.$this->_db->quoteName($parent_table).' LIKE '.$this->_db->quote('fnum'));
									try {
										if (empty($this->_db->loadResult())) {
											$bad_columns[] = $column_number;
											$database_elements[$column_number] = new stdClass();
											$database_elements[$column_number]->table = $table;
											$database_elements[$column_number]->column = $element;
											$repeat = true;
											continue;
										}
									} catch (Exception $e) {
										$bad_columns[] = $column_number;
										continue;
									}

								}

								$repeat = true;

							} else {

								// If not, we check for the presence of the fnum.
								$this->_db->setQuery('SHOW COLUMNS FROM '.$this->_db->quoteName($table).' LIKE '.$this->_db->quote('fnum'));
								try {
									if (empty($this->_db->loadResult())) {
										$bad_columns[] = $column_number;
										$database_elements[$column_number] = new stdClass();
										$database_elements[$column_number]->table = $table;
										$database_elements[$column_number]->column = $element;
										continue;
									}
								} catch (Exception $e) {
									$bad_columns[] = $column_number;
									continue;
								}
								$checked_tables[] = $table;
							}
						}

						$this->_db->setQuery('SHOW COLUMNS FROM '.$this->_db->quoteName($table).' LIKE '.$this->_db->quote($element));
						try {
							if (empty($this->_db->loadResult())) {
								$bad_columns[] = $column_number;
								continue;
							}
						} catch (Exception $e) {
							$bad_columns[] = $column_number;
							continue;
						}

					}
					if(empty($database_elements[$column_number]))
					{
						$database_elements[$column_number] = new stdClass();
						$database_elements[$column_number]->table  = $table;
						$database_elements[$column_number]->column = $element;
					}
				}
			} else {
				Log::add('ERROR: Empty file was uploaded.', Log::ERROR, 'com_emundus.csvimport');
				$this->app->enqueueMessage('ERROR: Empty file was uploaded.', 'error');
				return false;
			}

			$parsed_data = [];
			while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
				
				foreach ($data as $column_number => $column) {
					// Clean up data from any invisible chars in xls.
					$column = trim(preg_replace('/[^\PC\s]/u', '', $column));

					if ($column_number === $profile_column) 
					{
						$profile_row[$row] = $column;
					}
					else if ($column_number === $campaign_column) 
					{
						$campaign_row[$row] = $column;

						// If we have no profile, we must get the associated one using the campaign.
						if (empty($profile_column)) 
						{
							$query->clear()
								->select($this->_db->quoteName('profile_id'))
								->from($this->_db->quoteName('#__emundus_setup_campaigns'))
								->where($this->_db->quoteName('id').' = '.$column);
							$this->_db->setQuery($query);

							try {
								$profile_row[$row] = $this->_db->loadResult();
							} catch (Exception $e) {
								Log::add('ERROR: Could not get profile using campaign in row.', Log::ERROR, 'com_emundus.csvimport');
								continue;
							}
						}
						continue;
					} 
					elseif ($column_number === $status_column) 
					{
						$status_row[$row] = $column;
						continue;
					}
					elseif ($column_number === $group_column) 
					{
						$group_row[$row] = $column;
					} 
					elseif ($column_number === $email_column) 
					{
						$email_row[$row] = $column;
					}
					elseif ($column_number === $username_column)
					{
						$username_row[$row] = $column;
					}
					elseif ($column_number === $lastname_column) 
					{
						$lastname_row[$row] = $column;
					} 
					elseif ($column_number === $firstname_column) 
					{
						$firstname_row[$row] = $column;
					}
					elseif ($column_number === $fnum_column)
					{
						$fnum_row[$row] = $column;
					}

					// If in bad columns we import in an other table
					if (in_array($column_number, $bad_columns))
					{
						if (in_array($column_number, array_keys($repeat_tables))) {
							// The repeat values are inserted into a separate array as they will be inserted into the DB AFTER their parent table.
							$parsed_repeat[$row][$repeat_tables[$column_number]->parent][$repeat_tables[$column_number]->table][$database_elements[$column_number]->column] = $column;
						} else {
							if(!empty($database_elements[$column_number]->table)) {
								// Build the complex data structure.
								$parsed_data[$row][$database_elements[$column_number]->table][$database_elements[$column_number]->column] = $column;
							}
						}
					}

					if (in_array($column_number, array_keys($repeat_tables))) 
					{
						// The repeat values are inserted into a separate array as they will be inserted into the DB AFTER their parent table.
						if(!empty($database_elements[$column_number]->table)) {
							$parsed_repeat[$row][$repeat_tables[$column_number]->parent][$repeat_tables[$column_number]->table][$database_elements[$column_number]->column] = $column;
						}
					} else {
						// Build the complex data structure.
						if(!empty($database_elements[$column_number]->table)) {
							$parsed_data[$row][$database_elements[$column_number]->table][$database_elements[$column_number]->column] = $column;
						}
					}

				}

				$row++;
			}
			fclose($handle);


			// If we never incremented row then there are not files being imported.
			if ($row === 0) {
				Log::add('ERROR: No data sent in file.', Log::ERROR, 'com_emundus.csvimport');
				$this->app->enqueueMessage('ERROR: No data sent in file.', 'error');
				return false;
			}

			// If have no parsed data, something went wrong.
			if (empty($parsed_data)) {
				Log::add('ERROR: Something went wrong, please check that your CSV is separated by semi-colons (;).', Log::ERROR, 'com_emundus.csvimport');
				$this->app->enqueueMessage('ERROR: Something went wrong, please check that your CSV is separated by semi-colons (;).', 'error');
				return false;
			}

			$totals = [
				'user' => 0,
				'fnum' => 0,
				'write' => 0
			];


			if (!$this->app->getIdentity()->authorise('core.admin') && !EmundusHelperAccess::asAccessAction(12, 'c')) {
				$can_create_user = false;
			} else {
				$can_create_user = true;
			}

			// Handle parsed data insertion
			foreach ($parsed_data as $row_id => $insert_row) {
				$new_user = false;
				$user_id = 0;

				if (isset($username_row[$row_id])) {
					$username = $username_row[$row_id];
				} elseif (isset($email_row[$row_id])) {
					$username = $email_row[$row_id];
				} else {
					Log::add('ERROR: Something went wrong, we need email or username to insert datas', Log::ERROR, 'com_emundus.csvimport');
					$this->app->enqueueMessage('ERROR: Something went wrong, we need email or username to insert datas', 'error');
					return false;
				}

				if(isset($campaign_row[$row_id])){
					$campaign = $campaign_row[$row_id];
				} elseif (isset($campaign_id)){
					$campaign = $campaign_id;

					if (empty($profile_column))
					{
						$query->clear()
							->select($this->_db->quoteName('profile_id'))
							->from($this->_db->quoteName('#__emundus_setup_campaigns'))
							->where($this->_db->quoteName('id').' = '.$campaign);
						$this->_db->setQuery($query);

						try {
							$profile_row[$row_id] = $this->_db->loadResult();
						} catch (Exception $e) {
							Log::add('ERROR: Could not get profile using campaign in row.', Log::ERROR, 'com_emundus.csvimport');
							continue;
						}
					}
				} else {
					Log::add('ERROR: Something went wrong, we need a campaign to create a file', Log::ERROR, 'com_emundus.csvimport');
					$this->app->enqueueMessage('ERROR: Something went wrong, we need a campaign to create a file', 'error');
					return false;
				}

				if(!empty($username)) {
					$query->clear()
						->select('id')
						->from($this->_db->quoteName('#__users'))
						->where($this->_db->quoteName('username') . ' LIKE ' . $this->_db->quote($username))
						->orWhere($this->_db->quoteName('email') . ' LIKE ' . $this->_db->quote($username));
					$this->_db->setQuery($query);
					$user_id = $this->_db->loadResult();
				}
				
				if($can_create_user && empty($user_id))
				{
					include_once(JPATH_SITE . '/components/com_emundus/models/users.php');
					$m_users = new EmundusModelUsers();

					$password = UserHelper::genRandomPassword();

					$insert_user = [
						'name' => $firstname_row[$row_id] . ' ' . $lastname_row[$row_id],
						'username' => $username,
						'email' => $email_row[$row_id],
						'password' => UserHelper::hashPassword($password),
						'registerDate' => date('Y-m-d H:i:s'),
						'lastvisitDate' => date('Y-m-d H:i:s'),
						'params' => '{}'
					];
					$insert_user = (object) $insert_user;

						try {
							$this->_db->insertObject('#__users', $insert_user);
							$user_id = $this->_db->insertid();

							$query->clear()
								->insert($this->_db->quoteName('#__user_usergroup_map'))
								->set($this->_db->quoteName('user_id') . ' = ' . $user_id)
								->set($this->_db->quoteName('group_id') . ' = 2');
							$this->_db->setQuery($query);
							$this->_db->execute();
						}
						catch (Exception $e) {
							Log::add("Failed to insert jos_users" . $e->getMessage(), Log::ERROR, 'com_emundus.error');
						}

						if (!empty($user_id)) {
							$other_param['firstname']    = $firstname_row[$row_id];
							$other_param['lastname']     = $lastname_row[$row_id];
							$other_param['profile']      = $profile_row[$row_id];
							$other_param['em_oprofiles'] = '';
							$other_param['univ_id']      = 0;
							$other_param['em_groups']    = '';
							$other_param['em_campaigns'] = [];
							$other_param['news']         = '';
							$m_users->addEmundusUser($user_id, $other_param);
						}

					$new_user = true;
				}

				$fnum = '';
				if(isset($fnum_row[$row_id])){
					$fnum = $fnum_row[$row_id];
				} elseif($create_new_fnum) {
					include_once(JPATH_SITE . '/components/com_emundus/helpers/files.php');
					$h_files = new EmundusHelperFiles();

					$status = 0;
					if(isset($status_row[$row_id])){
						$status = $status_row[$row_id];
					}

					$fnum = $h_files->createFnum($campaign, $user_id);
					$columns = [
						'date_time' => date('Y-m-d H:i:s'),
						'applicant_id' => $user_id,
						'user_id' => JFactory::getUser()->id,
						'campaign_id' => $campaign,
						'fnum' => $fnum,
						'status' => $status,
					];
					if(array_key_exists('jos_emundus_campaign_candidature',$insert_row)){
						foreach ($insert_row['jos_emundus_campaign_candidature'] as $key => $cc){
							if($key == 'date_time' && !empty($cc)) {
								$dt = DateTime::createFromFormat("d/m/Y", $cc);
								if($dt) {
									$ts            = $dt->getTimestamp();
									$columns[$key] = date('Y-m-d H:i:s', $ts);
								}
							} elseif (!empty($cc)) {
								$columns[$key] = $cc;
							}
						}
					}
					
					$query->clear()
						->insert($this->_db->quoteName('#__emundus_campaign_candidature'))
						->columns($this->_db->quoteName(array_keys($columns)))
						->values(implode(',',$this->_db->quote(array_values($columns))));
					$this->_db->setQuery($query);
					$file_created = $this->_db->execute();
				} else {
					Log::add('ERROR: Something went wrong, please provide a fnum or check yes to create new file', Log::ERROR, 'com_emundus.csvimport');
					$this->app->enqueueMessage('ERROR: Something went wrong, please provide a fnum or check yes to create new file', 'error');
					return false;
				}

				if(!empty($user_id) && !empty($fnum))
				{
					$executed_parent_tables = [];
					$tables                 = array_keys($insert_row);
					foreach ($tables as $table) {
						if($table !== 'jos_emundus_campaign_candidature') {
							$datas = $insert_row[$table];

							$query->clear()
								->select('id')
								->from($this->_db->quoteName($table))
								->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));
							$this->_db->setQuery($query);
							$existing_row = $this->_db->loadResult();

							if (empty($existing_row)) {
								$columns_query = "SELECT COLUMN_NAME FROM information_schema.COLUMNS where TABLE_NAME like ".$this->_db->quote($table);
								$this->_db->setQuery($columns_query);
								$columns_names = $this->_db->loadColumn();

								$this->_db->setQuery("SET sql_mode=(SELECT REPLACE(REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''), 'STRICT_TRANS_TABLES', ''));");
								$this->_db->execute();

								$query->clear()
									->insert($this->_db->quoteName($table));
								foreach ($datas as $key => $data) {
									$query_element = $this->_db->getQuery(true);
									$query_element->select('plugin')
										->from($this->_db->quoteName('#__fabrik_elements'))
										->where($this->_db->quoteName('name') . ' LIKE ' . $this->_db->quote($key));
									$this->_db->setQuery($query_element);
									$plugin = $this->_db->loadResult();

									if($plugin == 'date' || $plugin == 'birthday') {
										$dt = DateTime::createFromFormat("d/m/Y", $data);
										$ts = $dt->getTimestamp();
										$query->set($this->_db->quoteName($key) . ' = ' . $this->_db->quote(date('Y-m-d H:i:s',$ts)));
									} else {
										$query->set($this->_db->quoteName($key) . ' = ' . $this->_db->quote($data));
									}
								}
								$query->set($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum));

								if(in_array('student_id',$columns_names)){
									$query->set($this->_db->quoteName('student_id') . ' = ' . $this->_db->quote($user_id));
								}
								if(in_array('user',$columns_names)){
									$query->set($this->_db->quoteName('user') . ' = ' . $this->_db->quote($user_id));
								}
								if(in_array('date_time',$columns_names)){
									$query->set($this->_db->quoteName('date_time') . ' = ' . $this->_db->quote(date('Y-m-d H:i:s')));
								}

							}
							else {
								$query->update($this->_db->quoteName($table));
								foreach ($datas as $key => $data) {
									$query->set($this->_db->quoteName($key) . ' = ' . $this->_db->quote($data));
								}
								$query->where($this->_db->quoteName('id') . ' = ' . $existing_row);
							}

							$this->_db->setQuery($query);
							try {
								$this->_db->execute();
								$totals['write']++;
							}
							catch (Exception $e) {
								Log::add('ERROR inserting data in query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()) . ' error text -> ' . $e->getMessage(), Log::ERROR, 'com_emundus.csvimport');
							}

							// Insert into child repeat tables.
							if (isset($parsed_repeat) && !in_array($table, $executed_parent_tables) && in_array($table, array_keys($parsed_repeat[$row_id]))) {

								$parent_id = $this->_db->insertid();
								foreach ($parsed_repeat[$row_id] as $parent_table => $repeat_table) {
									foreach ($repeat_table as $repeat_table_name => $repeat_columns) {
										$this->_db->setQuery("SET sql_mode=(SELECT REPLACE(REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''), 'STRICT_TRANS_TABLES', ''));");
										$this->_db->execute();

										$query->clear()
											->insert($this->_db->quoteName($repeat_table_name));
										$inserting_rows = [];

										$repeat_columns = array_merge(...array_map(function ($r_column, $k_key) {
											return [$k_key => explode('|', trim($r_column))];
										}, $repeat_columns, array_keys($repeat_columns)));
										$query->columns($this->_db->quoteName('parent_id') . ',' . implode(',', $this->_db->quoteName(array_keys($repeat_columns))));

										$number_values = sizeof($repeat_columns[array_keys($repeat_columns)[0]]);
										for ($i = 0; $i < $number_values; $i++) {
											$repeat_insert_row = [];

											foreach (array_keys($repeat_columns) as $r_key) {
												$repeat_insert_row[] = is_numeric($repeat_columns[$r_key][$i]) ? $repeat_columns[$r_key][$i] : $this->_db->quote($repeat_columns[$r_key][$i]);
											}
											$query->values($parent_id . ', ' . implode(',', $repeat_insert_row));
										}

										$this->_db->setQuery($query);
										try {
											$this->_db->execute();
											$executed_parent_tables[] = $table;
											Log::add(' --- INSERTED REPEAT ROW :' . $this->_db->insertid() . ' AT TABLE : ' . $repeat_table_name, Log::INFO, 'com_emundus.csvimport');
										}
										catch (Exception $e) {
											Log::add('ERROR inserting data in query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()) . ' error text -> ' . $e->getMessage(), Log::ERROR, 'com_emundus.csvimport');
										}
									}
								}
							}
						}
					}
				}

				if ($new_user && !empty($send_email) && $send_email == 1) {
					$totals['user']++;

					// Send email indicating account creation.
					$m_emails = new EmundusModelEmails();
					$tags = array('patterns' => [], 'replacements' => []);

					$email = $m_emails->getEmail('new_account');
					try {
						$tags = $m_emails->setTags($user_id, null, $fnum, $password, $email->emailfrom.$email->name.$email->subject.$email->message);
					} catch(Exception $e) {
						Log::add('ERROR setting tags in query : error text -> '.$e->getMessage(), Log::ERROR, 'com_emundus.csvimport');
					}

					$mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
					$from = preg_replace($tags['patterns'], $tags['replacements'], $email->emailfrom);
					$fromname = preg_replace($tags['patterns'], $tags['replacements'], $email->name);
					$subject = preg_replace($tags['patterns'], $tags['replacements'], $email->subject);
					$body = $email->message;

					if (!empty($email->Template)) {
						$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $email->Template);
					}
					$body = preg_replace($tags['patterns'], $tags['replacements'], $body);
					$body = $m_emails->setTagsFabrik($body, [$fnum]);

					$mail_from_address = $this->app->getConfig()->get('mailfrom');

					$sender = [
						$mail_from_address,
						$fromname
					];

					$mailer->setSender($sender);
					$mailer->addReplyTo($email->emailfrom, $email->name);
					$mailer->addRecipient($email_row[$row_id]);
					$mailer->setSubject($email->subject);
					$mailer->isHTML(true);
					$mailer->Encoding = 'base64';
					$mailer->setBody($body);

					try {
						$send = $mailer->Send();

						if ($send === false) {
							Log::add('No email configuration!', Log::ERROR, 'com_emundus.csvimport');
						} else {

                            Log::add('Email account sent: ' . $email_row[$row_id], Log::INFO, 'com_emundus.csvimport');

                            if (ComponentHelper::getParams('com_emundus')->get('logUserEmail', '0') == '1') {
                                $message = array(
                                    'user_id_to' => $user_id,
                                    'subject' => $email->subject,
                                    'message' => $body
                                );
                                $m_emails->logEmail($message);
                            }
                        }

					} catch (Exception $e) {
						Log::add('ERROR: Could not send email to user : '.$user_id, Log::ERROR, 'com_emundus.csvimport');
					}
				}

				$parsed_data[$row_id]['user_id'] = $user_id;
				$parsed_data[$row_id]['fnum'] = $fnum;
			}
		}

		if (!empty($totals)) {

			$totals['write'] += ((2*$totals['user']) + (2*$totals['ldap']));

			$summary = '';

			if (!empty($totals['user'])) {
				$summary .= 'Added '.$totals['user'].' new users. <br>';
			}

			if (!empty($totals['ldap'])) {
				$summary .= 'Added '.$totals['ldap'].' users found in the LDAP system. <br>';
			}

			if (!empty($totals['cas'])) {
				$summary .= 'Added '.$totals['cas'].' users using the CAS system. <br>';
			}

			if (!empty($totals['fnum'])) {
				$summary .= 'Added '.$totals['fnum'].' new candidacy files. <br>';
			}

			if (!empty($totals['write'])) {
				$summary .= 'Wrote '.$totals['write'].' lines.';
			}
			$resume = $summary;
			$this->app->enqueueMessage($summary, 'info');
		}

		$data = array(
			'csv' => $csv,
			'rows' => $parsed_data,
			'bad_columns' => $bad_columns,
			'checked_tables' => $checked_tables,
			'repeat_tables' => $repeat_tables,
			'database_elements' => $database_elements,
			'formData' => $formModel->formData,
			'resume' => !empty($resume) ? $resume : null
		);

		PluginHelper::importPlugin('emundus', 'custom_event_handler');
		$this->app->triggerEvent('onCallEventHandler', ['onAfterImportCSV', $data]);

		return true;
	}
}
