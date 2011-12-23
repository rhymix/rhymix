
;(function($) {

    var defaults = {
    };

    var filebox = {
        selected : null,
        /**
         * pop up the file box
         */
        open : function(input_obj, filter) {
            this.selected = input_obj;

            var url = request_uri
                .setQuery('module', 'module')
                .setQuery('act', 'dispModuleFileBox')
                .setQuery('input', this.selected.name)
                .setQuery('filter', filter);

            popopen(url, 'filebox');
        },

        /**
         * select a file
         */
        selectFile : function(file_url, module_filebox_srl){
            var target = $(opener.XE.filebox.selected);
            var target_name = target.attr('name');

            target.val(file_url);
            var html = _displayMultimedia(file_url, '100%', '100%');
            $('#filebox_preview_' + target_name, opener.document).html(html).show();
            $('#filebox_cancel_' + target_name, opener.document).show();

            window.close();
        },

        /**
         * cancel
         */
        cancel : function(name) {
            $('[name=' + name + ']').val('');
            $('#filebox_preview_' + name).hide().html('');
            $('#filebox_cancel_' + name).hide();
        },

        /**
         * delete a file
         */
        deleteFile : function(module_filebox_srl){
            var params = {
                'module_filebox_srl' : module_filebox_srl
            };

            $.exec_json('module.procModuleFileBoxDelete', params, function() { document.location.reload(); });
        },

        /**
         * initialize
         */
        init : function(name) {
            var file;

            if(opener && opener.selectedWidget && opener.selectedWidget.getAttribute("widget")) {
                file = opener.selectedWidget.getAttribute(name);
            } else if($('[name=' + name + ']').val()) {
                file = $('[name=' + name + ']').val();
            }

            if(file) {
                var html = _displayMultimedia(file, '100%', '100%');
                $('#filebox_preview_' + name).html(html).show();
                $('#filebox_cancel_' + name).show();
            }
        }
    };

    // put the file into XE
    $.extend(window.XE, {'filebox' : filebox});

}) (jQuery);

function addRow(ulId){
	var $ = jQuery;
	var count = $('#'+ulId).children().length;
	var clone = $('#'+ulId).find('li:last-child').prev().clone();
	$('#'+ulId).find('li:last-child').prev().find('.__addBtn').remove();

	clone.find('input[name^="attribute_name"]').attr("name", "attribute_name"+count).attr('value', '')
		.attr("id", "attribute_name"+count)
		.prev('label').attr('for', 'attribute_name'+count);
	clone.find('input[name^="attribute_value"]').attr("name", "attribute_value"+count).attr('value', '')
		.attr("id", "attribute_value"+count)
		.prev('label').attr('for', 'attribute_value'+count);
	clone.find('.__deleteBtn').attr("href", "javascript:clearRow('fileUp', "+count+")");

	$('#'+ulId).find('li:last-child').before(clone);
}

function clearRow(ulId,rowNumber){
	var $ = jQuery;
	var count = $('#'+ulId).children().length - 1;
	if (count <= 1) return;

	$('#'+ulId).find('input[name="attribute_name'+rowNumber+'"]').parent().remove();
}
