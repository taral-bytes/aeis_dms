<?php
/**
 * Implementation of Search result view
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
 * Class which outputs the html page for Search result view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Search extends SeedDMS_Theme_Style {

	/**
	 * Mark search query sting in a given string
	 *
	 * @param string $str mark this text
	 * @param string $tag wrap the marked text with this html tag
	 * @return string marked text
	 */
	function markQuery($str, $tag = "b") { /* {{{ */
		$querywords = preg_split("/ /", $this->query);

		foreach ($querywords as $queryword)
			$str = str_ireplace("($queryword)", "<" . $tag . ">\\1</" . $tag . ">", $str);

		return $str;
	} /* }}} */

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');

		parent::jsTranslations(array('cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));

?>
$(document).ready( function() {
	$('#export').on('click', function(e) {
		e.preventDefault();
		var url = "";
		url = $(this).attr('href')+'&includecontent='+($('#includecontent').prop('checked') ? '1' : '0');

		var inputs = $('input[name^=\"marks\"]');
		var values = {};
		inputs.each(function() {
			if(this.checked)
				values[this.name] = 1;
		});
		url += '&'+$.param(values);
		window.location.href = url;
	});

	$('#changecategory').on('click', function(e) {
		e.preventDefault();
		var url = "";
		url = $(this).attr('href')+'&changecategory='+$('#batchcategory').val()+'&removecategory='+($('#removecategory').prop('checked') ? '1' : '0');
		var inputs = $('input[name^=\"marks\"]');
		var values = {};
		inputs.each(function() {
			if(this.checked)
				values[this.name] = 1;
		});
		url += '&'+$.param(values);
		window.location.href = url;
	});

<?php if($this->getParam('theme') !== 'bootstrap4'): ?>
	$('body').on('click', 'a.change-owner-btn', function(ev){
		ev.preventDefault();
		ev.stopPropagation();
		confirmmsg = $(ev.currentTarget).attr('confirmmsg');
		href = $(ev.currentTarget).attr('href');
		bootbox.dialog(confirmmsg, [{
			"label" : "<i class='fa fa-user'></i> <?= getMLText("batch_change_owner") ?>",
			"class" : "btn-danger",
			"callback": function() {
				var url = "";
				url = href+'&newowner='+($('#newowner').val());
				var inputs = $('input[name^=\"marks\"]');
				var values = {};
				inputs.each(function() {
					if(this.checked)
						values[this.name] = 1;
				});
				url += '&'+$.param(values);
				window.location.href = url;
			}
		}, {
			"label" : "<?= getMLText("cancel") ?>",
			"class" : "btn-cancel",
			"callback": function() {
			}
		}]);
	});
<?php else: ?>
	$('body').on('click', 'a.change-owner-btn', function(ev){
		ev.preventDefault();
		ev.stopPropagation();
		confirmmsg = $(ev.currentTarget).attr('confirmmsg');
		href = $(ev.currentTarget).attr('href');
		bootbox.confirm({
			"message": confirmmsg,
			"buttons": {
				"confirm": {
					"label" : "<i class='fa fa-user'></i> <?= getMLText("batch_change_owner") ?>",
					"className" : "btn-danger",
				},
				"cancel": {
				"label" : " <?= getMLText("cancel") ?>",
					"className" : "btn-secondary",
				}
			},
			"callback": function(result) {
				if(result) {
					var url = "";
					url = href+'&newowner='+($('#newowner').val());
					var inputs = $('input[name^=\"marks\"]');
					var values = {};
					inputs.each(function() {
						if(this.checked)
							values[this.name] = 1;
					});
					url += '&'+$.param(values);
					window.location.href = url;
				}
			}
		});
	});
<?php endif; ?>
});
<?php
//		$this->printFolderChooserJs("form1");
		$this->printDeleteFolderButtonJs();
		$this->printMarkDocumentButtonJs();
		$this->printDeleteDocumentButtonJs();
		/* Add js for catching click on document in one page mode */
		$this->printClickDocumentJs();
		$this->printClickFolderJs();
?>
$(document).ready(function() {
	$('body').on('submit', '#form1', function(ev){
	});
});
<?php
	} /* }}} */

	/**
	 * Print button with icon for marking a document
	 *
	 * @param object $document document to be marked
	 * @param boolean $return return html instead of printing it
	 * @return string html content if $return is true, otherwise an empty string
	 */
	function printMarkDocumentButton($document, $return=false){ /* {{{ */
		$docid = $document->getID();
		$content = '';
		$content .= '<br /><span class="mark-btn document-unmarked" title="'.getMLText('mark_document').'" rel="D'.$docid.'"><i class="fa fa-square-o"></i></span><input type="checkbox" id="marks_D'.$docid.'" name="marks[D'.$docid.']" value="1" style="display: none;">';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	/**
	 * Print button with icon for marking a folder
	 *
	 * @param object $folder folder to be marked
	 * @param boolean $return return html instead of printing it
	 * @return string html content if $return is true, otherwise an empty string
	 */
	function printMarkFolderButton($folder, $return=false){ /* {{{ */
		$folderid = $folder->getID();
		$content = '';
		$content .= '<br /><span class="mark-btn folder-unmarked" title="'.getMLText('mark_folder').'" rel="F'.$folderid.'"><i class="fa fa-square-o"></i></span><input type="checkbox" id="marks_F'.$folderid.'" name="marks[F'.$folderid.']" value="1" style="display: none;">';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	function printMarkDocumentButtonJs(){ /* {{{ */
		$url = $this->html_url('Search', array_merge($_GET, array('action'=>null)));
		echo "
		// ".$url."
		$(document).ready(function () {
			$('body').on('click', 'span.mark-btn', function(ev){
				ev.stopPropagation();
				id = $(ev.currentTarget).attr('rel');
				$('#marks_'+id).each(function () { this.checked = !this.checked; });
				$(this).parents('tr').toggleClass('table-info');
				$(this).find('i').toggleClass('fa-square-o fa-check-square-o')
			});
		});
		";
	} /* }}} */

	function export() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$entries = $this->params['searchhits'];
		$includecontent = $this->params['includecontent'];
		$marks = $this->params['marks'];

		include("../inc/inc.ClassDownloadMgr.php");
		$downmgr = new SeedDMS_Download_Mgr();
		if($extraheader = $this->callHook('extraDownloadHeader'))
			$downmgr->addHeader($extraheader);
		foreach($entries as $entry) {
			if($entry->isType('document')) {
				if(empty($marks) || !empty($marks['D'.$entry->getId()])) {
					$extracols = $this->callHook('extraDownloadColumns', $entry);
					$filename = $this->callHook('filenameDownloadItem', $entry->getLatestContent());
					if($includecontent && $rawcontent = $this->callHook('rawcontent', $entry->getLatestContent())) {
						$downmgr->addItem($entry->getLatestContent(), $extracols, $rawcontent, $filename);
					} else
						$downmgr->addItem($entry->getLatestContent(), $extracols, null, $filename);
				}
			}
		}
		$filename = tempnam(sys_get_temp_dir(), '');
		if($includecontent) {
			$downmgr->createArchive($filename);
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: " . filesize($filename));
			header("Content-Disposition: attachment; filename=\"export-" .date('Y-m-d') . ".zip\"");
			header("Content-Type: application/zip");
			header("Cache-Control: must-revalidate");
		} else {
			$downmgr->createToc($filename);
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: " . filesize($filename));
			header("Content-Disposition: attachment; filename=\"export-" .date('Y-m-d') . ".xlsx\"");
			header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
			header("Cache-Control: must-revalidate");
		}

		readfile($filename);
		unlink($filename);
	} /* }}} */

	function changeowner() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$entries = $this->params['searchhits'];
		$newowner = $this->params['newowner'];
		$marks = $this->params['marks'];

		if($newowner && $user->isAdmin()) {
			$j = $i = 0;
			foreach($entries as $entry) {
				$prefix = $entry->isType('document') ? 'D' : 'F';
				if(empty($marks) || !empty($marks[$prefix.$entry->getId()])) {
					if($entry->getOwner()->getId() != $newowner->getId()) {
						$entry->setOwner($newowner);
						$j++;
					}
				}
			}
			$this->setParam('batchmsg', getMLText('batch_new_owner_msg', ['count'=>$j]));
		} else {
		}

		return self::show();
	} /* }}} */

	function changecategory() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$entries = $this->params['searchhits'];
		$changecategory = $this->params['changecategory'];
		$removecategory = $this->params['removecategory'];
		$marks = $this->params['marks'];

		if($changecategory && $user->isAdmin()) {
			$j = $i = 0;
			foreach($entries as $entry) {
				if($entry->isType('document')) {
					if(empty($marks) || !empty($marks['D'.$entry->getId()])) {
						if(!$removecategory) {
							if(!$entry->hasCategory($changecategory)) {
								$entry->addCategories([$changecategory]);
								$j++;
							}
						} else {
							if($entry->hasCategory($changecategory)) {
								$entry->removeCategories([$changecategory]);
								$j++;
							}
						}
					}
				}
			}
			if($removecategory) {
				$this->setParam('batchmsg', getMLText('batch_remove_category_msg', ['count'=>$j, 'catname'=>$changecategory->getName()]));
			} else {
				$this->setParam('batchmsg', getMLText('batch_add_category_msg', ['count'=>$j, 'catname'=>$changecategory->getName()]));
			}
		} else {
		}

		return self::show();
	} /* }}} */

	function opensearchsuggestion() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$query = $this->params['query'];
		$entries = $this->params['searchhits'];
		$recs = array();
		$content = "<?xml version=\"1.0\"?>\n";
		$content .= "<SearchSuggestion version=\"2.0\" xmlns=\"http://opensearch.org/searchsuggest2\">\n";
		$content .= "<Query xml:space=\"preserve\">".$query."</Query>";
		if($entries) {
			$content .= "<Section>\n";
			foreach ($entries as $entry) {
				$content .= "<Item>\n";
				if($entry->isType('document')) {
					$content .= "<Text xml:space=\"preserve\">".$entry->getName()."</Text>\n";
					$content .= "<Url xml:space=\"preserve\">http:".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$entry->getId()."</Url>\n";
				} elseif($entry->isType('folder')) {
					$content .= "<Text xml:space=\"preserve\">".$entry->getName()."</Text>\n";
					$content .= "<Url xml:space=\"preserve\">http:".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewFolder.php?folderid=".$entry->getId()."</Url>\n";
				}
				$content .= "</Item>\n";
			}
			$content .= "</Section>\n";
		}
		$content .= "</SearchSuggestion>";
		header("Content-Disposition: attachment; filename=\"search.xml\"; filename*=UTF-8''search.xml");
		header('Content-Type: application/x-suggestions+xml');
		echo $content;
	} /* }}} */

	function typeahead() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$query = $this->params['query'];
		$entries = $this->params['searchhits'];
		$terms = $this->params['terms'];
		$recs = array();
		$recs[] = array('type'=>'S', 'name'=>$query, 'occurences'=>'');
		if($terms) {
			foreach($terms as $term)
				$recs[] = array('type'=>'S', 'name'=>$term->text, 'occurences'=>$term->_occurrence, 'column'=>$term->field);
		}
		if($entries) {
			foreach ($entries as $entry) {
				if($entry->isType('document')) {
					$recs[] = array('type'=>'D', 'id'=>$entry->getId(), 'name'=>htmlspecialchars($entry->getName()), 'path'=>htmlspecialchars($entry->getParent()->getFolderPathPlain(true, '/')));
				} elseif($entry->isType('folder')) {
					$recs[] = array('type'=>'F', 'id'=>$entry->getId(), 'name'=>htmlspecialchars($entry->getName()), 'path'=>htmlspecialchars($entry->getParent()->getFolderPathPlain(true, '/')));
				}
			}
		}
		header('Content-Type: application/json');
		echo json_encode($recs);
	} /* }}} */

	public function folderListHeaderName() { /* {{{ */
		$orderby = $this->params['orderby'];
		$fullsearch = $this->params['fullsearch'];
		parse_str($_SERVER['QUERY_STRING'], $tmp);
		$tmp['orderby'] = ($orderby=="n"||$orderby=="na") ? "nd" : "n";
		$headcol = getMLText("name");
		if(!$fullsearch) {
			$headcol .= $orderby." <a href=\"../out/out.Search.php?".http_build_query($tmp)."\" title=\"".getMLText("sort_by_name")."\">".($orderby=="n"||$orderby=="na"?' <i class="fa fa-sort-alpha-asc selected"></i>':($orderby=="nd"?' <i class="fa fa-sort-alpha-desc selected"></i>':' <i class="fa fa-sort-alpha-asc"></i>'))."</a>";
			$tmp['orderby'] = ($orderby=="d"||$orderby=="da") ? "dd" : "d";
			$headcol .= " <a href=\"../out/out.Search.php?".http_build_query($tmp)."\" title=\"".getMLText("sort_by_date")."\">".($orderby=="d"||$orderby=="da"?' <i class="fa fa-sort-amount-asc selected"></i>':($orderby=="dd"?' <i class="fa fa-sort-amount-desc selected"></i>':' <i class="fa fa-sort-amount-asc"></i>'))."</a>";
		}
		return $headcol;
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$request = $this->params['request'];
		$fullsearch = $this->params['fullsearch'];
		$facetsearch = $this->params['facetsearch'];
		$total = $this->params['total'];
		$totaldocs = $this->params['totaldocs'];
		$totalfolders = $this->params['totalfolders'];
		$limit = $this->params['limit'];
		$attrdefs = $this->params['attrdefs'];
		$allCats = $this->params['allcategories'];
		$allUsers = $this->params['allusers'];
		$mode = $this->params['mode'];
		$resultmode = $this->params['resultmode'];
		$workflowmode = $this->params['workflowmode'];
		$enablefullsearch = $this->params['enablefullsearch'];
		$enablefacetsearch = ($settings->_fullSearchEngine != 'lucene');
		$enableclipboard = $this->params['enableclipboard'];
		$attributes = $this->params['attributes'];
		$categories = $this->params['categories'];
		$category = $this->params['category'];
		$mimetype = $this->params['mimetype'];
		$owner = $this->params['owner'];
		$startfolder = $this->params['startfolder'];
		$createstartdate = $this->params['createstartdate'];
		$createenddate = $this->params['createenddate'];
		$created = $this->params['created'];
		$modifystartdate = $this->params['modifystartdate'];
		$modifyenddate = $this->params['modifyenddate'];
		$modified = $this->params['modified'];
		$expstartdate = $this->params['expstartdate'];
		$expenddate = $this->params['expenddate'];
		$statusstartdate = $this->params['statusstartdate'];
		$statusenddate = $this->params['statusenddate'];
		$revisionstartdate = $this->params['revisionstartdate'];
		$revisionenddate = $this->params['revisionenddate'];
		$status = $this->params['status'];
		$record_type = $this->params['recordtype'];
		$this->query = $this->params['query'];
		$orderby = $this->params['orderby'];
		$entries = $this->params['searchhits'];
		$facets = $this->params['facets'];
		$totalpages = $this->params['totalpages'];
		$pageNumber = $this->params['pagenumber'];
		$searchTime = $this->params['searchtime'];
		$urlparams = $this->params['urlparams'];
		$searchin = $this->params['searchin'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$conversionmgr = $this->params['conversionmgr'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];
		$reception = $this->params['reception'];
		$showsinglesearchhit = $this->params['showsinglesearchhit'];

		$this->htmlStartPage(getMLText("search_results"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation("", "");

		$this->rowStart();
		$this->columnStart(4);
		//$this->contentHeading("<button class=\"btn btn-primary\" id=\"searchform-toggle\" data-toggle=\"collapse\" href=\"#searchform\"><i class=\"fa fa-exchange\"></i></button> ".getMLText('search'), true);
		$this->contentHeading(getMLText('search'), true);
		if($this->query) {
			echo "<div id=\"searchform\" class=\"_collapse mb-sm-4\">";
		}
?>
  <ul class="nav nav-pills" id="searchtab">
	  <li class="nav-item <?php echo ($fullsearch == false) ? 'active' : ''; ?>"><a class="nav-link <?php echo ($fullsearch == false) ? 'active' : ''; ?>" data-target="#database" data-toggle="tab" role="button"><?php printMLText('databasesearch'); ?></a></li>
<?php
		if($enablefullsearch) {
?>
	  <li class="nav-item <?php echo ($fullsearch == true && $facetsearch == false) ? 'active' : ''; ?>"><a class="nav-link <?php echo ($fullsearch == true && $facetsearch == false) ? 'active' : ''; ?>" data-target="#fulltext" data-toggle="tab" role="button"><?php printMLText('fullsearch'); ?></a></li>
<?php
		}
		if($enablefacetsearch) {
?>
	  <li class="nav-item <?php echo ($facetsearch == true && $facetsearch == true) ? 'active' : ''; ?>"><a class="nav-link <?php echo ($facetsearch == true && $facetsearch == true) ? 'active' : ''; ?>" data-target="#facetfulltext" data-toggle="tab" role="button"><?php printMLText('facetfullsearch'); ?></a></li>
<?php
		}
?>
	</ul>
	<div class="tab-content">
	  <div class="tab-pane <?php echo ($fullsearch == false) ? 'active' : ''; ?>" id="database">
		<form class="form-horizontal mb-4" action="<?= $this->params['settings']->_httpRoot ?>out/out.Search.php" name="form1">
<input type="hidden" name="fullsearch" value="0" />
<?php
// Database search Form {{{
		$this->contentContainerStart();

		$this->formField(
			getMLText("search_query"),
			array(
				'element'=>'input',
				'type'=>'search',
				'name'=>'query',
				'value'=>htmlspecialchars($this->query)
			)
		);
		$options = array();
		$options[] = array('1', getMLText('search_mode_and'), $mode=='AND');
		$options[] = array('0', getMLText('search_mode_or'), $mode=='OR');
		$this->formField(
			getMLText("search_mode"),
			array(
				'element'=>'select',
				'name'=>'mode',
				'multiple'=>false,
				'options'=>$options
			)
		);
		$options = array();
		$options[] = array('1', getMLText('keywords').' ('.getMLText('documents_only').')', in_array('1', $searchin));
		$options[] = array('2', getMLText('name'), in_array('2', $searchin));
		$options[] = array('3', getMLText('comment'), in_array('3', $searchin));
		$options[] = array('4', getMLText('attributes'), in_array('4', $searchin));
		$options[] = array('5', getMLText('id'), in_array('5', $searchin));
		$this->formField(
			getMLText("search_in"),
			array(
				'element'=>'select',
				'name'=>'searchin[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'options'=>$options
			)
		);
		$options = array();
		$options[] = array('', getMLText('orderby_unsorted'));
		$options[] = array('dd', getMLText('orderby_date_desc'), 'dd'==$orderby);
		$options[] = array('d', getMLText('orderby_date_asc'), 'd'==$orderby);
		$options[] = array('nd', getMLText('orderby_name_desc'), 'nd'==$orderby);
		$options[] = array('n', getMLText('orderby_name_asc'), 'n'==$orderby);
		$options[] = array('id', getMLText('orderby_id_desc'), 'id'==$orderby);
		$options[] = array('i', getMLText('orderby_id_asc'), 'i'==$orderby);
		$this->formField(
			getMLText("orderby"),
			array(
				'element'=>'select',
				'name'=>'orderby',
				'class'=>'chzn-select',
				'multiple'=>false,
				'options'=>$options
			)
		);
		$options = array();
		foreach ($allUsers as $currUser) {
			if($user->isAdmin() || (!$currUser->isGuest() && (!$currUser->isHidden() || $currUser->getID() == $user->getID())))
				$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin()), in_array($currUser->getID(), $owner), array(array('data-subtitle', htmlspecialchars($currUser->getFullName()))));
		}
		$this->formField(
			getMLText("owner"),
			array(
				'element'=>'select',
				'name'=>'owner[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'options'=>$options
			)
		);
		$options = array();
		$options[] = array('1', getMLText('search_mode_documents'), $resultmode==1);
		$options[] = array('2', getMLText('search_mode_folders'), $resultmode==2);
		$options[] = array('3', getMLText('search_resultmode_both'), $resultmode==3);
		$this->formField(
			getMLText("search_resultmode"),
			array(
				'element'=>'select',
				'name'=>'resultmode',
				'multiple'=>false,
				'options'=>$options
			)
		);
		$this->formField(getMLText("under_folder"), $this->getFolderChooserHtml("form1", M_READ, -1, $startfolder));
		$this->formField(
			getMLText("creation_date")." (".getMLText('from').")",
			$this->getDateChooser(!empty($createstartdate) ? getReadableDate($createstartdate) : null, "created[from]", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("creation_date")." (".getMLText('to').")",
			$this->getDateChooser(!empty($createenddate) ? getReadableDate($createenddate) : null, "created[to]", $this->params['session']->getLanguage())
		);
		$this->contentContainerEnd();

		// Seach options for documents and folders {{{
		/* First check if any of the folder/document filters are set. If it is,
		 * open the accordion.
		 */
		$openfilterdlg = false;
		$hasattrs = false;
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_all) {
					/* Do not check dates because they always have 'from' and 'to' element
					 * even if it is empty. FIXME should be also checked.
					 */
					$hasattrs = true;
					if(!in_array($attrdef->getType(), [SeedDMS_Core_AttributeDefinition::type_date, SeedDMS_Core_AttributeDefinition::type_int, SeedDMS_Core_AttributeDefinition::type_float]))
						if(!empty($attributes[$attrdef->getID()]))
							$openfilterdlg = true;
				}
			}
		}
		if($hasattrs) {
			ob_start();
			if($attrdefs) {
				foreach($attrdefs as $attrdef) {
					if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_all) {
						if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_date) {
							$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('from').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['from']) ? getReadableDate(makeTsFromDate($attributes[$attrdef->getID()]['from'])) : '', 'attributes', true, 'from'));
							$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('to').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['to']) ? getReadableDate(makeTsFromDate($attributes[$attrdef->getID()]['to'])) : '', 'attributes', true, 'to'));
						} elseif(in_array($attrdef->getType(), [SeedDMS_Core_AttributeDefinition::type_int, SeedDMS_Core_AttributeDefinition::type_float]) && !$attrdef->getValueSet()) {
							$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('from').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['from']) ? $attributes[$attrdef->getID()]['from'] : '', 'attributes', true, 'from'));
							$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('to').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['to']) ? $attributes[$attrdef->getID()]['to'] : '', 'attributes', true, 'to'));
						} else
							$this->formField(htmlspecialchars($attrdef->getName()), $this->getAttributeEditField($attrdef, isset($attributes[$attrdef->getID()]) ? $attributes[$attrdef->getID()] : '', 'attributes', true));
					}
				}
			}
			$content = ob_get_clean();
			$this->printAccordion(getMLText('filter_for_documents_and_folders'), $content, $openfilterdlg);
		}
		// }}}

		// Seach options for documents {{{
		/* First check if any of the folder filters are set. If it is,
		 * open the accordion.
		 */
		$openfilterdlg = false;
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_document || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_documentcontent) {
					/* Do not check dates because they always have 'from' and 'to' element
					 * even if it is empty. FIXME should be also checked.
					 */
					if(!in_array($attrdef->getType(), [SeedDMS_Core_AttributeDefinition::type_date, SeedDMS_Core_AttributeDefinition::type_int, SeedDMS_Core_AttributeDefinition::type_float]))
						if(!empty($attributes[$attrdef->getID()]))
							$openfilterdlg = true;
				}
			}
		}
		if($categories)
			$openfilterdlg = true;
		if($status)
			$openfilterdlg = true;
		if($modifyenddate || $modifystartdate)
			$openfilterdlg = true;
		if($revisionstartdate || $revisionenddate)
			$openfilterdlg = true;
		if($reception)
			$openfilterdlg = true;
		if($expenddate || $expstartdate)
			$openfilterdlg = true;
		if($statusstartdate || $statusenddate)
			$openfilterdlg = true;

		/* Start of fields only applicable to documents */
		ob_start();
		$tmpcatids = array();
		foreach($categories as $tmpcat)
			$tmpcatids[] = $tmpcat->getID();
		$options = array();
		$allcategories = $dms->getDocumentCategories();
		foreach($allcategories as $acategory) {
			$options[] = array($acategory->getID(), $acategory->getName(), in_array($acategory->getId(), $tmpcatids));
		}
		$this->formField(
			getMLText("categories"),
			array(
				'element'=>'select',
				'class'=>'chzn-select',
				'name'=>'category[]',
				'multiple'=>true,
				'attributes'=>array(array('data-placeholder', getMLText('select_category'), array('data-no_results_text', getMLText('unknown_document_category')))),
				'options'=>$options
			)
		);
		$options = array();
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
			if($workflowmode == 'traditional') { 
				$options[] = array(S_DRAFT_REV, getOverallStatusText(S_DRAFT_REV), in_array(S_DRAFT_REV, $status));
			}
		} elseif($workflowmode == 'advanced') {
			$options[] = array(S_IN_WORKFLOW, getOverallStatusText(S_IN_WORKFLOW), in_array(S_IN_WORKFLOW, $status));
		}
		$options[] = array(S_DRAFT_APP, getOverallStatusText(S_DRAFT_APP), in_array(S_DRAFT_APP, $status));
		$options[] = array(S_RELEASED, getOverallStatusText(S_RELEASED), in_array(S_RELEASED, $status));
		$options[] = array(S_REJECTED, getOverallStatusText(S_REJECTED), in_array(S_REJECTED, $status));
		$options[] = array(S_IN_REVISION, getOverallStatusText(S_IN_REVISION), in_array(S_IN_REVISION, $status));
		$options[] = array(S_EXPIRED, getOverallStatusText(S_EXPIRED), in_array(S_EXPIRED, $status));
		$options[] = array(S_OBSOLETE, getOverallStatusText(S_OBSOLETE), in_array(S_OBSOLETE, $status));
		$options[] = array(S_NEEDS_CORRECTION, getOverallStatusText(S_NEEDS_CORRECTION), in_array(S_NEEDS_CORRECTION, $status));
		$this->formField(
			getMLText("status"),
			array(
				'element'=>'select',
				'class'=>'chzn-select',
				'name'=>'status[]',
				'multiple'=>true,
				'attributes'=>array(array('data-placeholder', getMLText('select_status')), array('data-no_results_text', getMLText('unknown_status'))),
				'options'=>$options
			)
		);
		$this->formField(
			getMLText("modified")." (".getMLText('from').")",
			$this->getDateChooser(!empty($modifystartdate) ? getReadableDate($modifystartdate) : null, "modified[from]", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("modified")." (".getMLText('to').")",
			$this->getDateChooser(!empty($modifyenddate) ? getReadableDate($modifyenddate) : null, "modified[to]", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("expires")." (".getMLText('from').")",
			$this->getDateChooser($expstartdate, "expirationstart", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("expires")." (".getMLText('to').")",
			$this->getDateChooser($expenddate, "expirationend", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("revision")." (".getMLText('from').")",
			$this->getDateChooser($revisionstartdate, "revisiondatestart", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("revision")." (".getMLText('to').")",
			$this->getDateChooser($revisionenddate, "revisiondateend", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("status_change")." (".getMLText('from').")",
			$this->getDateChooser($statusstartdate, "statusdatestart", $this->params['session']->getLanguage())
		);
		$this->formField(
			getMLText("status_change")." (".getMLText('to').")",
			$this->getDateChooser($statusenddate, "statusdateend", $this->params['session']->getLanguage())
		);
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_document || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_documentcontent) {
					if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_date) {
						$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('from').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['from']) ? getReadableDate(makeTsFromDate($attributes[$attrdef->getID()]['from'])) : '', 'attributes', true, 'from'));
						$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('to').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['to']) ? getReadableDate(makeTsFromDate($attributes[$attrdef->getID()]['to'])) : '', 'attributes', true, 'to'));
					} elseif(in_array($attrdef->getType(), [SeedDMS_Core_AttributeDefinition::type_int, SeedDMS_Core_AttributeDefinition::type_float]) && !$attrdef->getValueSet()) {
						$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('from').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['from']) ? $attributes[$attrdef->getID()]['from'] : '', 'attributes', true, 'from'));
						$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('to').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['to']) ? $attributes[$attrdef->getID()]['to'] : '', 'attributes', true, 'to'));

					} else
						$this->formField(htmlspecialchars($attrdef->getName()), $this->getAttributeEditField($attrdef, isset($attributes[$attrdef->getID()]) ? $attributes[$attrdef->getID()] : '', 'attributes', true, '', true));
				}
			}
		}

		$content = ob_get_clean();
		$this->printAccordion(getMLText('filter_for_documents'), $content, $openfilterdlg);
		// }}}

		// Seach options for folders {{{
		/* First check if any of the folder filters are set. If it is,
		 * open the accordion.
		 */
		$openfilterdlg = false;
		$hasattrs = false;
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_folder) {
					$hasattrs = true;
					if(!in_array($attrdef->getType(), [SeedDMS_Core_AttributeDefinition::type_date, SeedDMS_Core_AttributeDefinition::type_int, SeedDMS_Core_AttributeDefinition::type_float]))
						if(!empty($attributes[$attrdef->getID()]))
							$openfilterdlg = true;
				}
			}
		}
		if($hasattrs) {
			ob_start();
			if($attrdefs) {
				foreach($attrdefs as $attrdef) {
					if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_folder) {
						if($attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_date) {
							$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('from').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['from']) ? getReadableDate(makeTsFromDate($attributes[$attrdef->getID()]['from'])) : '', 'attributes', true, 'from'));
							$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('to').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['to']) ? getReadableDate(makeTsFromDate($attributes[$attrdef->getID()]['to'])) : '', 'attributes', true, 'to'));
						} elseif(in_array($attrdef->getType(), [SeedDMS_Core_AttributeDefinition::type_int, SeedDMS_Core_AttributeDefinition::type_float]) && !$attrdef->getValueSet()) {
							$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('from').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['from']) ? $attributes[$attrdef->getID()]['from'] : '', 'attributes', true, 'from'));
							$this->formField(htmlspecialchars($attrdef->getName().' ('.getMLText('to').')'), $this->getAttributeEditField($attrdef, !empty($attributes[$attrdef->getID()]['to']) ? $attributes[$attrdef->getID()]['to'] : '', 'attributes', true, 'to'));

						} else
							$this->formField(htmlspecialchars($attrdef->getName()), $this->getAttributeEditField($attrdef, isset($attributes[$attrdef->getID()]) ? $attributes[$attrdef->getID()] : '', 'attributes', true, '', true));
					}
				}
			}
			$content = ob_get_clean();
			$this->printAccordion(getMLText('filter_for_folders'), $content, $openfilterdlg);
		}
		// }}}

		$this->formSubmit("<i class=\"fa fa-search\"></i> ".getMLText('search'));
