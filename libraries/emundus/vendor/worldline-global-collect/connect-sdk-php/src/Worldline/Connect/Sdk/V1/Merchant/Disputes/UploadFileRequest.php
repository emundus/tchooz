<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Merchant\Disputes;

use Worldline\Connect\Sdk\Communication\MultipartDataObject;
use Worldline\Connect\Sdk\Communication\MultipartFormDataObject;
use Worldline\Connect\Sdk\Domain\UploadableFile;

/**
 * Multipart/form-data parameters for Upload File
 *
 * @package Worldline\Connect\Sdk\V1\Merchant\Disputes
 * @link    https://apireference.connect.worldline-solutions.com/fileserviceapi/v1/en_US/php/disputes/uploadFile.html Upload File
 */
class UploadFileRequest extends MultipartDataObject
{
    /**
     * @var UploadableFile|null
     */
    public ?UploadableFile $file;

    public function toMultipartFormDataObject(): MultipartFormDataObject
    {
        $result = new MultipartFormDataObject();
        if (!is_null($this->file)) {
            $result->addFile("file", $this->file);
        }
        return $result;
    }
}
