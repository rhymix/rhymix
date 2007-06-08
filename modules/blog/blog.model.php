
<?php
    /**
     * @class  blogModel
     * @author zero (zero@nzeo.com)
     * @version 0.1
     * @brief  blog 모듈의 Model class
     **/

    class blogModel extends blog {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 블로그의 코멘트 폼을 찾아서 return
         **/
        function getBlogCommentEditorForm() {
            $document_srl = Context::get('document_srl');

            $upload_target_srl = getNextSequence();

            // 에디터 모듈의 getEditor를 호출하여 세팅
            $oEditorModel = &getModel('editor');
            $option->allow_fileupload = $this->grant->comment_fileupload;
            $option->enable_autosave = false;
            $option->enable_default_component = true;
            $option->enable_component = true;
            $option->resizable = true;
            $option->height = 100;
            $comment_editor = $oEditorModel->getEditor($upload_target_srl, $option);

            // 변수 설정 
            Context::set('comment_editor', $comment_editor);
            Context::set('document_srl', $document_srl);
            Context::set('comment_srl', $upload_target_srl);

            // template 가져옴
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->skin);

            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($template_path, 'comment_form');

            // 결과 설정
            $this->add('document_srl', $document_srl);
            $this->add('upload_target_srl', $upload_target_srl);
            $this->add('tpl', $tpl);
        }

        /**
         * @brief DB 에 생성된 카테고리 정보를 구함
         * 생성된 메뉴의 DB정보+XML정보를 return
         **/
        function getCategory($module_srl) {
            $category_info->xml_file = sprintf('./files/cache/blog_category/%s.xml.php',$module_srl);
            return $category_info;
        }

        /**
         * @brief 특정 모듈의 전체 카테고리를 구함
         **/
        function getCategoryList($module_srl) {
            $args->module_srl = $module_srl;
            $args->sort_index = 'listorder';
            $output = executeQuery('blog.getBlogCategories', $args);
            if(!$output->toBool()) return;
            return $output->data;
        }

        /**
         * @brief 특정 카테고리의 정보를 return
         * 이 정보중에 group_srls의 경우는 , 로 연결되어 들어가며 사용시에는 explode를 통해 array로 변환 시킴
         **/
        function getCategoryInfo($category_srl) {
            // category_srl이 있으면 해당 메뉴의 정보를 가져온다
            $args->category_srl= $category_srl;
            $output = executeQuery('blog.getCategoryInfo', $args);
            $node = $output->data;
            if($node->group_srls) $node->group_srls = explode(',',$node->group_srls);
            else $node->group_srls = array();
            return $node;
        }

    }
?>
