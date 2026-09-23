<?php

namespace Tchooz\Synchronizers;

use Tchooz\Entities\Upload\UploadEntity;

/**
 * Optional transport capability: upload a file to the remote API and return its remote URL.
 *
 * Only synchronizers whose remote API accepts file uploads implement this. The executor replaces
 * UploadEntity values in the mapped data with the returned URL only when the transport implements
 * this capability.
 */
interface FileUploadInterface
{
	public function uploadFile(UploadEntity $upload, int $synchronizerId): ?string;
}
