<?php

namespace scripts;

class Release2_3_3Installer extends ReleaseInstaller
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
			$query->clear()
				->select('id, params')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' like ' . $this->db->quote('mod_emundus_applications'));
			$this->db->setQuery($query);
			$app_modules = $this->db->loadObjectList();

			foreach ($app_modules as $app_module)
			{
				$params = json_decode($app_module->params);
				if(is_array($params->mod_emundus_applications_actions)) {
					// Add documents
					$params->mod_emundus_applications_actions[] = 'documents';
					$app_module->params = json_encode($params);
					$this->db->updateObject('#__modules', $app_module, 'id');
				}
			}

			$query->clear()
				->update($this->db->quoteName('#__menu'))
				->set($this->db->quoteName('published') . ' = 1')
				->where($this->db->quoteName('link') . ' like ' . $this->db->quote('index.php?option=com_emundus&view=application&layout=history'));
			$this->db->setQuery($query);
			$tasks['history'] = $this->db->execute();

			$tags = ['SETUP_LETTERS_GROUP_179_INTRO', 'SETUP_LETTERS_GROUP_185_INTRO'];
			$fr = '<p>Pour rendre ce courrier dynamique, insérer des <a href=\'/component/emundus/?view=export_select_columns&format=html&layout=all_programs\' target=\'_blank\' rel=\'noopener noreferrer\'>balises</a> dans sa construction afin d’ajouter des informations personnalisées pour chaque candidat. Par exemple, la balise <em>$APPLICANT_NAME</em> sera remplacée par le nom de votre candidat. Bonjour <em>$APPLICANT_NAME</em> deviendra Bonjour Julien.</p><p>Pour les champs de type wysiwyg et si le modèle est un fichier word, il faudra insérer la balise ainsi pour conserver la mise en forme du contenu : $&#123;textarea_&lt;ID_CHAMP&gt;&#125;$&#123;ID_CHAMP&#125;$&#123;textarea_&lt;ID_CHAMP&gt;&#125;</p>';
			$en = "<p>To make this mail dynamic, insert <a href='/component/emundus/?view=export_select_columns&amp;format=html&amp;layout=all_programs' target='_blank' rel='noopener noreferrer'>tags</a> into its construction to add custom information for each candidate. For example, the <em>\$APPLICANT_NAME</em> tag will be replaced with your candidate's name. Hello <em>\$APPLICANT_NAME</em> will become Hello Julian.</p><p>For wysiwyg fields and if the template is a Word file, you will need to insert the tag this way to preserve the content formatting: $&#123;textarea_&lt;ID_FIELD&gt;&#125;$&#123;ID_FIELD&#125;$&#123;textarea_&lt;ID_FIELD&gt;&#125;</p>";

			require_once(JPATH_ROOT . '/components/com_emundus/models/translations.php');
			$m_translations = new \EmundusModelTranslations();
			foreach($tags as $tag) {
				$m_translations->updateTranslation($tag, $fr, 'fr-FR', 'override', '', 0, '', 1);
				$m_translations->updateTranslation($tag, $en, 'en-GB','override', '', 0, '', 1);
			}

			$result['status'] = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status'] = false;
			$result['message'] = $e->getMessage();

			return $result;
		}

		return $result;
	}
}