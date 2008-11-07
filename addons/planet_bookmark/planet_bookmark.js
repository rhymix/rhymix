function planetBookmarkTab(){
    if($('planet_tab')) $('planet_tab').innerHTML += '<ul id="planetBookmarkTab" class="exTab"><li><a href="#planet_tab" onclick="planetShowBookmarkList(this);">Bookmark<sup><span id="planet_bookmark_count">'+ planet_bookmark_count + '</span></a></li></ul>';
}
xAddEventListener(window,'load',planetBookmarkTab);

function planetShowBookmarkList(o){
    $ElementList('#planetBookmarkTab li').removeClass('active');
    $Element(o).parent().addClass('active');
    $ElementList('#planet_myTab li').removeClass('active');
    planetBookmarkReload();
}

function planetBookmarkReload(){
    $('commentList').innerHTML = '<div class="commentHeader"><h3 class="exTitle">Bookmark</h3><span class="button strong black todoWrite"><button type="button" onclick="showWritePostBookmark()">Bookmark</button></span></div>\n<div id="bookmarkList" class="commentBody todoManager"></div>\n<div id="todoList_page" class="pagination a1"></div>\n\n<span class="tl"></span><span class="tr"></span><span class="bl"></span><span class="br"></span>\n';
    planetGetBookmark(0);
}

function planetGetBookmark(page){
    $('bookmarkList').innerHTML ='';
    var response_tags = new Array('error','message','contentList','pageNavigation');
    exec_xml('planet','dispPlanetContentTagSearch',{keyword:'bookmark',page:page,mid:current_mid},completeGetBookmark,response_tags);
}

function completeGetBookmark(ret_obj,response_tags, params, fo_obj) {
    if(ret_obj['error'] == 0 && ret_obj.contentList){
        var o = new Array();
        var it = new Array();
        var items = ret_obj['contentList']['item'];

        if(typeof(items[0]) == 'undefined') {
            it[0] = items;
        } else {
            it = items;
        }

        o.push('<ul>');
        for(var i=0,c=it.length;i<c;i++){
            var tag = $A();
            if(typeof(it[i]['tag_list']['item'])=='string'){
                tag.push(it[i]['tag_list']['item']);
            }else{
                tag = $A(it[i]['tag_list']['item']);
            }

            tag = tag.filter(function(v){return !/bookmark/i.test(v);});
            tag = tag.$value().join(',');

            o.push('<li>');
            o.push(it[i]['content']);
            if(tag) {
                o.push('<div class="tag">');
                o.push('<img src="'+request_uri+'addons/planet_bookmark/tag.gif" title="tag" />');
                o.push(tag);
                o.push('</div>');
            }
            o.push("</li>\n");
        }

        o.push('</ul>');
        o.push('<span class="tl"></span><span class="tr"></span><span class="bl"></span><span class="br"></span>');

        $('bookmarkList').innerHTML = o.join('');

        var pageNavigation = ret_obj['pageNavigation'];
        $('planet_bookmark_count').innerHTML = pageNavigation.total_count ? pageNavigation.total_count : 0;

        if(pageNavigation.total_page > 1){

            var str = "";
            if(pageNavigation.first_page>1) str += '<a class="prev" href="#planet_tab" onclick="planetGetBookmark('+(pageNavigation.first_page-1)+');">Prev</a>';

            for(var i=pageNavigation.first_page;i<=pageNavigation.page_count;i++){
                if(i== pageNavigation.cur_page){
                    str += "<strong>"+i+"</strong>";
                }else{
                    str += '<a href="#planet_tab" onclick="planetGetBookmark('+i+');">'+ i +'</a>';
                }
            }

            if(pageNavigation.total_page != pageNavigation.last_page) str += '<a class="next" href="#planet_tab" onclick="planetGetBookmark('+(pageNavigation.last_page+1)+');">next</a>';
            $('bookmarkList_page').innerHTML = str;
        }
        window.location.href="#planet_tab";
    }else{
        $('planet_bookmark_count').innerHTML = 0;
    }
}

function showWritePostBookmark(tag){
    tag = tag||'bookmark';
    $Element($('writePostForm').about_tag).hide();
    $Element($('writePostForm').content_tag).show();
    $('writePostForm').content_tag.value = tag;
    window.document.location.href="#writePost";
    showWritePost();
}
