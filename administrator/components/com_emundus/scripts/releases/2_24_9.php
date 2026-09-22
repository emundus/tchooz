<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

class Release2_24_9Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		try
		{
			$this->updateMentionsLegalesArticle();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function updateMentionsLegalesArticle(): void
	{
		$query = $this->db->createQuery(true);

		$query->select('id, introtext')
			->from($this->db->qn('#__content'))
			->where($this->db->qn('alias') . ' = ' . $this->db->q('mentions-legales'));
		$this->db->setQuery($query);
		$mentionsLegalesArticle = $this->db->loadObject();

		if(!empty($mentionsLegalesArticle) && !empty($mentionsLegalesArticle->id))
		{
			$mentionsLegalesArticle->introtext = str_replace('website : scaleway.com', 'lien : scaleway.com', $mentionsLegalesArticle->introtext);

			$this->tasks[] = $this->db->updateObject('#__content', $mentionsLegalesArticle, 'id');
		}
	}
}
