<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die('Restricted access');

class modEmundusCampaignHelper
{
	private $totalCurrent;
	private $totalFutur;
	private $totalPast;
	private $total;
	private $offset;
	public $now;

	private $app;
	private $db;

	public function __construct()
	{
		$this->app = Factory::getApplication();
		$this->db  = Factory::getContainer()->get('DatabaseDriver');

		$this->totalCurrent = 0;
		$this->totalFutur   = 0;
		$this->totalPast    = 0;
		$this->total        = 0;
		$this->offset       = $this->app->get('offset', 'UTC');

		try
		{
			$dateTime  = new DateTime(gmdate("Y-m-d H:i:s"), new DateTimeZone('UTC'));
			$dateTime  = $dateTime->setTimezone(new DateTimeZone($this->offset));
			$this->now = $dateTime->format('Y-m-d H:i:s');
		}
		catch (Exception $e)
		{
			echo $e->getMessage() . '<br />';
		}

		Log::addLogger(array('text_file' => 'mod_emundus_campaign.php'), Log::ALL, array('mod_emundus_campaign'));
	}

	public function getCurrent($condition, $teachingUnityDates = null, $order = 'start_date')
	{
		$current_campaigns = [];

		$query = $this->db->getQuery(true);

		$columns = [
			'ca.*',
			'pr.apply_online',
			'pr.code',
			'pr.ordering as programme_ordering',
			'pr.label as programme',
			'pr.color as tag_color',
			'pr.link',
			'pr.programmes as prog_type',
			'pr.id as p_id',
			'pr.notes',
			'pr.logo',
			'MONTH(ca.' . $order . ') as month',
			'concat(MONTHNAME(ca.' . $order . '),"-",YEAR(ca.' . $order . ')) as month_name'
		];

		if ($teachingUnityDates)
		{
			$columns[] = 'tu.date_start as formation_start';
			$columns[] = 'tu.date_end as formation_end';

			$query->select($columns)
				->from($this->db->qn('#__emundus_setup_campaigns', 'ca'))
				->leftJoin($this->db->qn('#__emundus_setup_programmes', 'pr') . ' ON ' . $this->db->qn('pr.code') . ' = ' . $this->db->qn('ca.training'))
				->leftJoin($this->db->qn('#__emundus_setup_teaching_unity', 'tu') . ' ON ' . $this->db->qn('tu.code') . ' = ' . $this->db->qn('ca.training') . ' AND ' . $this->db->quoteName('ca.year') . ' = ' . $this->db->quoteName('tu.schoolyear'))
				->where('ca.published = 1 AND ca.visible = 1 AND "' . $this->now . '" <= ca.end_date and "' . $this->now . '">= ca.start_date ' . $condition);
		}
		else
		{
			$query->select($columns)
				->from('#__emundus_setup_campaigns as ca, #__emundus_setup_programmes as pr')
				->where('ca.training = pr.code AND ca.published = 1 AND ca.visible = 1 AND "' . $this->now . '" <= ca.end_date and "' . $this->now . '">= ca.start_date ' . $condition);
		}

		try
		{
			$this->db->setQuery($query);
			$current_campaigns  = (array) $this->db->loadObjectList();
			$this->totalCurrent = count($current_campaigns);
		}
		catch (Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'mod_emundus_campaign');
			$this->app->enqueueMessage(Text::_('MOD_EMUNDUS_CAMPAIGN_ERROR_GETTING_CURRENT_CAMPAIGNS'), 'error');
		}

