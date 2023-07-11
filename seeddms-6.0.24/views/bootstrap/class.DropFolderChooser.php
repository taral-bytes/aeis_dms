<?php
/**
 * Implementation of DropFolderChooser view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for DropFolderChooser view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_DropFolderChooser extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
?>
$('.fileselect').click(function(ev) {
	attr_filename = $(ev.currentTarget).data('filename');
	attr_form = $(ev.currentTarget).data('form');
	fileSelected(attr_filename, attr_form);
});
$('.folderselect').click(function(ev) {
	attr_foldername = $(ev.currentTarget).data('foldername');
	attr_form = $(ev.currentTarget).data('form');
	folderSelected(attr_foldername, attr_form);
});
<?php
	} /* }}} */

	public function menuList() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$dropfolderdir = $this->params['dropfolderdir'];
		$showfolders = $this->params['showfolders'];
		$cachedir = $this->params['cachedir'];
		$conversionmgr = $this->params['conversionmgr'];
		$previewwidth = $this->params['previewWidthMenuList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];
		$folder = $this->params['folder'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		$c = 0; // count files
		$menuitems['dropfolder'] = array('label'=>'', 'children'=>array());
		$dir = rtrim($dropfolderdir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$user->getLogin();
		/* Check if we are still looking in the configured directory and
		 * not somewhere else, e.g. if the login was '../test'
		 */
		if(dirname($dir) == $dropfolderdir) {
			if(is_dir($dir)) {
				$d = dir($dir);

				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				while (false !== ($entry = $d->read())) {
					if($entry != '..' && $entry != '.') {
						if($showfolders == 0 && !is_dir($dir.DIRECTORY_SEPARATOR.$entry) && is_readable($dir.DIRECTORY_SEPARATOR.$entry)) {
							$c++;
							$subitem = array('label'=>'', 'attributes'=>array(array('title', getMLText('menu_upload_from_dropfolder'))));
							if($folder)
								$subitem['link'] = $settings->_httpRoot.'out/out.AddDocument.php?folderid='.$folder->getId()."&dropfolderfileform1=".urldecode($entry);
							$mimetype = finfo_file($finfo, $dir.DIRECTORY_SEPARATOR.$entry);
							if(file_exists($dir.DIRECTORY_SEPARATOR.$entry)) {
								if($previewwidth) {
									$previewer->createRawPreview($dir.DIRECTORY_SEPARATOR.$entry, 'dropfolder'.DIRECTORY_SEPARATOR, $mimetype);
									if($previewer->hasRawPreview($dir.DIRECTORY_SEPARATOR.$entry, 'dropfolder'.DIRECTORY_SEPARATOR)) {
										$subitem['label'] .= "<div class=\"dropfolder-menu-img\" style=\"display: none; overflow:hidden; position: absolute; left:-".($previewwidth+10)."px; border: 1px solid #888;background: white;\"><img filename=\"".$entry."\" width=\"".$previewwidth."\" src=\"".$settings->_httpRoot."op/op.DropFolderPreview.php?filename=".$entry."&width=".$previewwidth."\" title=\"".htmlspecialchars($mimetype)."\"></div>";
									}
									$subitem['label'] .= "<div class=\"dropfolder-menu-text\" style=\"margin-left:10px; margin-right: 10px; display:inline-block;\">".$entry."<br /><span style=\"font-size: 85%;\">".SeedDMS_Core_File::format_filesize(filesize($dir.'/'.$entry)).", ".date('Y-m-d H:i:s', filectime($dir.'/'.$entry))."</span></div>";
									$menuitems['dropfolder']['children'][] = $subitem;
								}
							}
						} elseif($showfolders && is_dir($dir.'/'.$entry)) {
							$subitem = array('label'=>$entry);
							$menuitems['dropfolder']['children'][] = $subitem;
						}
					}
				}
			}
		}
		if($c) {
			$menuitems['dropfolder']['label'] = getMLText('menu_dropfolder')." (".$c.")";
			self::showNavigationBar($menuitems, array('id'=>'main-menu-dropfolderlist', 'right'=>true));
		}
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$dropfolderfile = $this->params['dropfolderfile'];
		$form = $this->params['form'];
		$dropfolderdir = $this->params['dropfolderdir'];
		$cachedir = $this->params['cachedir'];
		$conversionmgr = $this->params['conversionmgr'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];
		$showfolders = $this->params['showfolders'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		if($conversionmgr)
			$previewer->setConversionMgr($conversionmgr);
		else
			$previewer->setConverters($previewconverters);

		$dir = $dropfolderdir.'/'.$user->getLogin();
		/* Check if we are still looking in the configured directory and
		 * not somewhere else, e.g. if the login was '../test'
		 */
		if(dirname($dir) == $dropfolderdir) {
			if(is_dir($dir)) {
				$d = dir($dir);
				echo "<table class=\"table table-condensed\">\n";
				echo "<thead>\n";
				echo "<tr><th></th><th>".getMLText('name')."</th><th align=\"right\">".getMLText('file_size')."</th><th>".getMLText('date')."</th></tr>\n";
				echo "</thead>\n";
				echo "<tbody>\n";
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				while (false !== ($entry = $d->read())) {
					if($entry != '..' && $entry != '.') {
						if($showfolders == 0 && !is_dir($dir.'/'.$entry)) {
							$mimetype = finfo_file($finfo, $dir.'/'.$entry);
							$previewer->createRawPreview($dir.'/'.$entry, 'dropfolder/', $mimetype);
							echo "<tr><td style=\"min-width: ".$previewwidth."px;\">";
							if($previewer->hasRawPreview($dir.'/'.$entry, 'dropfolder/')) {
								echo "<img style=\"cursor: pointer;\" class=\"fileselect mimeicon\" data-filename=\"".$entry."\" data-form=\"".$form."\" width=\"".$previewwidth."\" src=\"../op/op.DropFolderPreview.php?filename=".$entry."&width=".$previewwidth."\" title=\"".htmlspecialchars($mimetype)."\">";
							}
							echo "</td><td><span style=\"cursor: pointer;\" class=\"fileselect\" data-filename=\"".$entry."\" data-form=\"".$form."\">".$entry."</span></td><td align=\"right\">".SeedDMS_Core_File::format_filesize(filesize($dir.'/'.$entry))."</td><td>".date('Y-m-d H:i:s', filectime($dir.'/'.$entry))."</td></tr>\n";
						} elseif($showfolders && is_dir($dir.'/'.$entry)) {
							echo "<tr>";
							echo "<td></td>";
							echo "<td><span style=\"cursor: pointer;\" class=\"folderselect\" data-foldername=\"".$entry."\" data-form=\"".$form."\">".$entry."</span></td><td align=\"right\"></td><td></td>";
							echo "</tr>\n";
						}
					}
				}
				echo "</tbody>\n";
				echo "</table>\n";
				echo '<script src="../out/out.DropFolderChooser.php?action=js&'.$_SERVER['QUERY_STRING'].'"></script>'."\n";
			} else {
				echo $this->errorMsg(getMLText('invalid_dropfolder_folder'));
			}
		}
	} /* }}} */
}
?>
