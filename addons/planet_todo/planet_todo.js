function planetTab(){
    if($('planet_tab')) $('planet_tab').innerHTML += '<ul id="planetex_Tab" class="exTab"><li><a id="aa" href="#" onclick="planet_showTodoList(this); return false;">ToDo<sup><span id="planet_todo_count">'+ planet_todo_count.todo + '</span>/<span id="planet_done_count">' + planet_todo_count.done + '</span></sup></a></li>';
}
xAddEventListener(window,'load',planetTab);




function planetTabOff(){
    $Element('planetex_Tab').leave();
}

function planet_showTodoList(o){
    $ElementList('#planetex_Tab li').removeClass('active');
    $Element(o).parent().addClass('active');
    $ElementList('#planet_myTab li').removeClass('active');

    planet_reload_todo();
}

function planet_reload_todo(){
    $('commentList').innerHTML = '<div class="commentHeader"><h3 class="exTitle">TODO</h3></div>\n<div id="todoList" class="commentBody taskManager"></div>\n<div id="todoList_page" class="pagination a1"></div>\n<div class="commentHeader"><h3 class="exTitle">DONE</h3></div>\n<div id="doneList" class="commentBody taskManager"></div>\n<div id="doneList_page" class="pagination a1"></div>\n<span class="tl"></span><span class="tr"></span><span class="bl"></span><span class="br"></span>\n';
    planet_getTodo();
    planet_getDone();
}

function planet_getTodo(page){
    $('todoList').innerHTML ='';
    _getPlanetTagSeachResult('todo',page,result_planet_getTodo);
}

function planet_getDone(page){
    $('doneList').innerHTML ='';
    _getPlanetTagSeachResult('done',page,result_planet_getDone);
}

function _getPlanetTagSeachResult(tag,page,func){
    var response_tags = new Array('error','message','contentList','pageNavigation');
    exec_xml('planet','dispPlanetContentTagSearch',{keyword:tag,page:page,mid:current_mid},func,response_tags);
}

function result_planet_getTodo(ret_obj,response_tags, params, fo_obj) {

    if(ret_obj['error'] == 0 && ret_obj.contentList){
        var o = new Array();
        var it = new Array();
        var items = ret_obj['contentList']['item'];

        if(typeof(items[0]) == 'undefined'){
            it[0] = items;
        }else{
            it = items;
        }

        o.push('<ul>');
        for(var i=0,c=it.length;i<c;i++){

            it[i]['regdate'] += '';
            it[i]['regdate'] = it[i]['regdate'].substr(0,4) + '/' + it[i]['regdate'].substr(4,2) + '/' + it[i]['regdate'].substr(6,2);

            var tag = $A();
            if(typeof(it[i]['tag_list']['item'])=='string'){
                tag.push(it[i]['tag_list']['item']);
            }else{
                tag = $A(it[i]['tag_list']['item']);
            }

            tag = tag.refuse('todo');
            tag.push('done');
            tag = tag.$value().join(',');

            o.push('<li>');
                    o.push(it[i]['content']);
				o.push('<span class="button small"><button type="button" id="document_srl:'+it[i]['document_srl']+'" value="'+tag+'" onclick="planet_todo_setDone(this)">&radic; DONE</button></span>');
                o.push('<span class="time">');
                    o.push(it[i]['regdate']);
                o.push('</span>');
            o.push("</li>\n");
        }

        o.push('</ul>');
		o.push('<span class="tl"></span><span class="tr"></span><span class="bl"></span><span class="br"></span>');

        $('todoList').innerHTML = o.join('');


        var pageNavigation = ret_obj['pageNavigation'];

        $('planet_todo_count').innerHTML = pageNavigation.total_count;
        var str = "";
        for(var i=pageNavigation.first_page;i<=pageNavigation.page_count;i++){
            if(i== pageNavigation.cur_page){
                str += "<strong>"+i+"</strong>";
            }else{
                str += '<a href="#" onclick="planet_getTodo('+i+');return false;">'+ i +'</a>';
            }
        }
        $('todoList_page').innerHTML = str;
    }
}


function result_planet_getDone(ret_obj,response_tags, params, fo_obj) {

    if(ret_obj['error'] == 0 && ret_obj.contentList){
        var o = new Array();
        var it = new Array();
        var items = ret_obj['contentList']['item'];


        if(typeof(items[0]) == 'undefined'){
            it[0] = items;
        }else{
            it = items;
        }

        o.push('<ul>');
        for(var i=0,c=it.length;i<c;i++){

            it[i]['regdate'] += '';
            it[i]['regdate'] = it[i]['regdate'].substr(0,4) + '/' + it[i]['regdate'].substr(4,2) + '/' + it[i]['regdate'].substr(6,2);

            var tag = $A();
            if(typeof(it[i]['tag_list']['item'])=='string'){
                tag.push(it[i]['tag_list']['item']);
            }else{
                tag = $A(it[i]['tag_list']['item']);
            }

            tag = tag.refuse('done');
            tag.push('todo');
            tag = tag.$value().join(',');

            o.push('<li>');
                    o.push(it[i]['content']);
				o.push('<span class="button small"><button type="button" id="document_srl:'+it[i]['document_srl']+'" value="'+tag+'" onclick="planet_todo_setDone(this)">REDO</button></span>');
                o.push('<span class="button small"><button type="button" onclick="planet_todo_setDel('+it[i]['document_srl']+')">Delete</button></span>');
            o.push("</li>\n");
        }


        o.push('</ul>');
		o.push('<span class="tl"></span><span class="tr"></span><span class="bl"></span><span class="br"></span>');

		$('doneList').innerHTML = o.join('');

        var pageNavigation = ret_obj['pageNavigation'];
       $('planet_done_count').innerHTML = pageNavigation.total_count;
        var str = "";
        for(var i=pageNavigation.first_page;i<=pageNavigation.page_count;i++){
            if(i== pageNavigation.cur_page){
                str += "<strong>"+i+"</strong>";
            }else{
                str += '<a href="#" onclick="planet_getDone('+i+');return false;">'+ i +'</a>';
            }
        }
        $('doneList_page').innerHTML = str;
    }
}




function planet_todo_setDone(o){
    var document_srl = o.id.replace(/.*:/,'');
    var tag = o.value;
    var params = {};
    params['document_srl'] = document_srl;
    params['planet_content_tag'] = tag;

    exec_xml('planet','procPlanetContentTagModify',params,planet_reload_todo);
    return false;
}

function planet_todo_setDel(document_srl){
    var tag = $A($('document_srl:'+document_srl).value.split(','));
    tag = tag.refuse('todo');
    tag.push('hide');
    tag = tag.$value().join(',');

    var params = {};
    params['document_srl'] = document_srl;
    params['planet_content_tag'] = tag;

    exec_xml('planet','procPlanetContentTagModify',params,planet_reload_todo);
    return false;
}

function showWritePostTodo(tag){
    tag = tag||'todo';
    $Element($('writePostForm').about_tag).hide();
    $Element($('writePostForm').content_tag).show();
    $('writePostForm').content_tag.value = tag;
    showWritePost();
}