		return $current_campaigns;
	}

	public function getPast($condition, $teachingUnityDates = null, $order = 'start_date')
	{
		$list = [];

		$query    = $this->db->getQuery(true);

		$columns = [
			'ca.*',
			'pr.apply_online',
			'pr.code',
			'pr.ordering as programme_ordering',
			'pr.label as programme',
			'pr.color as tag_color',
			'pr.link',
			'pr.programmes as prog_type',
			'pr.id as p_id',
			'pr.notes',
			'pr.logo',
			'MONTH(ca.' . $order . ') as month',
			'concat(MONTHNAME(ca.' . $order . '),"-",YEAR(ca.' . $order . ')) as month_name'
		];

		if ($teachingUnityDates)
		{
			$columns[] = 'tu.date_start as formation_start';
			$columns[] = 'tu.date_end as formation_end';

			$query->select($columns)
				->from($this->db->qn('#__emundus_setup_campaigns', 'ca'))
				->leftJoin($this->db->qn('#__emundus_setup_programmes', 'pr') . ' ON ' . $this->db->qn('pr.code') . ' = ' . $this->db->qn('ca.training'))
				->leftJoin($this->db->qn('#__emundus_setup_teaching_unity', 'tu') . ' ON ' . $this->db->qn('tu.code') . ' = ' . $this->db->qn('ca.training') . ' AND ' . $this->db->quoteName('ca.year') . ' = ' . $this->db->quoteName('tu.schoolyear'))
				->where('ca.published = 1 AND ca.visible = 1 AND "' . $this->now . '" >= ca.end_date ' . $condition);
		}
		else
		{
			$query->select($columns)
				->from('#__emundus_setup_campaigns as ca, #__emundus_setup_programmes as pr')
				->where('ca.training = pr.code AND ca.published = 1 AND ca.visible = 1 AND "' . $this->now . '" >= ca.end_date ' . $condition);
		}

		try
		{
			$this->db->setQuery($query);
			$list            = (array) $this->db->loadObjectList();
			$this->totalPast = count($list);
		}
		catch (Exception $e)
		{
			$this->app->enqueueMessage(Text::_('MOD_EMUNDUS_CAMPAIGN_ERROR_GETTING_PAST_CAMPAIGNS'), 'error');
			Log::add($e->getMessage(), Log::ERROR, 'mod_emundus_campaign');
		}

		return $list;
	}

	public function getFutur($condition, $teachingUnityDates = null, $order = 'start_date')
	{
		$list = [];

		$query    = $this->db->getQuery(true);

		$columns = [
			'ca.*',
			'pr.apply_online',
			'pr.code',
			'pr.ordering as programme_ordering',
			'pr.label as programme',
			'pr.color as tag_color',
			'pr.link',
			'pr.programmes as prog_type',
			'pr.id as p_id',
			'pr.notes',
			'pr.logo',
			'MONTH(ca.' . $order . ') as month',
			'concat(MONTHNAME(ca.' . $order . '),"-",YEAR(ca.' . $order . ')) as month_name'
		];

		if ($teachingUnityDates)
		{
			$columns[] = 'tu.date_start as formation_start';
			$columns[] = 'tu.date_end as formation_end';

			$query
				->select($columns)
				->from($this->db->qn('#__emundus_setup_campaigns', 'ca'))
				->leftJoin($this->db->qn('#__emundus_setup_programmes', 'pr') . ' ON ' . $this->db->qn('pr.code') . ' = ' . $this->db->qn('ca.training'))
				->leftJoin($this->db->qn('#__emundus_setup_teaching_unity', 'tu') . ' ON ' . $this->db->qn('tu.code') . ' = ' . $this->db->qn('ca.training') . ' AND ' . $this->db->quoteName('ca.year') . ' = ' . $this->db->quoteName('tu.schoolyear'))
				->where('ca.published = 1 AND ca.visible = 1 AND "' . $this->now . '" <= ca.start_date ' . $condition);
		}
		else
		{
			$query->select($columns)
				->from('#__emundus_setup_campaigns as ca,#__emundus_setup_programmes as pr')
				->where('ca.training = pr.code AND ca.published = 1 AND ca.visible = 1 AND "' . $this->now . '" <= ca.start_date ' . $condition);
		}

		try
		{
			$this->db->setQuery($query);
			$list             = (array) $this->db->loadObjectList();
			$this->totalFutur = count($list);
		}
		catch (Exception $e)
		{
			$this->app->enqueueMessage(Text::_('MOD_EMUNDUS_CAMPAIGN_ERROR_GETTING_FUTUR_CAMPAIGNS'), 'error');
			Log::add($e->getMessage(), Log::ERROR, 'mod_emundus_campaign');
		}

		return $list;
	}

	public function getProgram($condition, $teachingUnityDates = null)
	{
		$list     = [];

		$query    = $this->db->getQuery(true);

		$columns = [
			'ca.*',
			'pr.apply_online',
			'pr.code',
			'pr.ordering as programme_ordering',
			'pr.label as programme',
			'pr.color as tag_color',
			'pr.link',
			'pr.logo'
		];

		if ($teachingUnityDates)
		{
			$columns[] = 'tu.date_start as formation_start';
			$columns[] = 'tu.date_end as formation_end';
			$columns[] = 'pr.notes as desc';
			$columns[] = 'ca.is_limited';
			$columns[] = 'pr.programmes as prog_type';
			$query
				->select($columns)
				->from($this->db->qn('#__emundus_setup_campaigns', 'ca'))
				->leftJoin($this->db->qn('#__emundus_setup_programmes', 'pr') . ' ON ' . $this->db->qn('pr.code') . ' = ' . $this->db->qn('ca.training'))
				->leftJoin($this->db->qn('#__emundus_setup_teaching_unity', 'tu') . ' ON ' . $this->db->qn('tu.code') . ' = ' . $this->db->qn('ca.training') . ' AND ' . $this->db->quoteName('ca.year') . ' = ' . $this->db->quoteName('tu.schoolyear'))
				->where('ca.training = pr.code AND ca.published=1 ' . $condition);
		}
		else
		{
			$columns[] = 'pr.notes';
			$query
				->select($columns)
				->from('#__emundus_setup_campaigns as ca, #__emundus_setup_programmes as pr')
				->where('ca.training = pr.code AND ca.published=1 ' . $condition);
		}

		try
		{
			$this->db->setQuery($query);
			$list        = (array) $this->db->loadObjectList();
			$this->total = count($list);
		}
		catch (Exception $e)
		{
			$this->app->enqueueMessage(Text::_('MOD_EMUNDUS_CAMPAIGN_ERROR_GETTING_PAST_CAMPAIGNS'), 'error');
			Log::add($e->getMessage(), Log::ERROR, 'mod_emundus_campaign');
		}

		return $list;
	}

	public function getTotalCurrent()
	{
		return $this->totalCurrent;
	}

	public function getTotalPast()
	{
		return $this->totalPast;
	}

	public function getTotalFutur()
	{
		return $this->totalFutur;
	}

	public function getTotal()
	{
		return $this->total;
	}

	function getCampaignTags($id)
	{
		$query    = $this->db->getQuery(true);

		$query->select('d.*')
			->from($this->db->qn('data_tags', 'd'))
			->leftJoin($this->db->qn('#__emundus_setup_campaigns_repeat_discipline', 'rd') . ' ON ' . $this->db->qn('d.id') . " = " . $this->db->qn("rd.discipline"))
			->where($this->db->qn('d.published') . ' = 1 AND ' . $this->db->qn('rd.parent_id') . ' = ' . $id);

		$this->db->setQuery($query);

		return $this->db->loadAssocList('id', 'label');
	}

	function getReseaux($cid)
	{
		$query    = $this->db->getQuery(true);

		$query->select('reseaux_cult, hors_reseaux')
			->from($this->db->qn('#__emundus_setup_campaigns'))
			->where($this->db->qn('id') . ' = ' . $cid);

		$this->db->setQuery($query);

		return $this->db->loadObject();
	}

	/***
	 * Custoom function for Nantes
	 *
	 * @param $id
	 *
	 * @return mixed|null
	 *
	 * @since version
	 */
	function getNantesInfos($id)
	{
		$query    = $this->db->getQuery(true);

		$query
			->select([$this->db->quoteName('p.public'), $this->db->quoteName('tu.formation_length'), $this->db->quoteName('tu.date_start')])
			->from($this->db->qn('#__emundus_setup_programmes', 'p'))
			->leftJoin($this->db->quoteName('#__emundus_setup_teaching_unity', 'tu') . ' ON ' . $this->db->quoteName('tu.code') . ' = ' . $this->db->quoteName('p.code'))
			->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.training') . ' = ' . $this->db->quoteName('tu.code') . ' AND ' . $this->db->quoteName('esc.year') . ' LIKE ' . $this->db->quoteName('tu.schoolyear'))
			->where($this->db->quoteName('esc.id') . ' = ' . $id);

		try
		{
			$this->db->setQuery($query);

			return $this->db->loadObject();
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	public function getFaq()
	{
		$query    = $this->db->getQuery(true);

		$query
			->select('c.id,c.title,c.introtext')
			->from($this->db->quoteName('#__content', 'c'))
			->leftJoin($this->db->quoteName('#__categories', 'ca') . ' ON ' . $this->db->quoteName('ca.id') . ' = ' . $this->db->quoteName('c.catid'))
			->where($this->db->quoteName('ca.alias') . ' LIKE ' . $this->db->quote('f-a-q'))
			->andWhere($this->db->quoteName('c.state') . ' = 1');

		try
		{
			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	public function getFormationsWithType()
	{
		$query    = $this->db->getQuery(true);

		$query
			->select('*')
			->from($this->db->quoteName('data_formation'));

		try
		{
			$this->db->setQuery($query);

			$formations = $this->db->loadObjectList();

			foreach ($formations as $formation)
			{
				$query
					->clear()
					->select('repeat.voie_d_acces')
					->from($this->db->quoteName('data_acces_formation_repeat_voie_d_acces', 'repeat'))
					->leftJoin($this->db->quoteName('data_acces_formation', 'daf') . ' ON ' . $this->db->quoteName('repeat.parent_id') . ' = ' . $this->db->quoteName('daf.id'))
					->where($this->db->quoteName('daf.id') . ' = ' . $formation->id);

				$formation->voies_d_acces = $this->db->setQuery($query)->loadObjectList();
			}

			return $formations;
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	public function getFormationTypes()
	{
		$query    = $this->db->getQuery(true);

		$query
			->select('*')
			->from($this->db->quoteName('data_formation_type'));

		try
		{
			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	public function getFormationLevels()
	{
		$query    = $this->db->getQuery(true);

		$query
			->select('*')
			->from($this->db->quoteName('data_formation_level'));

		try
		{
			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	public function getVoiesDAcces()
	{
		$query    = $this->db->getQuery(true);

		$query
			->select('*')
			->from($this->db->quoteName('data_voies_d_acces'))
			->where($this->db->quoteName('published') . ' = 1')
			->order($this->db->quoteName('order'));

		try
		{
			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	public function addClassToData($data, $formations)
	{
		// Add a custom class parameter to data items
		$data = array_map(function ($item) use ($formations) {
			$item->class = !isset($item->class) ? '' : $item->class;

			// find formation associated to item inside formations array
			foreach ($formations as $formation)
			{
				if ($formation->id == $item->formation)
				{
					$item->class .= 'formation_type-' . $formation->type;
					$item->class .= ' formation_level-' . $formation->level;

					foreach ($formation->voies_d_acces as $voie)
					{
						$item->class .= ' voie_d_acces-' . $voie->voie_d_acces;

					}

					break;
				}
			}

			$query    = $this->db->getQuery(true);
			$query->select('label')
				->from('#__emundus_setup_campaigns')
				->where('id = ' . $item->id);

			$this->db->setQuery($query);
			$item->label = $this->db->loadResult();

			return $item;
		}, $data);

		return $data;
	}

	public function getLinks()
	{
		$query    = $this->db->getQuery(true);

		try
		{
			$query->select('params')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus_user_dropdown'))
				->andWhere($this->db->quoteName('published') . ' = 1');
			$this->db->setQuery($query);
			$params = $this->db->loadResult();

			if (!empty($params))
			{
				$params = json_decode($params);
			}

			return $params;
		}
		catch (Exception $e)
		{
			return new stdClass();
		}
	}

	public function getProgramLabel($codes)
	{
		$label    = '';
		$query    = $this->db->getQuery(true);

		try
		{
			$query->select('label, id')
				->from($this->db->quoteName('#__emundus_setup_programmes'))
				->where($this->db->quoteName('code') . ' IN (' . $this->db->quote($codes) . ')');
			$this->db->setQuery($query);
			$program_data = $this->db->loadAssoc();

			$label = $program_data['label'];
		}
		catch (Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'mod_emundus_campaign');
		}

		return $label;
	}
}
