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
            if(!$category_srl) return;
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
