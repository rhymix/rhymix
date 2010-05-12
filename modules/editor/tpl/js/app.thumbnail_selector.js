/**
 * @author BNU <bnu@perbiz.co.kr>
 * @brief 썸네일 선택 및 크롭
 **/

;(function($) {
    var thumbnailSelector = xe.createApp('thumbnailSelector', {
        'configs' : [],
        'init' : function(editor_sequence) {
console.log('init', arguments);
        },
        'setConfig' : function(editor_sequence, settings) {
console.log('setConfig', arguments);
            var self = this;
            this.container = $('#thumbnail_selector');
            this.editor_sequence = editor_sequence;
            this.selectedImage = {};
            this.elObj = {
                'container' : this.container.find('.container'),
                'cropArea' : this.container.find('.cropArea .image'),
                'description' : this.container.find('p.description'),
                'btnSaveThumbnail' : this.container.find('button#btn_save_thumbnail')
            };
            this.elObj.btnSaveThumbnail.click(function() {
                self.cast('SAVE_THUMBNAIL_SETUP', []);
            });
            $.extend(this.configs[editor_sequence], settings || {});
        },
        'getConfig' : function(editor_sequence) {
console.log('setConfig', arguments);
            if(!this.configs[editor_sequence]) return {'asdf':'asdefseaff'};
            return this.configs[editor_sequence];
        },
        'setImage' : function(editor_sequence, settings) {
console.log('setImage', arguments);
            var self = this;
            var settings = uploaderSettings[editor_sequence];
            var fileListAreaID = settings["fileListAreaID"];
            var selected = $('#'+fileListAreaID+' option:selected');
            var file_srl = selected.val();
            if(!file_srl) return;

            var text = '';

            this.selectedImage = uploadedFiles[file_srl];
            console.log(this.selectedImage);

            this.elObj.description.text('선택된 이미지 : '+this.selectedImage.source_filename);

            if(this.selectedImage.direct_download == 'Y' && /\.(jpg|jpeg|png|gif)$/i.test(this.selectedImage.download_url)) {
                if(loaded_images[file_srl]) {
                    var obj = loaded_images[file_srl];
                } else {
                    var obj = new Image();
                    obj.src = this.selectedImage.download_url;
                }
                temp_code = '';
                temp_code += "<img src=\""+this.selectedImage.download_url+"\" alt=\""+this.selectedImage.source_filename+"\"";
                if(obj.complete == true) temp_code += " width=\""+obj.width+"\" height=\""+obj.height+"\"";
                temp_code += " />";
                text = temp_code;
            } else {
                alert('이미지만 선택해주세요');
                return;
            }

            this.container.show();
            console.log(this.container);
            this.elObj.cropArea.html(text);
            this.elObj.container.show();
            $('#previewddd').html("<img src=\""+this.selectedImage.download_url+"\" alt=\""+this.selectedImage.source_filename+"\""+" />").css({'width':100, 'height':100, 'overflow':'hidden'});
            this.imageObj = $('img', this.elObj.cropArea).imgAreaSelect({
                instance: true,
                aspectRatio: '4:3',
                handles: true,
                onSelectChange : function(img, selection) {
                    self.cast('IMG_AREA_SELECTCHANGE', [img, selection]);
                }
            });
        },
        API_IMG_AREA_SELECTCHANGE : function(self, info) {
console.log('API_IMG_AREA_SELECTCHANGE');
            var selection = info[1];
            if(!selection.width || !selection.height) return;
        },
        API_SAVE_THUMBNAIL_SETUP : function() {
console.log('API_SAVE_THUMBNAIL_SETUP', arguments);
            var thumbnailData = $.extend({}, this.imageObj.getSelection(), {
                'article_srl': editorRelKeys[this.editor_sequence]['primary'].value,
                'file_srl' : this.selectedImage.file_srl
            });

            if(!thumbnailData.width || !thumbnailData.height) {
                if(!confirm('지정된 영역이 없습니다. 이미지 전체 크기를 사용하시겠습니까?')) return;
                thumbnailData.fullsize = true;
            }

            $.exec_json('media.procMediaSetThumbnail', thumbnailData, function(ret) {
                alert(ret.message);
            });

        }
    });

    var oThumbnailSelector = new thumbnailSelector;
    xe.registerApp(oThumbnailSelector);
}) (jQuery);