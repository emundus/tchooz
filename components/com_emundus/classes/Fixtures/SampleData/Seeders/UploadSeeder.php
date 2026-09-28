<?php
/**
 * @package     Tchooz\Fixtures\SampleData\Seeders
 * @subpackage
 *
 * @copyright   (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Fixtures\SampleData\Seeders;

use Joomla\Database\DatabaseInterface;

\defined('_JEXEC') or die;

/**
 * Copies a sample PDF and registers it as an upload for a subset of dossiers.
 */
final class UploadSeeder
{
	private const SAMPLE_PDF     = JPATH_ROOT . '/plugins/sampledata/emundus/src/samples/pdf_emundus.pdf';
	private const FILES_BASE_DIR = JPATH_ROOT . '/images/emundus/files/';
	private const FILENAME       = 'pdf_emundus.pdf';

	private DatabaseInterface $db;

	public function __construct(DatabaseInterface $db)
	{
		$this->db = $db;
	}

	/**
	 * @param   array<string, int>  $fnumToApplicant  fnum => applicant_id.
	 * @param   int                 $attachmentId     Setup attachment id to bind the upload to.
	 * @param   int                 $limit            Max dossiers to attach a file to (0 = all).
	 *
	 * @return  int  Number of uploads created.
	 */
	public function seed(array $fnumToApplicant, int $attachmentId, int $limit = 0): int
	{
		if (empty($fnumToApplicant) || !is_file(self::SAMPLE_PDF))
		{
			return 0;
		}

		if ($limit > 0 && count($fnumToApplicant) > $limit)
		{
			$fnumToApplicant = array_slice($fnumToApplicant, 0, $limit, true);
		}

		$now     = date('Y-m-d H:i:s');
		$created = 0;

		foreach ($fnumToApplicant as $fnum => $applicantId)
		{
			$destinationDir = self::FILES_BASE_DIR . $applicantId;
			if (!is_dir($destinationDir) && !mkdir($destinationDir, 0755, true) && !is_dir($destinationDir))
			{
				continue;
			}

			copy(self::SAMPLE_PDF, $destinationDir . '/' . self::FILENAME);

			$upload = (object) [
				'timedate'      => $now,
				'user_id'       => (int) $applicantId,
				'fnum'          => $fnum,
				'attachment_id' => $attachmentId,
				'filename'      => self::FILENAME,
			];

			if ($this->db->insertObject('#__emundus_uploads', $upload))
			{
				$created++;
			}
		}

		return $created;
	}
}
