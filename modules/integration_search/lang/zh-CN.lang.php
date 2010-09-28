<?php
    /**
     * @file   modules/integration_search/lang/ko.lang.php
     * @author NHN (developers@xpressengine.com) 翻译：guny
     * @brief  综合搜索简体中文语言包
     **/

    $lang->integration_search = "综合搜索";

    $lang->sample_code = "代码";
    $lang->about_target_module = "所选模块作为搜索对象。请注意权限设置。";
    $lang->about_sample_code = "可把上述代码插入到相应布局当中即可实现搜索功能。";
    $lang->msg_no_keyword = '请输入搜索关键词。';
    $lang->msg_document_more_search  = '利用\'继续搜索\'按钮可以进一步搜索。';

    $lang->is_result_text = "符合<strong>'%s'</strong>的搜索结果约有<strong>%d</strong>项";
    $lang->multimedia = "图片/视频";
    
    $lang->include_search_target = '只搜索所选对象';
    $lang->exclude_search_target = '所选对象从搜索中排除';

    $lang->is_search_option = array(
        'document' => array(
        'title_content' => '标题+内容',
        'title' => '标题',
        'content' => '内容',
        'tag' => '标签',
        ),
        'trackback' => array(
            'url' => '对象URL',
            'blog_name' => '对象网站名称',
            'title' => '标题',
            'excerpt' => '内容',
        ),
    );

    $lang->is_sort_option = array(
        'regdate' => '日期',
        'comment_count' => '评论',
        'readed_count' => '查看',
        'voted_count' => '推荐',
    );
?>
