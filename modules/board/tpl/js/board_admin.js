/**
 * @file   modules/board/js/board_admin.js
 * @author zero (zero@nzeo.com)
 * @brief  board 모듈의 관리자용 javascript
 **/


function Tree(url){
    // clear tree;
    jQuery('#menu > ul > li > ul').remove();
    if(jQuery("ul.simpleTree > li > a").size() ==0)jQuery('<a href="#" class="add"><img src="./common/tpl/images/tree/iconAdd.gif" /></a>').bind("click",function(e){addNode(0,e);}).appendTo("ul.simpleTree > li");

    //ajax get data and transeform ul il
    jQuery.get(url,function(data){
        jQuery(data).find("node").each(function(i){
            var text = jQuery(this).attr("text");
            var node_srl = jQuery(this).attr("node_srl");
            var parent_srl = jQuery(this).attr("parent_srl");
            var url = jQuery(this).attr("url");

            // node
            var node = jQuery('<li id="tree_'+node_srl+'"><span>'+text+'</span></li>');

            // button
            jQuery('<a href="#" class="add"><img src="./common/tpl/images/tree/iconAdd.gif" /></a>').bind("click",function(e){
                jQuery("#tree_"+node_srl+" > span").click();
                addNode(node_srl,e);
                return false;
            }).appendTo(node);

            jQuery('<a href="#" class="modify"><img src="./common/tpl/images/tree/iconModify.gif" /></a>').bind("click",function(e){
                jQuery("#tree_"+node_srl+" > span").click();
                modifyNode(node_srl,e);
                return false;
            }).appendTo(node);

            jQuery('<a href="#" class="delete"><img src="./common/tpl/images/tree/iconDel.gif" /></a>').bind("click",function(e){
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
                //alert("text-"+jQuery('span:first',node).text());
            },
            afterDblClick:function(node){
                //alert("text-"+jQuery('span:first',node).text());
            },
            afterMove:function(destination, source, pos){
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












/* 모듈 생성 후 */
function completeInsertBoard(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);

    var url = current_url.setQuery('act','dispBoardAdminBoardInfo');
    if(module_srl) url = url.setQuery('module_srl',module_srl);
    if(page) url.setQuery('page',page);
    location.href = url;
}

/* 모듈 삭제 후 */
function completeDeleteBoard(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    alert(message);

    var url = current_url.setQuery('act','dispBoardAdminContent').setQuery('module_srl','');
    if(page) url = url.setQuery('page',page);
    location.href = url;
}

/* 카테고리 관련 작업들 */
function doUpdateCategory(category_srl, mode, message) {
    if(typeof(message)!='undefined'&&!confirm(message)) return;

    var fo_obj = xGetElementById('fo_category_info');
    fo_obj.category_srl.value = category_srl;
    fo_obj.mode.value = mode;

    procFilter(fo_obj, update_category);
}

/* 카테고리 정보 수정 후 */
function completeUpdateCategory(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var module_srl = ret_obj['module_srl'];
    var page = ret_obj['page'];
    alert(message);

    var url = current_url.setQuery('module_srl',module_srl).setQuery('act','dispBoardAdminCategoryInfo');
    if(page) url.setQuery('page',page);
    location.href = url;
}

/* 권한 관련 */
function completeInsertGrant(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);
}

/* 카테고리 이동 */
function doChangeCategory(fo_obj) {
    var module_category_srl = fo_obj.module_category_srl.options[fo_obj.module_category_srl.selectedIndex].value;
    if(module_category_srl==-1) {
        location.href = current_url.setQuery('act','dispModuleAdminCategory');
        return false;
    }
    return true;
}


/* 일괄 설정 */
function doCartSetup(act_type) {
    var fo_obj = xGetElementById('fo_list');
    var module_srl = new Array();
    if(typeof(fo_obj.cart.length)=='undefined') {
        if(fo_obj.cart.checked) module_srl[module_srl.length] = fo_obj.cart.value;
    } else {
        for(var i=0;i<fo_obj.cart.length;i++) {
            if(fo_obj.cart[i].checked) module_srl[module_srl.length] = fo_obj.cart[i].value;
        }
    }
    if(module_srl.length<1) return;

    var url = current_url.setQuery('act',act_type).setQuery('module_srl','').setQuery('module_srls',module_srl.join(','));
    location.href = url;
}

/**
 * 카테고리 관리
 **/





/* 서버로부터 받아온 카테고리 정보를 출력 */
xAddEventListener(document,'mousedown',checkMousePosition);
var _xPos = 0;
var _yPos = 0;
function checkMousePosition(e) {
    var evt = new xEvent(e);
    _xPos = evt.pageX;
    _yPos = evt.pageY;
}



function completeGetCategoryTplInfo(ret_obj, response_tags) {
    var obj = xGetElementById('category_info');
    if(xScrollTop()>200) {
        obj.style.marginTop = ( xScrollTop() - 210 )+'px';
    } else {
        obj.style.marginTop = '0px';
    }

    var tpl = ret_obj['tpl'];
    xInnerHtml(obj, tpl);
    obj.style.display = 'block';

    var fo_obj = xGetElementById("fo_category");
    fo_obj.category_title.focus();
}




/* 카테고리를 드래그하여 이동한 후 실행할 함수 , 이동하는 category_srl과 대상 category_srl을 받음 */
function doMoveTree(category_id, source_category_srl, target_category_srl) {
    source_category_srl = source_category_srl.replace(/menu_category_/,'');
    target_category_srl = target_category_srl.replace(/menu_category_/,'');
    var p_fo_obj = xGetElementById("fo_category");

    var fo_obj = xGetElementById("fo_move_category");
    fo_obj.source_category_srl.value = source_category_srl;
    fo_obj.target_category_srl.value = target_category_srl;
    fo_obj.module_srl.value = p_fo_obj.module_srl.value;

    // 이동 취소를 선택하였을 경우 다시 그림;;
    if(!procFilter(fo_obj, move_category)) {
        var params = new Array();
        params["xml_file"] = xGetElementById('fo_category').xml_file.value;
        params["source_category_srl"] = source_category_srl;
        completeMoveCategory(params);
    }
}

function completeMoveCategory(ret_obj) {
    var source_category_srl = ret_obj['source_category_srl'];
    var xml_file = ret_obj['xml_file'];

    loadTreeMenu(xml_file, 'category', "zone_category", category_title, '', doGetCategoryInfo, source_category_srl, doMoveTree);
}

/* 카테고리 목록 갱신 */
function doReloadTreeCategory(module_srl) {
    var params = new Array();
    params["module_srl"] = module_srl;

    // 서버에 요청하여 해당 노드의 정보를 수정할 수 있도록 한다.
    var response_tags = new Array('error','message', 'xml_file');
    exec_xml('board', 'procBoardAdminMakeXmlFile', params, completeInsertCategory, response_tags, params);
}

/* 카테고리 삭제 */
function doDeleteCategory(category_srl) {
      var fo_obj = xGetElementById("fo_category");
      if(!fo_obj) return;

      procFilter(fo_obj, delete_category);
}

/* 카테고리 아이템 삭제 후 */
function completeDeleteCategory(ret_obj) {
    var module_srl = ret_obj['module_srl'];
    var category_srl = ret_obj['category_srl'];
    var xml_file = ret_obj['xml_file'];
    alert(ret_obj['message']);

    loadTreeMenu(xml_file, 'category', 'zone_category', category_title, '', doGetCategoryInfo, category_srl, doMoveTree);

    var obj = xGetElementById('category_info');
    xInnerHtml(obj, "");
    obj.style.display = 'none';
}

