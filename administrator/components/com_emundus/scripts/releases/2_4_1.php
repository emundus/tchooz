<?php

namespace scripts;

class Release2_4_1Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$query  = $this->db->getQuery(true);
		$result = ['status' => false, 'message' => ''];

		$tasks = [];

		try
		{
			$query_fk = 'SET FOREIGN_KEY_CHECKS=0;';
			$this->db->setQuery($query_fk);
			$this->db->execute();

			// First check if the foreign key exists
			$query_fk = 'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = "jos_emundus_hikashop" AND COLUMN_NAME = "fnum" AND REFERENCED_TABLE_NAME IS NOT NULL;';
			$this->db->setQuery($query_fk);
			$fkExists = $this->db->loadResult();

			if(!empty($fkExists))
			{
				$query_fk = 'ALTER TABLE jos_emundus_hikashop DROP FOREIGN KEY '.$fkExists;
				$this->db->setQuery($query_fk);
				$tasks[] = $this->db->execute();
			}

			$query_fk         = 'ALTER TABLE jos_emundus_hikashop ADD CONSTRAINT jos_emundus_hikashop_ibfk_2 FOREIGN KEY (fnum) REFERENCES jos_emundus_campaign_candidature (fnum) ON UPDATE CASCADE;';
			$this->db->setQuery($query_fk);
			$tasks[] = $this->db->execute();

			$query_fk = 'SET FOREIGN_KEY_CHECKS=1;';
			$this->db->setQuery($query_fk);
			$this->db->execute();

			$result['status'] = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();

			return $result;
		}

		return $result;
	}
}