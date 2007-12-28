<?php
    /**
     * @class webzine
     * @author zero (zero@nzeo.com)
     * @brief 최근글을 이미지와 같이 웹진형으로 출력
     * @version 0.1
     **/

    class webzine extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 글자 제목 길이
            $widget_info->subject_cut_size = (int)$args->subject_cut_size;
            if(!$widget_info->subject_cut_size) $widget_info->subject_cut_size = 10;

            // 내용 길이
            $widget_info->content_cut_size = (int)$args->content_cut_size ;
            if(!$widget_info->content_cut_size) $widget_info->content_cut_size= 50;

            // 썸네일 생성 방법
            $widget_info->thumbnail_type = $args->thumbnail_type;
            if(!$widget_info->thumbnail_type) $widget_info->thumbnail_type = 'crop';

            // 썸네일 가로 크기
            $widget_info->thumbnail_width = (int)$args->thumbnail_width;
            if(!$widget_info->thumbnail_width) $widget_info->thumbnail_width = 100;

            // 썸네일 세로 크기
            $widget_info->thumbnail_height = (int)$args->thumbnail_height;
            if(!$widget_info->thumbnail_height) $widget_info->thumbnail_height = 100;

            // 세로 이미지 수
            $widget_info->rows_list_count = (int)$args->rows_list_count;
            if(!$widget_info->rows_list_count) $widget_info->rows_list_count = 5;

            // 가로 이미지 수
            $widget_info->cols_list_count = (int)$args->cols_list_count;
            if(!$widget_info->cols_list_count) $widget_info->cols_list_count = 1;

            // 정렬 대상
            $widget_info->order_target = $args->order_target;
            if(!in_array($widget_info->order_target, array('list_order','update_order'))) $widget_info->order_target = 'list_order';

            // 정렬 순서
            $widget_info->order_type = $args->order_type;
            if(!in_array($widget_info->order_type, array('asc','desc'))) $widget_info->order_type = 'asc';


            // 노출 여부 체크
            if($args->display_author!='Y') $widget_info->display_author = 'N';
            else $widget_info->display_author = 'Y';
            if($args->display_regdate!='Y') $widget_info->display_regdate = 'N';
            else $widget_info->display_regdate = 'Y';
            if($args->display_readed_count!='Y') $widget_info->display_readed_count = 'N';
            else $widget_info->display_readed_count = 'Y';
            if($args->display_voted_count!='Y') $widget_info->display_voted_count = 'N';
            else $widget_info->display_voted_count = 'Y';

            // 제목
            $widget_info->title = $args->title;

            // 대상 모듈 정리
            $mid_list = explode(",",$args->mid_list);

            // 템플릿 파일에서 사용할 변수들을 세팅
            if(count($mid_list)==1) $widget_info->module_name = $mid_list[0];

            // mid에 해당하는 module_srl을 구함
            $oModuleModel = &getModel('module');
            $module_srl_list = $oModuleModel->getModuleSrlByMid($mid_list);
            if(is_array($module_srl_list)) $obj->module_srl = implode(",",$module_srl_list);
            else $obj->module_srl = $module_srl_list;
            $obj->sort_index = $widget_info->order_target;
            $obj->order_type = $widget_info->order_type=="desc"?"asc":"desc";
            $obj->list_count = $widget_info->rows_list_count * $widget_info->cols_list_count;

            $output = executeQueryArray('widgets.newest_document.getNewestDocuments', $obj);

            // document 모듈의 model 객체를 받아서 결과를 객체화 시킴
            $oDocumentModel = &getModel('document');

            // 오류가 생기면 그냥 무시
            if(!$output->toBool()) return;

            // 결과가 있으면 각 문서 객체화를 시킴
            if(count($output->data)) {
                foreach($output->data as $key => $attribute) {
                    $document_srl = $attribute->document_srl;

                    $oDocument = null;
                    $oDocument = new documentItem();
                    $oDocument->setAttribute($attribute);

                    $document_list[$key] = $oDocument;
                }
            } else {

                $document_list = array();
                
            }

            $document_count = count($document_list);
            $total_count = $widget_info->rows_list_count * $widget_info->cols_list_count;
            for($i=$document_count;$i<$total_count;$i++) $document_list[] = new DocumentItem();

            $widget_info->document_list = $document_list;

            Context::set('widget_info', $widget_info);

            // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            Context::set('colorset', $args->colorset);

            // 템플릿 파일을 지정
            $tpl_file = 'list';

            // 템플릿 컴파일
            $oTemplate = &TemplateHandler::getInstance();
            $output = $oTemplate->compile($tpl_path, $tpl_file);
            return $output;
        }
    }
?>
