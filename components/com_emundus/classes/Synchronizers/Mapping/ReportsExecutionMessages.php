<?php

namespace Tchooz\Synchronizers\Mapping;

use Tchooz\Entities\Automation\ActionExecutionMessage;

/**
 * Optional capability for mapping objects whose run is worth narrating item by item.
 *
 * A mapping object reports success or failure with a boolean and an exception, which says nothing
 * about a run that partly succeeded — three documents deposited, one refused. Objects implementing
 * this expose one message per item; the MappingExecutor collects them and the calling action hands
 * them to the task, where they surface in the task history.
 *
 * Messages describe what happened to each item; the exception still says whether the run failed.
 */
interface ReportsExecutionMessages
{
	/**
	 * Messages accumulated during the last execute() call, oldest first.
	 *
	 * @return array<ActionExecutionMessage>
	 */
	public function getExecutionMessages(): array;
}
