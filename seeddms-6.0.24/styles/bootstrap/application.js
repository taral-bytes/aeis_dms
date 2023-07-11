/* Template function which outputs an option in a chzn-select
 * The replace() call is required to prevent xss attacks (see CVE-2019-12745)
 * Using htmlspecialchars() in php isn't sufficient because, chzn_template_func
 * will receive an unescaped string
 * (see https://forums.select2.org/t/propperly-escape-option-value-to-prevent-xss/788)
 */
chzn_template_func =  function (state) {
	var subtitle = '';
	if($(state.element).data('subtitle'))
		subtitle = $(state.element).data('subtitle')+''; /* make sure it is a string */
	var warning = '';
	if($(state.element).data('warning'))
		warning = $(state.element).data('warning')+''; /* make sure it is a string */
	var html = '<span>';
	if($(state.element).data('icon-before'))
		html += '<i class="fa fa-'+$(state.element).data('icon-before')+'"></i> ';
	html += state.text.replace(/</g, '&lt;')+'';
	if(subtitle)
		html += '<br /><i>'+subtitle.replace(/</g, '&lt;')+'</i>';
	if(warning)
		html += '<br /><span class="label label-warning"><i class="fa fa-warning"></i></span> '+warning+'';
	html += '</span>';
	var $newstate = $(html);
	return $newstate;
};
function escapeHtml(text) {
  var map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };

  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function treeFolderSelected(formid, nodeid, nodename) {
	$('#'+formid).val(nodeid);
	$('#choosefoldersearch'+formid).val(nodename);
	$('#folderChooser'+formid).modal('hide');
}

function treeDocumentSelected(formid, nodeid, nodename) {
	$('#'+formid).val(nodeid);
	$('#choosedocsearch'+formid).val(nodename);
	$('#docChooser'+formid).modal('hide');
}

