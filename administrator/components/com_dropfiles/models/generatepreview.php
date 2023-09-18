<?php
/**
 * Dropfiles
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 *
 * @package   Dropfiles
 * @copyright Copyright (C) 2013 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2013 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license   GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') || die;

jimport('joomla.application.component.modellist');
//jimport('joomla.application.component.modeladmin');

/**
 * Class DropfilesModelGeneratepreview
 */
class DropfilesModelGeneratepreview extends JModelList
{
    const RETRY = 200;
    const RETRY_ON_MAX_REQUEST = 429;
    const ABORT_REMOVE = 0;

    const GENERATED_FAILED = -1;

    const MAX_RETRIES = 3;
    const WAIT_MINUTES = 1;

    /**
     * DEBUG
     *
     * @var boolean
     */
    private static $debug = true;

    /**
     * JOOMUNITED TOKEN
     *
     * @var string
     */
    private $juToken = '';

    /**
     * Endpoint
     *
     * @var string
     */
    private $endpoint = 'https://previewer.joomunited.com/file';

    /**
     * Push URL
     *
     * @var string
     */
    private $pushUrl;

    /**
     * Support extensions
     *
     * @var array
     */
    private $supportExtensions = array('ai', 'csv', 'doc', 'docx', 'html', 'json', 'odp', 'ods', 'pdf', 'ppt', 'pptx', 'rtf', 'sketch', 'xd', 'xls', 'xlsx', 'xml', 'jpg', 'png', 'gif', 'jpeg');

    /**
     * Support extensions to generate thumbnail
     *
     * @var array
     */
    private $thumbnailSupportExtensions = array('jpg', 'png', 'gif', 'jpeg');

    /**
     * Folder helper
     *
     * @var boolean|object
     */
    private $folderHelper = false;

    /**
     * Resize engine
     *
     * @var boolean|object
     */
    private $resize = false;

    /**
     * Option model
     *
     * @var boolean|object
     */
    private $modelOption = false;

    /**
     * Files model
     *
     * @var boolean|object
     */
    private $modelFiles = false;

