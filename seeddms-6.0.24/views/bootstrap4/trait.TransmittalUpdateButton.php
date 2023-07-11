<?php

trait TransmittalUpdateButton {
	/**
	 * Print button for updating the transmittal item to the newest version
	 *
	 * @param object $item
	 * @param string $msg message shown in case of successful update
	 */
	protected function printUpdateItemButton($item, $msg, $return=false){ /* {{{ */
		$itemid = $item->getID();
		$content = '';
    $content .= '<a class="update-transmittalitem-btn" transmittal="'.$item->getTransmittal()->getID().'" rel="'.$itemid.'" msg="'.htmlspecialchars($msg, ENT_QUOTES).'" confirmmsg="'.htmlspecialchars(getMLText("confirm_update_transmittalitem"), ENT_QUOTES).'"><i class="fa fa-refresh"></i></a>';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	protected function printUpdateItemButtonJs(){ /* {{{ */
		echo "
		$(document).ready(function () {
			$('body').on('click', 'a.update-transmittalitem-btn', function(ev){
				ev.stopPropagation();
				id = $(ev.currentTarget).attr('rel');
				transmittalid = $(ev.currentTarget).attr('transmittal');
				confirmmsg = $(ev.currentTarget).attr('confirmmsg');
				msg = $(ev.currentTarget).attr('msg');
				formtoken = '".createFormKey('updatetransmittalitem')."';
				bootbox.dialog({
					\"message\": confirmmsg,
					\"buttons\": {
						\"confirm\": {
							\"label\" : \"<i class='fa fa-remove'></i> ".getMLText("update_transmittalitem")."\",
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
									action: 'updatetransmittalitem',
									id: id,
									formtoken: formtoken
								},
								success: function(data) {
									if(data.success) {
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
								}
							});
						}
					}
				});
			});
		});
		";
	} /* }}} */

}

