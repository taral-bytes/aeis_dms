<?php
/**
 * Implementation of EditOnline view
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
 * Class which outputs the html page for EditOnline view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_EditOnline extends SeedDMS_Theme_Style {
	var $dms;
	var $folder_count;
	var $document_count;
	var $file_count;
	var $storage_size;

	function js() { /* {{{ */
		$document = $this->params['document'];
		header('Content-Type: application/javascript; charset=UTF-8');
?>
mySeedSettings = {
	nameSpace:          'markdown', // Useful to prevent multi-instances CSS conflict
	previewParserPath:  '~/sets/markdown/preview.php',
	onShiftEnter:       {keepDefault:false, openWith:'\n\n'},
	markupSet: [
		{name:'First Level Heading', key:"1", placeHolder:'Your title here...', closeWith:function(markItUp) { return miu.markdownTitle(markItUp, '=') } },
		{name:'Second Level Heading', key:"2", placeHolder:'Your title here...', closeWith:function(markItUp) { return miu.markdownTitle(markItUp, '-') } },
		{name:'Heading 3', key:"3", openWith:'### ', placeHolder:'Your title here...' },
		{name:'Heading 4', key:"4", openWith:'#### ', placeHolder:'Your title here...' },
		{name:'Heading 5', key:"5", openWith:'##### ', placeHolder:'Your title here...' },
		{name:'Heading 6', key:"6", openWith:'###### ', placeHolder:'Your title here...' },
		{separator:'---------------' },
		{name:'Bold', key:"B", openWith:'**', closeWith:'**'},
		{name:'Italic', key:"I", openWith:'_', closeWith:'_'},
		{separator:'---------------' },
		{name:'Bulleted List', openWith:'- ' },
		{name:'Numeric List', openWith:function(markItUp) {
				return markItUp.line+'. ';
		}},
		{separator:'---------------' },
		{name:'Picture', key:"P", replaceWith:'![[![Alternative text]!]]([![Url:!:http://]!] "[![Title]!]")'},
		{name:'Link', key:"L", openWith:'[', closeWith:']([![Url:!:http://]!] "[![Title]!]")', placeHolder:'Your text to link here...' },
		{separator:'---------------'},
		{name:'Quotes', openWith:'> '},
		{name:'Code Block / Code', openWith:'(!(\t|!|`)!)', closeWith:'(!(`)!)'},
//		{separator:'---------------'},
//		{name:'Preview', call:'preview', className:"preview"}
	]
}
$(document).ready(function()	{
	$('#markdown').markItUp(mySeedSettings);

	$('#update').click(function(event) {
		event.preventDefault();
		$.post("../op/op.EditOnline.php", $('#form1').serialize(), function(response) {
			noty({
				text: response.message,
				type: response.success === true ? 'success' : 'error',
				dismissQueue: true,
				layout: 'topRight',
				theme: 'defaultTheme',
				timeout: 1500,
			});
			$('div.ajax').trigger('update', {documentid: <?php echo $document->getId(); ?>});
		}, "json");
		return false;
	});
});
<?php
	} /* }}} */

	function preview() { /* {{{ */
		$dms = $this->params['dms'];
		$document = $this->params['document'];
		$version = $this->params['version'];
?>
		<ul class="nav nav-pills" id="preview-tab" role="tablist">
		  <li class="nav-item active"><a class="nav-link active" data-target="#preview_markdown" data-toggle="tab" role="button"><?php printMLText('preview_markdown'); ?></a></li>
		  <li class="nav-item"><a class="nav-link" data-target="#preview_plain" data-toggle="tab" role="button"><?php printMLText('preview_plain'); ?></a></li>
		</ul>
		<div class="tab-content">
		  <div class="tab-pane active" id="preview_markdown" role="tabpanel">
<?php
		$Parsedown = new Parsedown();
		echo $Parsedown->text(file_get_contents($dms->contentDir . $version->getPath()));
?>
			</div>
		  <div class="tab-pane" id="preview_plain" role="tabpanel">
<?php
		echo "<pre>".htmlspecialchars(file_get_contents($dms->contentDir . $version->getPath()), ENT_SUBSTITUTE)."</pre>";
?>
			</div>
		</div>
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$document = $this->params['document'];
		$version = $this->params['version'];
		$cachedir = $this->params['cachedir'];
		$previewwidthlist = $this->params['previewWidthList'];
		$previewwidthdetail = $this->params['previewWidthDetail'];
		$accessobject = $this->params['accessobject'];

		$set = 'markdown'; //default or markdown
		$skin = 'simple'; // simple or markitup
		$this->htmlAddHeader('<link href="../styles/bootstrap/markitup/skins/'.$skin.'/style.css" rel="stylesheet">'."\n", 'css');
		$this->htmlAddHeader('<link href="../styles/bootstrap/markitup/sets/'.$set.'/style.css" rel="stylesheet">'."\n", 'css');
		$this->htmlAddHeader('<script type="text/javascript" src="../styles/bootstrap/markitup/jquery.markitup.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../styles/bootstrap/markitup/sets/'.$set.'/set.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("edit_online"));
		$this->globalNavigation();
		$this->contentStart();
		$folder = $document->getFolder();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->rowStart();
		$this->columnStart(6);
		$this->contentHeading(getMLText("content"));
?>
<form action="../op/op.EditOnline.php" id="form1" method="post">
<input type="hidden" name="documentid" value="<?php echo $document->getId(); ?>" />
<textarea id="markdown" name="data" style="width: 100%;" rows="15">
<?php
		$luser = $document->getLatestContent()->getUser();
		echo htmlspecialchars(file_get_contents($dms->contentDir . $version->getPath()), ENT_SUBSTITUTE);
?>
</textarea>
<?php
		if($accessobject->check_controller_access('EditOnline')) {
		if($user->getId() == $luser->getId()) {
			echo $this->warningMsg(getMLText('edit_online_warning'));
			$this->formSubmit('<i class="fa fa-save"></i> '.getMLText('save'),'update','','primary');
		} else {
			echo $this->errorMsg(getMLText('edit_online_not_allowed'));
		}
		}
?>
</form>
<?php
		$this->columnEnd();
		$this->columnStart(6);
		$this->contentHeading(getMLText("preview"));
		echo "<div class=\"ajax\" data-view=\"EditOnline\" data-action=\"preview\" data-query=\"documentid=".$document->getId()."\"></div>";
		$this->columnEnd();
		$this->rowEnd();
		$this->contentContainerEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
