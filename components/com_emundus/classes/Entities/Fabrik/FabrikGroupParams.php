<?php
/**
 * @package     Tchooz\Entities\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Fabrik;

class FabrikGroupParams
{
	private int $split_page = 0;

	private int $list_view_and_query = 0;

	private int $access = 0;

	private string $intro = '';

	private string $outro = '';

	private int $repeat_group_button = 0;

	private string $repeat_template = 'repeatgroup';

	private int $repeat_max = 0;

	private int $repeat_min = 0;

	private string $repeat_num_element = '';

	private int $repeat_sortable = 0;

	private string $repeat_order_element = '';

	private string $repeat_error_message = '';

	private string $repeat_no_data_message = '';

	private string $repeat_intro = '';

	private int $repeat_add_access = 1;

	private int $repeat_delete_access = 1;

	private string $repeat_delete_access_user = '';

	private int $repeat_copy_element_values = 0;

	private int $group_columns = 1;

	private int $group_column_widths = 0;

	private string $repeat_group_show_first = '-1';

	private int $random = 0;

	private string $labels_above = '-1';

	private string $labels_above_details = '-1';

	public function __construct()
	{
	}

	public function getSplitPage(): int
	{
		return $this->split_page;
	}

	public function setSplitPage(int $split_page): void
	{
		$this->split_page = $split_page;
	}

	public function getListViewAndQuery(): int
	{
		return $this->list_view_and_query;
	}

	public function setListViewAndQuery(int $list_view_and_query): void
	{
		$this->list_view_and_query = $list_view_and_query;
	}

	public function getAccess(): int
	{
		return $this->access;
	}

	public function setAccess(int $access): void
	{
		$this->access = $access;
	}

	public function getIntro(): string
	{
		return $this->intro;
	}

	public function setIntro(string $intro): void
	{
		$this->intro = $intro;
	}

	public function getOutro(): string
	{
		return $this->outro;
	}

	public function setOutro(string $outro): void
	{
		$this->outro = $outro;
	}

	public function getRepeatGroupButton(): int
	{
		return $this->repeat_group_button;
	}

	public function setRepeatGroupButton(int $repeat_group_button): void
	{
		$this->repeat_group_button = $repeat_group_button;
	}

	public function getRepeatTemplate(): string
	{
		return $this->repeat_template;
	}

	public function setRepeatTemplate(string $repeat_template): void
	{
		$this->repeat_template = $repeat_template;
	}

	public function getRepeatMax(): int
	{
		return $this->repeat_max;
	}

	public function setRepeatMax(int $repeat_max): void
	{
		$this->repeat_max = $repeat_max;
	}

	public function getRepeatMin(): int
	{
		return $this->repeat_min;
	}

	public function setRepeatMin(int $repeat_min): void
	{
		$this->repeat_min = $repeat_min;
	}

	public function getRepeatNumElement(): string
	{
		return $this->repeat_num_element;
	}

	public function setRepeatNumElement(string $repeat_num_element): void
	{
		$this->repeat_num_element = $repeat_num_element;
	}

	public function getRepeatSortable(): int
	{
		return $this->repeat_sortable;
	}

	public function setRepeatSortable(int $repeat_sortable): void
	{
		$this->repeat_sortable = $repeat_sortable;
	}

	public function getRepeatOrderElement(): string
	{
		return $this->repeat_order_element;
	}

	public function setRepeatOrderElement(string $repeat_order_element): void
	{
		$this->repeat_order_element = $repeat_order_element;
	}

	public function getRepeatErrorMessage(): string
	{
		return $this->repeat_error_message;
	}

	public function setRepeatErrorMessage(string $repeat_error_message): void
	{
		$this->repeat_error_message = $repeat_error_message;
	}

	public function getRepeatNoDataMessage(): string
	{
		return $this->repeat_no_data_message;
	}

	public function setRepeatNoDataMessage(string $repeat_no_data_message): void
	{
		$this->repeat_no_data_message = $repeat_no_data_message;
	}

	public function getRepeatIntro(): string
	{
		return $this->repeat_intro;
	}

	public function setRepeatIntro(string $repeat_intro): void
	{
		$this->repeat_intro = $repeat_intro;
	}

	public function getRepeatAddAccess(): int
	{
		return $this->repeat_add_access;
	}

	public function setRepeatAddAccess(int $repeat_add_access): void
	{
		$this->repeat_add_access = $repeat_add_access;
	}

	public function getRepeatDeleteAccess(): int
	{
		return $this->repeat_delete_access;
	}

	public function setRepeatDeleteAccess(int $repeat_delete_access): void
	{
		$this->repeat_delete_access = $repeat_delete_access;
	}

	public function getRepeatDeleteAccessUser(): string
	{
		return $this->repeat_delete_access_user;
	}

	public function setRepeatDeleteAccessUser(string $repeat_delete_access_user): void
	{
		$this->repeat_delete_access_user = $repeat_delete_access_user;
	}

	public function getRepeatCopyElementValues(): int
	{
		return $this->repeat_copy_element_values;
	}

	public function setRepeatCopyElementValues(int $repeat_copy_element_values): void
	{
		$this->repeat_copy_element_values = $repeat_copy_element_values;
	}

	public function getGroupColumns(): int
	{
		return $this->group_columns;
	}

	public function setGroupColumns(int $group_columns): void
	{
		$this->group_columns = $group_columns;
	}

	public function getGroupColumnWidths(): int
	{
		return $this->group_column_widths;
	}

	public function setGroupColumnWidths(int $group_column_widths): void
	{
		$this->group_column_widths = $group_column_widths;
	}

	public function getRepeatGroupShowFirst(): string
	{
		return $this->repeat_group_show_first;
	}

	public function setRepeatGroupShowFirst(string $repeat_group_show_first): void
	{
		$this->repeat_group_show_first = $repeat_group_show_first;
	}

	public function getRandom(): int
	{
		return $this->random;
	}

	public function setRandom(int $random): void
	{
		$this->random = $random;
	}

	public function getLabelsAbove(): string
	{
		return $this->labels_above;
	}

	public function setLabelsAbove(string $labels_above): void
	{
		$this->labels_above = $labels_above;
	}

	public function getLabelsAboveDetails(): string
	{
		return $this->labels_above_details;
	}

	public function setLabelsAboveDetails(string $labels_above_details): void
	{
		$this->labels_above_details = $labels_above_details;
	}
}