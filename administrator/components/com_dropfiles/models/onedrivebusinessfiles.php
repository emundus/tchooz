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


/**
 * Class DropfilesModelOneDriveBusinessfiles
 */
class DropfilesModelOneDriveBusinessfiles extends JModelLegacy
{
    /**
     * Returns a Table object, always creating it.
     *
     * @param string $type   The table type to instantiate
     * @param string $prefix A prefix for the table class name. Optional.
     * @param array  $config Configuration array for model. Optional.
     *
     * @return JTable    A database object
     * @since  version
     */
    public function getTable($type = 'OnedriveBusiness', $prefix = 'DropfilesTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Save file info to database
     *
     * @param array $data Data
     *
     * @return boolean
     * @throws Exception Throw when application can not start
     * @since  version
     */
    public function save($data)
    {
        $table = $this->getTable();
        $key   = $table->getKeyName();
        $pk    = (!empty($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
        $isNew = true;

        // Allow an exception to be thrown.
        try {
            // Load the row if saving an existing record.
            if ($pk > 0) {
                $table->load($pk);
                $isNew = false;
            }

            // Bind the data.
            if (!$table->bind($data)) {
                $this->setError($table->getError());

                return false;
            }

            // Check the data.
            if (!$table->check()) {
                $this->setError($table->getError());

                return false;
            }


            // Store the data.
            if (!$table->store()) {
                $this->setError($table->getError());

                return false;
            }
        } catch (Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        if (isset($table->$key)) {
            $this->setState($this->getName() . '.id', $table->$key);
        }

        $this->setState($this->getName() . '.new', $isNew);

        return true;
    }
    /**
     * Methode to retrieve file information
     *
     * @param string $id_file Onedrive business file id
     *
     * @return object file, false if an error occurs
     * @since  version
     */
    public function getFile($id_file)
    {
        $dbo = $this->getDbo();
        $query = 'SELECT * FROM #__dropfiles_onedrive_business_files WHERE file_id LIKE BINARY ' . $dbo->quote($id_file);
        $dbo->setQuery($query);
        if (!$dbo->execute()) {
            return false;
        }
        return $dbo->loadObject();
    }
    /**
     * Get all item in category
     *
     * @param string $catid    Onedrive Business category id
     * @param string $ordering Ordering
     * @param string $dir      Direction
     *
     * @return boolean
     * @since  version
     */
    public function getItems($catid, $ordering = 'ordering', $dir = 'asc')
    {
        $dbo = $this->getDbo();
        $query = 'SELECT file_id as id, ext,title,description,size,state,created_time,modified_time,version,hits ';
        $query .= ' FROM #__dropfiles_onedrive_business_files WHERE catid=' . $dbo->quote($catid);
        $query .= ' Order By ' . $dbo->escape($ordering . ' ' . $dir);
        $dbo->setQuery($query);
        if (!$dbo->execute()) {
            return false;
        }
        return $dbo->loadObjectList();
    }

    /**
     * Create new file base on google file object
     *
     * @param \Microsoft\Graph\Model\DriveItem $file   Onedrive Business file object
     * @param string                           $parent Parent id
     *
     * @return boolean
     * @throws Exception Throw when application can not start
     * @since  5.9.0
     */
    public function createFile($file, $parent)
    {
        $data = $this->generateFileData($file, $parent);

        return $this->save($data);
    }
    /**
     * Generate file data
     *
     * @param \Microsoft\Graph\Model\DriveItem $file   Onedrive Business File object
     * @param string                           $parent Parent id
     *
     * @return array|boolean
     * @since  5.9.0
     */
    private function generateFileData($file, $parent)
    {
        $data                  = array();
        $data['id']            = 0;
        $data['file_id']       = $file->getId();
        $data['ext']           = DropfilesCloudHelper::getExt($file->getName());
        $data['size']          = $file->getSize();
        $data['title']         = $file->getName() ? DropfilesCloudHelper::stripExt($file->getName()) : $file->getName();
        $data['description']   = $file->getDescription() ? $file->getDescription() : '';
        $data['catid']         = $parent;
        $data['created_time']  = $file->getCreatedDateTime()->format('Y-m-d H:i:s');
        $data['modified_time'] = $file->getLastModifiedDateTime()->format('Y-m-d H:i:s');
        // todo: Made this working
        $data['path']     = '';
        $data['file_tags']     = '';
        $data['version']       = '';
        $data['hits']          = 0;

        return $data;
    }

    /**
     * Move file base on google file object
     *
     * @param \Microsoft\Graph\Model\DriveItem $file   Onedrive Business File object
     * @param string                           $parent Parent id
     *
     * @return boolean
     *
     * @throws Exception Throw when application can not start
     *
     * @since 5.9.0
     */
    public function moveFile($file, $parent)
    {
        $localFile = $this->getFile($file->getId());
        $localFile->catid = $parent;
        $dbo = $this->getDbo();

        return $dbo->updateObject('#__dropfiles_onedrive_business_files', $localFile, 'id');
    }

    /**
     * Update file
     *
     * @param \Microsoft\Graph\Model\DriveItem $file   Onedrive Business File object
     * @param string                           $parent Parent id
     *
     * @return boolean
     *
     * @since 5.9.0
     */
    public function updateFile($file, $parent)
    {
        $data = $this->generateFileData($file, $parent);

        $localFile = $this->getFile($file->getId());

        $diff = array_diff($data, (array) $localFile);

        if (!empty($diff)) {
            foreach ($diff as $key => $value) {
                $localFile->{$key} = $value;
            }
        }

        $dbo = $this->getDbo();
        return $dbo->updateObject('#__dropfiles_onedrive_business_files', $localFile, 'id');
    }

    /**
     * Delete file onedrive business
     *
     * @param string $id_file Onedrive Business file id
     *
     * @return void
     * @since  5.9.0
     */
    public function deleteFile($id_file)
    {
        $dbo = $this->getDbo();
        $query = 'DELETE From #__dropfiles_onedrive_business_files WHERE file_id LIKE BINARY ' . $dbo->quote($id_file);
        $dbo->setQuery($query);
        $dbo->execute();
    }

    /**
     * Save file info
     *
     * @param array   $data       File info
     * @param integer $idCategory Term id
     *
     * @return mixed
     *
     * @since xxx
     */
    public function saveOneDriveBusinessFileInfo($data, $idCategory)
    {
        if (DropfilesOneDriveBusiness::saveOnDriveBusinessFileInfos($data)) {
            return true;
        }

        return false;
    }
}