$(document).ready( function() {
	/* close popovers when clicking somewhere except in the popover or the
	 * remove icon
	 */
	$('html').on('click', function(e) {
		if (typeof $(e.target).data('original-title') == 'undefined' && !$(e.target).parents().is('.popover.in') && !$(e.target).is('.fa fa-remove')) {
			$('[data-original-title]').popover('hide');
		}
	});

	$('body').on('hidden', '.modal', function () {
		$(this).removeData('modal');
	});

	$('body').on('touchstart.dropdown', '.dropdown-menu', function (e) { e.stopPropagation(); });

	$('.datepicker, #expirationdate, #createstartdate, #createenddate, #expirationstartdate, #expirationenddate, #revisionstartdate')
		.datepicker({todayHighlight: true, toggleActive: true})
		.on('changeDate', function(ev){
			if(ev.date && $(ev.target).data('selectmenu')) {
				$("#"+$(ev.target).data('selectmenu')).val('date');
			}
			$(ev.currentTarget).datepicker('hide');
		});

	$(".chzn-select").select2({
		width: '100%',
		templateResult: chzn_template_func//,
		//templateSelection: chzn_template_func
	});

	/* change the color and length of the bar graph showing the password
	 * strength on each change to the passwod field.
	 */
	$(".pwd").passStrength({ /* {{{ */
		url: "../op/op.Ajax.php",
		onChange: function(data, target) {
			pwsp = 100*data.score;
			$('#'+target+' div.bar').width(pwsp+'%');
			if(data.ok) {
				$('#'+target+' div.bar').removeClass('bar-danger');
				$('#'+target+' div.bar').addClass('bar-success');
			} else {
				$('#'+target+' div.bar').removeClass('bar-success');
				$('#'+target+' div.bar').addClass('bar-danger');
			}
		}
	}); /* }}} */

	/* The typeahead functionality useѕ the modified version of
	 * bootstrap-typeahead, which is able to set the render function.
	 * This was needed because the search function return json objects
	 * for each hit and render could only process strings.
	 * */
	$("#searchfield").typeahead({ /* {{{ */
		minLength: 3,
		items: 100, /* the query will limit the number of hits */
		source: function(query, process) {
			var d = new Date();
			var pastYear = d.getFullYear() - 1;
			d.setFullYear(pastYear);
//			console.log(d.toISOString().split('T')[0]);

//			$.get('../restapi/index.php/search', { query: query, limit: 8, mode: 'typeahead' }, function(data) {
			var data = {
				query: query,
				limit: 18,
//				fullsearch: 1,
//				creationdate: 1,
//				createstart: d.toISOString().split('T')[0],
				action: 'typeahead'
			};
			/* Return a list of json objects, each containing
			 * type: type of object (D=doc, F=folder, S=searchterm)
			 * name: name of object
			 */
			$.get('../out/out.Search.php', data, function(data) {
				process(data);
			});
		},
		/* updater is called when the item in the list is clicked. It is
		 * actually provided to update the input field, but here we use
		 * it to set the document location. The passed value is the string
		 * set in data-value of the list items.
		 * This method relies on some changes in bootstrap-typeahead.js
		 * Originally, update was passed only the data-value of the li item
		 * which is set in the render fuction below,
		 * but the modified version passes all data fields. which also
		 * contain the 'id' and 'type' (also set in render function).
		 **/
		updater: function (item) {
			if(item.id) {
				if(item.type == 'D')
					document.location = "../out/out.ViewDocument.php?documentid=" + item.id;
				else
					document.location = "../out/out.ViewFolder.php?folderid=" + item.id;
			} else
				document.location = "../out/out.Search.php?query=" + encodeURIComponent(item.value);
			return item.value;
		},
		sorter: function(items) {
			return items;
		},
		/* matcher will always return true, because the initial search returns
		 * matches only
  	 */
		matcher : function (item) {
			return true;
		},
		/* highlighter is for modifying the 'a' tag text. It places an icon
		 * in front of the name and replaces a '<' within the name with an
		 * entity.
		 **/
		highlighter : function (item) {
			if(item.type.charAt(0) == 'D')
				return '<i class="fa fa-file"></i> ' + item.name.replace(/</g, '&lt;');
			else if(item.type.charAt(0) == 'F')
				return '<i class="fa fa-folder-o"></i> ' + item.name.replace(/</g, '&lt;');
			else
				return '<i class="fa fa-search"></i> ' + item.name.replace(/</g, '&lt;');
		},
		/* This only works with a modified version of bootstrap typeahead located
		 * in boostrap-typeahead.js Search for 'render'
		 * The line
		 * this.render = this.options.render || this.render
		 * was added to bootstrap-typeahead.js
		 * The following function is a copy of the original render function but
		 * access item.name instead of item
		 */
		render : function (items) {
      var that = this

      items = $(items).map(function (i, item) {
        i = $(that.options.item).attr('data-value', item.name).attr('data-id', item.id).attr('data-type', item.type);
        i.find('a').html(that.highlighter(item))
        return i[0]
      })

      items.first().addClass('active')
      this.$menu.html(items)
      return this
    }

	}); /* }}} */

	/* Document chooser */
	$("[id^=choosedocsearch]").typeahead({ /* {{{ */
		minLength: 3,
		source: function(query, process) {
//		console.log(this.options);
			$.get('../op/op.Ajax.php', { command: 'searchdocument', query: query, limit: 8 }, function(data) {
					process(data);
			});
		},
		/* updater is called when the item in the list is clicked. It is
		 * actually provided to update the input field where you type, but here
		 * we use it to update a second input field with the doc id. */
		updater: function (item) {
			strarr = item.value.split("#");
			target = this.$element.data('target');
			$('#'+target).attr('value', strarr[0]);
			return strarr[1];
		},
		/* Set a matcher that allows any returned value */
		matcher : function (item) {
			return true;
		},
		highlighter : function (item) {
			strarr = item.split("#");
			return '<i class="fa fa-file"></i> ' + strarr[1].replace(/</g, '&lt;');
		}
	}); /* }}} */

	/* Folder chooser */
	$("[id^=choosefoldersearch]").typeahead({ /* {{{ */
		minLength: 3,
		source: function(query, process) {
//		console.log(this.options);
			$.get('../op/op.Ajax.php', { command: 'searchfolder', query: query, limit: 8 }, function(data) {
					process(data);
			});
		},
		/* updater is called when the item in the list is clicked. It is
		 * actually provided to update the input field, but here we use
		 * it to set the document location. */
		updater: function (item) {
			strarr = item.value.split("#");
			//console.log(this.$element.data('target'));
			target = this.$element.data('target');
			$('#'+target).attr('value', strarr[0]);
			return strarr[1];
		},
		/* Set a matcher that allows any returned value */
		matcher : function (item) {
			return true;
		},
		highlighter : function (item) {
			strarr = item.split("#");
			return '<i class="fa fa-folder-o"></i> ' + strarr[1].replace(/</g, '&lt;');
		}
	}); /* }}} */

	$('body').on('click', '[id^=clearfolder]', function(ev) { /* {{{ */
		ev.preventDefault();
		ev.stopPropagation();
		target = $(this).data('target');
		$('#choosefoldersearch'+target).val('');
		$('#'+target).val('');
	}); /* }}} */

	$('body').on('click', '[id^=cleardocument]', function(ev) { /* {{{ */
		ev.preventDefault();
		ev.stopPropagation();
		target = $(this).data('target');
		$('#choosedocsearch'+target).val('');
		$('#'+target).val('');
	}); /* }}} */

	$('body').on('click', '#clipboard-float', function(ev) { /* {{{ */
		ev.preventDefault();
		ev.stopPropagation();
		$('#clipboard-container').toggleClass('clipboard-container');
	}); /* }}} */

	$('body').on('click', 'a.addtoclipboard', function(ev) { /* {{{ */
		ev.preventDefault();
		ev.stopPropagation();
		attr_rel = $(ev.currentTarget).attr('rel');
		attr_msg = $(ev.currentTarget).attr('msg');
		type = attr_rel.substring(0, 1) == 'F' ? 'folder' : 'document';
		id = attr_rel.substring(1);
		$.get('../op/op.Ajax.php',
			{ command: 'addtoclipboard', type: type, id: id },
			function(data) {
				if(data.success) {
					$("#main-clipboard").html('Loading').load('../out/out.Clipboard.php?action=mainclipboard')
					$("#menu-clipboard div").html('Loading').load('../out/out.Clipboard.php?action=menuclipboard')
					noty({
						text: attr_msg,
						type: 'success',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500
					});
				} else {
					noty({
						text: data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 3500
					});
				}
			},
			'json'
		);
	}); /* }}} */

	$('body').on('click', 'a.removefromclipboard', function(ev){ /* {{{ */
		ev.preventDefault();
		attr_rel = $(ev.currentTarget).attr('rel');
		attr_msg = $(ev.currentTarget).attr('msg');
		type = attr_rel.substring(0, 1) == 'F' ? 'folder' : 'document';
		id = attr_rel.substring(1);
		$.get('../op/op.Ajax.php',
			{ command: 'removefromclipboard', type: type, id: id },
			function(data) {
				if(data.success) {
					$("#main-clipboard").html('Loading').load('../out/out.Clipboard.php?action=mainclipboard')
					$("#menu-clipboard div").html('Loading').load('../out/out.Clipboard.php?action=menuclipboard')
					noty({
						text: attr_msg,
						type: 'success',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500
					});
				} else {
					noty({
						text: data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 3500
					});
				}
			},
			'json'
		);
	}); /* }}} */

	$('body').on('click', 'a.lock-document-btn', function(ev){ /* {{{ */
		ev.preventDefault();
		attr_rel = $(ev.currentTarget).attr('rel');
		attr_msg = $(ev.currentTarget).attr('msg');
		id = attr_rel;
		$.get('../op/op.Ajax.php',
			{ command: 'tooglelockdocument', id: id },
			function(data) {
				if(data.success) {
					//$("#table-row-document-"+id).html('Loading').load('../op/op.Ajax.php?command=view&view=documentlistrow&id='+id)
					$("#table-row-document-"+id).html('Loading').load('../out/out.ViewDocument.php?action=documentlistitem&documentid='+id)
					noty({
						text: attr_msg,
						type: 'success',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500
					});
				} else {
					noty({
						text: data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 3500
					});
				}
			},
			'json'
		);
	}); /* }}} */

	$('a.movefolder').click(function(ev){ /* {{{ */
		ev.preventDefault();
		attr_source = $(ev.currentTarget).attr('source');
		attr_dest = $(ev.currentTarget).attr('dest');
		attr_msg = $(ev.currentTarget).attr('msg');
		attr_formtoken = $(ev.currentTarget).attr('formtoken');
		$.get('../op/op.Ajax.php',
			{ command: 'movefolder', folderid: attr_source, targetfolderid: attr_dest, formtoken: attr_formtoken },
			function(data) {
				if(data.success) {
					$('#table-row-folder-'+attr_source).hide('slow');
					noty({
						text: data.msg,
						type: data.success ? 'success' : 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500
					});
				}
			},
			'json'
		);
	}); /* }}} */

	$('a.movedocument').click(function(ev){ /* {{{ */
		ev.preventDefault();
		attr_source = $(ev.currentTarget).attr('source');
		attr_dest = $(ev.currentTarget).attr('dest');
		attr_msg = $(ev.currentTarget).attr('msg');
		attr_formtoken = $(ev.currentTarget).attr('formtoken');
		$.get('../op/op.Ajax.php',
			{ command: 'movedocument', docid: attr_source, targetfolderid: attr_dest, formtoken: attr_formtoken },
			function(data) {
				if(data.success) {
					$('#table-row-document-'+attr_source).hide('slow');
					noty({
						text: data.msg,
						type: data.success ? 'success' : 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500
					});
				}
			},
			'json'
		);
	}); /* }}} */

	$('.send-missing-translation a').click(function(ev){ /* {{{ */
//		console.log($(ev.target).parent().children('[name=missing-lang-key]').val());
//		console.log($(ev.target).parent().children('[name=missing-lang-lang]').val());
//		console.log($(ev.target).parent().children('[name=missing-lang-translation]').val());
		$.ajax('../op/op.Ajax.php', {
			type:"POST",
			async:true,
			dataType:"json",
			data: {
				command: 'submittranslation',
				key: $(ev.target).parent().children('[name=missing-lang-key]').val(),
				lang: $(ev.target).parent().children('[name=missing-lang-lang]').val(),
				phrase: $(ev.target).parent().children('[name=missing-lang-translation]').val()
			},
			success: function(data, textStatus) {
				noty({
					text: data.message,
					type: data.success ? 'success' : 'error',
					dismissQueue: true,
					layout: 'topRight',
					theme: 'defaultTheme',
					timeout: 1500
				});
			}
		});
	}); /* }}} */

	$('div.ajax').each(function(index) { /* {{{ */
		var element = $(this);
		var url = '';
		var href = element.data('href');
		var base = element.data('base');
		if(typeof base == 'undefined')
			base = '';
		var view = element.data('view');
		var action = element.data('action');
		var query = element.data('query');
		var afterload = $(this).data('afterload');
		if(view && action) {
			url = seeddms_webroot+base+"out/out."+view+".php?action="+action;
			if(query) {
				url += "&"+query;
			}
		} else
			url = href;
		if(!element.data('no-spinner'))
			element.prepend('<div style="position: _absolute; overflow: hidden; background: #f7f7f7; z-index: 1000; height: 200px; width: '+element.width()+'px; opacity: 0.7; display: table;"><div style="display: table-cell;text-align: center; vertical-align: middle; "><img src="../views/bootstrap/images/ajax-loader.gif"></div>');
		$.get(url, function(data) {
			element.html(data);
			$(".chzn-select").select2({
				width: '100%',
				templateResult: chzn_template_func//,
				//templateSelection: chzn_template_func
			});
			$(".pwd").passStrength({ /* {{{ */
				url: "../op/op.Ajax.php",
				onChange: function(data, target) {
					pwsp = 100*data.score;
					$('#'+target+' div.bar').width(pwsp+'%');
					if(data.ok) {
						$('#'+target+' div.bar').removeClass('bar-danger');
						$('#'+target+' div.bar').addClass('bar-success');
					} else {
						$('#'+target+' div.bar').removeClass('bar-success');
						$('#'+target+' div.bar').addClass('bar-danger');
					}
				}
			}); /* }}} */
			if(afterload) {
				var func = eval(afterload);
				if(typeof func === "function"){
					func();
				}
			}
		});
	}); /* }}} */

	$('div.ajax').on('update', function(event, param1, callback) { /* {{{ */
		var element = $(this);
		var url = '';
		var href = element.data('href');
		var base = element.data('base');
		if(typeof base == 'undefined')
			base = '';
		var view = element.data('view');
		var action = element.data('action');
		var query = element.data('query');
		var afterload = $(this).data('afterload');
		if(view && action) {
			url = seeddms_webroot+base+"out/out."+view+".php?action="+action;
			if(query) {
				url += "&"+query;
			}
		} else
			url = href;
		if(typeof param1 === 'object') {
			for(var key in param1) {
				if(key == 'callback')
					callback = param1[key];
				else {
					if($.isArray(param1[key])) {
						if(param1[key].length > 0)
							url += "&"+key+"[]="+param1[key].join("&"+key+"[]=");
					} else
						url += "&"+key+"="+param1[key];
				}
			}
		} else {
			url += "&"+param1;
		}
		console.log(url);
		if(!element.data('no-spinner'))
			element.prepend('<div style="position: absolute; overflow: hidden; background: #f7f7f7; z-index: 1000; height: '+element.height()+'px; width: '+element.width()+'px; opacity: 0.7; display: table;"><div style="display: table-cell;text-align: center; vertical-align: middle; "><img src="../views/bootstrap/images/ajax-loader.gif"></div>');
		$.get(url, function(data) {
			element.html(data);
			$(".chzn-select").select2({
				width: '100%',
				templateResult: chzn_template_func//,
				//templateSelection: chzn_template_func
			});
			$(".pwd").passStrength({ /* {{{ */
				url: "../op/op.Ajax.php",
				onChange: function(data, target) {
					pwsp = 100*data.score;
					$('#'+target+' div.bar').width(pwsp+'%');
					if(data.ok) {
						$('#'+target+' div.bar').removeClass('bar-danger');
						$('#'+target+' div.bar').addClass('bar-success');
					} else {
						$('#'+target+' div.bar').removeClass('bar-success');
						$('#'+target+' div.bar').addClass('bar-danger');
					}
				}
			}); /* }}} */
			if(callback)
				callback.call();
			if(afterload) {
				var func = eval(afterload);
				if(typeof func === "function"){
					func();
				}
			}
		});
	}); /* }}} */

	$("body").on("click", ".ajax-click", function() { /* {{{ */
		var element = $(this);
		var url = element.data('href')+"?"+element.data('param1');
		$.ajax({
			type: 'GET',
			url: url,
			dataType: 'json',
			success: function(data){
				if(data.success) {
					if(element.data('param1') == 'command=clearclipboard') {
						$("#main-clipboard").html('Loading').load('../out/out.Clipboard.php?action=mainclipboard')
						$("#menu-clipboard div").html('Loading').load('../out/out.Clipboard.php?action=menuclipboard')
					}
					noty({
						text: data.message,
						type: 'success',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500
					});
				} else {
					noty({
						text: data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 3500
					});
				}
			}
		});
	}); /* }}} */

	$('button.history-back').on('click', function(event) { /* {{{ */
		window.history.back();
	}); /* }}} */

	$("body").on("blur", "span.editable", function(e) { /* {{{ */
		console.log($(this).data('document'));
		console.log('Hallo'+$(this).text());
		e.preventDefault();
		$.post( "../op/op.Ajax.php", { command: "setdocumentname", id: $(this).data('document'), name: $(this).text() })
			.done(function( data ) {
				noty({
					text: data.message,
					type: data.success ? 'success' : 'error',
					dismissQueue: true,
					layout: 'topRight',
					theme: 'defaultTheme',
					timeout: 1500
				});
		});
	}); /* }}} */

	$("body").on("keypress", "span.editable", function(e) { /* {{{ */
		if(e.which == 13) {
			$(this).blur();
		}
		return e.which != 13;
	}); /* }}} */
});

