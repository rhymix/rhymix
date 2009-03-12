
;(function($) {

    var defaults = {
    };

    var filebox = {
        selected : null,
        /**
         * 파일 박스 창 팝업
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
         * 파일 선택
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
         * 파일 선택 취소
         */
        cancel : function(name) {
            $('[name=' + name + ']').val('');
            $('#filebox_preview_' + name).hide().html('');
            $('#filebox_cancel_' + name).hide();
        },

        /**
         * 파일 삭제
         */
        deleteFile : function(module_filebox_srl){
            var params = {
                'module_filebox_srl' : module_filebox_srl
            };

            $.exec_json('module.procModuleFileBoxDelete', params, function() { document.location.reload(); });
        },

        /**
         * 초기화
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

    // XE에 담기
    $.extend(window.XE, {'filebox' : filebox});

}) (jQuery);
