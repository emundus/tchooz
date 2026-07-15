<?php

/**
 * @package     Joomla.API
 * @subpackage  com_emundus
 *
 * @copyright   (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Emundus\Api\View\Attachments;

use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\Component\Emundus\Api\Serializer\EmundusSerializer;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The attachments view
 *
 * @since  4.0.0
 */
class JsonapiView extends BaseApiView
{
	/**
	 * The fields to render item in the documents
	 *
	 * @var  array
	 * @since  4.0.0
	 */
	protected $fieldsToRenderItem = [
		'lbl',
		'value',
		'description',
		'allowed_types',
		'nbmax',
		'ordering',
		'published',
		'category'
	];

	/**
	 * The fields to render items in the documents
	 *
	 * @var  array
	 * @since  4.0.0
	 */
	protected $fieldsToRenderList = [
		'lbl',
		'value',
		'description',
		'allowed_types',
		'nbmax',
		'ordering',
		'published',
		'category'
	];

	/**
	 * Constructor.
	 *
	 * @param   array  $config  A named configuration array for object construction.
	 *                          contentType: the name (optional) of the content type to use for the serialization
	 *
	 * @since   4.0.0
	 */
	public function __construct($config = [])
	{
		if (\array_key_exists('contentType', $config)) {
			$this->serializer = new EmundusSerializer($config['contentType']);
		}

		parent::__construct($config);
	}

	public function displayList(?array $items = null)
	{
		return parent::displayList();
	}

	public function displayItem($item = null)
	{
		return parent::displayItem($item);
	}
}
