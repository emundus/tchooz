<?php
defined('JPATH_BASE') or die;

use Joomla\CMS\Filesystem\File;

$d   = $displayData;
$ext = File::getExt($d->filename);
?>

<a class="download-archive fabrik-filetype-<?php echo $ext; ?>" title="<?php echo $d->filename; ?>"
	href="<?php echo $d->file; ?>" target="_blank" rel="noopener noreferrer">

	<?php
	if ($d->thumb) :
		?>
		<img src="<?php echo $d->thumb;?>" alt="<?php echo $d->filename;?>" />
	<?php
	else :
		?>
		<?php echo $d->filename;
		?>
		<?php
	endif;
	?>
</a>
