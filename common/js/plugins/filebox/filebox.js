
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

function addRow(tableID){
	
	var table = document.getElementById(tableID);
    var rowCount = table.rows.length;
    var initial = table.rows(0);
    var text1 = initial.cells(0).children(0).innerHTML;
    var text2 = initial.cells(1).children(0).innerHTML;
    var addrow = table.rows(rowCount-2).cells(2).children(0);
    var deleteLink = table.rows(rowCount-2).cells(3).children(0).cloneNode(true);
    var row = table.insertRow(rowCount-1);
    
	//cell for attribute name
    var cell0= row.insertCell(0)
    var element0 = document.createElement("label");
    element0.innerHTML = text1;
    element0.htmlFor = "attribute_name"+rowCount;
    cell0.appendChild(element0);
    var element1 = document.createElement("input");
    element1.type = "text";
    element1.name="attribute_name"+rowCount;
    element1.id="attribute_name"+rowCount;
    cell0.appendChild(element1);
    
	//cell for attribute value
    var cell1 = row.insertCell(1);
    var element2 = document.createElement("label");
    element2.innerHTML = text2;
    element2.htmlFor = "attribute_value"+rowCount;
    cell1.appendChild(element2);
    var element3 = document.createElement("input");
    element3.type = "text";
    element3.id="attribute_value"+rowCount;
    element3.name="attribute_value"+rowCount;
    cell1.appendChild(element3);
    
    //cell for addrow link
    var cell2 = row.insertCell(2);
    cell2.appendChild(addrow);
    
    //cell for delete link
    var cell3 = row.insertCell(3);
    deleteLink.href = "javascript:clearRow('attributes',"+rowCount+")";
    cell3.appendChild(deleteLink);
}

function clearRow(tableID,rowNumber){
	var table = document.getElementById(tableID);
	var text = "attribute_name"+rowNumber;
	var rowCount = table.rows.length;
	var sw = 0;
	for(i=0;i<rowCount-2;i++){
		if(table.rows(i).cells[0].children(1).id == text) {
			table.deleteRow(i);
			sw = 1;
		}
	}
	if(!sw){
		var addrow = table.rows(rowCount-2).cells(2).children(0);
		table.rows(rowCount-3).cells(2).appendChild(addrow);
		table.deleteRow(rowCount-2);
	}
}
