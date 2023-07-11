<?php
/**
 * Implementation of FolderChooser view
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
 * Class which outputs the html page for FolderChooser view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_FolderChooser extends SeedDMS_Theme_Style {

	public function subtree() { /* {{{ */
		$user = $this->params['user'];
		$node = $this->params['node'];
		$orderby = $this->params['orderby'];

		$this->printNewTreeNavigationSubtree($node->getID(), 0, $orderby);
	} /* }}} */

	function js() { /* {{{ */
		$dms = $this->params['dms'];
		$rootfolderid = $this->params['rootfolderid'];
		$form = $this->params['form'];
		$mode = $this->params['mode'];
		$orderby = $this->params['orderby'];

		header('Content-Type: application/javascript; charset=UTF-8');
		$this->printNewTreeNavigationJs($dms->getRootFolder()->getId()/*$rootfolderid*/, $mode, 0, $form, 0, $orderby);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$mode = $this->params['mode'];
		$orderby = $this->params['orderby'];
		$exclude = $this->params['exclude'];
		$form = $this->params['form'];
		$rootfolderid = $this->params['rootfolderid'];

//		$this->htmlStartPage(getMLText("choose_target_folder"));
		$this->printNewTreeNavigationHtml($dms->getRootFolder()->getId()/*$rootfolderid*/, $mode, 0, $form, 0, $orderby);
		echo '<script src="../out/out.FolderChooser.php?action=js&'.$_SERVER['QUERY_STRING'].'"></script>'."\n";
//		$this->htmlEndPage(true);
	} /* }}} */
}
?>
