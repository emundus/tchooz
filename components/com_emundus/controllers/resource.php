<?php
/**
 * @package     controllers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Controller\EmundusController;
use Tchooz\EmundusResponse;
use Tchooz\Entities\Resource\ResourceEntity;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Enums\List\ListSortEnum;
use Tchooz\Repositories\Resource\ResourceFolderRepository;
use Tchooz\Repositories\Resource\ResourceRepository;
use Tchooz\Services\Resource\ResourceService;
use Tchooz\Services\Resource\ResourceShareService;
use Tchooz\Transformers\Resource\ResourceListItemTransformer;

class EmundusControllerResource extends EmundusController
{
	private const PERMISSION_VIEW   = 1;
	private const PERMISSION_EDIT   = 2;
	private const PERMISSION_MANAGE = 3;

	private ResourceService $resourceService;

	private ResourceRepository $resourceRepository;

	private ResourceFolderRepository $resourceFolderRepository;

	private ResourceShareService $shareService;

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->resourceService          = new ResourceService();
		$this->resourceRepository       = new ResourceRepository();
		$this->resourceFolderRepository = new ResourceFolderRepository();
		$this->shareService             = new ResourceShareService();
	}

	/* ----------------------------------------------------------------- Reads */

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::READ]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function getresources(): EmundusResponse
	{
		$sort      = $this->input->getString('sort', 'ASC');
		$sort      = ListSortEnum::tryFrom($sort) ?? ListSortEnum::ASC;
		$recherche = $this->input->getString('recherche', '');
		$lim       = $this->input->getInt('lim', 0);
		$page      = $this->input->getInt('page', 0);
		$order_by  = $this->input->getString('order_by');
		$order_by  = $order_by == 'label' ? 'name' : $order_by;

		[, $folderId] = $this->parseResourceRef($this->input->getString('folder_id', ''));

		// Type filter ('file' | 'folder'); anything else means no restriction.
		$typeFilter = $this->input->getString('resource_type', '');
		$typeFilter = in_array($typeFilter, ['file', 'folder'], true) ? $typeFilter : null;

		// Format filter: comma-separated list of file extensions (multiselect).
		$formats = array_values(array_filter(
			array_map('trim', explode(',', $this->input->getString('format', ''))),
			static fn ($format) => $format !== ''
		));

		// Managers holding the "resource" read action browse the full folder tree; everyone
		// else only sees the resources explicitly shared with them (directly or via profile/group).
		$isManager = \EmundusHelperAccess::asAccessAction(ActionEnum::RESOURCE->value,CrudEnum::READ->value, $this->user->id);

		if ($isManager)
		{
			$resources = $this->resourceService->getResources($folderId, $recherche, $lim, $page, $order_by, $sort, $typeFilter, $formats);
			$count     = $this->resourceService->countResources($folderId, $recherche, $typeFilter, $formats);
		}
		else
		{
			$resources = $this->resourceService->getAccessibleResources((int) $this->user->id, $folderId ?: null, $recherche, $lim, $page, $order_by, $sort, $typeFilter, $formats);
			$count     = $this->resourceService->countAccessibleResources((int) $this->user->id, $folderId ?: null, $recherche, $typeFilter, $formats);
		}

		$transformer = new ResourceListItemTransformer(null, $isManager);

		$datas = array_map(function ($resource) use ($transformer) {
			return $transformer->transform($resource);
		}, $resources);

		// The unpaginated total, not the page length: the list builds its pager from this.
		return EmundusResponse::ok(
			['datas' => $datas, 'count' => $count],
			Text::_('COM_EMUNDUS_RESOURCES_RETRIEVED')
		);
	}

	// TODO: no caller yet. The guard below is the "view" rank, but getResource() loads the relations,
	// so the response also carries the access list (with target emails) and the share code — data
	// getaccess() and getsharelink() gate behind PERMISSION_MANAGE. Before wiring a caller, decide
	// which one this endpoint is: a document read (then load it without relations) or an admin read
	// (then require PERMISSION_MANAGE).
	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::READ]])]
	//#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	// Until further developement and real usage, close access to this method that opens the door to
	// getting access to any resource.
	#[AccessAttribute(accessLevel: AccessLevelEnum::ADMINISTRATOR)]
	public function getresource(): EmundusResponse
	{
		$id = $this->app->input->getInt('id', 0);
		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$this->assertFileAccess($id, self::PERMISSION_VIEW, CrudEnum::READ);

		return EmundusResponse::ok($this->resourceService->getResource($id));
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::READ]])]
	public function getfolders(): EmundusResponse
	{
		$parentId = $this->app->input->getInt('parent_id', 0);

		return EmundusResponse::ok($this->resourceService->getFolders($parentId > 0 ? $parentId : null));
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::UPDATE]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function getaccess(): EmundusResponse
	{
		[$type, $id] = $this->parseResourceRef($this->app->input->getString('id', ''));
		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		// A whole-folder share is a manager-only action (folders are never shared per-file).
		if ($type === 'folder')
		{
			$this->assertManagerAction(CrudEnum::UPDATE);

			return EmundusResponse::ok($this->resourceService->getFolderAccess($id));
		}

		$this->assertFileAccess($id, self::PERMISSION_MANAGE, CrudEnum::UPDATE);

		return EmundusResponse::ok($this->resourceService->getResource($id)->getAccess());
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::UPDATE]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function getdisplayspaces(): EmundusResponse
	{
		$id = $this->app->input->getInt('id', 0);
		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$this->assertFileAccess($id, self::PERMISSION_MANAGE, CrudEnum::UPDATE);

		return EmundusResponse::ok($this->resourceService->getResource($id)->getDisplaySpaces());
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::UPDATE]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function getsharelink(): EmundusResponse
	{
		$id = $this->app->input->getInt('id', 0);
		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$this->assertFileAccess($id, self::PERMISSION_MANAGE, CrudEnum::UPDATE);

		return EmundusResponse::ok($this->shareService->getByResource($id));
	}

	/* ------------------------------------------------------------- Mutations */

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::CREATE]])]
	public function createfolder(): EmundusResponse
	{
		$this->assertToken();

		$name     = $this->app->input->getString('input', '');
		$parentId = $this->app->input->getInt('parent_id', 0);

		$folder = $this->resourceService->createFolder($name, $parentId > 0 ? $parentId : null, $this->user->id);

		return EmundusResponse::ok($folder, Text::_('COM_EMUNDUS_RESOURCE_FOLDER_CREATED'));
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::CREATE]])]
	public function import(): EmundusResponse
	{
		$this->assertToken();

		$file     = $this->app->input->files->get('file');
		$folderId = $this->app->input->getInt('folder_id', 0);

		if (empty($file) || !is_array($file))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_FILE_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$resource = $this->resourceService->importDocument($file, $folderId > 0 ? $folderId : null, $this->user->id);

		return EmundusResponse::ok($resource, Text::_('COM_EMUNDUS_RESOURCE_IMPORTED'), EmundusResponse::HTTP_CREATED);
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::UPDATE]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function rename(): EmundusResponse
	{
		$this->assertToken();

		[$type, $id] = $this->parseResourceRef($this->app->input->getString('id', ''));
		$name = $this->app->input->getString('input', $this->app->input->getString('name', ''));

		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		if ($type === 'folder')
		{
			$this->assertManagerAction(CrudEnum::UPDATE);
			$resource = $this->resourceService->renameFolder($id, $name);
		}
		else
		{
			$this->assertFileAccess($id, self::PERMISSION_EDIT, CrudEnum::UPDATE);
			$resource = $this->resourceService->rename($id, $name);
		}

		return EmundusResponse::ok($resource, Text::_('COM_EMUNDUS_RESOURCE_RENAMED'));
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::UPDATE]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function move(): EmundusResponse
	{
		$this->assertToken();

		[$type, $id] = $this->parseResourceRef($this->app->input->getString('id', ''));
		$target = $this->app->input->getString('input', $this->app->input->getString('folder_id', ''));

		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$folderId = ($target !== '' && (int) $target > 0) ? (int) $target : null;

		if ($type === 'folder')
		{
			$this->assertManagerAction(CrudEnum::UPDATE);
			$entity = $this->resourceService->moveFolder($id, $folderId);
		}
		else
		{
			$this->assertFileAccess($id, self::PERMISSION_EDIT, CrudEnum::UPDATE);
			$entity = $this->resourceService->move($id, $folderId);
		}

		return EmundusResponse::ok($entity, Text::_('COM_EMUNDUS_RESOURCE_MOVED'));
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::DELETE]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function delete(): EmundusResponse
	{
		$this->assertToken();

		$ref  = $this->app->input->getString('id', '');
		$refs = $ref !== '' ? [$ref] : json_decode($this->app->input->getString('ids', '[]'), true);

		if (!is_array($refs) || empty($refs))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_IDS_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		foreach ($refs as $reference)
		{
			[$type, $id] = $this->parseResourceRef((string) $reference);
			if ($id <= 0)
			{
				continue;
			}

			if ($type === 'folder')
			{
				$this->assertManagerAction(CrudEnum::DELETE);
				$this->resourceService->deleteFolder($id);
			}
			else
			{
				$this->assertFileAccess($id, self::PERMISSION_EDIT, CrudEnum::DELETE);
				$this->resourceService->deleteResources([$id]);
			}
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_RESOURCE_DELETED'));
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::UPDATE]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function saveaccess(): EmundusResponse
	{
		$this->assertToken();

		[$type, $id] = $this->parseResourceRef($this->app->input->getString('id', ''));
		$access      = json_decode($this->app->input->getString('access', '[]'), true);
		$access      = is_array($access) ? $access : [];

		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		// A whole-folder share is a manager-only action (folders are never shared per-file).
		if ($type === 'folder')
		{
			$this->assertManagerAction(CrudEnum::UPDATE);
			$this->resourceService->saveFolderAccess($id, $access);
		}
		else
		{
			$this->assertFileAccess($id, self::PERMISSION_MANAGE, CrudEnum::UPDATE);
			$this->resourceService->saveAccess($id, $access);
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_RESOURCE_ACCESS_SAVED'));
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::UPDATE]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function savedisplayspaces(): EmundusResponse
	{
		$this->assertToken();

		$id     = $this->app->input->getInt('id', 0);
		$spaces = json_decode($this->app->input->getString('spaces', '[]'), true);

		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$this->assertFileAccess($id, self::PERMISSION_MANAGE, CrudEnum::UPDATE);

		$this->resourceService->saveDisplaySpaces($id, is_array($spaces) ? $spaces : []);

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_RESOURCE_DISPLAY_SPACES_SAVED'));
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::UPDATE]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function savesharelink(): EmundusResponse
	{
		$this->assertToken();

		$id         = $this->app->input->getInt('id', 0);
		$password   = $this->app->input->getString('password', null);
		$expiration = $this->app->input->getString('expiration_date', null);

		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$this->assertFileAccess($id, self::PERMISSION_MANAGE, CrudEnum::UPDATE);

		$share = $this->shareService->createOrUpdate($id, $password, $expiration);

		return EmundusResponse::ok($share, Text::_('COM_EMUNDUS_RESOURCE_SHARE_SAVED'));
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::UPDATE]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function revokesharelink(): EmundusResponse
	{
		$this->assertToken();

		$id = $this->app->input->getInt('id', 0);
		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$this->assertFileAccess($id, self::PERMISSION_MANAGE, CrudEnum::UPDATE);

		$this->shareService->revoke($id);

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_RESOURCE_SHARE_REVOKED'));
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::UPDATE]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function getfolderoptions(): EmundusResponse
	{
		// TODO: If possible get folder options via rights and shared access
		return EmundusResponse::ok($this->resourceService->getFolderOptions(Text::_('COM_EMUNDUS_RESOURCES_MOVE_ROOT')));
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::READ]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function getformats(): EmundusResponse
	{
		$userId    = (int) $this->user->id;
		$isManager = \EmundusHelperAccess::asAccessAction(ActionEnum::RESOURCE->value,CrudEnum::READ->value, $userId);

		return EmundusResponse::ok($this->resourceService->getFormatOptions($userId, $isManager));
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::READ]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function download(): EmundusResponse
	{
		[, $id] = $this->parseResourceRef($this->app->input->getString('id', ''));
		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$this->assertFileAccess($id, self::PERMISSION_VIEW, CrudEnum::READ);

		$this->resourceService->markResourceSeen((int) $this->user->id, $id);

		$url = $this->resourceService->download($id);

		// 'download_file' triggers the generic list "file ready" download prompt; 'url' kept for direct callers.
		return EmundusResponse::ok(['download_file' => $url, 'url' => $url]);
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::READ]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function downloadfolderarchive(): EmundusResponse
	{
		[$type, $folderId] = $this->parseResourceRef($this->app->input->getString('id', ''));
		if ($type !== 'folder' || empty($folderId))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$userId    = (int) $this->user->id;
		$isManager = \EmundusHelperAccess::asAccessAction(ActionEnum::RESOURCE->value,CrudEnum::READ->value, $userId);

		$url = $this->resourceService->downloadFolderArchive($folderId, $userId, $isManager);

		// 'download_file' triggers the generic list "file ready" download prompt; 'url' kept for direct callers.
		return EmundusResponse::ok(['download_file' => $url, 'url' => $url]);
	}

	/**
	 * Parse a list reference ("file-3" / "folder-5") into [type, id].
	 * A plain numeric value defaults to a file.
	 *
	 * @return array{0:string,1:int}
	 */
	private function parseResourceRef(string $ref): array
	{
		if (preg_match('/^(file|folder)-(\d+)$/', $ref, $matches))
		{
			return [$matches[1], (int) $matches[2]];
		}

		return ['file', (int) $ref];
	}

	/**
	 * Guard a per-file action. Managers holding the "resource" action (matching CRUD) pass;
	 * otherwise the user must hold at least $requiredRank on that specific file through a share.
	 */
	private function assertFileAccess(int $resourceId, int $requiredRank, CrudEnum $managerCrud): void
	{
		$userId = (int) $this->user->id;

		if (\EmundusHelperAccess::asAccessAction(ActionEnum::RESOURCE->value,$managerCrud->value, $userId))
		{
			return;
		}

		if ($this->resourceService->getUserPermissionRank($userId, $resourceId) >= $requiredRank)
		{
			return;
		}

		throw new \RuntimeException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
	}

	/**
	 * Guard a manager-only action (folders and share administration are never shared per-file).
	 */
	private function assertManagerAction(CrudEnum $crud): void
	{
		if (!\EmundusHelperAccess::asAccessAction(ActionEnum::RESOURCE->value,$crud->value, (int) $this->user->id))
		{
			throw new \RuntimeException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::READ]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function view(): EmundusResponse
	{
		$id = $this->app->input->getInt('id', 0);
		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$this->assertFileAccess($id, self::PERMISSION_VIEW, CrudEnum::READ);

		$this->resourceService->markResourceSeen((int) $this->user->id, $id);

		return EmundusResponse::ok(['url' => $this->resourceService->getViewUrl($id)]);
	}

	#[AccessAttribute(actions: [['id' => ActionEnum::RESOURCE, 'mode' => CrudEnum::READ]])]
	#[AccessAttribute(accessLevel: AccessLevelEnum::REGISTERED)]
	public function preview(): EmundusResponse
	{
		$id = $this->app->input->getInt('id', 0);
		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_RESOURCE_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$this->assertFileAccess($id, self::PERMISSION_VIEW, CrudEnum::READ);

		$this->resourceService->markResourceSeen((int) $this->user->id, $id);

		return EmundusResponse::ok($this->resourceService->getPreview($id));
	}

	/**
	 * Public access to a shared resource by its link code (+ optional password).
	 * Open endpoint — protected by the share code, expiration and password.
	 */
	public function viewshared(): EmundusResponse
	{
		$code     = $this->app->input->getString('code', '');
		$password = $this->app->input->getString('password', null);

		if (!$this->shareService->validate($code, $password))
		{
			throw new \DomainException(Text::_('COM_EMUNDUS_RESOURCE_SHARE_INVALID'), EmundusResponse::HTTP_FORBIDDEN);
		}

		$share = $this->shareService->getByCode($code);

		// The download itself goes through getfile, which cannot re-check the share password on a plain
		// GET. Record the grant on the session that just passed the code/expiration/password check, so
		// the URL only works for this visitor instead of becoming a permanent public link.
		$session = $this->app->getSession();
		$granted = (array) $session->get('emundus.resource.shared_granted', []);
		$granted[] = $share->getResourceId();
		$session->set('emundus.resource.shared_granted', array_values(array_unique($granted)));

		return EmundusResponse::ok(['url' => $this->resourceService->download($share->getResourceId())]);
	}
}