?>
</form>
		</div>
<?php
		// }}}

		// Fulltext search Form {{{
		if($enablefullsearch) {
	  	echo "<div class=\"tab-pane ".(($fullsearch == true && $facetsearch == false) ? 'active' : '')."\" id=\"fulltext\">\n";
?>
<form class="form-horizontal" action="<?= $this->params['settings']->_httpRoot ?>out/out.Search.php" name="form2" style="min-height: 330px;">
<input type="hidden" name="fullsearch" value="1" />
<?php
			$this->contentContainerStart();
			$this->formField(
				getMLText("search_query"),
				array(
					'element'=>'input',
					'type'=>'search',
					'name'=>'query',
					'placeholder'=>getMLText('search_query_placeholder'),
					'value'=>htmlspecialchars($this->query)
				)
			);
			$this->formField(getMLText("under_folder"), $this->getFolderChooserHtml("form2", M_READ, -1, $startfolder, 'folderfullsearchid'));
			$options = array();
			$options[] = array('', getMLText('orderby_relevance'));
			$options[] = array('dd', getMLText('orderby_date_desc'), 'dd'==$orderby);
			$options[] = array('d', getMLText('orderby_date_asc'), 'd'==$orderby);
			$options[] = array('nd', getMLText('orderby_name_desc'), 'nd'==$orderby);
			$options[] = array('n', getMLText('orderby_name_asc'), 'n'==$orderby);
			$this->formField(
				getMLText("orderby"),
				array(
					'element'=>'select',
					'name'=>'orderby',
					'class'=>'chzn-select',
					'multiple'=>false,
					'options'=>$options
				)
			);

			$this->formField(
				getMLText("creation_date")." (".getMLText('from').")",
				$this->getDateChooser(!empty($created['from']) ? getReadableDate($created['from']) : null, "created[from]", $this->params['session']->getLanguage())
			);
			$this->formField(
				getMLText("creation_date")." (".getMLText('to').")",
				$this->getDateChooser(!empty($created['to']) ? getReadableDate($created['to']) : null, "created[to]", $this->params['session']->getLanguage())
			);
			$this->formField(
				getMLText("modification_date")." (".getMLText('from').")",
				$this->getDateChooser(!empty($modified['from']) ? getReadableDate($modified['from']) : null, "modified[from]", $this->params['session']->getLanguage())
			);
			$this->formField(
				getMLText("modification_date")." (".getMLText('to').")",
				$this->getDateChooser(!empty($modified['to']) ? getReadableDate($modified['to']) : null, "modified[to]", $this->params['session']->getLanguage())
			);
			if(!isset($facets['owner'])) {
				$options = array();
				foreach ($allUsers as $currUser) {
					if($user->isAdmin() || (!$currUser->isGuest() && (!$currUser->isHidden() || $currUser->getID() == $user->getID())))
						$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin()), in_array($currUser->getID(), $owner), array(array('data-subtitle', htmlspecialchars($currUser->getFullName()))));
				}
				$this->formField(
					getMLText("owner"),
					array(
						'element'=>'select',
						'name'=>'owner[]',
						'class'=>'chzn-select',
						'multiple'=>true,
						'options'=>$options
					)
				);
			}
			if(!isset($facets['record_type'])) {
				$options = array();
				$options[] = array('document', getMLText('document'), $record_type && in_array('document', $record_type));
				$options[] = array('folder', getMLText('folder'), $record_type && in_array('folder', $record_type));
				$this->formField(
					getMLText("record_type"),
					array(
						'element'=>'select',
						'class'=>'chzn-select',
						'name'=>'record_type[]',
						'multiple'=>true,
						'attributes'=>array(array('data-placeholder', getMLText('select_record_type'))),
						'options'=>$options
					)
				);
			}
			if(!isset($facets['category'])) {
				$tmpcatids = array();
				foreach($categories as $tmpcat)
					$tmpcatids[] = $tmpcat->getID();
				$options = array();
				$allcategories = $dms->getDocumentCategories();
				foreach($allcategories as $acategory) {
					$options[] = array($acategory->getID(), $acategory->getName(), in_array($acategory->getId(), $tmpcatids));
				}
				$this->formField(
					getMLText("category_filter"),
					array(
						'element'=>'select',
						'class'=>'chzn-select',
						'name'=>'category[]',
						'multiple'=>true,
						'attributes'=>array(array('data-placeholder', getMLText('select_category'), array('data-no_results_text', getMLText('unknown_document_category')))),
						'options'=>$options
					)
				);
			}
			if(!isset($facets['status'])) {
				$options = array();
				if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
					if($workflowmode == 'traditional') { 
						$options[] = array(S_DRAFT_REV, getOverallStatusText(S_DRAFT_REV), in_array(S_DRAFT_REV, $status));
					}
				} elseif($workflowmode == 'advanced') {
					$options[] = array(S_IN_WORKFLOW, getOverallStatusText(S_IN_WORKFLOW), in_array(S_IN_WORKFLOW, $status));
				}
				$options[] = array(S_DRAFT_APP, getOverallStatusText(S_DRAFT_APP), in_array(S_DRAFT_APP, $status));
				$options[] = array(S_RELEASED, getOverallStatusText(S_RELEASED), in_array(S_RELEASED, $status));
				$options[] = array(S_REJECTED, getOverallStatusText(S_REJECTED), in_array(S_REJECTED, $status));
				$options[] = array(S_EXPIRED, getOverallStatusText(S_EXPIRED), in_array(S_EXPIRED, $status));
				$options[] = array(S_OBSOLETE, getOverallStatusText(S_OBSOLETE), in_array(S_OBSOLETE, $status));
				$this->formField(
					getMLText("status"),
					array(
						'element'=>'select',
						'class'=>'chzn-select',
						'name'=>'status[]',
						'multiple'=>true,
						'attributes'=>array(array('data-placeholder', getMLText('select_status')), array('data-no_results_text', getMLText('unknown_status'))),
						'options'=>$options
					)
				);
			}

			if($facets) {
				foreach($facets as $facetname=>$values) {
					$multiple = true;
					$options = array();
					if($facetname == 'owner') {
						foreach($values as $v=>$c) {
							$uu = $dms->getUserByLogin($v);
							if($uu) {
								$option = array($uu->getId(), $v);
								if(isset(${$facetname}) && in_array($uu->getId(), ${$facetname}))
									$option[] = true;
								else
									$option[] = false;
								$option[] = array(array('data-subtitle', $c.' ×'));
								$options[] = $option;
							}
						}
					} elseif($facetname == 'category') {
						foreach($values as $v=>$c) {
							$cat = $dms->getDocumentCategoryByName($v);
							if($cat) {
								$option = array($cat->getId(), $v);
								if(isset(${$facetname}) && in_array($cat->getId(), ${$facetname}))
									$option[] = true;
								else
									$option[] = false;
								$option[] = array(array('data-subtitle', $c.' ×'));
								$options[] = $option;
							}
						}
					} elseif($facetname == 'status') {
						foreach($values as $v=>$c) {
							$option = array($v, getOverallStatusText($v)/*.' ('.$c.')'*/);
							if(isset(${$facetname}) && in_array($v, ${$facetname}))
								$option[] = true;
							else
								$option[] = false;
							$option[] = array(array('data-subtitle', $c.' ×'));
							$options[] = $option;
						}
					} elseif(substr($facetname, 0, 5) == 'attr_' || $facetname == 'created' || $facetname == 'modified') {
						/* Do not even create a list of options, because it isn't used */
					} else {
						foreach($values as $v=>$c) {
							$option = array($v, $v);
							if(isset(${$facetname}) && in_array($v, ${$facetname}))
								$option[] = true;
							else
								$option[] = false;
							$option[] = array(array('data-subtitle', $c.' ×'));
							$options[] = $option;
						}
					}
					if(substr($facetname, 0, 5) != 'attr_' && $facetname != 'created' && $facetname != 'modified') {
						$this->formField(
							getMLText($facetname),
							array(
								'element'=>'select',
								'id'=>$facetname,
								'name'=>$facetname."[]",
								'class'=>'chzn-select',
								'attributes'=>array(array('data-placeholder', getMLText('select_'.$facetname)), array('data-allow-clear', 'true')),
								'options'=>$options,
								'multiple'=>$multiple
							)
						);
					}
				}
				foreach($facets as $facetname=>$values) {
					if(substr($facetname, 0, 5) == 'attr_') {
						/* If the facet is empty, don't show the input field */
						if($values) {
						$tmp = explode('_', $facetname);
						if($attrdef = $dms->getAttributeDefinition($tmp[1])) {
							$dispname = $attrdef->getName();
							switch($attrdef->getType()) {
							case 556: //SeedDMS_Core_AttributeDefinition::type_int:
								$this->formField(
									$dispname.' ('.getMLText('from').')',
									array(
										'element'=>'input',
										'type'=>'number',
										'id'=>$facetname,
										'name'=>'attributes['.$facetname.'][from]',
										'placeholder'=>implode(' ', array_keys($values)),
									)
								);
								$this->formField(
									$dispname.' ('.getMLText('to').')',
									array(
										'element'=>'input',
										'type'=>'number',
										'id'=>$facetname,
										'name'=>'attributes['.$facetname.'][to]',
										'placeholder'=>implode(' ', array_keys($values)),
									)
								);
								break;
							default:
								$options = [];
								foreach($values as $v=>$c) {
									switch($attrdef->getType()) {
									case SeedDMS_Core_AttributeDefinition::type_date:
										$option = array($v, getReadableDate($v));
										break;
									default:
										$option = array($v, $v);
									}
									if(isset($attributes[$facetname]) && in_array($v, $attributes[$facetname]))
										$option[] = true;
									else
										$option[] = false;
									$option[] = array(array('data-subtitle', $c.' ×'));
									$options[] = $option;
								}

								if($options) {
									$this->formField(
										$dispname,
										array(
											'element'=>'select',
											'id'=>$facetname,
											'name'=>'attributes['.$facetname.'][]',
											'class'=>'chzn-select',
											'attributes'=>array(array('data-placeholder', $dispname), array('data-allow-clear', 'true')),
											'options'=>$options,
											'multiple'=>$multiple
										)
									);
								}
							}
						}
						}
					}
				}
			}
			$this->contentContainerEnd();
			$this->formSubmit("<i class=\"fa fa-search\"></i> ".getMLText('search'));
