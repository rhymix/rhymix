function planetTab(){
    if($('planet_tab')) $('planet_tab').innerHTML += '<ul id="planetex_Tab" class="exTab"><li><a id="aa" href="#" onclick="planet_showTodoList(this); return false;">ToDo<sup>-</sup></a></li>';
}

xAddEventListener(window,'load',planetTab);



function planet_showTodoList(o){
    $ElementList('#planetex_Tab li').removeClass('active');
    $Element(o).parent().addClass('active');
    $ElementList('#planet_myTab li').removeClass('active');

    planet_reload_todo();
}

function planet_reload_todo(){
    $('commentList').innerHTML = '<h3>todo</h3><div id="todoList"></div>\n<div id="todoList_page"></div>\n<h3>done</h3>\n<div id="doneList"></div>\n<div id="doneList_page"></div>\n';
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
    exec_xml('planet','dispPlanetContentTagSearch',{keyword:tag,page:page},func,response_tags);
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

            o.push('<li><input type="checkbox" id="document_srl:'+it[i]['document_srl']+'" value="'+tag+'" onclick="planet_todo_setDone(this)"/>');
                o.push('<em>');
                    o.push(it[i]['content']);
                o.push('</em><span>');
                    o.push(it[i]['regdate']);
                o.push('</span>');
            o.push("</li>\n");
        }

        o.push('</ul>');

        $('todoList').innerHTML = o.join('');


        var pageNavigation = ret_obj['pageNavigation'];
        var str = "";
        for(var i=pageNavigation.first_page;i<=pageNavigation.page_count;i++){
            str += "|";
            if(i== pageNavigation.cur_page){
                str += "<strong>"+i+"</strong>";
            }else{
                str += '<a href="#" onclick="planet_getTodo('+i+');return false;">'+ i +'</a>';
            }
        }
        $('todoList_page').innerHTML = str + '|';
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

            o.push('<li><input type="checkbox" id="document_srl:'+it[i]['document_srl']+'" value="'+tag+'" onclick="planet_todo_setDone(this)" checked="checked=" />');
                o.push('<em>');
                    o.push(it[i]['content']);
                o.push('</em><button type="button" onclick="planet_todo_setDel('+it[i]['document_srl']+')">삭제</button>');
            o.push("</li>\n");
        }


        o.push('</ul>');
        $('doneList').innerHTML = o.join('');

        var pageNavigation = ret_obj['pageNavigation'];
        var str = "";
        for(var i=pageNavigation.first_page;i<=pageNavigation.page_count;i++){
            str += "|";
            if(i== pageNavigation.cur_page){
                str += "<strong>"+i+"</strong>";
            }else{
                str += '<a href="#" onclick="planet_getDone('+i+');return false;">'+ i +'</a>';
            }
        }
        $('doneList_page').innerHTML = str + '|';
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



function xeAjax(param,func){
	var url = "./";
	var myAjax = $Ajax(url,{
		onload: function(res){
			//func($Json.fromXML(res.text()));
			func(res.text());
		}
	});
	
	// set header
	myAjax.header('Content-Type','application/json; charset=UTF-8');
	myAjax.option("method", "post");
	myAjax.request(param);
}

function geta(){
	var param = {mid:'sol',module:'planet',act:'dispPlanetContentTagSearch',keyword:'done',page:1}
	xeAjax(param,resultGetFavorite);
}

function resultGetFavorite(obj){
	alert(obj);
}

