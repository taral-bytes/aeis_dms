<?php
/**
 * Implementation of WorkspaceMgr view
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
 * Class which outputs the html page for WorkspaceMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_WorkflowGraph extends SeedDMS_Theme_Style {

	function js() { /* {{{ */
		$this->workflow = $this->params['workflow'];
		/* curtransitions is a list of transition that shall be highlighted.
		 * It is used to mark the current transition a user can trigger.
		 * Setting this will automatically show all other transitions with
		 * higher opacity.
		 */
		$this->curtransitions = $this->params['transitions'];
		header('Content-Type: application/javascript; charset=UTF-8');

		$renderdata = $this->workflow->getLayoutData();
		$positions = array();
		if($renderdata) {
			$data = json_decode($renderdata);
			foreach($data->nodes as $node) {
				if($node->group == 'nodes') {
					$positions[$node->data->id] = $node->position;
				}
			}
		}
?>
var cy = cytoscape({
	container: document.getElementById('canvas'),

	style: [
	{
		selector: 'node',
		style: {
			'content': 'data(name)',
			'height': 40,
			'width': 40,
			'text-valign': 'top',
			'text-halign': 'center',
//			'color': '#fff',
			'background-color': '#11479e',
//			'text-outline-color': '#11479e',
//			'text-outline-width': '3px',
//			'font-size': '10px',
			'text-wrap': 'wrap'
		}
	},

	{
		selector: 'node.action',
		style: {
			'shape': 'roundrectangle',
			'height': 30,
			'width': 30,
			'background-color': '#91479e',
//			'text-outline-color': '#91479e'
		}
	},

	{
		selector: 'node.current',
		style: {
			'font-weight': 'bold',
		}
	},

	{
		selector: 'node.light',
		style: {
			'opacity': '0.3',
		}
	},

	{
		selector: 'node.init',
		style: {
			'background-color': '#ff9900',
//			'text-outline-color': '#b06000'
		}
	},

	{
		selector: 'node.released',
		style: {
			'background-color': '#00b000',
			'text-valign': 'bottom',
			'text-margin-y': '3px',
//			'text-outline-color': '#00b000'
		}
	},

	{
		selector: 'node.rejected',
		style: {
			'background-color': '#b00000',
			'text-valign': 'bottom',
			'text-margin-y': '3px',
//			'text-outline-color': '#b00000'
		}
	},

	{
		selector: 'edge',
		style: {
			'content': 'data(name)',
			'text-wrap': 'wrap',
			'width': 4,
			'curve-style': 'bezier',
			'target-arrow-shape': 'triangle',
			'line-color': '#9dbaea',
			'target-arrow-color': '#9dbaea',
			'curve-style': 'bezier'
		}
	},

	{
		selector: 'edge.light',
		style: {
			'opacity': '0.3',
		}
	}
	]
});

cy.gridGuide({
	discreteDrag: false,
	guidelinesStyle: {
		strokeStyle: "red"
	}
});

function save_handler(evt) {
//	console.log(JSON.stringify(cy.json().elements));
	$.ajax('../op/op.WorkflowMgr.php', {
		type:"POST",
		async:true,
		dataType:"json",
		data: {
			action: 'setrenderdata',
			workflowid: <?php echo $this->workflow->getID(); ?>,
			data: JSON.stringify(cy.json().elements)
		},
		success: function(data, textStatus) {
			noty({
				text: data.message,
				type: data.success ? 'success' : 'error',
				dismissQueue: true,
				layout: 'topRight',
				theme: 'defaultTheme',
				timeout: 1500,
			});
		}
	});
};

cy.on('free', 'node', function(evt) {
	$('#png').attr('src', cy.png({'full': true}));
});

cy.on('tap', 'node', function(evt) {
	var node = evt.target;
	var scratch = node.scratch('app');
	if(typeof scratch !== 'undefined') {
	noty({
		text: (scratch.users ? '<p><?= '<i class="fa fa-user"></i> ' ?> ' + scratch.users + '</p>' : '') + (scratch.groups ? '<?= '<i class="fa fa-group"></i> ' ?> ' + scratch.groups + '</p>' : ''),
		type: 'information',
		dismissQueue: true,
		layout: 'topCenter',
		theme: 'defaultTheme',
		timeout: 4000,
		killer: true,
	});
	}
});

