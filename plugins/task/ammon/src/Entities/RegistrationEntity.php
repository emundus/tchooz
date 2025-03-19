<?php

namespace Joomla\Plugin\Task\Ammon\Entities;

class RegistrationEntity {
	public string $Date;
	public string $SourceCode = 'WEB';
	public int $ParticipantsNumber = 1;
	public int $OrderCreation = -1;
	public $OrderPricingTypeCode = "STANDARD";
	public $OrderPricingDate = null;

	/**
	 * @param   string  $TraineeExternalReference Emundus internal identifier
	 * @param   string  $ExternalReference         Ammon internal identifier
	 * @param   int     $CourseId
	 * @param   int     $StateCode
	 * @param   string  $HistoryLog1Title
	 * @param   string  $HistoryLog1Content
	 * @param   string  $CompanyExternalReference
	 * @param   string  $ContactExternalReference
	 */
	public function __construct(
		public string $TraineeExternalReference,
		public string $ExternalReference,
		public int $CourseId,
		public int $StateCode = 4,
		public string $HistoryLog1Title = '',
		public string $HistoryLog1Content = '',
		public string $CompanyExternalReference = '',
		public string $ContactExternalReference = '',
	) {
		$utc_date = new \DateTime('now', new \DateTimeZone('UTC'));
		$this->Date = $utc_date->format('Y-m-d\TH:i:s\Z');
		$this->OrderPricingDate = $utc_date->format('Y-m-d\TH:i:s\Z');
	}
}