?>
</form>
<?php
			echo "</div>\n";
		}
		// }}}

		// Fulltext search with facets Form {{{
		if($enablefacetsearch) {
	  	echo "<div class=\"tab-pane ".(($fullsearch == true && $facetsearch == true) ? 'active' : '')."\" id=\"facetfulltext\">\n";
?>
<form class="form-horizontal" action="<?= $this->params['settings']->_httpRoot ?>out/out.Search.php" name="form2">
<input type="hidden" name="fullsearch" value="1" />
<input type="hidden" name="facetsearch" value="1" />
<?php
			$this->contentContainerStart();
			$this->formField(
				getMLText("search_query"),
				array(
					'element'=>'input',
					'type'=>'search',
					'name'=>'query',
					'value'=>htmlspecialchars($this->query),
					'placeholder'=>getMLText('search_query_placeholder'),
				)
			);
			$this->formField(getMLText("under_folder"), $this->getFolderChooserHtml("form3", M_READ, -1, $startfolder, 'folderfullsearchid'));

			$options = array();
			$options[] = array('', getMLText('orderby_relevance'));
			$options[] = array('dd', getMLText('orderby_date_desc'), 'dd'==$orderby);
			$options[] = array('d', getMLText('orderby_date_asc'), 'd'==$orderby);
			$options[] = array('nd', getMLText('orderby_name_desc'), 'nd'==$orderby);
			$options[] = array('n', getMLText('orderby_name_asc'), 'n'==$orderby);
			$this->formField(
				getMLText("orderby"),
				array(
					'element'=>'select',
					'name'=>'orderby',
					'class'=>'chzn-select',
					'multiple'=>false,
					'options'=>$options
				)
			);

			$this->contentContainerEnd();

			$menuitems = [];
			if($facets) {
				foreach($facets as $facetname=>$values) {
					if($values) {
					if(substr($facetname, 0, 5) == 'attr_') {
						$tmp = explode('_', $facetname);
						if($attrdef = $dms->getAttributeDefinition($tmp[1])) {
							$dispname = $attrdef->getName();
							/* Create a link to remove the filter */
							$allparams = $request->query->all();
							if(isset($allparams['attributes'][$facetname])) {
								if(isset($allparams['attributes'][$facetname]['to']) && isset($allparams['attributes'][$facetname]['from'])) {
									$oldvalue = $allparams['attributes'][$facetname];
									if(!empty($oldvalue['from']) || !empty($oldvalue['to'])) {
										unset($allparams['attributes'][$facetname]);
										$newrequest = Symfony\Component\HttpFoundation\Request::create($request->getBaseUrl(), 'GET', $allparams);
										$menuitems[] = array('label'=>'<i class="fa fa-remove"></i> '.$dispname.' = '.$oldvalue['from'].' TO '.$oldvalue['to'], 'link'=>$newrequest->getRequestUri(), 'attributes'=>[['title', 'Click to remove']], '_badge'=>'x');
										echo '<input type="hidden" name="attributes['.$facetname.'][from]" value="'.$oldvalue['from'].'" />';
										echo '<input type="hidden" name="attributes['.$facetname.'][to]" value="'.$oldvalue['to'].'" />';
									}
								} else {
									if(is_array($allparams['attributes'][$facetname])) {
										switch($attrdef->getType()) {
										case SeedDMS_Core_AttributeDefinition::type_date:
											array_walk($allparams['attributes'][$facetname], function(&$v, $k){$v=getReadableDate($v);});
											break;
										}
										$oldvalue = $allparams['attributes'][$facetname];
									} else {
										$oldvalue = [$allparams['attributes'][$facetname]];
									}
									unset($allparams['attributes'][$facetname]);
									$newrequest = Symfony\Component\HttpFoundation\Request::create($request->getBaseUrl(), 'GET', $allparams);
									$menuitems[] = array('label'=>'<i class="fa fa-remove"></i> '.$dispname.' = '.implode(', ', $oldvalue), 'link'=>$newrequest->getRequestUri(), 'attributes'=>[['title', 'Click to remove']], '_badge'=>'x');
									foreach($oldvalue as $ov)
										echo '<input type="hidden" name="attributes['.$facetname.'][]" value="'.$ov.'" />';
								}
							}
						}
					} else {
						/* Create a link to remove the filter */
						$allparams = $request->query->all();
						if(isset($allparams[$facetname])) {
							switch($facetname) {
							case 'category':
								$oldvalue = is_array($allparams[$facetname]) ? $allparams[$facetname] : [$allparams[$facetname]];
								$oldtransval = [];
								foreach($oldvalue as $v) {
									if(is_numeric($v))
										$fu = $dms->getDocumentCategory($v);
									else
										$fu = $dms->getDocumentCategoryByName($v);
									if($fu)
										$oldtransval[] = $fu->getName();
								}
								break;
							case 'owner':
								$oldvalue = is_array($allparams[$facetname]) ? $allparams[$facetname] : [$allparams[$facetname]];
								$oldtransval = [];
								foreach($oldvalue as $v) {
									if(is_numeric($v))
										$fu = $dms->getUser($v);
									else
										$fu = $dms->getUserByLogin($v);
									if($fu)
										$oldtransval[] = $fu->getLogin();
								}
								break;
							case 'status':
								$oldvalue = is_array($allparams[$facetname]) ? $allparams[$facetname] : [$allparams[$facetname]];
								$oldtransval = $oldvalue;
								array_walk($oldtransval, function(&$v, $k){$v = getOverallStatusText($v);});
								break;
							case 'created':
							case 'modified':
								if(!empty($allparams[$facetname]['from']) || !empty($allparams[$facetname]['to'])) {
									array_walk($allparams[$facetname], function(&$v, $k){$v=getReadableDate($v);});
									$oldvalue = $allparams[$facetname];
									$oldtransval = $oldvalue; //$oldvalue['from'].' TO '.$oldvalue['to'];
								} else {
									$oldvalue = null;
								}
								break;
							default:
								$oldvalue = is_array($allparams[$facetname]) ? $allparams[$facetname] : [$allparams[$facetname]];
								$oldtransval = $oldvalue;
							}
							if($oldvalue) {
								unset($allparams[$facetname]);
								$newrequest = Symfony\Component\HttpFoundation\Request::create($request->getBaseUrl(), 'GET', $allparams);
								$menuitems[] = array('label'=>'<i class="fa fa-remove"></i> '.getMLText($facetname).' = '.implode(', ', $oldtransval), 'link'=>$newrequest->getRequestUri(), 'attributes'=>[['title', 'Click to remove']], '_badge'=>'x');
								foreach($oldvalue as $ok=>$ov)
									echo '<input type="hidden" name="'.$facetname.'['.$ok.']" value="'.$ov.'" />';
							}
						}
					}
					}
				}
			}

			/* Create remove links for query 'notset'. The don't have any facet
			 * values and will not show up in the lists created above.
			 * This currently just workѕ for attributes
			 */
			$allparams = $request->query->all();
			if(isset($allparams['attributes'])) {
				foreach($allparams['attributes'] as $an=>$av) {
					if(is_string($av) && $av == '__notset__') {
						$tmp = explode('_', $an);
						if($attrdef = $dms->getAttributeDefinition($tmp[1])) {
							$dispname = $attrdef->getName();
							unset($allparams['attributes'][$an]);
							$newrequest = Symfony\Component\HttpFoundation\Request::create($request->getBaseUrl(), 'GET', $allparams);
							$menuitems[] = array('label'=>'<i class="fa fa-remove"></i> '.$dispname.' is not set', 'link'=>$newrequest->getRequestUri(), 'attributes'=>[['title', 'Click to remove']], '_badge'=>'x');
							echo '<input type="hidden" name="attributes['.$an.']" value="__notset__" />';
						}
					}
				}
			}

			if($menuitems) {
				self::showNavigationListWithBadges($menuitems);
			}

			echo "<p></p>";
			$this->formSubmit("<i class=\"fa fa-search\"></i> ".getMLText('search'));
			echo "<p></p>";
			if($facets) {
				$allparams = $request->query->all();
				if(!isset($allparams['fullsearch']))
					$allparams['fullsearch'] = 1;
				if(!isset($allparams['facetsearch']))
					$allparams['facetsearch'] = 1;
				$newrequest = Symfony\Component\HttpFoundation\Request::create($request->getBaseUrl(), 'GET', $allparams);
				foreach($facets as $facetname=>$values) {
					if(substr($facetname, 0, 5) == 'attr_') {
						$tmp = explode('_', $facetname);
						if($attrdef = $dms->getAttributeDefinition($tmp[1])) {
							$dispname = $attrdef->getName();
							switch($attrdef->getType()) {
							case SeedDMS_Core_AttributeDefinition::type_int:
							case SeedDMS_Core_AttributeDefinition::type_float:
								/* See below on an explaination for the if statement */
								if($values && (count($values) > 1 || reset($values) < $total)) {
									if(empty($allparams['attributes'][$facetname]['from']) && empty($allparams['attributes'][$facetname]['to'])) {
										$tt = array_keys($values);
										$content = '';
										$content .= '<p><a href="'.$newrequest->getRequestUri().'&attributes['.$facetname.']=__notset__">'.getMLText('objects_without_attribute').'</a></p>';
										$content .= '<div class="input-group">';
										$content .= '<span class="input-group-text" style="border-right: 0;"> from </span>';
										$content .= '<input type="number" class="form-control" name="attributes['.$facetname.'][from]" value="" placeholder="'.min($tt).'" />';
										$content .= '<span class="input-group-text" style="border-left: 0; border-right: 0;"> to </span>';
										$content .= '<input type="number" class="form-control" name="attributes['.$facetname.'][to]" value="" placeholder="'.max($tt).'" />';
										$content .= '<button class="btn btn-primary" type="submit">Set</button>';
										$content .= '</div>';
										$this->printAccordion($dispname, $content);
									}
								}
								break;
							case SeedDMS_Core_AttributeDefinition::type_date:
								if($values && (count($values) > 1 || reset($values) < $total)) {
									if(empty($allparams['attributes'][$facetname]['from']) && empty($allparams['attributes'][$facetname]['to'])) {
										$tt = array_keys($values);
										$content = '';
										$content .= '<p><a href="'.$newrequest->getRequestUri().'&attributes['.$facetname.']=__notset__">'.getMLText('objects_without_attribute').'</a></p>';
										$content .= '<div class="input-group">';
										$content .= '<span class="input-group-text" style="border-right: 0;"> from </span>';
										$content .= $this->getDateChooser('', "attributes[".$facetname."][from]", $this->params['session']->getLanguage(), '', getReadableDate(min($tt)), getReadableDate(max($tt)), null, '', true);
										$content .= '<span class="input-group-text" style="border-left: 0; border-right: 0;"> to </span>';
										$content .= $this->getDateChooser('', "attributes[".$facetname."][to]", $this->params['session']->getLanguage(), '', getReadableDate(min($tt)), getReadableDate(max($tt)), null, '', true);
										$content .= '<button class="btn btn-primary" type="submit">Set</button>';
										$content .= '</div>';
										$this->printAccordion($dispname, $content);
									}
								}
								break;
							default:
								/* See below on an explaination for the if statement */
								if($values && (count($values) > 1 || reset($values) < $total)) {
									$menuitems = array();
									$menuitems[] = array('label'=>getMLText('no_value_set'), 'link'=>$newrequest->getRequestUri().'&attributes['.$facetname.']=__notset__');
									arsort($values);
									foreach($values as $v=>$c) {
										switch($attrdef->getType()) {
										case SeedDMS_Core_AttributeDefinition::type_date:
											$menuitems[] = array('label'=>getReadableDate($v), 'link'=>$newrequest->getRequestUri().'&attributes['.$facetname.'][]='.urlencode($v), 'badge'=>$c);
											break;
										default:
											$menuitems[] = array('label'=>htmlspecialchars($v), 'link'=>$newrequest->getRequestUri().'&attributes['.$facetname.'][]='.urlencode($v), 'badge'=>$c);
										}
									}
									ob_start();
									self::showNavigationListWithBadges($menuitems);
									$content = ob_get_clean();
									$this->printAccordion($dispname, $content);
								}
							}
						}
					} elseif($facetname == 'created' || $facetname == 'modified') {
						if(empty($allparams[$facetname]['from']) && empty($allparams[$facetname]['to'])) {
							$tt = array_keys($values);
							$content = '<div class="input-group">';
							$content .= '<span class="input-group-text" style="border-right: 0;"> from </span>';
							$content .= $this->getDateChooser('', $facetname."[from]", $this->params['session']->getLanguage(), '', '' /*getReadableDate(min($tt))*/, getReadableDate(time()), null, '', true);
							$content .= '<span class="input-group-text" style="border-left: 0; border-right: 0;"> to </span>';
							$content .= $this->getDateChooser('', $facetname."[to]", $this->params['session']->getLanguage(), '', '' /*getReadableDate(min($tt))*/, getReadableDate(time()), null, '', true);
							$content .= '<button class="btn btn-primary" type="submit">Set</button>';
							$content .= '</div>';
							if($facetname == 'created')
								$this->printAccordion(getMLText('creation_date'), $content);
							elseif($facetname == 'modified') {
								$this->printAccordion(getMLText('modification_date'), $content);
							}
						}
					} else {
						/* Further filter makes only sense if the facet has more than 1 value
						 * or in case of 1 value, if that value has a count < $total. That second
						 * case will reduce the result set on those objects which have the field
						 * actually set.
						 */
						if($values && (count($values) > 1 || reset($values) < $total)) {
							$menuitems = array();
							arsort($values);
							switch($facetname) {
							case 'status':
								foreach($values as $v=>$c) {
									$menuitems[] = array('label'=>getOverallStatusText($v), 'link'=>$newrequest->getRequestUri().'&'.$facetname.'[]='.urlencode($v), 'badge'=>$c);
								}
								break;
							case 'owner':
								foreach($values as $v=>$c) {
									if($fu = $dms->getUserByLogin($v))
										$menuitems[] = array('label'=>$fu->getLogin(), 'link'=>$newrequest->getRequestUri().'&'.$facetname.'[]='.$fu->getId(), 'badge'=>$c);
								}
								break;
							default:
								foreach($values as $v=>$c) {
									$menuitems[] = array('label'=>htmlspecialchars($v), 'link'=>$newrequest->getRequestUri().'&'.$facetname.'[]='.urlencode($v), 'badge'=>$c);
								}
							}
							ob_start();
							self::showNavigationListWithBadges($menuitems);
							$content = ob_get_clean();
							$this->printAccordion(getMLText($facetname), $content);
						}
					}
				}
			}
//			echo "<pre>";
//			print_r($facets);
//			echo "</pre>";
?>
</form>
<?php
			echo "</div>\n";
		}
		// }}}
