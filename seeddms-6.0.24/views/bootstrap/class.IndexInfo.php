<?php
/**
 * Implementation of IndexInfo view
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
 * Class which outputs the html page for IndexInfo view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_IndexInfo extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$index = $this->params['index'];

		$this->htmlStartPage(getMLText('fulltext_info'));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("fulltext_info"));

		$numDocs = $index->count();
		echo "<legend>".$numDocs." ".getMLText('documents')."</legend>";
		/*
		$this->contentContainerStart('fulltextinfo');
		for ($id = 0; $id < $numDocs; $id++) {
			if (!$index->isDeleted($id)) {
				if($hit = $index->getDocument($id))
					echo "<span title=\"".$hit->document_id."\">".htmlspecialchars($hit->title)."</span> ";
			}
		}
		$this->contentContainerEnd();
		 */

		$terms = $index->terms();
		echo "<legend>".count($terms)." overall Terms</legend>";
//		echo "<pre>";
		$field = '';
		foreach($terms as $term) {
			if($field != $term->field) {
				if($field)
					$this->contentContainerEnd();
				echo "<h5>".htmlspecialchars($term->field)."</h5>";
				$this->contentContainerStart('fulltextinfo');
				$field = $term->field;
			}
			echo '<span title="'.$term->_occurrence.'">'.htmlspecialchars($term->text)."</span> ";
//			echo "<span title=\"".$term->_occurrence."\">".htmlspecialchars($term->text)."</span>\n";
		}
		$this->contentContainerEnd();
//		echo "</pre>";

		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