cy.on('zoom', function(evt) {
	$('#zoom button').text(Math.round(cy.zoom()*100)+'%');
});

<?php
		$this->printGraph($positions);
?>
	cy.layout({ name: '<?php echo $renderdata ? 'preset' : 'cose'; ?>', ready: function() {$('#png').attr('src', cy.png({'full': true}))} });
	cy.maxZoom(2.5);
	cy.minZoom(0.4);
	$('#zoom button').text(Math.round(cy.zoom()*100)+'%');

$(document).ready(function() {
	$('body').on('click', '#savelayout', function(ev){
		ev.preventDefault();
		save_handler();
	});
	$('body').on('click', '#setlayout', function(ev){
		ev.preventDefault();
		var element = $(this);
		cy.layout({name: element.data('layout'), ready: function() {$('#png').attr('src', cy.png({'full': true}))}});
	});
	$('body').on('click', '#zoom button', function(ev){
		cy.zoom(1);
		cy.center();
	});
});
<?php
	} /* }}} */

	function printGraph($positions) { /* {{{ */
		$transitions = $this->workflow->getTransitions();	
		if($transitions) {

			$this->seentrans = array();
			$this->states = array();
			$this->actions = array();
			$highlightstates = array();
			foreach($transitions as $transition) {
				$action = $transition->getAction();
				$maxtime = $transition->getMaxTime();
				$state = $transition->getState();
				$nextstate = $transition->getNextState();

				if(1 || !isset($this->actions[$action->getID()])) {
					$iscurtransition = false;
					if($this->curtransitions) {
						foreach($this->curtransitions as $tr)
							if($transition->getID() == $tr->getID())
								$iscurtransition = true;
					}

					$this->actions[$action->getID()] = $action->getID();
					$transusers = $transition->getUsers();
					$unames = array();
					foreach($transusers as $transuser) {
						$unames[] = htmlspecialchars($transuser->getUser()->getLogin().' - '.$transuser->getUser()->getFullName());
					}
					$transgroups = $transition->getGroups();
					$gnames = array();
					foreach($transgroups as $transgroup) {
						$gnames[] = htmlspecialchars($transgroup->getGroup()->getName());
					}
					$nodeid = "A".$transition->getID()."-".$action->getID();
					echo "cy.add({
  data: {
    id: '".$nodeid."',
		name: \"".str_replace('"', "\\\"", $action->getName())/*.($unames ? "\\n(".str_replace('"', "\\\"", implode(", ", $unames)).")" : '').($gnames ? "\\n(".str_replace('"', "\\\"", implode(", ", $gnames)).")" : '')*/."\"
  },".(isset($positions[$nodeid]) ? "
  position: {x: ".$positions[$nodeid]->x.", y: ".$positions[$nodeid]->y."}," : "")."
  classes: 'action".($iscurtransition ? " current" : ($this->curtransitions ? " light" : ""))."'".(!$this->curtransitions || $iscurtransition && $this->curtransitions ? ",
  scratch: {
    app: {groups: \"".str_replace('"', "\\\"", implode(", ", $gnames))."\", users: \"".str_replace('"', "\\\"", implode(", ", $unames))."\"}
  }" : "")."
});\n";
				}

				/* Collect all states and remember those which are part of a
				 * current transition.
				 */
				if(!isset($this->states[$state->getID()]) || $iscurtransition) {
					if($iscurtransition)
						$highlightstates[] = $state->getID();
					$this->states[$state->getID()] = $state;
				}
				if(!isset($this->states[$nextstate->getID()]) || $iscurtransition) {
					if($iscurtransition)
						$highlightstates[] = $nextstate->getID();
					$this->states[$nextstate->getID()] = $nextstate;
				}
			}

			foreach($this->states as $state) {
				$docstatus = $state->getDocumentStatus();
				$nodeid = 'S'.$state->getID();
				echo "cy.add({
  data: {
    id: '".$nodeid."',
    name: \"".str_replace('"', "\\\"", $state->getName())."\"
  },".(isset($positions[$nodeid]) ? "
  position: {x: ".$positions[$nodeid]->x.", y: ".$positions[$nodeid]->y."}," : "")."
  classes: 'state".($state == $this->workflow->getInitState() ? ' init' : ($docstatus == S_RELEASED ? ' released' : ($docstatus == S_REJECTED ? ' rejected' : ''))).($highlightstates && !in_array($state->getID(), $highlightstates) ? ' light' : '')."'
});\n";
			}

			foreach($transitions as $transition) {
//				if(!in_array($transition->getID(), $this->seentrans)) {
					$state = $transition->getState();
					$nextstate = $transition->getNextState();
					$action = $transition->getAction();

					$iscurtransition = false;
					if($this->curtransitions) {
						foreach($this->curtransitions as $tr)
							if($transition->getID() == $tr->getID())
								$iscurtransition = true;
					}

					echo "cy.add({
						data: {
							id: 'E1-".$transition->getID()."',
							name: '',
							source: 'S".$state->getID()."',
							target: 'A".$transition->getID()."-".$action->getID()."'
						},
						classes: '".($iscurtransition ? " current" : ($this->curtransitions ? " light" : ""))."',
					});\n";
					echo "cy.add({
						data: {
							id: 'E2-".$transition->getID()."',
							name: '',
							source: 'A".$transition->getID()."-".$action->getID()."',
							target: 'S".$nextstate->getID()."'
						},
						classes: '".($iscurtransition ? " current" : ($this->curtransitions ? " light" : ""))."',
					});\n";