?>
	</div>
<?php
		if($this->query) {
			echo "</div>\n";
		}

		/* Batch operations {{{ */
		if($total)
			$this->contentHeading(getMLText('batch_operation'));
		if($totaldocs) {
			ob_start();
			$this->formField(
				getMLText("include_content"),
				array(
					'element'=>'input',
					'type'=>'checkbox',
					'name'=>'includecontent',
					'id'=>'includecontent',
					'value'=>1,
				)
			);
			//$this->formSubmit("<i class=\"fa fa-download\"></i> ".getMLText('export'));
			print $this->html_link('Search', array_merge($_GET, array('action'=>'export')), array('class'=>'btn btn-primary', 'id'=>'export'), "<i class=\"fa fa-download\"></i> ".getMLText("export"), false, true)."\n";
			$content = ob_get_clean();
			$this->printAccordion(getMLText('export'), $content);
		}

		if($user->isAdmin() && $total) {
			ob_start();
			$users = $dms->getAllUsers();
			$options = array();
			$options[] = array("-1", getMLText("choose_user"));
			foreach ($users as $currUser) {
				$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin().' - '.$currUser->getFullName()), false, array(array('data-subtitle', htmlspecialchars($currUser->getEmail()))));
			}
			$this->formField(
				null, //getMLText("selection"),
				array(
					'element'=>'select',
					'id'=>'newowner',
					'class'=>'chzn-select',
					'options'=>$options,
					'placeholder'=>getMLText('select_users'),
					'attributes'=>array(array('style', 'width: 100%;'))
				)
			);
