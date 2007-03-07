<?php
    /**
     * @class  layoutController
     * @author zero (zero@nzeo.com)
     * @brief  layout 모듈의 Controller class
     **/

    class layoutController extends layout {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 레이아웃 신규 생성
         **/
        function procInsertLayout() {
            $oDB = &DB::getInstance();

            $args->layout_srl = $oDB->getNextSequence();
            $args->layout = Context::get('layout');
            $args->title = Context::get('title');

            $output = $oDB->executeQuery("layout.insertLayout", $args);
            if(!$output->toBool()) return $output;

            $this->add('layout_srl', $args->layout_srl);
        }
    }
?>
