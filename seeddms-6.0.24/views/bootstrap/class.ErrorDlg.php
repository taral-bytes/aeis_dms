<?php
/**
 * Implementation of ErrorDlg view
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
 * Class which outputs the html page for ErrorDlg view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ErrorDlg extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$pagetitle = $this->params['pagetitle'];
		$errormsg = $this->params['errormsg'];
		$plain = $this->params['plain'];
		$showbutton = $this->hasParam('nobackbutton') === false || $this->getParam('nobackbutton') === false;
		$settings = $this->params['settings'];

		if(!$plain) {
			$this->htmlStartPage($pagetitle, 'errorpage', $settings->_httpRoot."out/");
			$this->globalNavigation();
			$this->contentStart();
		}

		print "<h4>".getMLText('error')."!</h4>";
		$this->errorMsg(htmlspecialchars($errormsg));
		if($showbutton)
			print "<div><button class=\"btn btn-primary history-back\">".getMLText('back')."</button></div>";
		
		$this->contentEnd();
		$this->htmlEndPage();
		
		add_log_line(" UI::exitError error=".$errormsg." pagetitle=".$pagetitle, PEAR_LOG_ERR);

		return;
	} /* }}} */
}
?>