//			print $this->html_link('Search', array_merge($_GET, array('action'=>'changeowner')), array('class'=>'btn btn-primary', 'id'=>'changeowner'), "<i class=\"fa fa-user\"></i> ".getMLText("batch_change_owner"), false, true)."\n";

			print $this->html_link('Search', array_merge($_GET, array('action'=>'changeowner')), array('class'=>'btn btn-primary change-owner-btn mt-4', 'confirmmsg'=>htmlspecialchars(getMLText("confirm_change_owner", array ()), ENT_QUOTES)), "<i class=\"fa fa-user\"></i> ".getMLText("batch_change_owner"), false, true)."\n";

			$content = ob_get_clean();
			$this->printAccordion(getMLText('batch_change_owner'), $content);

			ob_start();
			$cats = $dms->getDocumentCategories();
			if($cats) {
				$options = array();
				$options[] = array("-1", getMLText("choose_category"));
				foreach ($cats as $currcat) {
					$options[] = array($currcat->getID(), htmlspecialchars($currcat->getName()), false);
				}
				$this->formField(
					null, 
					array(
						'element'=>'select',
						'id'=>'batchcategory',
						'class'=>'chzn-select',
						'options'=>$options,
						'multiple'=>false,
						'placeholder'=>getMLText('select_category'),
						'attributes'=>array(array('style', 'width: 100%;'))
					)
				);
				$this->formField(
					getMLText("batch_remove_category"),
					array(
						'element'=>'input',
						'type'=>'checkbox',
						'id'=>'removecategory',
						'value'=>'1',
					)
				);

				print $this->html_link('Search', array_merge($_GET, array('action'=>'changecategory')), array('class'=>'btn btn-primary change-category-btn mt-4', 'id'=>'changecategory'), "<i class=\"fa fa-user\"></i> ".getMLText("batch_change_category"), false, true)."\n";

				$content = ob_get_clean();
				$this->printAccordion(getMLText('batch_change_category'), $content);
			}
		}
		// }}}

