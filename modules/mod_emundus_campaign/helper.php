<?php

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die('Restricted access');

class modEmundusCampaignHelper
{
	private int $totalCurrent;

	private int $totalFutur;

	private int $totalPast;

	private int $total;

	private string $offset;

	public string $now;

	private CMSApplicationInterface|CMSApplication $app;

	private DatabaseInterface $db;

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

	public function getCampaigns(string $temporality, string $condition, ?int $teachingUnityDates = null, ?string $order = 'start_date', ?int $mod_em_campaign_show_pinned_campaign = 0): array
	{
		$campaigns = [];

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
			'concat(MONTHNAME(ca.' . $order . '),"-",YEAR(ca.' . $order . ')) as month_name',
			'ca.is_limited as campaign_is_limited',
			'ca.limit as campaign_limit',
			'group_concat(escrls.limit_status) as campaign_limit_status',
			'count(ecc.id) as nb_files_in_limit',
			'group_concat(esc_uc.user_category_id) as user_categories_allowed',
			'esc_parent.label as parent_label',
			'esc_parent.training as parent_code',
		];

		if ($teachingUnityDates)
		{
			$columns[] = 'tu.date_start as formation_start';
			$columns[] = 'tu.date_end as formation_end';

			$query->select($columns)
				->from($this->db->qn('#__emundus_setup_campaigns', 'ca'))
				->leftJoin($this->db->qn('#__emundus_setup_campaigns_user_category', 'esc_uc') . ' ON ' . $this->db->qn('esc_uc.campaign_id') . ' = ' . $this->db->qn('ca.id'))
				->leftJoin($this->db->qn('#__emundus_setup_campaigns', 'esc_parent') . ' ON ' . $this->db->qn('esc_parent.id') . ' = ' . $this->db->qn('ca.parent_id'))
				->leftJoin($this->db->qn('#__emundus_setup_programmes', 'pr') . ' ON ' . $this->db->qn('pr.code') . ' = ' . $this->db->qn('ca.training'))
				->leftJoin($this->db->qn('#__emundus_setup_teaching_unity', 'tu') . ' ON ' . $this->db->qn('tu.code') . ' = ' . $this->db->qn('ca.training') . ' AND ' . $this->db->quoteName('ca.year') . ' = ' . $this->db->quoteName('tu.schoolyear'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns_repeat_limit_status', 'escrls') . ' ON ' . $this->db->quoteName('escrls.parent_id') . ' = ' . $this->db->quoteName('ca.id'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.campaign_id') . ' = ' . $this->db->quoteName('ca.id') . ' AND ' . $this->db->quoteName('ecc.status') . ' IN (escrls.limit_status)');
		}
		else
		{
			$query->select($columns);
			$query->from($this->db->qn('#__emundus_setup_campaigns', 'ca'))
				->leftJoin($this->db->qn('#__emundus_setup_campaigns_user_category', 'esc_uc') . ' ON ' . $this->db->qn('esc_uc.campaign_id') . ' = ' . $this->db->qn('ca.id'))
				->leftJoin($this->db->qn('#__emundus_setup_campaigns', 'esc_parent') . ' ON ' . $this->db->qn('esc_parent.id') . ' = ' . $this->db->qn('ca.parent_id'))
				->leftJoin($this->db->qn('#__emundus_setup_programmes', 'pr') . ' ON ' . $this->db->qn('pr.code') . ' = ' . $this->db->qn('ca.training'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns_repeat_limit_status', 'escrls') . ' ON ' . $this->db->quoteName('escrls.parent_id') . ' = ' . $this->db->quoteName('ca.id'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.campaign_id') . ' = ' . $this->db->quoteName('ca.id') . ' AND ' . $this->db->quoteName('ecc.status') . ' IN (escrls.limit_status)');
		}

		$dates_query = '';
		switch ($temporality)
		{
			case 'current':
				$dates_query = ' AND "' . $this->now . '" <= ca.end_date and "' . $this->now . '">= ca.start_date ';
				break;
			case 'past':
				$dates_query = ' AND "' . $this->now . '" >= ca.end_date ';
				break;
			case 'futur':
				$dates_query = ' AND "' . $this->now . '" <= ca.start_date ';
				break;
		}

