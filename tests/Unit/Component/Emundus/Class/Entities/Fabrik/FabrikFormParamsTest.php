<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Fabrik;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Fabrik\FabrikFormParams;

/**
 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams
 */
class FabrikFormParamsTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::__construct
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getOutro
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isCopyButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getCopyButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getCopyButtonClass
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getCopyIcon
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getCopyIconLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isResetButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getResetButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getResetButtonClass
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getResetIcon
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getResetIconLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isApplyButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getApplyButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getApplyButtonClass
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getApplyIcon
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getApplyIconLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isGobackButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getGobackButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getGobackButtonClass
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getGobackIcon
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getGobackIconLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isSubmitButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getSubmitButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getSaveButtonClass
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getSaveIcon
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getSaveIconLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isSubmitOnEnter
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isDeleteButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getDeleteButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getDeleteButtonClass
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getDeleteIcon
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getDeleteIconLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isAjaxValidations
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isAjaxValidationsToggleSubmit
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getSubmitSuccessMessage
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isSuppressMessages
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isShowLoaderOnSubmit
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isSpoofCheck
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isMultipageSave
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getNote
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getLabelsAbove
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getLabelsAboveDetails
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getPdfTemplate
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getPdfOrientation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getPdfSize
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isPdfIncludeBootstrap
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getAdminFormTemplate
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getAdminDetailsTemplate
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isShowTitle
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getPrint
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getEmail
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getPdf
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::isShowReferringTableReleatedData
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getTipLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getProcessJplugins
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getPlugins
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getPluginState
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getPluginLocations
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getPluginEvents
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::getPluginDescription
	 */
	public function testDefaultValues(): void
	{
		$params = new FabrikFormParams();

		// Buttons defaults
		$this->assertSame('', $params->getOutro());
		$this->assertFalse($params->isCopyButton());
		$this->assertSame('COPY', $params->getCopyButtonLabel());
		$this->assertSame('', $params->getCopyButtonClass());
		$this->assertSame('', $params->getCopyIcon());
		$this->assertSame('before', $params->getCopyIconLocation());

		$this->assertFalse($params->isResetButton());
		$this->assertSame('RESET', $params->getResetButtonLabel());
		$this->assertSame('', $params->getResetButtonClass());
		$this->assertSame('', $params->getResetIcon());
		$this->assertSame('before', $params->getResetIconLocation());

		$this->assertFalse($params->isApplyButton());
		$this->assertSame('APPLY', $params->getApplyButtonLabel());
		$this->assertSame('', $params->getApplyButtonClass());
		$this->assertSame('', $params->getApplyIcon());
		$this->assertSame('before', $params->getApplyIconLocation());

		$this->assertFalse($params->isGobackButton());
		$this->assertSame('GO_BACK', $params->getGobackButtonLabel());
		$this->assertSame('goback-btn', $params->getGobackButtonClass());
		$this->assertSame('', $params->getGobackIcon());
		$this->assertSame('before', $params->getGobackIconLocation());

		$this->assertTrue($params->isSubmitButton());
		$this->assertSame('SAVE_CONTINUE', $params->getSubmitButtonLabel());
		$this->assertSame('btn-primary save-btn sauvegarder', $params->getSaveButtonClass());
		$this->assertSame('', $params->getSaveIcon());
		$this->assertSame('before', $params->getSaveIconLocation());

		$this->assertFalse($params->isSubmitOnEnter());

		$this->assertFalse($params->isDeleteButton());
		$this->assertSame('DELETE', $params->getDeleteButtonLabel());
		$this->assertSame('btn-danger', $params->getDeleteButtonClass());
		$this->assertSame('', $params->getDeleteIcon());
		$this->assertSame('before', $params->getDeleteIconLocation());

		// Validation / messages
		$this->assertFalse($params->isAjaxValidations());
		$this->assertFalse($params->isAjaxValidationsToggleSubmit());
		$this->assertSame('', $params->getSubmitSuccessMessage());
		$this->assertFalse($params->isSuppressMessages());
		$this->assertFalse($params->isShowLoaderOnSubmit());
		$this->assertTrue($params->isSpoofCheck());
		$this->assertFalse($params->isMultipageSave());

		// Layout
		$this->assertSame('', $params->getNote());
		$this->assertSame(1, $params->getLabelsAbove());
		$this->assertSame(1, $params->getLabelsAboveDetails());

		// PDF
		$this->assertSame('', $params->getPdfTemplate());
		$this->assertSame('portrait', $params->getPdfOrientation());
		$this->assertSame('letter', $params->getPdfSize());
		$this->assertTrue($params->isPdfIncludeBootstrap());

		// Templates
		$this->assertSame('', $params->getAdminFormTemplate());
		$this->assertSame('', $params->getAdminDetailsTemplate());

		// Misc
		$this->assertTrue($params->isShowTitle());
		$this->assertSame('', $params->getPrint());
		$this->assertSame('', $params->getEmail());
		$this->assertSame('', $params->getPdf());
		$this->assertFalse($params->isShowReferringTableReleatedData());
		$this->assertSame('above', $params->getTipLocation());
		$this->assertSame(2, $params->getProcessJplugins());

		// Plugins
		$this->assertSame([], $params->getPlugins());
		$this->assertSame([], $params->getPluginState());
		$this->assertSame([], $params->getPluginLocations());
		$this->assertSame([], $params->getPluginEvents());
		$this->assertSame([], $params->getPluginDescription());
	}

	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setOutro
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setCopyButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setCopyButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setCopyButtonClass
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setCopyIcon
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setCopyIconLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setResetButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setResetButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setResetButtonClass
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setResetIcon
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setResetIconLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setApplyButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setApplyButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setApplyButtonClass
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setApplyIcon
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setApplyIconLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setGobackButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setGobackButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setGobackButtonClass
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setGobackIcon
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setGobackIconLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setSubmitButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setSubmitButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setSaveButtonClass
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setSaveIcon
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setSaveIconLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setSubmitOnEnter
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setDeleteButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setDeleteButtonLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setDeleteButtonClass
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setDeleteIcon
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setDeleteIconLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setAjaxValidations
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setAjaxValidationsToggleSubmit
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setSubmitSuccessMessage
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setSuppressMessages
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setShowLoaderOnSubmit
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setSpoofCheck
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setMultipageSave
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setNote
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setLabelsAbove
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setLabelsAboveDetails
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setPdfTemplate
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setPdfOrientation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setPdfSize
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setPdfIncludeBootstrap
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setAdminFormTemplate
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setAdminDetailsTemplate
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setShowTitle
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setPrint
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setEmail
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setPdf
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setShowReferringTableReleatedData
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setTipLocation
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setProcessJplugins
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setPlugins
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setPluginState
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setPluginLocations
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setPluginEvents
	 * @covers \Tchooz\Entities\Fabrik\FabrikFormParams::setPluginDescription
	 */
	public function testSetters(): void
	{
		$params = new FabrikFormParams();

		$params->setOutro('Outro text');
		$params->setCopyButton(true);
		$params->setCopyButtonLabel('COPY_NEW');
		$params->setCopyButtonClass('btn-copy');
		$params->setCopyIcon('copy-icon');
		$params->setCopyIconLocation('after');

		$params->setResetButton(true);
		$params->setResetButtonLabel('RESET_NEW');
		$params->setResetButtonClass('btn-reset');
		$params->setResetIcon('reset-icon');
		$params->setResetIconLocation('after');

		$params->setApplyButton(true);
		$params->setApplyButtonLabel('APPLY_NEW');
		$params->setApplyButtonClass('btn-apply');
		$params->setApplyIcon('apply-icon');
		$params->setApplyIconLocation('after');

		$params->setGobackButton(true);
		$params->setGobackButtonLabel('BACK_NEW');
		$params->setGobackButtonClass('btn-back');
		$params->setGobackIcon('back-icon');
		$params->setGobackIconLocation('after');

		$params->setSubmitButton(false);
		$params->setSubmitButtonLabel('SUBMIT_NEW');
		$params->setSaveButtonClass('btn-save');
		$params->setSaveIcon('save-icon');
		$params->setSaveIconLocation('after');

		$params->setSubmitOnEnter(true);

		$params->setDeleteButton(true);
		$params->setDeleteButtonLabel('DELETE_NEW');
		$params->setDeleteButtonClass('btn-del');
		$params->setDeleteIcon('del-icon');
		$params->setDeleteIconLocation('after');

		$params->setAjaxValidations(true);
		$params->setAjaxValidationsToggleSubmit(true);
		$params->setSubmitSuccessMessage('Success');
		$params->setSuppressMessages(true);
		$params->setShowLoaderOnSubmit(true);
		$params->setSpoofCheck(false);
		$params->setMultipageSave(true);

		$params->setNote('A note');
		$params->setLabelsAbove(0);
		$params->setLabelsAboveDetails(0);

		$params->setPdfTemplate('custom_pdf');
		$params->setPdfOrientation('landscape');
		$params->setPdfSize('a4');
		$params->setPdfIncludeBootstrap(false);

		$params->setAdminFormTemplate('admin_form');
		$params->setAdminDetailsTemplate('admin_details');

		$params->setShowTitle(false);
		$params->setPrint('print_val');
		$params->setEmail('email_val');
		$params->setPdf('pdf_val');
		$params->setShowReferringTableReleatedData(true);
		$params->setTipLocation('below');
		$params->setProcessJplugins(1);

		$params->setPlugins(['plugin1']);
		$params->setPluginState([1]);
		$params->setPluginLocations(['front']);
		$params->setPluginEvents(['onSubmit']);
		$params->setPluginDescription(['desc1']);

		// Verify all setters
		$this->assertSame('Outro text', $params->getOutro());
		$this->assertTrue($params->isCopyButton());
		$this->assertSame('COPY_NEW', $params->getCopyButtonLabel());
		$this->assertSame('btn-copy', $params->getCopyButtonClass());
		$this->assertSame('copy-icon', $params->getCopyIcon());
		$this->assertSame('after', $params->getCopyIconLocation());

		$this->assertTrue($params->isResetButton());
		$this->assertSame('RESET_NEW', $params->getResetButtonLabel());
		$this->assertSame('btn-reset', $params->getResetButtonClass());
		$this->assertSame('reset-icon', $params->getResetIcon());
		$this->assertSame('after', $params->getResetIconLocation());

		$this->assertTrue($params->isApplyButton());
		$this->assertSame('APPLY_NEW', $params->getApplyButtonLabel());
		$this->assertSame('btn-apply', $params->getApplyButtonClass());
		$this->assertSame('apply-icon', $params->getApplyIcon());
		$this->assertSame('after', $params->getApplyIconLocation());

		$this->assertTrue($params->isGobackButton());
		$this->assertSame('BACK_NEW', $params->getGobackButtonLabel());
		$this->assertSame('btn-back', $params->getGobackButtonClass());
		$this->assertSame('back-icon', $params->getGobackIcon());
		$this->assertSame('after', $params->getGobackIconLocation());

		$this->assertFalse($params->isSubmitButton());
		$this->assertSame('SUBMIT_NEW', $params->getSubmitButtonLabel());
		$this->assertSame('btn-save', $params->getSaveButtonClass());
		$this->assertSame('save-icon', $params->getSaveIcon());
		$this->assertSame('after', $params->getSaveIconLocation());

		$this->assertTrue($params->isSubmitOnEnter());

		$this->assertTrue($params->isDeleteButton());
		$this->assertSame('DELETE_NEW', $params->getDeleteButtonLabel());
		$this->assertSame('btn-del', $params->getDeleteButtonClass());
		$this->assertSame('del-icon', $params->getDeleteIcon());
		$this->assertSame('after', $params->getDeleteIconLocation());

		$this->assertTrue($params->isAjaxValidations());
		$this->assertTrue($params->isAjaxValidationsToggleSubmit());
		$this->assertSame('Success', $params->getSubmitSuccessMessage());
		$this->assertTrue($params->isSuppressMessages());
		$this->assertTrue($params->isShowLoaderOnSubmit());
		$this->assertFalse($params->isSpoofCheck());
		$this->assertTrue($params->isMultipageSave());

		$this->assertSame('A note', $params->getNote());
		$this->assertSame(0, $params->getLabelsAbove());
		$this->assertSame(0, $params->getLabelsAboveDetails());

		$this->assertSame('custom_pdf', $params->getPdfTemplate());
		$this->assertSame('landscape', $params->getPdfOrientation());
		$this->assertSame('a4', $params->getPdfSize());
		$this->assertFalse($params->isPdfIncludeBootstrap());

		$this->assertSame('admin_form', $params->getAdminFormTemplate());
		$this->assertSame('admin_details', $params->getAdminDetailsTemplate());

		$this->assertFalse($params->isShowTitle());
		$this->assertSame('print_val', $params->getPrint());
		$this->assertSame('email_val', $params->getEmail());
		$this->assertSame('pdf_val', $params->getPdf());
		$this->assertTrue($params->isShowReferringTableReleatedData());
		$this->assertSame('below', $params->getTipLocation());
		$this->assertSame(1, $params->getProcessJplugins());

		$this->assertSame(['plugin1'], $params->getPlugins());
		$this->assertSame([1], $params->getPluginState());
		$this->assertSame(['front'], $params->getPluginLocations());
		$this->assertSame(['onSubmit'], $params->getPluginEvents());
		$this->assertSame(['desc1'], $params->getPluginDescription());
	}
}