//					$this->seentrans[] = $transition->getID();
//				}
			}
		}
?>
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$this->workflow = $this->params['workflow'];
		$document = $this->params['document'];

		if($document) {
			$latestContent = $document->getLatestContent();
			$this->wkflog = $latestContent->getWorkflowLog();
		} else {
			$this->wkflog = array();
		}

		$this->htmlAddHeader(
			'<script type="text/javascript" src="../views/bootstrap/vendors/cytoscape/cytoscape.min.js"></script>'."\n");
		$this->htmlAddHeader(
			'<script type="text/javascript" src="../views/bootstrap/vendors/cytoscape/cytoscape-grid-guide.js"></script>'."\n");
		$this->htmlAddHeader('
<style type="text/css">
body {padding: 0px;margin-bottom:0px;}
div.buttons {float: right; padding-left: 4px; height: 100px; width: 120px; margin-right: 5px;}
div.buttons button {margin: 3px; _float: right;}
div.buttons #zoom {margin: 3px; _float: right;}
#legend {display: inline-block; margin-left: 10px;}
#preview {height: 122px; background: #f5f5f5; border-top: 1px solid #e3e3e3;}
#preview img {float: left;border: 1px solid #bbb; background: #fff; min-height: 18vh; height: 18vh; _width: 100px; padding: 3px; margin: 3px;}
</style>
', 'css');
		$this->htmlStartPage(getMLText("admin_tools"));
//		$this->contentContainerStart();

?>
<div id="canvas" style="width: 100%; height:78vh; _border: 1px solid #bbb;"></div>
<div id="preview" style="height:21vh; padding-top: 5px; padding-left: 5px;">
	<img id="png" />
	<div id="legend">
		<i class="fa fa-circle initstate"></i> <?php printMLText("workflow_initstate"); ?><br />
		<i class="fa fa-circle released"></i> <?php echo getOverallStatusText(S_RELEASED); ?><br />
		<i class="fa fa-circle rejected"></i> <?php echo getOverallStatusText(S_REJECTED); ?><br />
		<i class="fa fa-circle in-workflow"></i> <?php echo getOverallStatusText(S_IN_WORKFLOW); ?><br />
		<i class="fa fa-square workflow-action"></i> <?php echo printMLText('global_workflow_actions'); ?>
	</div>
	<div class="buttons">
		<div id="zoom"><button class="btn btn-mini btn-secondary btn-sm">Zoom</button></div>
		<button class="btn btn-mini btn-secondary btn-sm" id="savelayout"><?php printMLText('workflow_save_layout'); ?></button>
		<button class="btn btn-secondary btn-mini btn-sm" id="setlayout" data-layout="cose"><?php printMLText('redraw'); ?></button>
	</div>
</div>
<?php
//		$this->contentContainerEnd();
		if(method_exists($this, 'js'))
			echo '<script src="../out/out.'.$this->params['class'].'.php?action=js&'.$_SERVER['QUERY_STRING'].'"></script>'."\n";
		echo "</body>\n</html>\n";
	} /* }}} */
}
?>
