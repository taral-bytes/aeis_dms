<?php
/**
 * Implementation of ImportUsers view
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
 * Class which outputs the html page for ImportUsers view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ImportUsers extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
		$this->printFileChooserJs();
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$accessobject = $this->params['accessobject'];
		$log = $this->params['log'];
		$newusers = $this->params['newusers'];
		$colmap = $this->params['colmap'];

		$this->htmlStartPage(getMLText("import_users"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->contentHeading(getMLText("import_users"));

		$this->rowStart();
		$this->columnStart(4);
		print "<form class=\"form-horizontal\" action=\"../op/op.ImportUsers.php\" name=\"form1\" enctype=\"multipart/form-data\" method=\"post\">";
		$this->contentContainerStart();
		$this->formField(
			getMLText("userdata_file"),
			$this->getFileChooserHtml('userdata', false)
		);
		$this->formField(
			getMLText("import_users_update"),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'update',
				'value'=>'1'
			)
		);
		$this->formField(
			getMLText("import_users_addnew"),
			array(
				'element'=>'input',
				'type'=>'checkbox',
				'name'=>'addnew',
				'value'=>'1'
			)
		);
		$this->contentContainerEnd();
		$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('import'));
		print "</form>\n";

		$this->columnEnd();
		$this->columnStart(8);
		if($newusers) {
			echo "<table class=\"table table-condensed\">\n";
			echo "<tr>";
			foreach($colmap as $col) {
				echo "<th>".$col[2]."</th>\n";
			}
			echo "<th>".getMLText('message')."</th>";
			echo "</tr>\n";
			echo "<tr>";
			foreach($newusers as $uhash=>$newuser) {
				foreach($colmap as $i=>$coldata) {
					echo "<td>";
					echo htmlspecialchars(call_user_func($colmap[$i][1], $colmap[$i][2], $newuser));
					echo "</td>\n";
				}
				echo "<td>";
				if(isset($newuser['__logs__'])) {
					foreach($newuser['__logs__'] as $item) {
						$class = $item['type'] == 'success' ? 'success' : 'error';
						echo "<i class=\"fa fa-circle text-".$class."\"></i> ".htmlspecialchars($item['msg'])."<br />";
					}
				}
				foreach($log[$uhash] as $item) {
					$class = $item['type'] == 'success' ? 'success' : 'error';
					echo "<i class=\"fa fa-circle text-".$class."\"></i> ".htmlspecialchars($item['msg'])."<br />";
				}
				echo "</td>";
				echo "</tr>\n";
			}
			echo "</tr>\n";
				/*
			foreach($log as $item) {
				$class = $item['type'] == 'success' ? 'success' : 'error';
				echo "<tr class=\"".$class."\"><td>".$item['id']."</td><td>".htmlspecialchars($item['msg'])."</td></tr>\n";
			}
				 */
			echo "</table>";
		} else {
			if($colmap)
				$this->warningMsg(getMLText('import_users_no_users'));
			else
				$this->warningMsg(getMLText('import_users_no_column_mapping'));
		}
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}

