<?php
/**
 * @package     Tchooz\Services\Export\Pdf
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Pdf;

class PdfParser
{
	const HTML_TAG = '<html lang="en">';
	const HTML_CLOSE_TAG = '</html>';

	const BODY_TAG = '<body>';
	const BODY_CLOSE_TAG = '</body>';

	const HEADER_TAG = '<header>';
	const HEADER_CLOSE_TAG = '</header>';

	const TABLE_CLOSE_TAG = '</table>';

	const HR_TAG = '<hr/>';

	const BOLD_TAG = '<b>';
	const BOLD_CLOSE_TAG = '</b>';

	const ITALIC_TAG = '<i>';
	const ITALIC_CLOSE_TAG = '</i>';

	const STYLE_TAG = '<style>';
	const STYLE_CLOSE_TAG = '</style>';

	public function buildHtmlHead(string $title): string
	{
		return '<head>
				  <title>' . $title . '</title>
				  <meta name="author" content="eMundus">
				</head>';
	}

	public function createHeader(int $columns): string
	{
		$topMargin = 120;

		// Columns is between 1 and 5
		switch ($columns) {
			case 1:
				$topMargin = 80;
				break;
			case 2:
				$topMargin = 100;
				break;
			case 3:
				$topMargin = 120;
				break;
			case 4:
				$topMargin = 130;
				break;
			case 5:
				$topMargin = 160;
				break;
		}

		$pageMargin = $topMargin + 10;

		$header = '<style>@page { margin-top: ' . $pageMargin . 'px !important; }</style>';
		$header .= '<header style="top: -' . $topMargin . 'px;">';

		return $header;
	}

	public function createTable(int $width = 100): string
	{
		return '<table style="width:' . $width . '%;">';
	}

	public function createTitle(string $title, int $level = 1): string
	{
		return '<h' . $level . '>' . $title . '</h' . $level . '>';
	}

	public function createContentBlock(string $content, string $type = 'bold')
	{
		if ($type === 'bold') {
			return self::BOLD_TAG . $content . self::BOLD_CLOSE_TAG;
		}
		elseif ($type === 'italic') {
			return self::ITALIC_TAG . $content . self::ITALIC_CLOSE_TAG;
		}

		return $content;
	}

	public function addTableColumn(string $content, string $id = '', bool $isHeader = false): string
	{
		$tag = $isHeader ? 'th' : 'td';

		return '<' . $tag . (!empty($id) ? ' id="' . $id . '"' : '') . '>' . $content . '</' . $tag . '>';
	}

	public function addTableRow(array $columns, bool $isHeader = false): string
	{
		$rowHtml = '<tr>';
		$tag     = $isHeader ? 'th' : 'td';

		foreach ($columns as $column) {
			$rowHtml .= '<' . $tag . '>' . $column . '</' . $tag . '>';
		}

		$rowHtml .= '</tr>';

		return $rowHtml;
	}

	public function createImg(string $src, int|string $width = 100, int|string $height = 100): string
	{
		return '<img src="' . $src . '" width="' . $width . '" height="' . $height . '"/>';
	}

	public function createPageNumbering(): string
	{
		return '<script type="text/php">
			        if ( isset($pdf) ) {
			            $x = 570;
			            $y = 760;
			            $text = "{PAGE_NUM} / {PAGE_COUNT}";
			            $font = $fontMetrics->get_font("helvetica", "bold");
			            $size = 8;
			            $color = array(0,0,0);
			            $word_space = 0.0;  //  default
			            $char_space = 0.0;  //  default
			            $angle = 0.0;   //  default
			            $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
			        }
    			</script>';
	}
}