		if (str_contains($condition, 'ORDER BY'))
		{
			$condition = explode('ORDER BY', $condition);
			$order     = $condition[1];
			$condition = $condition[0];
		}

		if ($mod_em_campaign_show_pinned_campaign)
		{
			$query->where('ca.pinned = 1 AND ca.published = 1 ' . $dates_query);
			$query->orWhere('ca.published = 1 ' . $dates_query . ' ' . $condition);
		}
		else
		{
			$query->where('ca.published = 1 ' . $dates_query . ' ' . $condition);
		}

		if (strpos($condition, 'ca.id') === false)
		{
			$query->andWhere($this->db->qn('ca.visible') . ' = 1');
		}

		$query->group('ca.id');

		if (!empty($order))
		{
			$query->order($order);
		}

		try
		{
			$this->db->setQuery($query);
			$campaigns = (array) $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'mod_emundus_campaign');
			$this->app->enqueueMessage(Text::_('MOD_EMUNDUS_CAMPAIGN_ERROR_GETTING_CAMPAIGNS') . $query->__toString(), 'error');
		}

		if (str_contains($order, 'label'))
		{
			usort($campaigns, function ($a, $b) use ($order) {
				return strnatcmp($a->label, $b->label);
			});
		}

		// If usertypes are enabled, filter campaigns by usertype
		$emConfig = ComponentHelper::getParams('com_emundus');
		if ($emConfig->get('enable_user_categories') == 1)
		{
			$current_user = Factory::getApplication()->getIdentity();
			if (!$current_user->guest)
			{
				// Get usercategory of current user
				$query->clear()
					->select('user_category')
					->from($this->db->quoteName('#__emundus_users'))
					->where($this->db->quoteName('user_id') . ' = ' . (int) $current_user->id);
				$this->db->setQuery($query);
				$current_user_category = $this->db->loadResult();

				// Filter campaigns by usercategory
				$campaigns = array_filter($campaigns, function ($campaign) use ($current_user_category) {
					if (empty($campaign->user_categories_allowed))
					{
						return true;
					}

					$user_categories_allowed = explode(',', $campaign->user_categories_allowed);

					return in_array($current_user_category, $user_categories_allowed);
				});
			}
		}

		//

		return $campaigns;
	}

	public function getCurrent(string $condition, ?int $teachingUnityDates = null, ?string $order = 'start_date', ?int $mod_em_campaign_show_pinned_campaign = 0): array
	{
		$current_campaigns = $this->getCampaigns('current', $condition, $teachingUnityDates, $order, $mod_em_campaign_show_pinned_campaign);

		PluginHelper::importPlugin('emundus');
		$dispatcher                 = Factory::getApplication()->getDispatcher();
		$onAfterGetCurrentCampaigns = new GenericEvent('onCallEventHandler', ['onAfterGetCurrentCampaigns', ['current_campaigns' => &$current_campaigns]]);
		$dispatcher->dispatch('onCallEventHandler', $onAfterGetCurrentCampaigns);

		$this->totalCurrent = count($current_campaigns);

		return $current_campaigns;
	}

	public function getPast(string $condition, ?int $teachingUnityDates = null, ?string $order = 'start_date', ?int $mod_em_campaign_show_pinned_campaign = 0): array
	{
		$campaigns       = $this->getCampaigns('past', $condition, $teachingUnityDates, $order);
		$this->totalPast = count($campaigns);

		return $campaigns;
	}

	public function getFutur(string $condition, ?int $teachingUnityDates = null, ?string $order = 'start_date', ?int $mod_em_campaign_show_pinned_campaign = 0): array
	{
		$campaigns        = $this->getCampaigns('futur', $condition, $teachingUnityDates, $order, $mod_em_campaign_show_pinned_campaign);
		$this->totalFutur = count($campaigns);

		return $campaigns;
	}

	public function getProgram(string $condition, ?int $teachingUnityDates = null): array
	{
		$campaigns   = $this->getCampaigns('all', $condition, $teachingUnityDates);
		$this->total = count($campaigns);

		return $campaigns;
	}

	public function getTotalCurrent(): int
	{
		return $this->totalCurrent;
	}

	public function getTotalPast(): int
	{
		return $this->totalPast;
	}

	public function getTotalFutur(): int
	{
		return $this->totalFutur;
	}

	public function getTotal(): int
	{
		return $this->total;
	}

	function getCampaignTags(int $id): array
	{
		$query = $this->db->getQuery(true);

		$query->select('d.*')
			->from($this->db->qn('data_tags', 'd'))
			->leftJoin($this->db->qn('#__emundus_setup_campaigns_repeat_discipline', 'rd') . ' ON ' . $this->db->qn('d.id') . " = " . $this->db->qn("rd.discipline"))
			->where($this->db->qn('d.published') . ' = 1 AND ' . $this->db->qn('rd.parent_id') . ' = ' . $id);

		$this->db->setQuery($query);

		return $this->db->loadAssocList('id', 'label');
	}

	function getReseaux(int $cid): object
	{
		$query = $this->db->getQuery(true);

		$query->select('reseaux_cult, hors_reseaux')
			->from($this->db->qn('#__emundus_setup_campaigns'))
			->where($this->db->qn('id') . ' = ' . $cid);

		$this->db->setQuery($query);

		return $this->db->loadObject();
	}

	function getNantesInfos(int $id): object
	{
		$query = $this->db->getQuery(true);

		$query->select([$this->db->quoteName('p.public'), $this->db->quoteName('tu.formation_length'), $this->db->quoteName('tu.date_start')])
			->from($this->db->qn('#__emundus_setup_programmes', 'p'))
			->leftJoin($this->db->quoteName('#__emundus_setup_teaching_unity', 'tu') . ' ON ' . $this->db->quoteName('tu.code') . ' = ' . $this->db->quoteName('p.code'))
			->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.training') . ' = ' . $this->db->quoteName('tu.code') . ' AND ' . $this->db->quoteName('esc.year') . ' LIKE ' . $this->db->quoteName('tu.schoolyear'))
			->where($this->db->quoteName('esc.id') . ' = ' . $id);

		$this->db->setQuery($query);

		return $this->db->loadObject();
	}

	public function getFaq(): array
	{
		$query = $this->db->getQuery(true);

		$query->select('c.id,c.title,c.introtext')
			->from($this->db->quoteName('#__content', 'c'))
			->leftJoin($this->db->quoteName('#__categories', 'ca') . ' ON ' . $this->db->quoteName('ca.id') . ' = ' . $this->db->quoteName('c.catid'))
			->where($this->db->quoteName('ca.alias') . ' LIKE ' . $this->db->quote('f-a-q'))
			->andWhere($this->db->quoteName('c.state') . ' = 1');
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getFormationsWithType(): array
	{
		$query = $this->db->getQuery(true);

		$query
			->select('*')
			->from($this->db->quoteName('data_formation'));

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

	public function getFormationTypes(): array
	{
		$query = $this->db->getQuery(true);

		$query
			->select('*')
			->from($this->db->quoteName('data_formation_type'));

		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getFormationLevels(): array
	{
		$query = $this->db->getQuery(true);

		$query
			->select('*')
			->from($this->db->quoteName('data_formation_level'));

		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	public function getVoiesDAcces(): array
	{
		$query = $this->db->getQuery(true);

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
			return [];
		}
	}

	public function addClassToData(array $data, array $formations): array
	{
		// Add a custom class parameter to data items
		return array_map(function ($item) use ($formations) {
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

			$query = $this->db->getQuery(true);
			$query->select('label')
				->from('#__emundus_setup_campaigns')
				->where('id = ' . $item->id);

			$this->db->setQuery($query);
			$item->label = $this->db->loadResult();

			return $item;
		}, $data);
	}

	public function getLinks(): object
	{
		$query = $this->db->getQuery(true);

		try
		{
			$query->select('id, params')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus_user_dropdown'))
				->andWhere($this->db->quoteName('published') . ' = 1');
			$this->db->setQuery($query);
			$user_module = $this->db->loadObject();

			$params = new stdClass();
			if (!empty($user_module) && !empty($user_module->params))
			{
				$params = json_decode($user_module->params);
			}

			return $params;
		}
		catch (Exception $e)
		{
			return new stdClass();
		}
	}

	public function getProgramLabel(string $codes): string
	{
		$label = '';
		$query = $this->db->getQuery(true);

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