function onAddClipboard(ev) { /* {{{ */
	ev.preventDefault();
	var source_info = JSON.parse(ev.originalEvent.dataTransfer.getData("text"));
	source_type = source_info.type;
	source_id = source_info.id;
	formtoken = source_info.formtoken;
	if(source_type == 'document' || source_type == 'folder') {
		$.get('../op/op.Ajax.php',
			{ command: 'addtoclipboard', type: source_type, id: source_id },
			function(data) {
				if(data.success) {
					$("#main-clipboard").html('Loading').load('../out/out.Clipboard.php?action=mainclipboard')
					$("#menu-clipboard div").html('Loading').load('../out/out.Clipboard.php?action=menuclipboard')
					noty({
						text: data.message,
						type: 'success',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500
					});
				} else {
					noty({
						text: data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 3500
					});
				}
			},
			'json'
		);
		//url = "../op/op.AddToClipboard.php?id="+source_id+"&type="+source_type;
		//document.location = url;
	}
} /* }}} */

(function( SeedDMSUpload, $, undefined ) { /* {{{ */
	var ajaxurl = "../op/op.Ajax.php";
	var editBtnLabel = "Edit";
	var abortBtnLabel = "Abort";
	var maxFileSize = 100000;
	var maxFileSizeMsg = 'File too large';
	var rowCount=0;

	SeedDMSUpload.setUrl = function(url)  {
		ajaxurl = url;
	}

	SeedDMSUpload.setAbortBtnLabel = function(label)  {
		abortBtnLabel = label;
	}

	SeedDMSUpload.setEditBtnLabel = function(label)  {
		editBtnLabel = label;
	}

	SeedDMSUpload.setMaxFileSize = function(size)  {
		maxFileSize = size;
	}

	SeedDMSUpload.setMaxFileSizeMsg = function(msg)  {
		maxFileSizeMsg = msg;
	}

	function sendFileToServer(formData,status,callback) {
		var uploadURL = ajaxurl; //Upload URL
		var extraData ={}; //Extra Data.
		var jqXHR=$.ajax({
			xhr: function() {
			var xhrobj = $.ajaxSettings.xhr();
			if (xhrobj.upload) {
				xhrobj.upload.addEventListener('progress', function(event) {
						var percent = 0;
						var position = event.loaded || event.position;
						var total = event.total;
						if (event.lengthComputable) {
								percent = Math.ceil(position / total * 100);
						}
						//Set progress
						status.setProgress(percent);
					}, false);
				}
				return xhrobj;
			},
			url: uploadURL,
			type: "POST",
			contentType: false,
			dataType:"json",
			processData: false,
			cache: false,
			data: formData,
			success: function(data, textStatus) {
				status.setProgress(100);
				if(data.success) {
					noty({
						text: data.message,
						type: 'success',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500
					});
					status.statusbar.after($('<a href="../out/out.EditDocument.php?documentid=' + data.data + '" class="btn btn-mini btn-primary">' + editBtnLabel + '</a>'));
					if(callback) {
						callback();
					}
				} else {
					noty({
						text: data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 3500
					});
				}
			}
		});

		status.setAbort(jqXHR);
	}

	function createStatusbar(obj) {
		rowCount++;
		var row="odd";
		this.obj = obj;
		if(rowCount %2 ==0) row ="even";
		this.statusbar = $("<div class='statusbar "+row+"'></div>");
		this.filename = $("<div class='filename'></div>").appendTo(this.statusbar);
		this.size = $("<div class='filesize'></div>").appendTo(this.statusbar);
		this.progressBar = $("<div class='progress'><div class='bar bar-success'></div></div>").appendTo(this.statusbar);
		this.abort = $("<div class='btn btn-mini btn-danger'>" + abortBtnLabel + "</div>").appendTo(this.statusbar);
//		$('.statusbar').empty();
		obj.after(this.statusbar);
		this.setFileNameSize = function(name,size) {
			var sizeStr="";
			var sizeKB = size/1024;
			if(parseInt(sizeKB) > 1024) {
				var sizeMB = sizeKB/1024;
				sizeStr = sizeMB.toFixed(2)+" MB";
			} else {
				sizeStr = sizeKB.toFixed(2)+" KB";
			}

			this.filename.html(name);
			this.size.html(sizeStr);
		}
		this.setProgress = function(progress) {
			var progressBarWidth =progress*this.progressBar.width()/ 100;
			this.progressBar.find('div').animate({ width: progressBarWidth }, 10).html(progress + "% ");
			if(parseInt(progress) >= 100) {
				this.abort.hide();
			}
		}
		this.setAbort = function(jqxhr) {
			var sb = this.statusbar;
			this.abort.click(function() {
				jqxhr.abort();
				sb.hide();
			});
		}
	}

	SeedDMSUpload.handleFileUpload = function(files,obj,statusbar) {
		/* target is set for the quick upload area */
		var target_id = obj.data('target');
		var target_type = 'folder';
		/* droptarget is set for folders and documents in lists */
		var droptarget = obj.data('droptarget');
		if(droptarget) {
			target_type = droptarget.split("_")[0];
			target_id = droptarget.split("_")[1];
		}
		if(target_type == 'folder' && target_id) {
			for (var i = 0; i < files.length; i++) {
				if(files[i].size <= maxFileSize) {
					var fd = new FormData();
					fd.append('targettype', target_type);
					fd.append('folderid', target_id);
					fd.append('formtoken', obj.data('uploadformtoken'));
					fd.append('userfile', files[i]);
					fd.append('command', 'uploaddocument');
//					fd.append('path', files[i].webkitRelativePath);

					statusbar.parent().show();
					var status = new createStatusbar(statusbar);
					status.setFileNameSize(files[i].name,files[i].size);
					sendFileToServer(fd,status,function(){
						if(target_id == seeddms_folder)
							$("div.ajax[data-action='folderList']").trigger('update', {folderid: seeddms_folder});
					});
				} else {
					noty({
						text: maxFileSizeMsg + '<br /><em>' + files[i].name + ' (' + files[i].size + ' Bytes)</em>',
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 5000
					});
				}
			}
		} else if(target_type == 'document' && target_id) {
			/*
			for (var i = 0; i < files.length; i++) {
				if(files[i].size <= maxFileSize) {
					var fd = new FormData();
					fd.append('targettype', target_type);
					fd.append('documentid', target_id);
					fd.append('formtoken', obj.data('uploadformtoken'));
					fd.append('userfile', files[i]);
					fd.append('command', 'uploaddocument');

					var status = new createStatusbar(statusbar);
					status.setFileNameSize(files[i].name,files[i].size);
					sendFileToServer(fd,status);
				} else {
					noty({
						text: maxFileSizeMsg + '<br /><em>' + files[i].name + ' (' + files[i].size + ' Bytes)</em>',
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 5000
					});
				}
			}
			*/
		}
	}
}( window.SeedDMSUpload = window.SeedDMSUpload || {}, jQuery )); /* }}} */

