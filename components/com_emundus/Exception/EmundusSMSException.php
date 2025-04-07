<?php

class EmundusSMSException extends Exception
{
	/**
	 * @var array Informations supplémentaires sur l'erreur
	 */
	protected $context = [];

	/**
	 * @var string Type d'erreur (validation, encodage, envoi, etc.)
	 */
	protected $errorType;

	/**
	 * Constructeur de l'exception SMS
	 *
	 * @param   string          $message    Message d'erreur
	 * @param   string          $errorType  Type d'erreur
	 * @param   array           $context    Contexte supplémentaire
	 * @param   int             $code       Code d'erreur
	 * @param   Throwable|null  $previous   Exception précédente
	 */
	public function __construct(
		string    $message,
		string    $errorType = 'generic',
		array     $context = [],
		int       $code = 0,
		Throwable $previous = null
	)
	{
		parent::__construct($message, $code, $previous);
		$this->errorType = $errorType;
		$this->context   = $context;
	}

	/**
	 * Récupère le type d'erreur
	 *
	 * @return string
	 */
	public function getErrorType(): string
	{
		return $this->errorType;
	}

	/**
	 * Récupère le contexte de l'erreur
	 *
	 * @return array
	 */
	public function getContext(): array
	{
		return $this->context;
	}

	/**
	 * Ajoute des informations au contexte
	 *
	 * @param   array  $context
	 *
	 * @return self
	 */
	public function addContext(array $context): self
	{
		$this->context = array_merge($this->context, $context);

		return $this;
	}

	/**
	 * Formate un message détaillé avec le contexte
	 *
	 * @return string
	 */
	public function getDetailedMessage(): string
	{
		$message = "[{$this->errorType}] {$this->message}";

		if (!empty($this->context))
		{
			$message .= "\nContexte:\n" . print_r($this->context, true);
		}

		if ($this->getPrevious() !== null)
		{
			$message .= "\nException précédente: " . $this->getPrevious()->getMessage();
		}

		return $message;
	}

	public static function invalidEncoding(string $message, array $invalidChars, array $compatibility, string $sanitized_message): self
	{
		return new self(
			$message,
			'encoding',
			['invalid_characters' => $invalidChars, 'compatibility' => $compatibility, 'sanitized_message' => $sanitized_message]
		);
	}
}