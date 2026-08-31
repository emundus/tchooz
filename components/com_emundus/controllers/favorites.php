<?php
/**
 * @package     com_emundus
 * @subpackage  Controllers
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

use Joomla\CMS\Language\Text;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Controller\EmundusController;
use Tchooz\EmundusResponse;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\Favorite\FavoriteFileRepository;

defined('_JEXEC') or die('Restricted access');

if (!class_exists('EmundusHelperAccess'))
{
	require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';
}

class EmundusControllerFavorites extends EmundusController
{
	/**
	 * Tells whether the given application file is in the current user's favorites.
	 * Read endpoint for clients that render their own toggle, such as the split-view modal.
	 */
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::FILE, 'mode' => CrudEnum::READ]
	])]
	public function isfavorite(): EmundusResponse
	{
		$this->checkToken('get');

		$fnum = $this->getAllowedFnum();

		return EmundusResponse::ok([
			'fnum'     => $fnum,
			'favorite' => (new FavoriteFileRepository())->isFavorite($fnum, $this->user->id),
		]);
	}

	/**
	 * Adds or removes the given application file from the current user's personal favorites.
	 */
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::FILE, 'mode' => CrudEnum::READ]
	])]
	public function togglefavorite(): EmundusResponse
	{
		$this->checkToken();

		$fnum     = $this->getAllowedFnum();
		$favorite = (new FavoriteFileRepository())->toggleFavorite($fnum, $this->user->id);

		return EmundusResponse::ok(
			['fnum' => $fnum, 'favorite' => $favorite],
			$favorite ? Text::_('COM_EMUNDUS_FAVORITES_ADDED') : Text::_('COM_EMUNDUS_FAVORITES_REMOVED')
		);
	}

	/**
	 * Reads the requested fnum and refuses anything the current user must not reach.
	 *
	 * Three distinct facts, none of which the others can express: the addon must be on, a fnum must
	 * be given, and the user must be allowed on that precise file — #[AccessAttribute] only gates
	 * the access level, it does not receive the fnum yet (see the TODO in EmundusController).
	 */
	private function getAllowedFnum(): string
	{
		if (!(new AddonRepository())->isActivated(AddonEnum::FAVORITE->value))
		{
			throw new AccessException(Text::_('COM_EMUNDUS_FAVORITES_ADDON_DISABLED'), EmundusResponse::HTTP_FORBIDDEN);
		}

		$fnum = $this->input->getString('fnum', '');

		if (empty($fnum))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_FAVORITES_MISSING_FNUM'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		if (!EmundusHelperAccess::asAccessAction(ActionEnum::FILE->value, CrudEnum::READ->value, $this->user->id, $fnum))
		{
			throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}

		return $fnum;
	}
}
