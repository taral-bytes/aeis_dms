<?php
/**
 * Implementation of SetRevisors view
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
 * Class which outputs the html page for SetRevisors view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2015 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_SetRevisors extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$content = $this->params['version'];
		$enableadminrevapp = $this->params['enableadminrevapp'];
		$enableownerrevapp = $this->params['enableownerrevapp'];
		$enableselfrevapp = $this->params['enableselfrevapp'];

		$overallStatus = $content->getStatus();

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("update_revisors"));

		// Retrieve a list of all users and groups that have review / approve privileges.
		$docAccess = $document->getReadAccessList($enableadminrevapp, $enableownerrevapp);

		// Retrieve list of currently assigned revisors, along with
		// their latest status.
		$revisionStatus = $content->getRevisionStatus();
		$startdate = getReadableDate(makeTsFromDate($content->getRevisionDate()));

		// Index the revision results for easy cross-reference with the revisor list.
		$revisionIndex = array("i"=>array(), "g"=>array());
		foreach ($revisionStatus as $i=>$rs) {
			if ($rs["type"]==0) {
				$revisionIndex["i"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
			} elseif ($rs["type"]==1) {
				$revisionIndex["g"][$rs["required"]] = array("status"=>$rs["status"], "idx"=>$i);
			}
		}
?>

<form class="form-horizontal" action="../op/op.SetRevisors.php" method="post" name="form1">
	<input type='hidden' name='documentid' value='<?php echo $document->getID() ?>'/>
	<input type='hidden' name='version' value='<?php echo $content->getVersion() ?>'/>

<?php
		$this->contentContainerStart();
		if($content->getStatus()['status'] == S_IN_REVISION) {
			echo '<input type="hidden" name="startdate" value="" />';
		} else {
			$this->formField(
				getMLText("revision_date"),
				$this->getDateChooser($startdate, "startdate", $this->params['session']->getLanguage())
			);
		}

		$options = [];
		foreach ($docAccess["users"] as $usr) {
			if (isset($revisionIndex["i"][$usr->getID()])) {

				switch ($revisionIndex["i"][$usr->getID()]["status"]) {
					case S_LOG_WAITING:
					case S_LOG_SLEEPING:
					case S_LOG_ACCEPTED:
					case S_LOG_REJECTED:
						$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), true);
						break;
					case S_LOG_USER_REMOVED:
						$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), false, array(array('data-subtitle', getMLText('user_previously_removed_from_revisors'))));
						break;
					default:
						$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()), false, ['disabled', 'disabled']);
						break;
				}
			} else {
				if (!$enableselfrevapp && $usr->getID()==$user->getID()) continue; 
				$options[] = array($usr->getID(), htmlspecialchars($usr->getLogin() . " - ". $usr->getFullName()));
			}
		}
		$this->formField(
			getMLText("individuals"),
			array(
				'element'=>'select',
				'id'=>'indRevisors',
				'name'=>'indRevisors[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'attributes'=>array(array('data-allow-clear', 'true'), array('data-placeholder', getMLText('select_ind_revisors')), array('data-no_results_text', getMLText('unknown_user'))),
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
				'id'=>'grpIndRevisors',
				'name'=>'grpIndRevisors[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'attributes'=>array(array('data-allow-clear', 'true'), array('data-placeholder', getMLText('select_grp_ind_revisors')), array('data-no_results_text', getMLText('unknown_group'))),
				'options'=>$options
			)
		);

		$options = [];
		foreach ($docAccess["groups"] as $group) {
			$grpusers = $group->getUsers();
			if (isset($revisionIndex["g"][$group->getID()])) {
				switch ($revisionIndex["g"][$group->getID()]["status"]) {
					case S_LOG_WAITING:
					case S_LOG_SLEEPING:
						$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), true);
						break;
					case S_LOG_USER_REMOVED:
						$options[] = array($group->getID(), htmlspecialchars($group->getName().' ('.count($grpusers).')'), false, array(array('data-subtitle', getMLText('group_previously_removed_from_revisors'))));
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
				'id'=>'grpRevisors',
				'name'=>'grpRevisors[]',
				'class'=>'chzn-select',
				'multiple'=>true,
				'attributes'=>array(array('data-allow-clear', 'true'), array('data-placeholder', getMLText('select_grp_revisors')), array('data-no_results_text', getMLText('unknown_group'))),
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
