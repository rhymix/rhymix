/**
 * @file   modules/board/tpl/js/board_category.js
 * @author sol (sol@ngleader.com)
 * @brief  board 모듈의 category tree javascript
 **/

function Tree(url){
    // clear tree;
    jQuery('#menu > ul > li > ul').remove();
    if(jQuery("ul.simpleTree > li > a").size() ==0)jQuery('<a href="#" class="add"><img src="./common/js/plugins/ui.tree/images/iconAdd.gif" /></a>').bind("click",function(e){addNode(0,e);}).appendTo("ul.simpleTree > li");

    //ajax get data and transeform ul il
    jQuery.get(url,function(data){
        jQuery(data).find("node").each(function(i){
            var text = jQuery(this).attr("text");
            var node_srl = jQuery(this).attr("node_srl");
            var parent_srl = jQuery(this).attr("parent_srl");
            var color = jQuery(this).attr("color");
            var url = jQuery(this).attr("url");

            // node
            var node = '';
            if(color){
                node = jQuery('<li id="tree_'+node_srl+'"><span style="color:'+color+';">'+text+'</span></li>');
            }else{
                node = jQuery('<li id="tree_'+node_srl+'"><span>'+text+'</span></li>');
            }

            // button
            jQuery('<a href="#" class="add"><img src="./common/js/plugins/ui.tree/images/iconAdd.gif" /></a>').bind("click",function(e){
                jQuery("#tree_"+node_srl+" > span").click();
                addNode(node_srl,e);
                return false;
            }).appendTo(node);

            jQuery('<a href="#" class="modify"><img src="./common/js/plugins/ui.tree/images/iconModify.gif" /></a>').bind("click",function(e){
                jQuery("#tree_"+node_srl+" > span").click();
                modifyNode(node_srl,e);
                return false;
            }).appendTo(node);

            jQuery('<a href="#" class="delete"><img src="./common/js/plugins/ui.tree/images/iconDel.gif" /></a>').bind("click",function(e){
                deleteNode(node_srl);
                return false;
            }).appendTo(node);

            // insert parent child
            if(parent_srl>0){
                if(jQuery('#tree_'+parent_srl+'>ul').length==0) jQuery('#tree_'+parent_srl).append(jQuery('<ul>'));
                jQuery('#tree_'+parent_srl+'> ul').append(node);
            }else{
                if(jQuery('#menu ul.simpleTree > li > ul').length==0) jQuery("<ul>").appendTo('#menu ul.simpleTree > li');
                jQuery('#menu ul.simpleTree > li > ul').append(node);
            }

        });

        //button show hide
        jQuery("#menu li").each(function(){
            if(jQuery(this).parents('ul').size() > max_menu_depth) jQuery("a.add",this).hide();
            if(jQuery(">ul",this).size()>0) jQuery(">a.delete",this).hide();
        });


        // draw tree
        simpleTreeCollection = jQuery('.simpleTree').simpleTree({
            autoclose: false,
            afterClick:function(node){
                jQuery('#category_info').html("");
                //alert("text-"+jQuery('span:first',node).text());
            },
            afterDblClick:function(node){
                //alert("text-"+jQuery('span:first',node).text());
            },
            afterMove:function(destination, source, pos){
                if(destination.size() == 0){
                    Tree(xml_url);
                    return;
                }
                var module_srl = jQuery("#fo_category input[name=module_srl]").val();
                var parent_srl = destination.attr('id').replace(/.*_/g,'');
                var source_srl = source.attr('id').replace(/.*_/g,'');

                var target = source.prevAll("li:not([class^=line])");
                var target_srl = 0;
                if(target.length >0){
                    target_srl = source.prevAll("li:not([class^=line])").get(0).id.replace(/.*_/g,'');
                    parent_srl = 0;
                }

                jQuery.exec_json("board.procBoardAdminMoveCategory",{ "module_srl":module_srl,"parent_srl":parent_srl,"target_srl":target_srl,"source_srl":source_srl},
                function(data){
                    jQuery('#category_info').html('');
                   if(data.error > 0) Tree(xml_url);
                });

            },

            // i want you !! made by sol
            beforeMovedToLine : function(destination, source, pos){
                return (jQuery(destination).parents('ul').size() + jQuery('ul',source).size() <= max_menu_depth);
            },

            // i want you !! made by sol
            beforeMovedToFolder : function(destination, source, pos){
                return (jQuery(destination).parents('ul').size() + jQuery('ul',source).size() <= max_menu_depth-1);
            },
            afterAjax:function()
            {
                //alert('Loaded');
            },
            animate:true
            ,docToFolderConvert:true
        });



        // open all node
        nodeToggleAll();
    },"xml");
}
function addNode(node,e){
    var params ={
            "category_srl":0
            ,"parent_srl":node
            ,"module_srl":jQuery("#fo_category [name=module_srl]").val()
            };

    jQuery.exec_json('board.getBoardAdminCategoryTplInfo', params, function(data){
        jQuery('#category_info').html(data.tpl);
    });
}

function modifyNode(node,e){
    var params ={
            "category_srl":node
            ,"parent_srl":0
            ,"module_srl":jQuery("#fo_category [name=module_srl]").val()
            };

    jQuery.exec_json('board.getBoardAdminCategoryTplInfo', params, function(data){
        jQuery('#category_info').html(data.tpl);
    });
}


function nodeToggleAll(){
    jQuery("[class*=close]", simpleTreeCollection[0]).each(function(){
        simpleTreeCollection[0].nodeToggle(this);
    });
}

function deleteNode(node){
    if(confirm(lang_confirm_delete)){
        jQuery('#category_info').html("");
        var params ={
                "category_srl":node
                ,"parent_srl":0
                ,"module_srl":jQuery("#fo_category [name=module_srl]").val()
                };

        jQuery.exec_json('board.procBoardAdminDeleteCategory', params, function(data){
            if(data.error==0) Tree(xml_url);
        });
    }
}

/* 카테고리 아이템 입력후 */
function completeInsertCategory(ret_obj) {
    jQuery('#category_info').html("");
    Tree(xml_url);
}

function hideCategoryInfo() {
    jQuery('#category_info').html("");
}

/* 카테고리 목록 갱신 */
function doReloadTreeCategory(module_srl) {
    var params = new Array();
    params["module_srl"] = module_srl;

    // 서버에 요청하여 해당 노드의 정보를 수정할 수 있도록 한다.
    var response_tags = new Array('error','message', 'xml_file');
    exec_xml('board', 'procBoardAdminMakeXmlFile', params, completeInsertCategory, response_tags, params);
}