$(document).ready(function() { /* {{{ */
	$(document).on('dragenter', "#draganddrophandler", function (e) {
		e.stopPropagation();
		e.preventDefault();
		$(this).css('border', '2px dashed #0B85A1');
	});
	$(document).on('dragleave', "#draganddrophandler", function (e) {
		$(this).css('border', '0px solid white');
	});
	$(document).on('dragover', "#draganddrophandler", function (e) {
		e.stopPropagation();
		e.preventDefault();
	});
	$(document).on('drop', "#draganddrophandler", function (e) {
		$(this).css('border', '0px dotted #0B85A1');
		e.preventDefault();
		var files = e.originalEvent.dataTransfer.files;

		//We need to send dropped files to Server
		SeedDMSUpload.handleFileUpload(files, $(this), $(this));
	});

	$(document).on('dragenter', '.droptarget', function (e) {
		e.stopPropagation();
		e.preventDefault();
		$(e.currentTarget).css('border', '2px dashed #0B85A1');
	});
	$(document).on('dragleave', '.droptarget', function (e) {
		e.stopPropagation();
		e.preventDefault();
		$(e.currentTarget).css('border', '0px solid white');
	});
	$(document).on('dragover', '.droptarget', function (e) {
		e.stopPropagation();
		e.preventDefault();
	});
	$(document).on('drop', '.droptarget', function (e) {
		e.preventDefault();
		e.stopPropagation();
		$(e.currentTarget).css('border', '0px solid white');
		attr_rel = $(e.currentTarget).data('droptarget');
		target_type = attr_rel.split("_")[0];
		target_id = attr_rel.split("_")[1];
		target_name = $(e.currentTarget).data('name')+''; // Force this to be a string
		if(target_type == 'folder') {
			var files = e.originalEvent.dataTransfer.files;
			if(files.length > 0) {
//				console.log('Drop '+files.length+' files on '+target_type+' '+target_id);
				SeedDMSUpload.handleFileUpload(files,$(e.currentTarget),$('div.statusbar-container h1')/*$(e.currentTarget).find("span")*/);
			} else {
				var source_info = JSON.parse(e.originalEvent.dataTransfer.getData("text"));
				source_type = source_info.type;
				source_id = source_info.id;
				formtoken = source_info.formtoken;
//				console.log('Drop '+source_type+' '+source_id+' on '+target_type+' '+target_id);
				if(source_type == 'document') {
					var bootbox_message = trans.confirm_move_document;
					if(source_info.name)
						bootbox_message += "<p> "+escapeHtml(source_info.name)+' <i class="fa fa-arrow-right"></i> '+escapeHtml(target_name)+"</p>";
					bootbox.dialog(bootbox_message, [{
						"label" : "<i class='fa fa-remove'></i> "+trans.move_document,
						"class" : "btn-danger",
						"callback": function() {
							$.get('../op/op.Ajax.php',
								{ command: 'movedocument', docid: source_id, targetfolderid: target_id, formtoken: formtoken },
								function(data) {
									if(data.success) {
										$('#table-row-document-'+source_id).hide('slow');
										noty({
											text: data.message,
											type: 'success',
											dismissQueue: true,
											layout: 'topRight',
											theme: 'defaultTheme',
											timeout: 1500
										});
									} else {
										noty({
											text: data.message,
											type: 'error',
											dismissQueue: true,
											layout: 'topRight',
											theme: 'defaultTheme',
											timeout: 3500
										});
									}
								},
								'json'
							);
						}
					}, {
						"label" : trans.cancel,
						"class" : "btn-cancel",
						"callback": function() {
						}
					}]);

					url = "../out/out.MoveDocument.php?documentid="+source_id+"&targetid="+target_id;
		//			document.location = url;
				} else if(source_type == 'folder' && source_id != target_id) {
					var bootbox_message = trans.confirm_move_folder;
					if(source_info.name)
						bootbox_message += "<p> "+escapeHtml(source_info.name)+' <i class="fa fa-arrow-right"></i> '+escapeHtml(target_name)+"</p>";
					bootbox.dialog(bootbox_message, [{
						"label" : "<i class='fa fa-remove'></i> "+trans.move_folder,
						"class" : "btn-danger",
						"callback": function() {
							$.get('../op/op.Ajax.php',
								{ command: 'movefolder', folderid: source_id, targetfolderid: target_id, formtoken: formtoken },
								function(data) {
									if(data.success) {
										$('#table-row-folder-'+source_id).hide('slow');
										noty({
											text: data.message,
											type: 'success',
											dismissQueue: true,
											layout: 'topRight',
											theme: 'defaultTheme',
											timeout: 1500
										});
									} else {
										noty({
											text: data.message,
											type: 'error',
											dismissQueue: true,
											layout: 'topRight',
											theme: 'defaultTheme',
											timeout: 3500
										});
									}
								},
								'json'
							);
						}
					}, {
						"label" : trans.cancel,
						"class" : "btn-cancel",
						"callback": function() {
						}
					}]);

					url = "../out/out.MoveFolder.php?folderid="+source_id+"&targetid="+target_id;
		//			document.location = url;
				}
			}
		} else if(target_type == 'document') {
			var files = e.originalEvent.dataTransfer.files;
			if(files.length > 0) {
//				console.log('Drop '+files.length+' files on '+target_type+' '+target_id);
				SeedDMSUpload.handleFileUpload(files,$(e.currentTarget),$('div.statusbar-container h1')/*$(e.currentTarget).find("span")*/);
			} else {
				var source_info = JSON.parse(e.originalEvent.dataTransfer.getData("text"));
				source_type = source_info.type;
				source_id = source_info.id;
				formtoken = source_info.formtoken;
//				console.log('Drop '+source_type+' '+source_id+' on '+target_type+' '+target_id);
				if(source_type == 'document') {
					if(source_id != target_id) {
						bootbox.dialog(trans.confirm_transfer_link_document, [{
							"label" : "<i class='fa fa-remove'></i> "+trans.transfer_content,
							"class" : "btn-danger",
							"callback": function() {
								$.get('../op/op.Ajax.php',
									{ command: 'transfercontent', docid: source_id, targetdocumentid: target_id, formtoken: formtoken },
									function(data) {
										if(data.success) {
											$('#table-row-document-'+source_id).hide('slow');
											noty({
												text: data.message,
												type: 'success',
												dismissQueue: true,
												layout: 'topRight',
												theme: 'defaultTheme',
												timeout: 1500
											});
										} else {
											noty({
												text: data.message,
												type: 'error',
												dismissQueue: true,
												layout: 'topRight',
												theme: 'defaultTheme',
												timeout: 3500
											});
										}
									},
									'json'
								);
							}
						}, {
							"label" : trans.link_document,
							"class" : "btn-danger",
							"callback": function() {
								$.get('../op/op.Ajax.php',
									{ command: 'linkdocument', docid: source_id, targetdocumentid: target_id, formtoken: formtoken },
									function(data) {
										if(data.success) {
											noty({
												text: data.message,
												type: 'success',
												dismissQueue: true,
												layout: 'topRight',
												theme: 'defaultTheme',
												timeout: 1500
											});
										} else {
											noty({
												text: data.message,
												type: 'error',
												dismissQueue: true,
												layout: 'topRight',
												theme: 'defaultTheme',
												timeout: 3500
											});
										}
									},
									'json'
								);
							}
						}, {
							"label" : trans.cancel,
							"class" : "btn-cancel",
							"callback": function() {
							}
						}]);
					}

					url = "../out/out.MoveDocument.php?documentid="+source_id+"&targetid="+target_id;
		//			document.location = url;
				}
			}
		} else if(target_type == 'attachment') {
			console.log('attachment');
			var files = e.originalEvent.dataTransfer.files;
			if(files.length > 0) {
			}
		}
	});
	$(document).on('dragstart', '.table-row-folder', function (e) {
		attr_rel = $(e.target).attr('rel');
		if(typeof attr_rel == 'undefined')
			return;
		var dragStartInfo = {
			id : attr_rel.split("_")[1],
			type : "folder",
			formtoken : $(e.target).attr('formtoken'),
			name: $(e.target).data('name')+''
		};
		/* Currently not used
		$.ajax({url: '../out/out.ViewFolder.php',
			type: 'GET',
			dataType: "json",
			data: {action: 'data', folderid: attr_rel.split("_")[1]},
			success: function(data) {
				if(data) {
					dragStartInfo.source = data;
				}
			},
			timeout: 3000
		});
		*/
		e.originalEvent.dataTransfer.setData("text", JSON.stringify(dragStartInfo));
	});

	$(document).on('dragstart', '.table-row-document', function (e) {
		attr_rel = $(e.target).attr('rel');
		if(typeof attr_rel == 'undefined')
			return;
		var dragStartInfo = {
			id : attr_rel.split("_")[1],
			type : "document",
			formtoken : $(e.target).attr('formtoken'),
			name: $(e.target).data('name')+''
		};
		e.originalEvent.dataTransfer.setData("text", JSON.stringify(dragStartInfo));
	});

	/* Dropping item on alert below clipboard */
	$(document).on('dragenter', '.add-clipboard-area', function (e) {
		e.stopPropagation();
		e.preventDefault();
		$(this).css('border', '2px dashed #0B85A1');
	});
	$(document).on('dragleave', '.add-clipboard-area', function (e) {
		$(this).css('border', '0px solid white');
	});
	$(document).on('dragover', '.add-clipboard-area', function (e) {
		e.preventDefault();
	});
	$(document).on('drop', '.add-clipboard-area', function (e) {
		$(this).css('border', '0px dotted #0B85A1');
		onAddClipboard(e);
	});

	$("#jqtree").on('dragenter', function (e) {
		attr_rel = $(e.srcElement).attr('rel');
		if(typeof attr_rel == 'undefined')
			return;
		$(e.target).parent().css('border', '1px dashed #0B85A1');
		e.stopPropagation();
		e.preventDefault();
	});
	$("#jqtree").on('dragleave', function (e) {
		attr_rel = $(e.srcElement).attr('rel');
		if(typeof attr_rel == 'undefined')
			return;
		$(e.target).parent().css('border', '0px solid white');
		e.stopPropagation();
		e.preventDefault();
	});
	$("#jqtree").on('dragover', function (e) {
		e.stopPropagation();
		e.preventDefault();
	});
	$("#jqtree").on('drop', function (e) {
		e.stopPropagation();
		e.preventDefault();
		attr_rel = $(e.target).attr('rel');
		if(typeof attr_rel == 'undefined')
			return;
		$(e.target).parent().css('border', '1px solid white');
		target_type = attr_rel.split("_")[0];
		target_id = attr_rel.split("_")[1];
		var source_info = JSON.parse(e.originalEvent.dataTransfer.getData("text"));
		source_type = source_info.type;
		source_id = source_info.id;
		formtoken = source_info.formtoken;
		if(source_type == 'document') {
			bootbox.dialog(trans.confirm_move_document, [{
				"label" : "<i class='fa fa-remove'></i> "+trans.move_document,
				"class" : "btn-danger",
				"callback": function() {
					$.get('../op/op.Ajax.php',
						{ command: 'movedocument', docid: source_id, targetfolderid: target_id, formtoken: formtoken },
						function(data) {
							if(data.success) {
								$('#table-row-document-'+source_id).hide('slow');
								noty({
									text: data.message,
									type: 'success',
									dismissQueue: true,
									layout: 'topRight',
									theme: 'defaultTheme',
									timeout: 1500
								});
							} else {
								noty({
									text: data.message,
									type: 'error',
									dismissQueue: true,
									layout: 'topRight',
									theme: 'defaultTheme',
									timeout: 3500
								});
							}
						},
						'json'
					);
				}
			}, {
				"label" : trans.cancel,
				"class" : "btn-cancel",
				"callback": function() {
				}
			}]);

			url = "../out/out.MoveDocument.php?documentid="+source_id+"&targetid="+target_id;
//			document.location = url;
		} else if(source_type == 'folder' && source_id != target_id) {
			bootbox.dialog(trans.confirm_move_folder, [{
				"label" : "<i class='fa fa-remove'></i> "+trans.move_folder,
				"class" : "btn-danger",
				"callback": function() {
					$.get('../op/op.Ajax.php',
						{ command: 'movefolder', folderid: source_id, targetfolderid: target_id, formtoken: formtoken },
						function(data) {
							if(data.success) {
								$('#table-row-folder-'+source_id).hide('slow');
								noty({
									text: data.message,
									type: 'success',
									dismissQueue: true,
									layout: 'topRight',
									theme: 'defaultTheme',
									timeout: 1500
								});
							} else {
								noty({
									text: data.message,
									type: 'error',
									dismissQueue: true,
									layout: 'topRight',
									theme: 'defaultTheme',
									timeout: 3500
								});
							}
						},
						'json'
					);
				}
			}, {
				"label" : trans.cancel,
				"class" : "btn-cancel",
				"callback": function() {
				}
			}]);

			url = "../out/out.MoveFolder.php?folderid="+source_id+"&targetid="+target_id;
//			document.location = url;
		}
	});

	$('div.splash').each(function(index) {
		var element = $(this);
		var msgtype = element.data('type');
		var timeout = element.data('timeout');
		var msg = element.text();
		noty({
			text: msg,
			type: msgtype,
			dismissQueue: true,
			layout: 'topRight',
			theme: 'defaultTheme',
			timeout: (typeof timeout == 'undefined' ? 1500 : timeout)
		});
	});

	$("body").on("click", "span.openpopupbox", function(e) {
		$(""+$(e.target).data("href")).toggle();
		e.stopPropagation();
	});
	$("body").on("click", "span.openpopupbox i", function(e) {
		$(e.target).parent().click();
	});
	$("body").on("click", "span.openpopupbox span", function(e) {
		$(e.target).parent().click();
	});
	$("body").on("click", "span.closepopupbox", function(e) {
		$(this).parent().hide();
		e.stopPropagation();
	});

	$("body").on("mouseenter", "#main-menu-dropfolderlist ul.dropdown-menu li a", function(e) {
		$(e.currentTarget).find('.dropfolder-menu-img').css('display', 'inline');
	});
	$("body").on("mouseleave", "#main-menu-dropfolderlist ul.dropdown-menu li a", function(e) {
		$(e.currentTarget).find('.dropfolder-menu-img').hide();
	});

}); /* }}} */

