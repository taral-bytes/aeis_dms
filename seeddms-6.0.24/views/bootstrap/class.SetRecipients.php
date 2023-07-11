<?php
/**
 * Implementation of SetRecipients view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2015 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for SetRecipients view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2015 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_SetRecipients extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$content = $this->params['version'];
		$enableownerreceipt = $this->params['enableownerreceipt'];
		$enableadminreceipt = $this->params['enableadminreceipt'];

		$overallStatus = $content->getStatus();

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("change_recipients"));

		// Retrieve a list of all users and groups that have receipt privileges.
		$docAccess = $document->getReadAccessList($enableadminreceipt, $enableownerreceipt);

		// Retrieve list of currently assigned recipients, along with
		// their latest status.
		$receiptStatus = $content->getReceiptStatus();

		// Index the receipt results for easy cross-reference with the recipient list.
		$receiptIndex = array("i"=>array(), "g"=>array());
		foreach ($receiptStatus as $i=>$rs) {
			if ($rs["type"]==0) {
				$receiptIndex["i"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
			} elseif ($rs["type"]==1) {
				$receiptIndex["g"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
			}
		}
?>

<form class="form-horizontal" action="../op/op.SetRecipients.php" method="post" name="form1">
	<input type='hidden' name='documentid' value='<?php echo $document->getID() ?>'/>
	<input type='hidden' name='version' value='<?php echo $content->getVersion() ?>'/>

<?php
		$this->contentContainerStart();

		$options = [];
		foreach ($docAccess["users"] as $usr) {
			if (isset($receiptIndex["i"][$usr->getID()])) {

				switch ($receiptIndex["i"][$usr->getID()]["status"]) {
					case 0:
						$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), true);
						break;
					case S_LOG_USER_REMOVED:
						$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), false, array(array('data-subtitle', getMLText('user_previously_removed_from_recipients'))));
						break;
					default:
						$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), false, ['disabled', 'disabled']);
						break;
				}
			} else {
				$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()));
			}
		}
		$this->formField(
			getMLText("individuals"),
			array(
				'element'=>'select',
				'id'=>'indRecipients',
				'name'=>'indRecipients[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'attributes'=>array(array('data-allow-clear', 'true'), array('data-placeholder', getMLText('select_ind_recipients')), array('data-no_results_text', getMLText('unknown_user'))),
				'options'=>$options
			)
		);

		$options = [];
		foreach ($docAccess["groups"] as $group) {
			$optopt = [];
			$grpusers = $group->getUsers();
			if(count($grpusers) == 0)
				$optopt[] = ['disabled', 'disabled'];
			$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), false, $optopt);
		}
		$this->formField(
			getMLText("individuals_in_groups"),
			array(
				'element'=>'select',
				'id'=>'grpIndRecipients',
				'name'=>'grpIndRecipients[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'attributes'=>array(array('data-allow-clear', 'true'), array('data-placeholder', getMLText('select_grp_ind_recipients')), array('data-no_results_text', getMLText('unknown_group'))),
				'options'=>$options
			)
		);

		$options = [];
		foreach ($docAccess["groups"] as $group) {
			$grpusers = $group->getUsers();
			if (isset($receiptIndex["g"][$group->getID()])) {
				switch ($receiptIndex["g"][$group->getID()]["status"]) {
					case 0:
						$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), true);
						break;
					case S_LOG_USER_REMOVED:
						$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), false, array(array('data-subtitle', getMLText('group_previously_removed_from_recipients'))));
						break;
					default:
						$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), false, array(array('disabled', 'disabled')));
						break;
				}
			} else {
				$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'));
			}
		}
		$this->formField(
			getMLText("groups"),
			array(
				'element'=>'select',
				'id'=>'grpRecipients',
				'name'=>'grpRecipients[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'attributes'=>array(array('data-allow-clear', 'true'), array('data-placeholder', getMLText('select_grp_recipients')), array('data-no_results_text', getMLText('unknown_group'))),
				'options'=>$options
			)
		);

		$this->contentContainerEnd();
		$this->formSubmit("<i class=\"fa fa-save\"></i> ".getMLText('update'));
?>
</form>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
