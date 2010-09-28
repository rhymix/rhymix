<?php
    /**
     * @file   modules/integration_search/lang/jp.lang.php
     * @author NHN (developers@xpressengine.com) 翻訳：RisaPapa、ミニミ
     * @brief  日本語言語パッケージ（基本的な内容のみう）
     **/

    $lang->integration_search = '統合検索';

    $lang->sample_code = 'サンプルコード';
    $lang->about_target_module = '選択されたモジュールだけを検索対象とします。各モジュールの権限設定にも注意して下さい。';
    $lang->about_sample_code = '上のコードをレイアウトなどに挿入すると統合検索が可能になります。';
    $lang->msg_no_keyword = '検索語を入力して下さい。';
    $lang->msg_document_more_search  = '継続サーチボタンを選択すると、まだ検索結果として引っかからなかった箇所を引き続き検索を行います。';

    $lang->is_result_text = "<strong>'%s'</strong>に対する検索結果<strong>%d</strong>件";
    $lang->multimedia = '画像/動画';
    
    $lang->include_search_target = '選択された対象のみ';
    $lang->exclude_search_target = '選択した対象を検索から除外';

    $lang->is_search_option = array(
        'document' => array(
            'title_content' => 'タイトル+内容',
            'title' => 'タイトル',
            'content' => '内容',
            'tag' => 'タグ',
        ),
        'trackback' => array(
            'url' => '対象URL',
            'blog_name' => '対象サイト（ブログ）名',
            'title' => 'タイトル',
            'excerpt' => '内容',
        ),
    );

    $lang->is_sort_option = array(
        'regdate' => '登録日',
        'comment_count' => 'コメント数',
        'readed_count' => '閲覧数',
        'voted_count' => '推薦数',
    );
?>
