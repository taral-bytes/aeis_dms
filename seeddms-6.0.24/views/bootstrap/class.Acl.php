<?php
/**
 * Implementation of Acl view
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
 * Class which outputs the html page for Acl view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Acl extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$selrole = $this->params['selrole'];
		header('Content-Type: application/javascript');
?>
$('#acostree').tree({
	autoOpen: 0,
	saveState: 'acostree<?php echo ($selrole ? $selrole->getID() : ''); ?>',
	openedIcon: $('<i class="fa fa-minus-circle"></i>'),
	closedIcon: $('<i class="fa fa-plus-circle"></i>'),
	onCreateLi: function(node, $li) {
		switch(node.permission) {
			case -1:
				$li.find('.jqtree-element span:last-child').after('<span style="position: absolute; right:10px;" class="jqtree-remove-permission" data-acoid="'+node.acoid+'" data-aroid="'+node.aroid+'"><i class="fa fa-minus-circle"></i></span> <span style="position: absolute; right:50px;" class="jqtree-toggle-permission" data-acoid="'+node.acoid+'" data-aroid="'+node.aroid+'"><i class="fa fa-exchange"></i></span>');
				$li.attr('style', 'background-color:#FDD');
				break;
			case 1:
				$li.find('.jqtree-element span:last-child').after('<span style="position: absolute; right:10px;" class="jqtree-remove-permission" data-acoid="'+node.acoid+'" data-aroid="'+node.aroid+'"><i class="fa fa-minus-circle"></i></span> <span style="position: absolute; right:50px;" class="jqtree-toggle-permission" data-acoid="'+node.acoid+'" data-aroid="'+node.aroid+'"><i class="fa fa-exchange"></i></span>');
				$li.attr('style', 'background-color:#DFD');
				break;
			default:
				$li.find('.jqtree-element span:last-child').after('<span style="position: absolute; right:10px;" class="jqtree-add-permission" data-acoid="'+node.acoid+'" data-aroid="'+node.aroid+'"><i class="fa fa-plus-circle"></i></span>');
		}
 }
});
$('#acostree').on('click', '.jqtree-toggle-permission', function(event) {
	acoid = $(event.target).parent().attr('data-acoid');
	aroid = $(event.target).parent().attr('data-aroid');
	$.ajax('../op/op.Acl.php?action=toggle_permission&acoid='+acoid+'&aroid='+aroid, {
		dataType: 'json',
		success: function(data, textStatus) {
			if(data.type == 'success')  {
				timeout = 1500;
				$('#acostree').tree('loadDataFromUrl');
			} else {
				timeout = 3500;
			}
			noty({text: data.msg, type: data.type, dismissQueue: true, layout: 'topRight', theme: 'defaultTheme', timeout: timeout});
		},
	});
});

$('#acostree').on('click', '.jqtree-add-permission', function(event) {
	acoid = $(event.target).parent().attr('data-acoid');
	aroid = $(event.target).parent().attr('data-aroid');
	$.ajax('../op/op.Acl.php?action=add_permission&acoid='+acoid+'&aroid='+aroid, {
		dataType: 'json',
		success: function(data, textStatus) {
			if(data.type == 'success')  {
				timeout = 1500;
				$('#acostree').tree('loadDataFromUrl');
			} else {
				timeout = 3500;
			}
			noty({text: data.msg, type: data.type, dismissQueue: true, layout: 'topRight', theme: 'defaultTheme', timeout: timeout});
		},
	});
});

$('#acostree').on('click', '.jqtree-remove-permission', function(event) {
	acoid = $(event.currentTarget).attr('data-acoid');
	aroid = $(event.currentTarget).attr('data-aroid');
	$.ajax('../op/op.Acl.php?action=remove_permission&acoid='+acoid+'&aroid='+aroid, {
		dataType: 'json',
		success: function(data, textStatus) {
			if(data.type == 'success')  {
				timeout = 1500;
				$('#acostree').tree('loadDataFromUrl');
			} else {
				timeout = 3500;
			}
			noty({text: data.msg, type: data.type, dismissQueue: true, layout: 'topRight', theme: 'defaultTheme', timeout: timeout});
		},
	});
});

$('#add_aro').on('click', function(event) {
	roleid = $(event.currentTarget).attr('data-roleid');
	$.ajax('../op/op.Acl.php?action=add_aro&roleid='+roleid, {
		dataType: 'json',
		success: function(data, textStatus) {
			if(data.type == 'success')  {
				timeout = 1500;
				window.location='out.Acl.php?action=show&roleid=' + roleid;
			} else {
				timeout = 3500;
			}
			noty({text: data.msg, type: data.type, dismissQueue: true, layout: 'topRight', theme: 'defaultTheme', timeout: timeout});
		},
	});
});

$(document).ready( function() {
	$( "#selector" ).change(function() {
		window.location='out.Acl.php?action=show&roleid=' + $(this).val();
//		$('#acostree').tree({dataUrl: 'out.Acl.php?action=tree&roleid=' + $(this).val()});
	});
});
<?php
	} /* }}} */

	function info() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selrole = $this->params['selrole'];
		$accessobject = $this->params['accessobject'];
		if($selrole) {
			$this->contentHeading(getMLText("role_info"));

			$users = $selrole->getUsers();
			if($users) {
				echo "<table class=\"table table-condensed\"><thead><tr><th>".getMLText('name')."</th><th></th></tr></thead><tbody>";
				foreach($users as $currUser) {
					echo "<tr>";
					echo "<td>";
					echo htmlspecialchars($currUser->getFullName())." (".htmlspecialchars($currUser->getLogin()).")";
					echo "<br /><a href=\"mailto:".htmlspecialchars($currUser->getEmail())."\">".htmlspecialchars($currUser->getEmail())."</a>";
					if($currUser->getComment())
						echo "<br /><small>".htmlspecialchars($currUser->getComment())."</small>";
					echo "</td>";
					echo "<td>";
					if($accessobject->check_view_access(array('UsrMgr', 'RemoveUser'))) {
						echo "<div class=\"list-action\">";
						if($accessobject->check_view_access('UsrMgr'))
							echo "<a href=\"../out/out.UsrMgr.php?userid=".$currUser->getID()."\"><i class=\"fa fa-edit\"></i></a> ";
						if($accessobject->check_view_access('RemoveUser'))
							echo "<a href=\"../out/out.RemoveUser.php?userid=".$currUser->getID()."\"><i class=\"fa fa-remove\"></i></a>";
						echo "</div>";
					}
					echo "</td>";
					echo "</tr>";
				}
				echo "</tbody></table>";
			}
		}
	} /* }}} */

	/**
	 * Show tree of acos
	 *
	 */
	private function _tree($aro=null, $aco=null) { /* {{{ */
		$children = array();
		$tchildren = $aco->getChildren();
		if($tchildren) {
			foreach($tchildren as $child) {
				$node = array();
				if(false === ($perm = $child->getPermission($aro)))
					$node['permission'] = 0;
				else
					$node['permission'] = $perm;
				$node['id'] = $child->getID();
				$node['label'] = $child->getAlias();
				$node['acoid'] = $child->getID();
				$node['aroid'] = $aro ? $aro->getID() : 0;

				$nchildren = $this->_tree($aro, $child);
				if($nchildren) {
					$node['is_folder'] = true;
					$node['children'] = $nchildren;
				}
				$children[] = $node;
			}
		}
		return $children;
	} /* }}} */

	/**
	 * List all registered hooks
	 *
	 */
	public function tree() { /* {{{ */
		$dms = $this->params['dms'];
		$selrole = $this->params['selrole'];

		$result = array();
		if($selrole) {
			$aro = SeedDMS_Aro::getInstance($selrole, $dms);

			if($acos = SeedDMS_Aco::getRoot($dms)) {
				foreach($acos as $aco) {
					if(false === ($perm = $aco->getPermission($aro)))
						$tree['permission'] = 0;
					else
						$tree['permission'] = $perm;
					$tree['id'] = $aco->getID();
					$tree['label'] = $aco->getAlias();
					$tree['acoid'] = $aco->getID();
					$tree['aroid'] = $aro ? $aro->getID() : 0;
					$tree['is_folder'] = true;
					$tree['children'] = $this->_tree($aro, $aco);
					$result[] = $tree;
				}
			}
		}
		echo json_encode($result);
	} /* }}} */

	public function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$roles = $this->params['allroles'];
		$selrole = $this->params['selrole'];
		$settings = $this->params['settings'];
		$accessop = $this->params['accessobject'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		if(!$settings->_advancedAcl) {
			$this->warningMsg(getMLText("access_control_is_off"));
		}
		$this->rowStart();
		$this->columnStart(4);
		$this->contentHeading(getMLText("role"));
?>
<form class="form-horizontal">
<?php
		$options = array();
		$options[] = array("-1", getMLText("choose_role"));
		foreach ($roles as $currRole) {
			$options[] = array($currRole->getID(), htmlspecialchars($currRole->getName()), $selrole && $currRole->getID()==$selrole->getID());
		}
		$this->formField(
			null, //getMLText("selection"),
			array(
				'element'=>'select',
				'id'=>'selector',
				'class'=>'chzn-select',
				'options'=>$options
			)
		);
?>
</form>
<?php if($accessop->check_view_access($this, array('action'=>'info')) || $user->isAdmin()) { ?>
<div class="ajax" data-view="Acl" data-action="info" <?php echo ($selrole ? "data-query=\"roleid=".$selrole->getID()."\"" : "") ?>></div>
<?php
	 	}
		$this->columnEnd();
		$this->columnStart(8);
		$this->contentHeading(getMLText("access_control"));

		if($selrole) {
			$aro = SeedDMS_Aro::getInstance($selrole, $dms);
			if(!$aro) {
				$this->warningMsg(getMLText("missing_request_object"));
				echo "<button id=\"add_aro\" class=\"btn btn-primary\" data-roleid=\"".$selrole->getID()."\">".getMLText('add')."</button>";
			} else {
?>
	<div id="acostree" data-url="out.Acl.php?action=tree&roleid=<?= ($selrole ? $selrole->getID() : 0) ?>"><?= getMLText('data_loading')?></div>
<?php
			}
		}
		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}


