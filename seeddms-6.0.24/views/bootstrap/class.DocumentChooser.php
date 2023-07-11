<?php
/**
 * Implementation of DocumentChooser view
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
 * Class which outputs the html page for DocumentChooser view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_DocumentChooser extends SeedDMS_Theme_Style {

	public function subtree() { /* {{{ */
		$user = $this->params['user'];
		$node = $this->params['node'];
		$orderby = $this->params['orderby'];

		$this->printNewTreeNavigationSubtree($node->getID(), 1, $orderby);
	} /* }}} */

	function js() { /* {{{ */
		$folder = $this->params['folder'];
		$form = $this->params['form'];
		$orderby = $this->params['orderby'];
		$partialtree = $this->params['partialtree'];

		header('Content-Type: application/javascript; charset=UTF-8');
		if($folder)
			$this->printNewTreeNavigationJs($folder->getID(), M_READ, 1, $form, 0, $orderby, $partialtree);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$form = $this->params['form'];
		$partialtree = $this->params['partialtree'];
		$orderby = $this->params['orderby'];

//		$this->htmlStartPage(getMLText("choose_target_document"));
//		$this->contentContainerStart();
//		$this->printNewTreeNavigationHtml($folder->getID(), M_READ, 1, $form);
		if($folder) {
			$this->printNewTreeNavigationHtml($folder->getID(), M_READ, 1, $form, 0, $orderby);
			echo '<script src="../out/out.DocumentChooser.php?action=js&'.$_SERVER['QUERY_STRING'].'"></script>'."\n";
		}
//		$this->contentContainerEnd();
//		$this->htmlEndPage(true);
	} /* }}} */
}
?>
