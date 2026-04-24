<?php
/**
 * @package    Joomla
 * @subpackage eMundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.controller');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Controller\EmundusController;

class EmundusControllerTrombinoscope extends EmundusController
{
	private EmundusModelTrombinoscope $m_trombinoscrope;

	public function __construct($config = array())
	{
		parent::__construct($config);

		if(!class_exists('EmundusModelTrombinoscope'))
		{
			require_once(JPATH_BASE . '/components/com_emundus/models/trombinoscope.php');
		}
		$this->m_trombinoscrope    = new EmundusModelTrombinoscope();
	}

	#[AccessAttribute(AccessLevelEnum::PARTNER, [
		['id' => 31, 'mode' => CrudEnum::CREATE]
	])]
	public function generate_preview(): void
	{
		$gridL        = $this->app->getInput()->get('gridL');
		$gridH        = $this->app->getInput()->get('gridH');
		$margin       = $this->app->getInput()->get('margin');
		$template     = $this->app->getInput()->post->get('template', null, 'raw');
		$string_fnums = $this->app->getInput()->post->get('string_fnums', null, 'raw');
		$generate     = $this->app->getInput()->get('generate');
		$border       = $this->app->getInput()->get('border');
		$fnums        = $this->m_trombinoscrope->fnums_json_decode($string_fnums);
		$headerHeight = $this->app->getInput()->get('headerHeight');

		// Génération du HTML
		$html_content = $this->generate_data_for_pdf($fnums, $gridL, $gridH, $margin, $template, false, false, $generate, false, false, $border, $headerHeight);
		$response     = array(
			'status'       => true,
			'html_content' => $html_content
		);

		echo json_encode($response);
		exit;
	}


	/**
	 * Génération du code HTML qui sera envoyé soit pour cosntruire le pdf, soit pour afficher la prévisualisation
	 *
	 * @param         $fnums
	 * @param         $gridL
	 * @param         $gridH
	 * @param         $margin
	 * @param         $template
	 * @param         $templHeader
	 * @param         $templFooter
	 * @param         $generate
	 * @param   bool  $preview
	 * @param   bool  $checkHeader
	 * @param         $border
	 *
	 * @return string
	 *
	 * @throws Exception
	 * @since version
	 */
	public function generate_data_for_pdf($fnums, $gridL, $gridH, $margin, $template, $templHeader, $templFooter, $generate, $preview = false, $checkHeader = false, $border = null, $headerHeight = null)
	{
		if (!class_exists('EmundusModelFiles'))
		{
			require_once(JPATH_SITE . DS . '/components/com_emundus/models/files.php');
		}
		if (!class_exists('EmundusModelEmails'))
		{
			include_once(JPATH_SITE . '/components/com_emundus/models/emails.php');
		}
		$emails  = new EmundusModelEmails();
		$m_files = new EmundusModelFiles();

		// Traitement du nombre de colonnes max par ligne
		$nb_col_max = $gridL;
		$nb_li_max  = $gridH;
		$tab_margin = explode(',', $margin);
		// Il faut ajouter px à la fin
		for ($i = 0; $i < count($tab_margin); $i++)
		{
			$tab_margin[$i] .= 'px';
		}

		if (count($tab_margin) > 1)
		{
			// L'utilisateur a séparé les marges par des virgules, il faut les séparer par des espaces pour le css
			$marge_css_top    = $tab_margin[0];
			$marge_css_left   = $tab_margin[1];
			$marge_css_right  = $tab_margin[2];
			$marge_css_bottom = $tab_margin[3];
		}
		else
		{
			$marge_css_top    = $tab_margin[0];
			$marge_css_left   = $tab_margin[0];
			$marge_css_right  = $tab_margin[0];
			$marge_css_bottom = $tab_margin[0];
		}
		// Génération du HTML

		$body     = '';
		$nb_cell  = 0;
		$tab_body = array();
		$fnumInfo = $m_files->getFnumInfos($fnums[0]['fnum']);

		$template = preg_replace_callback('/< *img[^>]*src *= *["\']?([^"\']*)/i', function ($match) {
			$src = $match[1];
			if (substr($src, 0, 1) === '/')
			{
				$src = substr($src, 1);
			}

			return '<img src="' . $src;
		}, $template);

		foreach ($fnums as $fnum)
		{
			$post       = [
				'FNUM'           => $fnum['fnum'],
				'CAMPAIGN_LABEL' => $fnumInfo['label'],
				'CAMPAIGN_YEAR'  => $fnumInfo['year'],
				'CAMPAIGN_START' => $fnumInfo['start_date'],
				'CAMPAIGN_END'   => $fnumInfo['end_date'],
				'SITE_URL'       => Uri::base()
			];
			$tags       = $emails->setTags($fnum["applicant_id"], $post, $fnum['fnum'], '', $template, true);
			$body_tags  = preg_replace($tags['patterns'], $tags['replacements'], $template);
			$body_tmp   = $emails->setTagsFabrik($body_tags, array($fnum["fnum"]));
			$body       .= $body_tmp;
			$tab_body[] = $body_tmp;
			$nb_cell++;
		}

		$programme =  $this->m_trombinoscrope->getProgByFnum($post['FNUM']);
		// Marge gauche + droite
		$marge_x = $this->m_trombinoscrope->pdf_margin_left + $this->m_trombinoscrope->pdf_margin_right;
		// Marge haut + bas par défaut tcpdf
		$marge_y = $this->m_trombinoscrope->pdf_margin_header + $this->m_trombinoscrope->pdf_margin_footer;
		// Nombre de cellules par page
		$nb_cell_par_page = $nb_li_max * $nb_col_max;
		// Nombre de pages
		$nb_page = (int) ($nb_cell / $nb_cell_par_page);
		// Dans le cas où le reste de la division n'est pas nul
		if (($nb_cell % $nb_cell_par_page) > 0)
		{
			$nb_page++;
		}
		// Si l'on est en mode preview, on n'ira pas au-delà d'une page
		$nb_page_max = ($preview) ? 1 : $nb_page;
		// Largeur de la page en pixels (page A4 en 92 DPI)
		$largeur_px = 690;
		// Hauteur de la page en pixels (page A4 en 92 DPI)
		$hauteur_px = 900;
		// Largeur d'une cellule
		$cell_width = (int) (($largeur_px - $marge_x - (($marge_css_left * $nb_col_max) + ($marge_css_right * $nb_col_max))) / $nb_col_max - 10);
		// Hauteur d'une cellule
		$cell_height = (int) (($hauteur_px - $marge_y - (($marge_css_bottom * $nb_li_max) + ($marge_css_top * $nb_li_max))) / $nb_li_max - 10);
		//border
		if ($border == 1)
		{
			$borderCSS = '1px solid';
		}
		else
		{
			$borderCSS = '0';
		}
		$htmlLetters = $this->m_trombinoscrope->selectHTMLLetters();
		$templ       = [];
		foreach ($htmlLetters as $letter)
		{
			$templ[$letter['attachment_id']] = $letter;
		}
		$headerH = (empty($headerHeight)) ? $this->m_trombinoscrope->default_header_height : $headerHeight;
		$head    = '
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="author" lang="fr" content="EMUNDUS SAS - https://www.emundus.fr" />
<meta name="generator" content="EMUNDUS SAS - https://www.emundus.fr" />
<title>' . $programme['label'] . '</title>
<style>
body {  
    font-family: "Helvetica";
    font-size: 8pt;
    margin: 0;
}

.div-cell {
    border: ' . $borderCSS . ';
    display: inline-block;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: no-wrap;
    line-height: 1;
    width: ' . $cell_width . 'px;
    height: ' . $cell_height . 'px;
    margin: ' . implode(' ', $tab_margin) . ';
  
}

/** Define now the real margins of every page in the PDF **/
.em-body {
    margin-top: ' . $headerH . 'px;
}

/** Define the header rules **/
header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: ' . $headerH . 'px;
}

