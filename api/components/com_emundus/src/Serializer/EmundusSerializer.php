<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Emundus\Api\Serializer;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Serializer\JoomlaSerializer;
use Joomla\CMS\Tag\TagApiSerializerTrait;
use Joomla\CMS\Uri\Uri;
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Relationship;
use Tobscure\JsonApi\Resource;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Temporary serializer
 *
 * @since  4.0.0
 */
class EmundusSerializer extends JoomlaSerializer
{
    use TagApiSerializerTrait;

    /**
     * Build category relationship
     *
     * @param   \stdClass  $model  Item model
     *
     * @return  Relationship
     *
     * @since 4.0.0
     */
    public function createdBy($model)
    {
        $serializer = new JoomlaSerializer('users');

        $resource = (new Resource($model->user, $serializer))
            ->addLink('self', Route::link('site', Uri::root() . 'api/index.php/v1/users/' . $model->user));

        return new Relationship($resource);
    }
}
