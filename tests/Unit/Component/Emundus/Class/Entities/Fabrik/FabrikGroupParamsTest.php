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
use Tchooz\Entities\Fabrik\FabrikGroupParams;

/**
 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams
 */
class FabrikGroupParamsTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::__construct
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getSplitPage
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getListViewAndQuery
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getAccess
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getIntro
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getOutro
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatGroupButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatTemplate
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatMax
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatMin
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatNumElement
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatSortable
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatOrderElement
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatErrorMessage
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatNoDataMessage
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatIntro
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatAddAccess
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatDeleteAccess
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatDeleteAccessUser
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatCopyElementValues
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getGroupColumns
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getGroupColumnWidths
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRepeatGroupShowFirst
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getRandom
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getLabelsAbove
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::getLabelsAboveDetails
	 */
	public function testDefaultValues(): void
	{
		$params = new FabrikGroupParams();

		$this->assertSame(0, $params->getSplitPage());
		$this->assertSame(0, $params->getListViewAndQuery());
		$this->assertSame(0, $params->getAccess());
		$this->assertSame('', $params->getIntro());
		$this->assertSame('', $params->getOutro());
		$this->assertSame(0, $params->getRepeatGroupButton());
		$this->assertSame('repeatgroup', $params->getRepeatTemplate());
		$this->assertSame(0, $params->getRepeatMax());
		$this->assertSame(0, $params->getRepeatMin());
		$this->assertSame('', $params->getRepeatNumElement());
		$this->assertSame(0, $params->getRepeatSortable());
		$this->assertSame('', $params->getRepeatOrderElement());
		$this->assertSame('', $params->getRepeatErrorMessage());
		$this->assertSame('', $params->getRepeatNoDataMessage());
		$this->assertSame('', $params->getRepeatIntro());
		$this->assertSame(1, $params->getRepeatAddAccess());
		$this->assertSame(1, $params->getRepeatDeleteAccess());
		$this->assertSame('', $params->getRepeatDeleteAccessUser());
		$this->assertSame(0, $params->getRepeatCopyElementValues());
		$this->assertSame(1, $params->getGroupColumns());
		$this->assertSame(0, $params->getGroupColumnWidths());
		$this->assertSame('-1', $params->getRepeatGroupShowFirst());
		$this->assertSame(0, $params->getRandom());
		$this->assertSame('-1', $params->getLabelsAbove());
		$this->assertSame('-1', $params->getLabelsAboveDetails());
	}

	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setSplitPage
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setListViewAndQuery
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setAccess
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setIntro
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setOutro
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatGroupButton
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatTemplate
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatMax
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatMin
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatNumElement
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatSortable
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatOrderElement
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatErrorMessage
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatNoDataMessage
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatIntro
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatAddAccess
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatDeleteAccess
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatDeleteAccessUser
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatCopyElementValues
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setGroupColumns
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setGroupColumnWidths
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRepeatGroupShowFirst
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setRandom
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setLabelsAbove
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupParams::setLabelsAboveDetails
	 */
	public function testSetters(): void
	{
		$params = new FabrikGroupParams();

		$params->setSplitPage(1);
		$params->setListViewAndQuery(1);
		$params->setAccess(2);
		$params->setIntro('Intro text');
		$params->setOutro('Outro text');
		$params->setRepeatGroupButton(1);
		$params->setRepeatTemplate('custom');
		$params->setRepeatMax(10);
		$params->setRepeatMin(2);
		$params->setRepeatNumElement('element');
		$params->setRepeatSortable(1);
		$params->setRepeatOrderElement('order_element');
		$params->setRepeatErrorMessage('Error');
		$params->setRepeatNoDataMessage('No data');
		$params->setRepeatIntro('Repeat intro');
		$params->setRepeatAddAccess(5);
		$params->setRepeatDeleteAccess(3);
		$params->setRepeatDeleteAccessUser('admin');
		$params->setRepeatCopyElementValues(1);
		$params->setGroupColumns(3);
		$params->setGroupColumnWidths(100);
		$params->setRepeatGroupShowFirst('0');
		$params->setRandom(1);
		$params->setLabelsAbove('1');
		$params->setLabelsAboveDetails('1');

		$this->assertSame(1, $params->getSplitPage());
		$this->assertSame(1, $params->getListViewAndQuery());
		$this->assertSame(2, $params->getAccess());
		$this->assertSame('Intro text', $params->getIntro());
		$this->assertSame('Outro text', $params->getOutro());
		$this->assertSame(1, $params->getRepeatGroupButton());
		$this->assertSame('custom', $params->getRepeatTemplate());
		$this->assertSame(10, $params->getRepeatMax());
		$this->assertSame(2, $params->getRepeatMin());
		$this->assertSame('element', $params->getRepeatNumElement());
		$this->assertSame(1, $params->getRepeatSortable());
		$this->assertSame('order_element', $params->getRepeatOrderElement());
		$this->assertSame('Error', $params->getRepeatErrorMessage());
		$this->assertSame('No data', $params->getRepeatNoDataMessage());
		$this->assertSame('Repeat intro', $params->getRepeatIntro());
		$this->assertSame(5, $params->getRepeatAddAccess());
		$this->assertSame(3, $params->getRepeatDeleteAccess());
		$this->assertSame('admin', $params->getRepeatDeleteAccessUser());
		$this->assertSame(1, $params->getRepeatCopyElementValues());
		$this->assertSame(3, $params->getGroupColumns());
		$this->assertSame(100, $params->getGroupColumnWidths());
		$this->assertSame('0', $params->getRepeatGroupShowFirst());
		$this->assertSame(1, $params->getRandom());
		$this->assertSame('1', $params->getLabelsAbove());
		$this->assertSame('1', $params->getLabelsAboveDetails());
	}
}