    /**
     * DropfilesModelGeneratepreview constructor.
     */
    public function __construct()
    {
        if (defined('DROPFILES_DEBUG') && DROPFILES_DEBUG) {
            self::$debug = true;
        }

        JLoader::register('DropfilesHelperfolder', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/dropfilesHelperFolder.php');
        JLoader::register('DropfilesResizeImage', JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesResizeImage.php');
        $optionModelPath = JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/options.php';
        require_once $optionModelPath;
        $filesModelPath = JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/files.php';
        require_once $filesModelPath;

        $this->folderHelper = new DropfilesHelperfolder();
        $this->resize = new DropfilesResizeImage();
        $this->modelOption = new DropfilesModelOptions();
        $this->modelFiles = new DropfilesModelFiles();
    }

    /**
     * Get current queue status
     *
     * @return array
     */
    public function getStatus()
    {
        $juToken = $this->getJuToken();

        if (is_null($juToken) || !$juToken || $juToken === '') {
            return array('error' => true, 'code' => 'user_not_login', 'message' => 'Please connect your Joomunited account!');
        }

        $defaultStatus = array(
            'p_total' => 0,
            'p_processing' => 0,
            'p_pending' => 0,
            'p_generated' => 0,
            'p_error' => 0,
            'error_files_id' => array(),
            'logs' => '',
        );

        // Get current queue
        $queueFilesInOption = $this->modelOption->get_option('_dropfiles_previewer_generate_queue_files');
        $queueFilesInOption = (array) json_decode($queueFilesInOption);

        if (!is_array($queueFilesInOption) || (is_array($queueFilesInOption) && empty($queueFilesInOption))) {
            return $defaultStatus;
        }

        // 1. Total files can generate preview
        $defaultStatus['p_total'] = count($queueFilesInOption);

        foreach ($queueFilesInOption as $queue) {
            $queue = (array) $queue;
            if (isset($queue['preview_generated']) && intval($queue['preview_generated']) === 0 && isset($queue['in_process']) && intval($queue['in_process']) === 0 && !isset($queue['ignore'])) {
                // 2. Total files not generate yet. preview_generated = 0, on_process = 0
                $defaultStatus['p_pending']++;
            } elseif (isset($queue['preview_generated']) && intval($queue['preview_generated']) === 1 && isset($queue['in_process']) && intval($queue['in_process']) === 0) {
                // 3. Generated
                $defaultStatus['p_generated']++;
            } elseif (isset($queue['preview_generated']) && intval($queue['preview_generated']) === 0 && isset($queue['in_process']) && intval($queue['in_process']) === 1) {
                // 4. Total files on processing.
                $defaultStatus['p_processing']++;
            } elseif (isset($queue['preview_generated']) && intval($queue['preview_generated']) === 0 && isset($queue['in_process']) && intval($queue['in_process']) === 0 && isset($queue['ignore']) && intval($queue['ignore']) === 1) {
                $defaultStatus['p_error']++;
                $defaultStatus['error_files_id'][] = $queue['file_id'];
            }
        }

        if ($defaultStatus['p_error'] > 0) {
            $defaultStatus['error_message'] = sprintf('%d file previews cannot be generated', $defaultStatus['p_error']);
        }

        // Get logs
        $logs = $this->getLatestLog();
        $defaultStatus['logs'] = $logs ? $logs : '';

        return $defaultStatus;
    }

    /**
     * Get joomunited token
     *
     * @return string|boolean
     */
    public function getJuToken()
    {
        $token = null;
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('value');
        $query->from('#__joomunited_config');
        $query->where('name="ju_user_token"');
        $db->setQuery($query);
        $res = $db->loadObject();

        if (!empty($res) && !empty($res->value)) {
            $token = str_replace('token=', '', $res->value);
        }

        return $token;
    }

    /**
     * Run queue
     *
     * @return boolean
     */
    public function runQueue()
    {
        $this->pushUrl = JURI::root() . 'index.php?option=com_dropfiles&task=file.previewdownload';
        $this->juToken = $this->getJuToken();

        if (is_null($this->juToken) || !$this->juToken || $this->juToken === '') {
            return false;
        }

        // Check queue is running?
        $isRunning = $this->modelOption->get_option('_dropfiles_preview_generate_queue_running');

        if ($isRunning) {
            self::log('Queue is running, abort!');
            return false;
        } elseif (is_null($isRunning)) {
            $this->modelOption->update_option('_dropfiles_preview_generate_queue_running', false);
        }

        // Check option is turn on
        $params = JComponentHelper::getParams('com_dropfiles');

        if (!isset($params['auto_generate_preview'])) {
            self::log('Global option not found, abort!');
            return false;
        }

        $generatePreviewOption = $params->get('auto_generate_preview', 0);
        $isEnabled = (int) $generatePreviewOption === 1 ? true : false;

        if (!$isEnabled) {
            self::log('Generate preview is disable, abort!');
            return false;
        }

        $queueFilesInOption = $this->modelOption->get_option('_dropfiles_previewer_generate_queue_files');
        $queueFilesInOption = (array) json_decode($queueFilesInOption);

        if (!is_array($queueFilesInOption) || (is_array($queueFilesInOption) && empty($queueFilesInOption))) {
            $queueFilesInOption = $this->generatequeue();
        }

        if (!is_array($queueFilesInOption) || (is_array($queueFilesInOption) && empty($queueFilesInOption))) {
            self::log('No file to generate preview, abort!');
            return false;
        }

        // Mark queue on running
        $this->modelOption->update_option('_dropfiles_preview_generate_queue_running', true);

        foreach ($queueFilesInOption as $fileId => &$queue) {
            if (gettype($queue) !== 'array') {
                $queue = (array) $queue;
            }

            // Check ignore file
            if (isset($queue['ignore']) && intval($queue['ignore']) === 1 || !isset($queue['type'])) {
                continue;
            }

            // Server generate
            if ($queue['type'] === 'server') {
                // Send request if not send yet
                if (isset($queue['in_process']) && intval($queue['in_process']) === 0 && isset($queue['preview_generated']) && intval($queue['preview_generated']) === 0) {
                    self::log('Current queue before send request: ' . json_encode($queue));
                    $this->writeLog($queue['file_id'], 'sending_file');
                    // Send request
                    $requestId = $this->getRequestId($queue);
                    if ($requestId !== self::ABORT_REMOVE && $requestId !== self::RETRY && $requestId !== self::RETRY_ON_MAX_REQUEST) {
                        $queue['in_process'] = 1;
                        $queue['request_id'] = $requestId;
                        $queue['send_request_time'] = time();
                        self::log('Request send!' . json_encode($queue));
                        $this->writeLog($queue['file_id'], 'server_accepted');
                    } elseif ($requestId === self::RETRY_ON_MAX_REQUEST) {
                        // Stop queue running and update current state
                        $queue['in_process'] = 0;
                        $queue['date_added'] = time();
                        $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
                        $this->modelOption->update_option('_dropfiles_preview_generate_queue_running', false);
                        self::log('Max request reached! Abort current schedule!');
                        $this->writeLog($queue['file_id'], 'too_many_request');
                        break;
                    } elseif ($requestId === self::RETRY) {
                        $queue['in_process'] = 0;
                        $queue['retries'] += 1;
                        $queue['date_added'] = time();
                        self::log('Retry!' . json_encode($queue));
                    } elseif ($requestId === self::ABORT_REMOVE) {
                        // Remove queue on other error
                        self::log('Queue need remove!' . json_encode($queue));
                        $queue['in_process'] = 0;
                        $queue['preview_generated'] = 0;
                        $queue['ignore'] = 1;
                        self::log('File Ignore!' . json_encode($queue));
                        $this->writeLog($queue['file_id'], 'ignore');
                    } else {
                        $queue['in_process'] = 0;
                        $queue['retries'] += 1;
                        $queue['date_added'] = time();
                        self::log('Retry On Unknown!' . json_encode($queue));
                    }
                    $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
                }

                // Get data if not receive notification yet
                if (isset($queue['in_process']) && intval($queue['in_process']) === 1 && isset($queue['preview_generated']) && intval($queue['preview_generated']) === 0) {
                    if (isset($queue['request_id']) && intval($queue['request_id']) > 0) {
                        // Check request time after 5 minute if not receive notification yet
                        if (isset($queue['send_request_time']) && (time() - intval($queue['send_request_time'])) > self::WAIT_MINUTES * 60) {
                            $pages = $this->checkImages($queue);
                            if ($pages === self::GENERATED_FAILED) {
                                // Ignore current queue
                                $queue['in_process'] = 0;
                                $queue['preview_generated'] = 0;
                                $queue['ignore'] = 1;
                                $this->writeLog($queue['file_id'], 'too_many_fail_ignore');

                                $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
                            } elseif (is_array($pages) && !empty($pages)) {
                                self::log('Pages received! Pages: ' . count($pages));
                                $this->writeLog($queue['file_id'], 'Received ' . count($pages) . ' document pages, creating the preview/thumbnail...');
                                // Save first page only as preview image.
                                $maxPages = count($pages);
                                $numPage = 3;
                                if ($maxPages >= $numPage) {
                                    $max = $numPage;
                                } else {
                                    $max = $maxPages;
                                }
                                $urls = array();

                                for ($i = 0; $i <= $max - 1; $i++) {
                                    $urls[] = isset($pages[$i]) && isset($pages[$i]['public_url']) ? $pages[$i]['public_url'] : '';
                                }

                                list($savedFilePath, $thumbnailPath) = $this->savePreviewFile($queue['file_id'], $urls);

                                if (false !== $savedFilePath) {
                                    // Store generated file path to file meta
                                    $savedFilePath = str_replace(JPATH_ROOT, '', $savedFilePath);
                                    $this->modelOption->update_option('_dropfiles_preview_file_path_' . $queue['file_id'], addslashes($savedFilePath));
                                    // todo: save generated file path for cloud
                                    $queue['in_process'] = 0;
                                    $queue['preview_generated'] = 1;
                                    self::log('Image saved!' . json_encode($queue));
                                    $this->writeLog($queue['file_id'], 'preview_generated');
                                    $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
                                } else {
                                    $queue['in_process'] = 0;
                                    $queue['preview_generated'] = 0;
                                    $queue['ignore'] = 1;
                                    $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
                                }

                                if (false !== $thumbnailPath) {
                                    $thumbnailPath = str_replace(JPATH_ROOT, '', $thumbnailPath);
                                    $this->modelOption->update_option('_dropfiles_thumbnail_image_file_path_' . $queue['file_id'], addslashes($thumbnailPath));
                                    $this->modelOption->update_option('_dropfiles_thumbnail_image_file_contain_class_' . $queue['file_id'], true);
                                }
                            }
                        } else {
                            self::log('Time ranger: ' . (time() - intval($queue['send_request_time'])));
                        }
                    }
                }
            }

            // Image type generate
            if ($queue['type'] === 'image') {
                if ($queue['retries'] > 4) {
                    $queue['ignore'] = 1;
                    $queue['in_process'] = 0;
                    $queue['date_added'] = time();
                    $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
                    $this->writeLog($queue['file_id'], 'too_many_fail_ignore');
                    continue;
                }

                if (isset($queue['in_process']) && $queue['in_process'] === 1 && isset($queue['preview_generated']) && $queue['preview_generated'] === 0) {
                    $this->writeLog($queue['file_id'], 'server_accepted');
                    $filePath = $queue['file_path'];
                    $imageQuality = 80;
                    $imageSize = array('w' => 200, 'h' => 200);
                    // Generate thumbnail
                    $saveFilePath = $this->folderHelper->getThumbnailsPath();
                    $fileName = $saveFilePath . strval($fileId) . '_' . strval(md5($filePath)) . '.png';

                    if (!file_exists($saveFilePath)) {
                        $this->folderHelper->createSecureFolder($saveFilePath);
                    }

                    if (!file_exists($filePath)) {
                        $queue['ignore'] = 1;
                        $queue['in_process'] = 0;
                        $queue['retries'] = 5;
                        $queue['date_added'] = time();
                        $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
                        $this->writeLog($queue['file_id'], 'file_not_exists');
                        continue;
                    }

                    // Check the original image size with
                    $tooSmall = false;
                    $originSize = array();
                    list($originSize['width'], $originSize['height'], $originSize['type']) = getimagesize($filePath);

                    if ($originSize['width'] < $imageSize['w'] && $originSize['height'] <= $imageSize['h']) {
                        // Copy and save
                        if (file_exists($filePath) && intval($originSize['type']) === 3) {
                            JFile::copy($filePath, $fileName);
                        } elseif (file_exists($filePath)) {
                            $this->resize->convertPNG($filePath, $fileName);
                        }

                        $tooSmall = true;
                    } else {
                        $jImage = new JImage(JPath::clean($filePath));
                        $createdThumbnail = $jImage->createThumbs(array('200x200'));

                        if (!$createdThumbnail) {
                            $queue['in_process'] = 0;
                            $queue['retries'] += 1;
                            $queue['date_added'] = time();

                            $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
                            $this->writeLog($queue['file_id'], 'Error while generating thumbnail for image ' . $queue['file_id']);
                            continue;
                        }

                        $thumbnailPath = $createdThumbnail[0]->getPath();

                        if (is_file($thumbnailPath) && intval($originSize['type']) === 3) {
                            JFile::copy($thumbnailPath, $fileName);
                        } elseif (is_file($thumbnailPath)) {
                            $this->resize->convertPNG($thumbnailPath, $fileName);
                        } else {
                            $queue['in_process'] = 1;
                            $queue['retries'] += 1;
                            $queue['date_added'] = time();

                            $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
                            $this->writeLog($queue['file_id'], 'resize_error');
                            continue;
                        }
                    }

                    // Generate preview for this image
                    $previewPath = $this->folderHelper->getPreviewsPath();
                    $previewFileName = $previewPath . strval($fileId) . '_' . strval(md5($filePath)) . '.png';
                    if ($tooSmall) {
                        if (intval($originSize['type']) === 3) {
                            JFile::copy($filePath, $previewFileName);
                        } else {
                            $this->resize->convertPNG($filePath, $previewFileName);
                        }
                    } else {
                        $previewImageQuality = 90;
                        $previewImageSize = array('w' => 800, 'h' => 800);

                        $jPreviewImage = new JImage(JPath::clean($filePath));
                        $previewFile = $jPreviewImage->resize($previewImageSize['w'], $previewImageSize['h'], false);
                        $previewFilePath = $previewFile->getPath();

                        if (is_file($previewFilePath) && intval($originSize['type']) === 3) {
                            JFile::copy($previewFilePath, $previewFileName);
                        } elseif (is_file($previewFilePath)) {
                            $this->resize->convertPNG($previewFilePath, $previewFileName);
                        } else {
                            $queue['in_process'] = 1;
                            $queue['retries'] += 1;
                            $queue['date_added'] = time();

                            $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
                            $this->writeLog($queue['file_id'], 'preview resize_error');
                            continue;
                        }
                    }

                    $queue['in_process'] = 0;
                    $queue['preview_generated'] = 1;
                    $previewFileName = str_replace(JPATH_ROOT, '', $previewFileName);
                    $this->modelOption->update_option('_dropfiles_preview_file_path_' . $queue['file_id'], addslashes($previewFileName));
                    $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
                    $this->writeLog($queue['file_id'], 'preview_generated');

                    $newFilePath = str_replace(JPATH_ROOT, '', $fileName);
                    $this->modelOption->update_option('_dropfiles_thumbnail_image_file_path_' . $queue['file_id'], addslashes($newFilePath));
                    $this->modelOption->update_option('_dropfiles_thumbnail_image_file_contain_class_' . $queue['file_id'], true);
                } else {
                    self::log(json_encode($queue));
                }
            }
        } // End foreach

        // Clean directory
        $dir = $this->folderHelper->getPreviewsPath() . 'thumbs';
        $this->folderHelper->rrmdir($dir);

        // Save metas
        $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
        $this->modelOption->update_option('_dropfiles_preview_generate_queue_running', false);
    }

    /**
     * Restart the queue from beginning
     *
     * @return boolean
     */
    public function restartQueue()
    {
        // Delete all preview files generated
        $this->deleteAllPreviewFiles();
        // Delete all file meta
        $this->modelOption->delete_option_groups('_dropfiles_preview_file_path_', 'right');
        $this->modelOption->delete_option_groups('_dropfiles_thumbnail_image_file_path_', 'right');
        $this->modelOption->delete_option_groups('_dropfiles_thumbnail_image_file_contain_class_', 'right');
        // Delete all options?
        $this->modelOption->delete_option('_dropfiles_preview_generate_queue_running');
        $this->modelOption->delete_option('_dropfiles_previewer_generate_queue_files');

        // Run generate queue
        return $this->generateQueue();
    }

    /**
     * Delete all preview files on disk
     *
     * @return boolean
     */
    public function deleteAllPreviewFiles()
    {
        $filePath = $this->folderHelper->getPreviewsPath();
        $thumbnailsPath = $this->folderHelper->getThumbnailsPath();
        $filesPath = glob($filePath . '*_*.[pPjJ][nNpP][gG]');
        $thumbnailsFilesPath = glob($thumbnailsPath . '*_*.[pPjJ][nNpP][gG]');
        $filesPath = $filesPath + $thumbnailsFilesPath;
        foreach ($filesPath as $fileName) {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }

        return true;
    }

    /**
     * Add file to queue
     *
     * @param integer|string $fileId File id to add to queue
     *
     * @return void
     */
    public function addFileToQueue($fileId)
    {
        if (!$fileId) {
            return;
        }

        $modelFiles = JModelLegacy::getInstance('Files', 'dropfilesModel');
        $file = $modelFiles->getFile($fileId);
        if (is_null($file) || !$file) {
            self::log('File Id not found');
            return;
        }
        $queueFilesInOption = $this->modelOption->get_option('_dropfiles_previewer_generate_queue_files');
        $queueFilesInOption = (array) json_decode($queueFilesInOption);
        $sourceFilePath = $this->getSourceFilePath($fileId);
        if (!$sourceFilePath) {
            self::log('Source file not exists!');
            return;
        }
        if (isset($queueFilesInOption[$file->id])) {
            self::log('File already in queue');
            return;
        }
        $fileInfo = pathinfo($sourceFilePath);
        // Check allow extension
        if (!in_array(strtolower($fileInfo['extension']), $this->supportExtensions)) {
            return;
        }

        $type = 'server';
        $inProcess = 0;
        if (in_array(strtolower($fileInfo['extension']), $this->thumbnailSupportExtensions)) {
            $type = 'image';
            $inProcess = 1;
        }
        $queueFilesInOption[$file->id] = array(
            'date_added' => time(),
            'type' => $type,
            'file_id' => $file->id,
            'file_path' => $sourceFilePath,
            'file_ext' => $fileInfo['extension'],
            'file_last_updated' => $file->modified_time,
            'retries' => 0,
            'in_process' => $inProcess,
            'request_id' => 0,
            'preview_generated' => 0,
        );
        $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
    }

    /**
     * Generate queue from beginning
     *
     * @return array|void
     */
    public function generateQueue()
    {
        $this->pushUrl = JURI::root() . 'index.php?option=com_dropfiles&task=file.previewdownload';
        $this->juToken = $this->getJuToken();
        JModelLegacy::addIncludePath(JPATH_ROOT . '/administrator/components/com_dropfiles/models/', 'DropfilesModel');
        $modelFiles = JModelLegacy::getInstance('Files', 'dropfilesModel');
        // Get all files - local

        $files = $modelFiles->getAllPictures();

        if (!$files || (is_array($files) && empty($files))) {
            self::log('no files');
            return;
        }
        $queueFiles = array();
        $queueFilesInOption = $this->modelOption->get_option('_dropfiles_previewer_generate_queue_files');

        if (is_null($queueFilesInOption) || !$queueFilesInOption) {
            $queueFilesInOption = array();
        } else {
            $queueFilesInOption = (array) $queueFilesInOption;
        }

        // Remove not exists file in queue
        $filesId = array_map(function ($file) {
            return $file->id;
        }, $files);

        foreach ($queueFilesInOption as $fileId => $queueData) {
            if (!in_array($fileId, $filesId)) {
                unset($queueFilesInOption[$fileId]);
            }
        }

        $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);

        // Filter queues
        foreach ($files as $file) {
            $sourceFilePath = $this->getSourceFilePath($file->id);

            if (!$sourceFilePath) {
                unset($queueFilesInOption[$file->id]);
                continue;
            }

            // Check file already in queue
            if (isset($queueFilesInOption[$file->id]) && is_array($queueFilesInOption[$file->id])) {
                $fileInOption = $queueFilesInOption[$file->id];
                // Check file in process
                if (isset($fileInOption['in_process']) && intval($fileInOption['in_process']) === 1) {
                    continue;
                }
                // Check last updated
                if (isset($fileInOption['preview_generated']) && intval($fileInOption['preview_generated']) === 1) {
                    if ($file->modified_time === $fileInOption['file_last_updated']) {
                        continue;
                    }
                    // Check preview file exists
                    $filePreviewPath = $this->modelOption->get_option('_dropfiles_preview_file_path_' . $file->id);
                    if (isset($filePreviewPath) && file_exists($filePreviewPath)) {
                        continue;
                    }
                }
            }

            if (isset($queueFilesInOption['ignore']) && intval($queueFilesInOption['ignore']) === 1) {
                continue;
            }
            $fileInfo = pathinfo($sourceFilePath);

            // Check file extension
            if (!in_array(strtolower($fileInfo['extension']), $this->supportExtensions)) {
                continue;
            }
            $type = 'server';
            $inProcess = 0;
            if (in_array(strtolower($fileInfo['extension']), $this->thumbnailSupportExtensions)) {
                $type = 'image';
                $inProcess = 1;
            }

            $queueFile = array(
                'date_added' => time(),
                'type' => $type,
                'file_id' => $file->id,
                'file_path' => $sourceFilePath,
                'file_ext' => $fileInfo['extension'],
                'file_last_updated' => $file->modified_time,
                'retries' => 0,
                'in_process' => $inProcess,
                'request_id' => 0,
                'preview_generated' => 0,
            );
            $queueFiles[$file->id] = $queueFile;
        }

        $queueFiles = array_replace_recursive($queueFilesInOption, $queueFiles);
        $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFiles);

        return $queueFiles;
    }