?>
<?php
		$this->columnEnd();
		$this->columnStart(8);
		if($batchmsg = $this->getParam('batchmsg')) {
			$this->contentHeading(getMLText('batch_operation_result'));
			echo $this->infoMsg($batchmsg);
		}
		$this->contentHeading(getMLText('search_results'));
// Search Result {{{
		$foldercount = $doccount = 0;
		if($entries) {
			/*
			foreach ($entries as $entry) {
				if($entry->isType('document')) {
					$doccount++;
				} elseif($entry->isType('document')) {
					$foldercount++;
				}
			}
			 */
			echo $this->infoMsg(getMLText("search_report", array("count"=>$total, "doccount" => $totaldocs, "foldercount" => $totalfolders, 'searchtime'=>$searchTime)));
			$this->pageList((int) $pageNumber, $totalpages, "../out/out.Search.php", $urlparams);
//			$this->contentContainerStart();

			$txt = $this->callHook('searchListHeader', $orderby, 'asc');
			if(is_string($txt)) {
				echo $txt;
			} elseif(is_array($txt)) {
				print "<table class=\"table table-condensed table-sm table-hover\">";
				print "<thead>\n<tr>\n";
				foreach($txt as $headcol)
					echo "<th>".$headcol."</th>\n";
				print "</tr>\n</thead>\n";
			} else {
				echo $this->folderListHeader(null, 'search');
			}
			print "<tbody>\n";

			$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
			if($conversionmgr)
				$previewer->setConversionMgr($conversionmgr);
			else
				$previewer->setConverters($previewconverters);
			foreach ($entries as $entry) {
				if($entry->isType('document')) {
					$document = $entry;
					if($lc = $document->getLatestContent())
						$previewer->createPreview($lc);

					$lcattributes = $lc ? $lc->getAttributes() : null;
					$attrstr = '';
					if($lcattributes) {
						$attrstr .= "<table class=\"table table-condensed table-sm\">\n";
						$attrstr .= "<tr><th>".getMLText('name')."</th><th>".getMLText('attribute_value')."</th></tr>";
						foreach($lcattributes as $lcattribute) {
							$arr = $this->callHook('showDocumentContentAttribute', $lc, $lcattribute);
							if(is_array($arr)) {
								$attrstr .= "<tr>";
								$attrstr .= "<td>".$arr[0].":</td>";
								$attrstr .= "<td>".$arr[1]."</td>";
								$attrstr .= "</tr>";
							} elseif(is_string($arr)) {
								$attrstr .= $arr;
							} else {
								$attrdef = $lcattribute->getAttributeDefinition();
								$attrstr .= "<tr><td>".htmlspecialchars($attrdef->getName())."</td><td>".htmlspecialchars(implode(', ', $lcattribute->getValueAsArray()))."</td></tr>\n";
								// TODO: better use printAttribute()
								// $this->printAttribute($lcattribute);
							}
						}
						$attrstr .= "</table>\n";
					}
					$docattributes = $document->getAttributes();
					if($docattributes) {
						$attrstr .= "<table class=\"table table-condensed table-sm\">\n";
						$attrstr .= "<tr><th>".getMLText('name')."</th><th>".getMLText('attribute_value')."</th></tr>";
						foreach($docattributes as $docattribute) {
							$arr = $this->callHook('showDocumentAttribute', $document, $docattribute);
							if(is_array($arr)) {
								$attrstr .= "<tr>";
								$attrstr .= "<td>".$arr[0].":</td>";
								$attrstr .= "<td>".$arr[1]."</td>";
								$attrstr .= "</tr>";
							} elseif(is_string($arr)) {
								$attrstr .= $arr;
							} else {
								$attrdef = $docattribute->getAttributeDefinition();
								$attrstr .= "<tr><td>".htmlspecialchars($attrdef->getName())."</td><td>".htmlspecialchars(implode(', ', $docattribute->getValueAsArray()))."</td></tr>\n";
							}
						}
						$attrstr .= "</table>\n";
					}
					$extracontent = array();
					$extracontent['below_title'] = $this->getListRowPath($document);
					if($attrstr)
						$extracontent['bottom_title'] = '<br />'.$this->printPopupBox('<span class="btn btn-mini btn-sm btn-secondary">'.getMLText('attributes').'</span>', $attrstr, true);
					$extracontent['end_action_list'] = $this->printMarkDocumentButton($document, true);

					$txt = $this->callHook('documentListItem', $entry, $previewer, false, 'search', $extracontent);
					if(is_string($txt))
						echo $txt;
					else {
						print $this->documentListRow($document, $previewer, false, 0, $extracontent);
					}
				} elseif($entry->isType('folder')) {
					$txt = $this->callHook('folderListItem', $entry, false, 'search');
					if(is_string($txt))
						echo $txt;
					else {
					$folder = $entry;

					$attrstr = '';
					$folderattributes = $folder->getAttributes();
					if($folderattributes) {
						$attrstr .= "<table class=\"table table-condensed table-sm\">\n";
						$attrstr .= "<tr><th>".getMLText('name')."</th><th>".getMLText('attribute_value')."</th></tr>";
						foreach($folderattributes as $folderattribute) {
							$attrdef = $folderattribute->getAttributeDefinition();
							$attrstr .= "<tr><td>".htmlspecialchars($attrdef->getName())."</td><td>".htmlspecialchars(implode(', ', $folderattribute->getValueAsArray()))."</td></tr>\n";
						}
						$attrstr .= "</table>";
					}
					$extracontent = array();
					$extracontent['below_title'] = $this->getListRowPath($folder);
					if($attrstr)
						$extracontent['bottom_title'] = '<br />'.$this->printPopupBox('<span class="btn btn-mini btn-sm btn-secondary">'.getMLText('attributes').'</span>', $attrstr, true);
					$extracontent['end_action_list'] = $this->printMarkFolderButton($folder, true);
					print $this->folderListRow($folder, false, $extracontent);
					}
				}
			}
			print "</tbody></table>\n";
//			$this->contentContainerEnd();
			$this->pageList((int) $pageNumber, $totalpages, "../out/out.Search.php", $_GET);
		} else {
			$numResults = $totaldocs + $totalfolders;
			if ($numResults == 0) {
				echo $this->warningMsg(getMLText("search_no_results"));
			}
		}
// }}}
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