/** Define the footer rules **/
footer {
    position: fixed; 
    bottom: 0; 
    left: 0; 
    right: 0;
    height: 50px;
}
.logo{ width: 100px; height: auto; position: relative; margin-top: 20px; display: inline;}
.title{text-align: center;}
</style>
</head>';
		$body    = '';

		$ind_cell = 0;
		for ($cpt_page = 0; $cpt_page < $nb_page_max; $cpt_page++)
		{

			for ($cpt_li = 0; $cpt_li < $nb_li_max; $cpt_li++)
			{
				for ($cpt_col = 0; $cpt_col < $nb_col_max && $ind_cell < $nb_cell; $cpt_col++)
				{
					$body .= '<div class="div-cell">' . $tab_body[$ind_cell] . '</div>';
					$ind_cell++;
				}
				$body .= '<br>';
			}
			// Si l'on a plus d'une page, il faut insérer un délimiteur de page pour pouvoir ensuite générer le pdf page par page
			if ($cpt_page > 0)
			{
				$body .= '<div style="page-break-after: always;"></div>';
			}
		}

		$header_tags = preg_replace($tags['patterns'], $tags['replacements'], $templHeader);
		$header_tmp  = $emails->setTagsFabrik($header_tags, array($fnum["fnum"]));
		$header      = preg_replace_callback('/< *img[^>]*src *= *["\']?([^"\']*)/i', function ($match) {
			$src = $match[1];
			if (substr($src, 0, 1) === '/')
			{
				$src = substr($src, 1);
			}

			return '<img src="' . JURI::base() . $src;
		}, $header_tmp);
		$footer      = preg_replace_callback('/< *img[^>]*src *= *["\']?([^"\']*)/i', function ($match) {
			$src = $match[1];
			if (substr($src, 0, 1) === '/')
			{
				$src = substr($src, 1);
			}

			return '<img src="' . JURI::base() . $src;
		}, $templFooter);

		$prefixSite = Uri::base(true);
		if(!empty($prefixSite) && str_starts_with($prefixSite, '/'))
		{
			$prefixSite = substr($prefixSite, 1);
		}
		$body = preg_replace_callback('/< *img[^>]*src *= *["\']?([^"\']*)/i', function ($match) use ($prefixSite) {
			$src = $match[1];

			// Remove .. that can be here in start of src
			$src = str_replace('../', '', $src);
			if(!empty($prefixSite))
			{
				$src = str_replace($prefixSite, '', $src);
			}
			if (str_starts_with($src, '/'))
			{
				$src = substr($src, 1);
			}

			$path     = parse_url($src, PHP_URL_PATH);
			$fullPath = JPATH_BASE . '/' . $path;

			// Vérifie si le fichier existe
			if (!file_exists($fullPath))
			{
				return $match[0]; // Ne rien modifier si l’image n'existe pas
			}

			// Lire et encoder l’image
			$mimeType         = mime_content_type($fullPath);
			$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
			if (!in_array($mimeType, $allowedMimeTypes))
			{
				return $match[0]; // Ne rien modifier si le type MIME n'est pas autor
			}

			$imageData = base64_encode(file_get_contents($fullPath));
			$base64Src = 'data:' . $mimeType . ';base64,' . $imageData;

			return '<img src="' . $base64Src . '"';
		}, $body);

		if ($checkHeader == 1)
		{
			return $head . '<body class="em-body"><header>' . $header . '</header><footer>' . $footer . '</footer><main>' . $body . '</main></body></html>';
		}
		else
		{
			return $head . '<body>' . $header . $body . $footer . '</body></html>';
		}
	}

	#[AccessAttribute(AccessLevelEnum::PARTNER, [
		['id' => 31, 'mode' => CrudEnum::CREATE]
	])]
	public function generate_pdf(): void
	{
		$response['msg'] = Text::_('BAD_REQUEST');

		$format = $this->app->getInput()->get('format');

		if (!empty($format))
		{
			$string_fnums = $this->app->getInput()->post->get('string_fnums', null, 'raw');
			$fnums        = $this->m_trombinoscrope->fnums_json_decode(trim($string_fnums));

			if (!empty($fnums))
			{
				$gridL        = $this->app->getInput()->get('gridL');
				$gridH        = $this->app->getInput()->get('gridH');
				$margin       = $this->app->getInput()->get('margin');
				$template     = $this->app->getInput()->post->get('template', '', 'raw');
				$header       = $this->app->getInput()->post->get('header', '', 'raw');
				$footer       = $this->app->getInput()->post->get('footer', '', 'raw');
				$generate     = $this->app->getInput()->get('generate');
				$checkHeader  = $this->app->getInput()->get('checkHeader');
				$format       = $this->app->getInput()->get('format');
				$border       = $this->app->getInput()->get('border');
				$headerHeight = $this->app->getInput()->get('headerHeight');
				$html_content = $this->generate_data_for_pdf($fnums, $gridL, $gridH, $margin, $template, $header, $footer, $generate, false, $checkHeader, $border, $headerHeight);

				if (!empty($html_content))
				{
					$response['pdf_url'] = $this->m_trombinoscrope->generate_pdf($html_content, $format);
					$response['status']  = true;
					$response['code']    = 200;
					$response['msg']     = Text::_('SUCCESS');
				}
				else
				{
					$response['code'] = 500;
					$response['msg']  = Text::_('FAIL');
				}
			}
		}

		echo json_encode($response);
		exit();
	}
}
