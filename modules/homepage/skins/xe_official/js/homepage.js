
function homepageLoadMenuInfo(url){
    // clear tree;
    jQuery('#menu > ul > li > ul').remove();
    if(jQuery("ul.simpleTree > li > a").size() ==0)jQuery('<a href="#" class="add"><img src="./common/js/plugins/ui.tree/images/iconAdd.gif" /></a>').bind("click",function(e){homepageAddMenu(0,e);}).appendTo("ul.simpleTree > li");

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
            jQuery('<a href="#" class="add"><img src="./common/js/plugins/ui.tree/images/iconAdd.gif" /></a>').bind("click",function(e){
                jQuery("#tree_"+node_srl+" > span").click();
                homepageAddMenu(node_srl,e);
                return false;
            }).appendTo(node);

            jQuery('<a href="#" class="modify"><img src="./common/js/plugins/ui.tree/images/iconModify.gif" /></a>').bind("click",function(e){
                jQuery.exec_json("homepage.getHomepageMenuItem",{ "node_srl":node_srl},function(data){
                    jQuery("#tree_"+node_srl+" > span").click();
                    data.menu_info['mode'] = 'update';
                    menuFormInsert(data.menu_info);
                    jQuery("#menuItem").css('position','absolute').css('visibility','visible').css('top',e.pageY - jQuery("#header").height() - 70).css('left',e.pageX - jQuery("#navigation").width() -40);
                    jQuery('#itemAttr4').css("display",'block');
                });
                return false;

            }).appendTo(node);

            jQuery('<a href="#" class="delete"><img src="./common/js/plugins/ui.tree/images/iconDel.gif" /></a>').bind("click",function(e){
                homepageDeleteMenu(node_srl);
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
                jQuery('#menuItem').css("visibility",'hidden');
                if(destination.size() == 0){
                    homepageLoadMenuInfo(xml_url);
                    return;
                }
                var menu_srl = jQuery("#fo_menu input[name=menu_srl]").val();
                var parent_srl = destination.attr('id').replace(/.*_/g,'');
                var target_srl = source.attr('id').replace(/.*_/g,'');
                var brothers = jQuery('#'+destination.attr('id')+' > ul > li:not([class^=line])').length;
                var mode = brothers >1 ? 'move':'insert';
                var source_srl = pos == 0 ? 0: source.prevAll("li:not(.line)").get(0).id.replace(/.*_/g,'');

                jQuery.exec_json("homepage.procHomepageMenuItemMove",{ "menu_srl":menu_srl,"parent_srl":parent_srl,"target_srl":target_srl,"source_srl":source_srl,"mode":mode},
                function(data){
                    if(data.error>0){
                        homepageLoadMenuInfo(xml_url);
                    }
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


function menuFormInsert(obj) {
    if(typeof(obj)=='undefined') return;

    var fo_obj = jQuery("#fo_menu").get(0);

    if(typeof(obj.parent_srl)!='undefined') fo_obj.parent_srl.value = obj.parent_srl;
    if(typeof(obj.menu_item_srl)!='undefined') fo_obj.menu_item_srl.value = obj.menu_item_srl;
    if(typeof(obj.mode)!='undefined') fo_obj.mode.value = obj.mode;

    if(typeof(obj.name)!='undefined') {
        for(var i in obj.name) {
            var o = fo_obj['menu_name_'+i];
            if(!o) continue;
            o.value = obj.name[i];
        }
    }

    if(typeof(obj.browser_title)!='undefined') fo_obj.browser_title.value = obj.browser_title;

    if(typeof(obj.open_window)!='undefined' && obj.open_window=='Y') fo_obj.menu_open_window.checked = true;
    if(typeof(obj.expand)!='undefined' && obj.expand=='Y') fo_obj.menu_expand.checked = true;

    if(typeof(obj.group_srls)!='undefined' && obj.group_srls && typeof(obj.group_srls.item)!='undefined' && obj.group_srls.item) {
        for(var j in obj.group_srls.item) {
            for(var i=0; i<fo_obj.group_srls.length;i++) {
                if(obj.group_srls.item[j]==fo_obj.group_srls[i].value) fo_obj.group_srls[i].checked = true;
            }
        }
    }

    if(typeof(obj.module_type)!='undefined') {
        if(obj.module_type == 'url') {
            fo_obj.module_type.selectedIndex = 2;
            if(typeof(obj.url)!='undefined') fo_obj.url.value = obj.url;
            fo_obj.module_type.disabled = "disabled";
            jQuery('#itemAttr2').css('display','none');
            jQuery('#itemAttr3').css('display','block');
        } else {
            if(obj.module_type == 'page') fo_obj.module_type.selectedIndex = 1;
            else fo_obj.module_type.selectedIndex = 1;
            if(typeof(obj.module_id)!='undefined') fo_obj.module_id.value = obj.module_id;
            fo_obj.module_type.disabled = "disabled";
            jQuery('#itemAttr2').css('display','block');
            jQuery('#itemAttr3').css('display','none');
        }
    }

    if(typeof(obj.normal_btn)!='undefined' && obj.normal_btn) {
        jQuery('#menu_normal_btn_img').attr("src",obj.normal_btn);
        jQuery('#menu_normal_btn_zone','#itemAttr4').css("display",'block');
        fo_obj.normal_btn.value = obj.normal_btn;
    }
    if(typeof(obj.hover_btn)!='undefined' && obj.hover_btn) {
        jQuery('#menu_hover_btn_img').attr("src",obj.hover_btn);
        jQuery('#menu_hover_btn_zone','#itemAttr4').css("display",'block');
        fo_obj.hover_btn.value = obj.hover_btn;
    }
    if(typeof(obj.active_btn)!='undefined' && obj.active_btn) {
        jQuery('#menu_active_btn_img').attr("src",obj.active_btn);
        jQuery('#menu_active_btn_zone','#itemAttr4').css("display",'block');
        fo_obj.active_btn.value = obj.active_btn;
    }
}


function menuFormReset() {
    var fo_obj = jQuery("#fo_menu").get(0);

    fo_obj.parent_srl.value = '';
    fo_obj.menu_item_srl.value = '';
    fo_obj.mode.value = '';

    jQuery(".menu_names").each(function(){ jQuery(this).val(''); });

    fo_obj.browser_title.value = '';

    fo_obj.menu_open_window.checked = false;
    fo_obj.menu_expand.checked = false;

    for(var i=0; i<fo_obj.group_srls.length;i++) fo_obj.group_srls[i].checked = false;

    fo_obj.module_type.selectedIndex = 0;
    fo_obj.module_type.disabled = "";

    fo_obj.module_id.value = '';
    fo_obj.url.value = '';
    jQuery('#itemAttr3').css("display","none");
    jQuery('#menu_normal_btn_zone','#menu_hover_btn_zone','#menu_active_btn_zone').css("display","none");
    jQuery('#menu_normal_btn_img','#menu_hover_btn_img','#menu_active_btn_img').attr("src","");
    jQuery('#itemAttr4').css("display","none");
    fo_obj.reset();
}

function completeInsertMenuItem(data) {
    var xml_file = data['xml_file'];
    if(!xml_file) return;
    homepageLoadMenuInfo(xml_url);
    jQuery('#menuItem').css("visibility",'hidden');
    menuFormReset();
}


function homepageAddMenu(node_srl,e) {
    menuFormReset();
    var obj = new Array();
    obj['mode'] = 'insert';
    if(typeof(node_srl)!='undefined' && node_srl > 0) {
        obj['parent_srl'] = node_srl;
    }

    menuFormInsert(obj);

    jQuery("#menuItem").css('position','absolute').css('visibility','visible').css('top',e.pageY - jQuery("#header").height() - 70).css('left',e.pageX - jQuery("#navigation").width() -40);
    jQuery('#itemAttr4').css("display",'block');
}


function homepageDeleteMenu(node_srl) {
    if(confirm(lang_confirm_delete)){
        jQuery('#menuItem').css("visibility",'hidden');
        var fo_obj = jQuery('#menu_item_form').get(0);
        fo_obj.menu_item_srl.value = node_srl;
        procFilter(fo_obj, delete_menu_item);
    }
}


function nodeToggleAll(){
    jQuery("[class*=close]", simpleTreeCollection[0]).each(function(){
        simpleTreeCollection[0].nodeToggle(this);
    });
}

function doReloadTreeMenu(){
    var menu_srl = jQuery("#fo_menu input[name=menu_srl]").val();

    jQuery.exec_json("menu.procMenuAdminMakeXmlFile",{ "menu_srl":menu_srl},
            function(data){
                 homepageLoadMenuInfo(xml_url);
            }
    );
    jQuery('#menuItem').css("visibility",'hidden');
    menuFormReset();
}

function closeTreeMenuInfo(){
    jQuery('#menuItem').css("visibility",'hidden');
}


/* 모듈 생성 후 */
function completeInsertBoard(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);

    var url = current_url.setQuery('act','dispHomepageBoardInfo');
    if(module_srl) url = url.setQuery('module_srl',module_srl);
    if(page) url.setQuery('page',page);
    location.href = url;
}
function completeInsertGroup(ret_obj) {
    location.href = current_url.setQuery('group_srl','');
}

function completeDeleteGroup(ret_obj) {
    location.href = current_url.setQuery('group_srl','');

}

function completeInsertGrant(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);
}

function completeInsertPage(ret_obj) {
    alert(ret_obj['message']);
    location.reload();
}

function completeChangeLayout(ret_obj) {
    location.reload();
}

function doDeleteGroup(group_srl) {
    var fo_obj = xGetElementById('fo_group');
    fo_obj.group_srl.value = group_srl;
    procFilter(fo_obj, delete_group);
}