(function( SeedDMSTask, $, undefined ) { /* {{{ */
	var approval_count, review_count, workflow_count, receipt_count, revision_count, needscorrection_count, rejected_count, checkedout_count;
	var timeout = 1000;
	var counter = 0;
	var tasks = Array(
		{name: 'checktasks', interval: 15, func: 
			checkTasks = function() {
				$.ajax({url: '../out/out.Tasks.php',
					type: 'GET',
					dataType: "json",
					data: {action: 'mytasks'},
					success: function(data) {
						if(data) {
							if((typeof data.data.approval != 'undefined' && approval_count != data.data.approval.length) ||
								 (typeof data.data.review != 'undefined' && review_count != data.data.review.length) ||
								 (typeof data.data.receipt != 'undefined' && receipt_count != data.data.receipt.length) ||
								 (typeof data.data.revision != 'undefined' && revision_count != data.data.revision.length) ||
								 (typeof data.data.needscorrection != 'undefined' && needscorrection_count != data.data.needscorrection.length) ||
								 (typeof data.data.rejected != 'undefined' && rejected_count != data.data.rejected.length) ||
								 (typeof data.data.checkedout != 'undefined' && checkedout_count != data.data.checkedout.length) ||
								 (typeof data.data.workflow != 'undefined' && workflow_count != data.data.workflow.length)) {
		//						$("#menu-tasks").html('Loading').hide().load('../out/out.Tasks.php?action=menutasks').fadeIn('500')
								$('#menu-tasks > div.ajax').trigger('update', {folderid: seeddms_folder});
								approval_count = typeof data.data.approval != 'undefined' ? data.data.approval.length : 0;
								review_count = typeof data.data.review != 'undefined' ? data.data.review.length : 0;
								receipt_count = typeof data.data.receipt != 'undefined' ? data.data.receipt.length : 0;
								revision_count = typeof data.data.revision != 'undefined' ? data.data.revision.length : 0;
								needscorrection_count = typeof data.data.needscorrection != 'undefined' ? data.data.needscorrection.length : 0;
								rejected_count = typeof data.data.rejected != 'undefined' ? data.data.rejected.length : 0;
								checkedout_count = typeof data.data.checkedout != 'undefined' ? data.data.checkedout.length : 0;
								workflow_count = typeof data.data.workflow != 'undefined' ? data.data.workflow.length : 0;
							}
						}
					},
					timeout: 3000
				}); 
			}
		}
	);

	SeedDMSTask.add = function(task) {
		tasks.push(task);
	}

	SeedDMSTask.run = function() {
		for(let task of tasks) {
			if(counter % task.interval == 0) {
//			console.log("Running task '" + task.name + "'");
				task.func();
			}
		}
		//console.log(counter);
		counter++;
		timeOutId = setTimeout(SeedDMSTask.run, timeout);
	}
}( window.SeedDMSTask = window.SeedDMSTask || {}, jQuery )); /* }}} */