    /**
     * Get source file path
     *
     * @param integer $fileId File id
     *
     * @return boolean|string
     */
    public function getSourceFilePath($fileId)
    {
        $file = $this->modelFiles->getFile($fileId);

        if (!$file || is_null($file) || empty($file)) {
            self::log('File not exist!');
            return false;
        }
        // Get category id
        $catId = isset($file->catid) ? (int) $file->catid : 0;

        if (!class_exists('DropfilesBase')) {
            JLoader::register('DropfilesBase', JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesBase.php');
        }

        $sourceFilePath = DropfilesBase::getFilesPath($catId) . $file->file;

        if (!file_exists($sourceFilePath)) {
            return false;
        }

        return $sourceFilePath;
    }

    /**
     * Log into a debug file
     *
     * @param string $msg Message
     *
     * @return void
     */
    public static function log($msg = '')
    {
        // Do nothing if not enabled
        if (!self::$debug) {
            return;
        }

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Log if enable debug
        error_log($msg);
    }

    /**
     * Write log
     *
     * @param object|mixed $fileId File id
     * @param string       $slug   Message slug
     *
     * @return void
     */
    public function writeLog($fileId, $slug = '')
    {
        $dateString = date('Y-m-d H:i:s');
        $fileAddress = '';
        $logFileName = $this->getLogFilePath();

        if (!empty($fileId)) {
            // Prepare file path
            $fileAddress = $this->getFileAddress($fileId);
        }

        if ($fileAddress) {
            $fileAddress = ' ' . $fileAddress;
        }

        // Prepare log message
        $message = sprintf('[%s]%s: %s', $dateString, $fileAddress, $this->getMessage($slug));

        // Append log to file
        $this->writeMessage($message, $logFileName);
    }

    /**
     * Get last log
     *
     * @param mixed $linesNumber Number of display rows
     *
     * @return boolean
     */
    public function getLatestLog($linesNumber = 20)
    {
        $logFilePath = $this->getLogFilePath();
        try {
            $logFile = new \SplFileObject($logFilePath, 'r');
            $logFile->seek(PHP_INT_MAX);
            $lastLine = $logFile->key();
            if ($lastLine <= $linesNumber) {
                $offset = 0;
            } else {
                $offset = $lastLine - $linesNumber;
            }
            $lines = new \LimitIterator($logFile, $offset, $lastLine);
            $linesArray = iterator_to_array($lines);

            return implode('', $linesArray);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get log of file path
     *
     * @return mixed
     */
    public function getLogFilePath()
    {
        $ds = DIRECTORY_SEPARATOR;
        $logFilePath = $this->folderHelper->getBasePath() . $ds . 'logs';
        $this->folderHelper->CreateSecureFolder($logFilePath);

        return $logFilePath . $ds. 'preview_generate_process.php';
    }

    /**
     * Get File Address
     *
     * @param integer $fileId File id
     *
     * @return boolean|string
     */
    public function getFileAddress($fileId)
    {
        $file = $this->modelFiles->getFile($fileId);

        if (!$file || gettype($file) === 'null') {
            return false;
        }
        if (!class_exists('DropfilesModelCategory')) {
            JLoader::register('DropfilesModelCategory', JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/category.php');
        }
        $categoryFilePath = '';
        $ds = '/';
        $fileExt = isset($file->ext) ? '.' . $file->ext : '';
        $catId = isset($file->catid) ? $file->catid : 0;
        $modelCategory = new DropfilesModelCategory();
        $category = $modelCategory->getCategory($catId);

        if (!$category || is_null($category)) {
            return $categoryFilePath;
        }

        if (isset($category->parent_id) && (int)$category->parent_id !== 0) {
            $parentCate = $modelCategory->getCategory($category->parent_id);
            if (!is_null($parentCate)) {
                $parentCategoryTitle = isset($parentCate->title) ? $parentCate->title : '';
                $categoryFilePath = $parentCategoryTitle . $ds;
            }
        }

        $categoryName = isset($category->title) ? $category->title : '';
        $categoryFilePath = $categoryFilePath . $categoryName . $ds . $file->title . $fileExt;

        return $categoryFilePath;
    }

    /**
     * Get the message by slug
     *
     * @param string $slug Message slug
     *
     * @return string
     */
    public function getMessage($slug = '')
    {
        $messages = array(
            'sending_file' => 'Sending file...',
            'server_accepted' => 'Accepted for preview generation, waiting for generation completion',
            'server_not_processed' => 'Not processed by server yet',
            'preview_generated' => 'Preview generated and saved',
            'too_many_request' => 'Too many files sent at the same time, waiting for other files we\'ve submitted to be completed',
            'too_many_fail_ignore' => 'Failed on generate preview file. Ignored!',
            'disabled' => 'Generate preview/thumbnail is disabled, please enable and save the settings first!',
            'ignore' => 'File ignore!',
            'file_not_exists' => 'File not exists on disk! Ignored!',
            'resize_error' => 'Resize file failed, retry later!',
        );

        if (!isset($messages[$slug])) {
            return $slug;
        }

        return (string) $messages[$slug];
    }

    /**
     * WriteMessage
     *
     * @param string $message  Message
     * @param string $fileName Destination file name
     *
     * @return void|mixed
     */
    public function writeMessage($message = '', $fileName = '') // phpcs:ignore PEAR.Functions.ValidDefaultValue.NotAtEnd -- it worked
    {
        $dieAdd = false;
        if (!file_exists($fileName)) {
            $dieAdd = true;
        }
        $hl = fopen($fileName, 'a');
        // Prepare file log if not exists
        if ($dieAdd) {
            fwrite($hl, '<?php die(); ?>'. "\n");
        }

        fwrite($hl, $message . "\n");

        fclose($hl);
    }

    /**
     * Get convert id from server
     *
     * @param array $queue Request params
     *                     ['file_id' => $id_file,
     *                     'file_path' => $file_dir . $newname,
     *                     'file_ext' => $file_ext]
     *
     * @return integer|boolean
     */
    public function getRequestId($queue)
    {
        if (!$this->juToken || $this->juToken === '') {
            self::log('JuToken missing!');
        }

        if (isset($queue['retries']) && $queue['retries'] > self::MAX_RETRIES) {
            self::log('File reach max retries: ' . $queue['file_id']);
            $this->writeLog($queue['file_id'], 'too_many_fail_ignore');
            return self::ABORT_REMOVE;
        }

        $filePath = isset($queue['file_path']) ? $queue['file_path'] : '';
        if (!file_exists($filePath)) {
            self::log('File path not exists: ' . $filePath);
            return self::ABORT_REMOVE;
        }

        $fileExtension = isset($queue['file_ext']) ? $queue['file_ext'] : '';

        if (!in_array(strtolower($fileExtension), $this->supportExtensions)) {
            self::log('Extension not support: ' . $filePath . ' File ext: ' . $queue['file_ext']);
            return self::ABORT_REMOVE;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'file'=> new CURLFILE($filePath), // phpcs:ignore PHPCompatibility.Classes.NewClasses.curlfileFound -- It's Ok, we use php >= 5.6
                'notification' => $this->pushUrl // A push url to be called when the optimization is finished, the submitted content is the same than you can retrieve with the get rest method
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $this->juToken, // We use the Juupdater token as api key
            ),
        ));

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }
        curl_close($curl);
        if (isset($error_msg)) {
            self::log($error_msg);
            return self::RETRY;
        }

        self::log($response);

        if ($response !== '' && intval($info['http_code']) === 200) {
            $response = json_decode($response, true);
            return isset($response['id']) ? $response['id'] : self::RETRY;
        } elseif ($response !== '' && intval($info['http_code']) === 429) { // Too many request
            self::log($response);
            return self::RETRY_ON_MAX_REQUEST; // We should try again late
        }

        // For any other error, bypass current file and remove current queue.
        return self::ABORT_REMOVE;
    }

    /**
     * Check API to get image generated
     *
     * @param array $queue Queue
     *
     * @return boolean
     */
    public function checkImages($queue)
    {
        if (!is_array($queue) || (isset($queue['request_id']) && $queue['request_id'] === '')) {
            return false;
        }
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->endpoint . '/' . $queue['request_id'], // Replace the file id retrieve from upload
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $this->juToken, // We use the Juupdater token as api key
            ),
        ));

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }
        curl_close($curl);
        if (isset($error_msg)) {
            self::log($error_msg);
            return false;
        }
        self::log($response);

        if ($response !== '' && intval($info['http_code']) === 200) {
            $datas = json_decode($response, true);

            if (isset($datas['status'])) {
                switch ($datas['status']) {
                    case 'success':
                        return isset($datas['pages']) ? $datas['pages'] : false;
                    case 'pending':
                        $this->writeLog($queue['file_id'], 'server_not_processed');
                        return false;
                    case 'failed':
                        return self::GENERATED_FAILED;
                }
            }
        }

        return false;
    }

    /**
     * Download preview file from API
     *
     * @return void
     */
    public function previewDownload()
    {
        $status = 200;
        header('X-PHP-Response-Code: ' . $status);
        header('Status: ' . $status);
        $datas = file_get_contents('php://input');
        self::log($datas);

        if ($datas) {
            $datas = json_decode($datas, true);
        }

        // Get file id from queue by request id
        $queues = $this->modelOption->get_option('_dropfiles_previewer_generate_queue_files');
        $queues = (array) json_decode($queues);

        if (empty($queues)) {
            self::log('Empty queue');
            return;
        }

        $requestFileId = 0;
        $currentQueue = array();

        foreach ($queues as $fileId => $queue) {
            if (gettype($queue) !== 'array') {
                $queue = (array) $queue;
            }
            if (isset($datas['id']) && $queue['request_id'] === $datas['id']) {
                $requestFileId = $fileId;
                $currentQueue = $queue;
                break;
            }
        }

        if ($requestFileId === 0) {
            return;
        }

        if (isset($datas['id']) && isset($datas['status']) && $datas['status'] === 'success') {
            $pages = $datas['pages'];

            if (!is_array($pages) && count($pages) === 0) {
                // Empty page?
                // Ignore current queue
                $currentQueue['in_process'] = 0;
                $currentQueue['preview_generated'] = 0;
                $currentQueue['ignore'] = 1;
                $queues[$requestFileId] = $currentQueue;
                $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queues);
                self::log('Pages received but empty. Ignored!' . json_encode($currentQueue));
                return;
            }

            $maxPages = count($pages);
            $numPage = 3;

            if ($maxPages >= $numPage) {
                $max = $numPage;
            } else {
                $max = $maxPages;
            }

            $urls = array();

            for ($i = 0; $i <= $max - 1; $i++) {
                $urls[] = isset($pages[$i]) && isset($pages[$i]['public_url']) ? $pages[$i]['public_url'] : '';
            }

            list($savedFilePath, $thumbnailPath) = $this->savePreviewFile($requestFileId, $urls);

            if (false !== $savedFilePath) {
                // Store generated file path to post meta
                $savedFilePath = str_replace(JPATH_ROOT, '', $savedFilePath);
                $this->modelOption->update_option('_dropfiles_preview_file_path_' . $requestFileId, addslashes($savedFilePath));
                $currentQueue['in_process'] = 0;
                $currentQueue['preview_generated'] = 1;
                $queues[$requestFileId] = $currentQueue;
                $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queues);
                self::log('Image saved!' . json_encode($currentQueue));
                $this->writeLog($currentQueue['file_id'], 'preview_generated');
            }

            if (false !== $thumbnailPath) {
                $thumbnailPath = str_replace(JPATH_ROOT, '', $thumbnailPath);
                $this->modelOption->update_option('_dropfiles_thumbnail_image_file_path_' . $currentQueue['file_id'], addslashes($thumbnailPath));
                $this->modelOption->update_option('_dropfiles_thumbnail_image_file_contain_class_' . $currentQueue['file_id'], true);
            }
        } elseif (isset($datas['id']) && isset($datas['status']) && $datas['status'] === 'failed') {
            // Ignore current queue
            $currentQueue['in_process'] = 0;
            $currentQueue['preview_generated'] = 0;
            $currentQueue['ignore'] = 1;
            $queues[$requestFileId] = $currentQueue;
            $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queues);
            self::log('Failed on generate preview file. Ignored!' . json_encode($currentQueue));
            $this->writeLog($requestFileId, 'too_many_fail_ignore');
        }
    }

    /**
     * Save preview file generated
     *
     * @param string|integer $fileId File id
     * @param string|array   $urls   URL of generated document
     * @param string         $prefix Prefix for file name. Useful to fast select files
     * @param string         $suffix Suffix for file name. Useful to fast select files
     *
     * @return boolean|string
     */
    public function savePreviewFile($fileId, $urls, $prefix = '', $suffix = '')
    {
        if (empty($fileId)) {
            return false;
        }
        $thumbnailPath = false;
        $filePath = $this->folderHelper->getPreviewsPath();
        $fileName = $filePath . $prefix . strval($fileId) . '_' . strval(uniqid()) . $suffix . '.png';

        if (is_array($urls)) {
            $allTempFiles = array();
            // Download all images
            foreach ($urls as $url) {
                // Try to use native wordpress download function
                if (function_exists('download_url')) {
                    $downloadedFile = download_url($url);
                    $allTempFiles[] = $downloadedFile;
                } else {
                    $tempFile = tempnam(sys_get_temp_dir(), 'dropfiles_');
                    $response = file_get_contents($url);
                    if ($response) {
                        $downloadedFile = file_put_contents($tempFile, $response);
                    }

                    if (false !== $downloadedFile) {
                        $allTempFiles[] = $tempFile;
                    }
                }
            }

            // Merge files into one or save the first preview file only
            $this->mergeImageVertical($allTempFiles, $fileName);
        } elseif (gettype($urls) === 'string' && $urls !== '') {
            // Single url
            if (function_exists('download_url')) {
                $downloadedFile = download_url($urls);
                if ($downloadedFile) {
                    rename($downloadedFile, $fileName);
                }
            } else {
                $response = file_get_contents($urls);
                if ($response) {
                    file_put_contents($fileName, $response);
                }
            }
        }

        if (file_exists($fileName)) {
            $thumbnailPath = str_replace(JPATH_ROOT, '', $this->resize->resizeThumbnail($fileName, $fileId));
            $fileName = str_replace(JPATH_ROOT, '', $fileName);

            return array(addslashes($fileName), $thumbnailPath);
        }

        return false;
    }

    /**
     * Merge images
     *
     * @param array  $images          Source image path
     * @param string $destinationPath Save path
     *
     * @return boolean
     */
    public function mergeImageVertical($images, $destinationPath)
    {
        $imgs = array();
        $success = false;
        $error = '';

        // ImageMagick extension
        if ($success === false && extension_loaded('imagick')) {
            try {
                $img = new Imagick;
                $lastKey = key(array_slice($images, -1, null, true));
                foreach ($images as $key => $image) {
                    $img->readImage($image);
                    if ($key !== $lastKey) {
                        // Generate new image for separator
                        $tempImage = new Imagick($image);
                        $geo = $tempImage->getImageGeometry();
                        $sizex = $geo['width'];
                        $img->newImage($sizex, 5, 'none');  // Add separator
                        $tempImage->destroy();
                    }
                }
                $img->resetIterator();
                $combined = $img->appendImages(true);

                $combined->setImageFormat('png');
                if ($combined->writeImage($destinationPath)) {
                    $success = true;
                }
            } catch (Exception $e) {
                // Imagemagick fails, try with GD
                $error = 'Imagick library error: ' . $e->getMessage();
            }
        }
        // GD extension
        if ($success === false && function_exists('imagecreatefrompng')) {
            try {
                foreach ($images as $image) {
                    $img = array();
                    list($img['width'], $img['height'], $img['type']) = getimagesize($image);
                    if ($img['type'] === 3) { // PNG
                        $img['instance'] = imagecreatefrompng($image);
                    } elseif ($img['type'] === 2) { // JPEG
                        $img['instance'] = imagecreatefromjpeg($image);
                    } else {
                        continue;
                    }

                    $imgs[] = $img;
                }

                // Compute new width/height
                $new_width = 0;
                $new_height = 0;
                foreach ($imgs as $img) {
                    $new_width = ($img['width'] > $new_width) ? $img['width'] : $new_width;
                    $new_height += $img['height'];
                }

                // Create new image and merge
                $new = imagecreatetruecolor($new_width, $new_height);
                imagesavealpha($new, true);

                $trans_colour = imagecolorallocatealpha($new, 0, 0, 0, 127);
                imagefill($new, 0, 0, $trans_colour);
                $last_top_height = 0;
                foreach ($imgs as $key => $img) {
                    if ($last_top_height > 0) {
                        $last_top_height += 5; // Add separator
                    }
                    imagecopy($new, $img['instance'], 0, $last_top_height, 0, 0, $img['width'], $img['height']);
                    $last_top_height += $img['height'];
                }
                // Save to file
                imagepng($new, $destinationPath, 9);

                $success = true;
            } catch (Exception $e) {
                // GD fails
                $error = 'GD library error: ' . $e->getMessage();
            }
        }
        // Do nothing, return false to save the first image only
        if ($success === false) {
            self::log($error);
            // Save the first image
            reset($images);
            if (rename(current($images), $destinationPath)) {
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Remove file from queue
     *
     * @param integer|string $fileId      File id
     * @param boolean        $deleteImage Permanently delete file on disk
     *
     * @return void
     */
    public function removeFileFromQueue($fileId, $deleteImage = true)
    {
        if (!$fileId) {
            return;
        }
        $queueFilesInOption = $this->modelOption->get_option('_dropfiles_previewer_generate_queue_files');
        $queueFilesInOption = (array) json_decode($queueFilesInOption);

        if (isset($queueFilesInOption[$fileId])) {
            // Remove saved file and file info
            if ($deleteImage) {
                $this->modelOption->delete_option('_dropfiles_preview_file_path_' . $fileId);
                $this->deletePreviewFiles($fileId);
                $this->deleteThumbnailFiles($fileId);
                $this->deleteLogFile();
            }
            unset($queueFilesInOption[$fileId]);
            $this->modelOption->update_option('_dropfiles_previewer_generate_queue_files', $queueFilesInOption);
        }
    }

    /**
     * Delete preview files generated
     *
     * @param string|integer $fileId File id
     *
     * @return boolean
     */
    public function deletePreviewFiles($fileId)
    {
        if (empty($fileId)) {
            return false;
        }
        $filePath = $this->folderHelper->getPreviewsPath();
        $filesPath = glob($filePath . strval($fileId) . '_*.png');
        foreach ($filesPath as $fileName) {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }

        return true;
    }

    /**
     * Delete thumbnail files generated
     *
     * @param string|integer $fileId File Id
     *
     * @return boolean
     */
    public function deleteThumbnailFiles($fileId)
    {
        if (empty($fileId)) {
            return false;
        }
        $filePath = $this->folderHelper->getThumbnailsPath();
        $filesPath = glob($filePath . strval($fileId) . '_*.png');
        foreach ($filesPath as $fileName) {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }

        return true;
    }

    /**
     * Delete log file on reset
     *
     * @return boolean
     */
    public function deleteLogFile()
    {
        $logPath = $this->getLogFilePath();
        if (file_exists($logPath)) {
            unlink($logPath);
        }

        return true;
    }
}
