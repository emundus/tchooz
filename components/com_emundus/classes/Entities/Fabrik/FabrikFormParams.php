<?php
/**
 * @package     Tchooz\Entities\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Fabrik;

class FabrikFormParams
{
	private string $outro = '';

	private bool $copyButton = false;

	private string $copyButtonLabel = 'COPY';

	private string $copyButtonClass = '';

	private string $copyIcon = '';

	private string $copyIconLocation = 'before';

	private bool $resetButton = false;

	private string $resetButtonLabel = 'RESET';

	private string $resetButtonClass = '';

	private string $resetIcon = '';

	private string $resetIconLocation = 'before';

	private bool $applyButton = false;

	private string $applyButtonLabel = 'APPLY';

	private string $applyButtonClass = '';

	private string $applyIcon = '';

	private string $applyIconLocation = 'before';

	private bool $gobackButton = false;

	private string $gobackButtonLabel = 'GO_BACK';

	private string $gobackButtonClass = 'goback-btn';

	private string $gobackIcon = '';

	private string $gobackIconLocation = 'before';

	private bool $submitButton = true;

	private string $submitButtonLabel = 'SAVE_CONTINUE';

	private string $saveButtonClass = 'btn-primary save-btn sauvegarder';

	private string $saveIcon = '';

	private string $saveIconLocation = 'before';

	private bool $submitOnEnter = false;

	private bool $deleteButton = false;

	private string $deleteButtonLabel = 'DELETE';

	private string $deleteButtonClass = 'btn-danger';

	private string $deleteIcon = '';

	private string $deleteIconLocation = 'before';

	private bool $ajaxValidations = false;

	private bool $ajaxValidationsToggleSubmit = false;

	private string $submitSuccessMessage = '';

	private bool $suppressMessages = false;

	private bool $showLoaderOnSubmit = false;

	private bool $spoofCheck = true;

	private bool $multipageSave = false;

	private string $note = '';

	private int $labelsAbove = 1;

	private int $labelsAboveDetails = 1;

	private string $pdfTemplate = '';

	private string $pdfOrientation = 'portrait';

	private string $pdfSize = 'letter';

	private bool $pdfIncludeBootstrap = true;

	private string $adminFormTemplate = '';

	private string $adminDetailsTemplate = '';

	private bool $showTitle = true;

	private string $print = '';

	private string $email = '';

	private string $pdf = '';

	private bool $showReferringTableReleatedData = false;

	private string $tipLocation = 'above';

	private int $processJplugins = 2;

	private array $plugins = [];

	private array $pluginState = [];

	private array $pluginLocations = [];

	private array $pluginEvents = [];

	private array $pluginDescription = [];

	public function __construct()
	{
	}

	public function getOutro(): string
	{
		return $this->outro;
	}

	public function setOutro(string $outro): void
	{
		$this->outro = $outro;
	}

	public function isCopyButton(): bool
	{
		return $this->copyButton;
	}

	public function setCopyButton(bool $copyButton): void
	{
		$this->copyButton = $copyButton;
	}

	public function getCopyButtonLabel(): string
	{
		return $this->copyButtonLabel;
	}

	public function setCopyButtonLabel(string $copyButtonLabel): void
	{
		$this->copyButtonLabel = $copyButtonLabel;
	}

	public function getCopyButtonClass(): string
	{
		return $this->copyButtonClass;
	}

	public function setCopyButtonClass(string $copyButtonClass): void
	{
		$this->copyButtonClass = $copyButtonClass;
	}

	public function getCopyIcon(): string
	{
		return $this->copyIcon;
	}

	public function setCopyIcon(string $copyIcon): void
	{
		$this->copyIcon = $copyIcon;
	}

	public function getCopyIconLocation(): string
	{
		return $this->copyIconLocation;
	}

	public function setCopyIconLocation(string $copyIconLocation): void
	{
		$this->copyIconLocation = $copyIconLocation;
	}

	public function isResetButton(): bool
	{
		return $this->resetButton;
	}

	public function setResetButton(bool $resetButton): void
	{
		$this->resetButton = $resetButton;
	}

	public function getResetButtonLabel(): string
	{
		return $this->resetButtonLabel;
	}

	public function setResetButtonLabel(string $resetButtonLabel): void
	{
		$this->resetButtonLabel = $resetButtonLabel;
	}

	public function getResetButtonClass(): string
	{
		return $this->resetButtonClass;
	}

	public function setResetButtonClass(string $resetButtonClass): void
	{
		$this->resetButtonClass = $resetButtonClass;
	}

	public function getResetIcon(): string
	{
		return $this->resetIcon;
	}

	public function setResetIcon(string $resetIcon): void
	{
		$this->resetIcon = $resetIcon;
	}

	public function getResetIconLocation(): string
	{
		return $this->resetIconLocation;
	}

	public function setResetIconLocation(string $resetIconLocation): void
	{
		$this->resetIconLocation = $resetIconLocation;
	}

	public function isApplyButton(): bool
	{
		return $this->applyButton;
	}

	public function setApplyButton(bool $applyButton): void
	{
		$this->applyButton = $applyButton;
	}

	public function getApplyButtonLabel(): string
	{
		return $this->applyButtonLabel;
	}

	public function setApplyButtonLabel(string $applyButtonLabel): void
	{
		$this->applyButtonLabel = $applyButtonLabel;
	}

	public function getApplyButtonClass(): string
	{
		return $this->applyButtonClass;
	}

	public function setApplyButtonClass(string $applyButtonClass): void
	{
		$this->applyButtonClass = $applyButtonClass;
	}

	public function getApplyIcon(): string
	{
		return $this->applyIcon;
	}

	public function setApplyIcon(string $applyIcon): void
	{
		$this->applyIcon = $applyIcon;
	}

	public function getApplyIconLocation(): string
	{
		return $this->applyIconLocation;
	}

	public function setApplyIconLocation(string $applyIconLocation): void
	{
		$this->applyIconLocation = $applyIconLocation;
	}

	public function isGobackButton(): bool
	{
		return $this->gobackButton;
	}

	public function setGobackButton(bool $gobackButton): void
	{
		$this->gobackButton = $gobackButton;
	}

	public function getGobackButtonLabel(): string
	{
		return $this->gobackButtonLabel;
	}

	public function setGobackButtonLabel(string $gobackButtonLabel): void
	{
		$this->gobackButtonLabel = $gobackButtonLabel;
	}

	public function getGobackButtonClass(): string
	{
		return $this->gobackButtonClass;
	}

	public function setGobackButtonClass(string $gobackButtonClass): void
	{
		$this->gobackButtonClass = $gobackButtonClass;
	}

	public function getGobackIcon(): string
	{
		return $this->gobackIcon;
	}

	public function setGobackIcon(string $gobackIcon): void
	{
		$this->gobackIcon = $gobackIcon;
	}

	public function getGobackIconLocation(): string
	{
		return $this->gobackIconLocation;
	}

	public function setGobackIconLocation(string $gobackIconLocation): void
	{
		$this->gobackIconLocation = $gobackIconLocation;
	}

	public function isSubmitButton(): bool
	{
		return $this->submitButton;
	}

	public function setSubmitButton(bool $submitButton): void
	{
		$this->submitButton = $submitButton;
	}

	public function getSubmitButtonLabel(): string
	{
		return $this->submitButtonLabel;
	}

	public function setSubmitButtonLabel(string $submitButtonLabel): void
	{
		$this->submitButtonLabel = $submitButtonLabel;
	}

	public function getSaveButtonClass(): string
	{
		return $this->saveButtonClass;
	}

	public function setSaveButtonClass(string $saveButtonClass): void
	{
		$this->saveButtonClass = $saveButtonClass;
	}

	public function getSaveIcon(): string
	{
		return $this->saveIcon;
	}

	public function setSaveIcon(string $saveIcon): void
	{
		$this->saveIcon = $saveIcon;
	}

	public function getSaveIconLocation(): string
	{
		return $this->saveIconLocation;
	}

	public function setSaveIconLocation(string $saveIconLocation): void
	{
		$this->saveIconLocation = $saveIconLocation;
	}

	public function isSubmitOnEnter(): bool
	{
		return $this->submitOnEnter;
	}

	public function setSubmitOnEnter(bool $submitOnEnter): void
	{
		$this->submitOnEnter = $submitOnEnter;
	}

	public function isDeleteButton(): bool
	{
		return $this->deleteButton;
	}

	public function setDeleteButton(bool $deleteButton): void
	{
		$this->deleteButton = $deleteButton;
	}

	public function getDeleteButtonLabel(): string
	{
		return $this->deleteButtonLabel;
	}

	public function setDeleteButtonLabel(string $deleteButtonLabel): void
	{
		$this->deleteButtonLabel = $deleteButtonLabel;
	}

	public function getDeleteButtonClass(): string
	{
		return $this->deleteButtonClass;
	}

	public function setDeleteButtonClass(string $deleteButtonClass): void
	{
		$this->deleteButtonClass = $deleteButtonClass;
	}

	public function getDeleteIcon(): string
	{
		return $this->deleteIcon;
	}

	public function setDeleteIcon(string $deleteIcon): void
	{
		$this->deleteIcon = $deleteIcon;
	}

	public function getDeleteIconLocation(): string
	{
		return $this->deleteIconLocation;
	}

	public function setDeleteIconLocation(string $deleteIconLocation): void
	{
		$this->deleteIconLocation = $deleteIconLocation;
	}

	public function isAjaxValidations(): bool
	{
		return $this->ajaxValidations;
	}

	public function setAjaxValidations(bool $ajaxValidations): void
	{
		$this->ajaxValidations = $ajaxValidations;
	}

	public function isAjaxValidationsToggleSubmit(): bool
	{
		return $this->ajaxValidationsToggleSubmit;
	}

	public function setAjaxValidationsToggleSubmit(bool $ajaxValidationsToggleSubmit): void
	{
		$this->ajaxValidationsToggleSubmit = $ajaxValidationsToggleSubmit;
	}

	public function getSubmitSuccessMessage(): string
	{
		return $this->submitSuccessMessage;
	}

	public function setSubmitSuccessMessage(string $submitSuccessMessage): void
	{
		$this->submitSuccessMessage = $submitSuccessMessage;
	}

	public function isSuppressMessages(): bool
	{
		return $this->suppressMessages;
	}

	public function setSuppressMessages(bool $suppressMessages): void
	{
		$this->suppressMessages = $suppressMessages;
	}

	public function isShowLoaderOnSubmit(): bool
	{
		return $this->showLoaderOnSubmit;
	}

	public function setShowLoaderOnSubmit(bool $showLoaderOnSubmit): void
	{
		$this->showLoaderOnSubmit = $showLoaderOnSubmit;
	}

	public function isSpoofCheck(): bool
	{
		return $this->spoofCheck;
	}

	public function setSpoofCheck(bool $spoofCheck): void
	{
		$this->spoofCheck = $spoofCheck;
	}

	public function isMultipageSave(): bool
	{
		return $this->multipageSave;
	}

	public function setMultipageSave(bool $multipageSave): void
	{
		$this->multipageSave = $multipageSave;
	}

	public function getNote(): string
	{
		return $this->note;
	}

	public function setNote(string $note): void
	{
		$this->note = $note;
	}

	public function getLabelsAbove(): int
	{
		return $this->labelsAbove;
	}

	public function setLabelsAbove(int $labelsAbove): void
	{
		$this->labelsAbove = $labelsAbove;
	}

	public function getLabelsAboveDetails(): int
	{
		return $this->labelsAboveDetails;
	}

	public function setLabelsAboveDetails(int $labelsAboveDetails): void
	{
		$this->labelsAboveDetails = $labelsAboveDetails;
	}

	public function getPdfTemplate(): string
	{
		return $this->pdfTemplate;
	}

	public function setPdfTemplate(string $pdfTemplate): void
	{
		$this->pdfTemplate = $pdfTemplate;
	}

	public function getPdfOrientation(): string
	{
		return $this->pdfOrientation;
	}

	public function setPdfOrientation(string $pdfOrientation): void
	{
		$this->pdfOrientation = $pdfOrientation;
	}

	public function getPdfSize(): string
	{
		return $this->pdfSize;
	}

	public function setPdfSize(string $pdfSize): void
	{
		$this->pdfSize = $pdfSize;
	}

	public function isPdfIncludeBootstrap(): bool
	{
		return $this->pdfIncludeBootstrap;
	}

	public function setPdfIncludeBootstrap(bool $pdfIncludeBootstrap): void
	{
		$this->pdfIncludeBootstrap = $pdfIncludeBootstrap;
	}

	public function getAdminFormTemplate(): string
	{
		return $this->adminFormTemplate;
	}

	public function setAdminFormTemplate(string $adminFormTemplate): void
	{
		$this->adminFormTemplate = $adminFormTemplate;
	}

	public function getAdminDetailsTemplate(): string
	{
		return $this->adminDetailsTemplate;
	}

	public function setAdminDetailsTemplate(string $adminDetailsTemplate): void
	{
		$this->adminDetailsTemplate = $adminDetailsTemplate;
	}

	public function isShowTitle(): bool
	{
		return $this->showTitle;
	}

	public function setShowTitle(bool $showTitle): void
	{
		$this->showTitle = $showTitle;
	}

	public function getPrint(): string
	{
		return $this->print;
	}

	public function setPrint(string $print): void
	{
		$this->print = $print;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function setEmail(string $email): void
	{
		$this->email = $email;
	}

	public function getPdf(): string
	{
		return $this->pdf;
	}

	public function setPdf(string $pdf): void
	{
		$this->pdf = $pdf;
	}

	public function isShowReferringTableReleatedData(): bool
	{
		return $this->showReferringTableReleatedData;
	}

	public function setShowReferringTableReleatedData(bool $showReferringTableReleatedData): void
	{
		$this->showReferringTableReleatedData = $showReferringTableReleatedData;
	}

	public function getTipLocation(): string
	{
		return $this->tipLocation;
	}

	public function setTipLocation(string $tipLocation): void
	{
		$this->tipLocation = $tipLocation;
	}

	public function getProcessJplugins(): int
	{
		return $this->processJplugins;
	}

	public function setProcessJplugins(int $processJplugins): void
	{
		$this->processJplugins = $processJplugins;
	}

	public function getPlugins(): array
	{
		return $this->plugins;
	}

	public function setPlugins(array $plugins): void
	{
		$this->plugins = $plugins;
	}

	public function getPluginState(): array
	{
		return $this->pluginState;
	}

	public function setPluginState(array $pluginState): void
	{
		$this->pluginState = $pluginState;
	}

	public function getPluginLocations(): array
	{
		return $this->pluginLocations;
	}

	public function setPluginLocations(array $pluginLocations): void
	{
		$this->pluginLocations = $pluginLocations;
	}

	public function getPluginEvents(): array
	{
		return $this->pluginEvents;
	}

	public function setPluginEvents(array $pluginEvents): void
	{
		$this->pluginEvents = $pluginEvents;
	}

	public function getPluginDescription(): array
	{
		return $this->pluginDescription;
	}

	public function setPluginDescription(array $pluginDescription): void
	{
		$this->pluginDescription = $pluginDescription;
	}
}