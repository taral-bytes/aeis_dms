<?php

trait TransmittalDeleteButton {
	/**
	 * Print button with link for deleting a transmittal item
	 *
	 * This button works just like the printDeleteDocumentButton()
	 *
	 * @param object $item transmittal item to be deleted
	 * @param string $msg message shown in case of successful deletion
	 * @param boolean $return return html instead of printing it
	 * @return string html content if $return is true, otherwise an empty string
	 */
	protected function printDeleteItemButton($item, $msg, $return=false){ /* {{{ */
		$itemid = $item->getID();
		$content = '';
    $content .= '<a class="delete-transmittalitem-btn" transmittal="'.$item->getTransmittal()->getID().'" rel="'.$itemid.'" msg="'.htmlspecialchars($msg, ENT_QUOTES).'" confirmmsg="'.htmlspecialchars(getMLText("confirm_rm_transmittalitem"), ENT_QUOTES).'"><i class="fa fa-remove"></i></a>';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	protected function printDeleteItemButtonJs(){ /* {{{ */
		echo "
		$(document).ready(function () {
			$('body').on('click', 'a.delete-transmittalitem-btn', function(ev){
				ev.stopPropagation();
				id = $(ev.currentTarget).attr('rel');
				transmittalid = $(ev.currentTarget).attr('transmittal');
				confirmmsg = $(ev.currentTarget).attr('confirmmsg');
				msg = $(ev.currentTarget).attr('msg');
				formtoken = '".createFormKey('removetransmittalitem')."';
				bootbox.confirm({
					\"message\": confirmmsg,
					\"buttons\": {
						\"confirm\": {
							\"label\" : \"<i class='fa fa-remove'></i> ".getMLText("rm_transmittalitem")."\",
							\"className\" : \"btn-danger\",
						},
						\"cancel\": {
							\"label\" : \"".getMLText("cancel")."\",
							\"className\" : \"btn-secondary\",
						}
					},
					\"callback\": function(result) {
						if(result) {
							$.ajax('".$this->params['settings']->_httpRoot."../op/op.TransmittalMgr.php', {
								type:'POST',
								async:true,
								dataType:'json',
								data: {
									action: 'removetransmittalitem',
									id: id,
									formtoken: formtoken
								},
								success: function(data) {
									if(data.success) {
										$('#table-row-transmittalitem-'+id).hide('slow');
										noty({
											text: msg,
											type: 'success',
											dismissQueue: true,
											layout: 'topRight',
											theme: 'defaultTheme',
											timeout: 1500,
										});
										$('div.ajax').trigger('update', {transmittalid: transmittalid});
									} else {
										noty({
											text: data.message,
											type: 'error',
											dismissQueue: true,
											layout: 'topRight',
											theme: 'defaultTheme',
											timeout: 3500,
										});
									}
								},
							});
						}
					}
				});
			});
		});
		";
	} /* }}} */

}
