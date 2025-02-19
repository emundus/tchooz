<?php

namespace Joomla\Plugin\Emundus\Ammon\Entities;

class RegistrationEntity {
	public string $Date;
	public string $SourceCode = 'WEB';
	public int $ParticipantsNumber = 1;
	public int $OrderCreation = 1;
	public $OrderPricingTypeCode = null;
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
		$this->Date = date('Y-m-d\TH:i:s\Z');
	}
}