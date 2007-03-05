<?php
    /**
     * @class  spamfilterController
     * @author zero (zero@nzeo.com)
     * @brief  spamfilter 모듈의 controller class
     **/

    class spamfilterController extends spamfilter {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 스팸필터 설정
         **/
        function procInsertConfig() {
            // 기본 정보를 받음
            $args = Context::gets('interval','limit_count');

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('spamfilter',$args);
            return $output;
        }
        
        /**
         * @brief 금지 IP등록
         **/
        function procInsertDeniedIP() {
            $ipaddress = Context::get('ipaddress');
            return $this->insertIP($ipaddress);
        }

        /**
         * @brief 금지 IP삭제
         **/
        function procDeleteDeniedIP() {
            $ipaddress = Context::get('ipaddress');
            return $this->deleteIP($ipaddress);
        }
        
        /**
         * @brief 금지 Word등록
         **/
        function procInsertDeniedWord() {
            $word = Context::get('word');
            return $this->insertWord($word);
        }

        /**
         * @brief 금지 Word삭제
         **/
        function procDeleteDeniedWord() {
            $word = Context::get('word');
            return $this->deleteWord($word);
        }

        /**
         * @brief IP 등록
         * 등록된 IP는 스패머로 간주
         **/
        function insertIP($ipaddress) {
            $oDB = &DB::getInstance();
            $args->ipaddress = $ipaddress;
            return $oDB->executeQuery('spamfilter.insertDeniedIP', $args);
        }

        /**
         * @brief IP 제거
         * 스패머로 등록된 IP를 제거
         **/
        function deleteIP($ipaddress) {
            if(!$ipaddress) return;

            $oDB = &DB::getInstance();
            $args->ipaddress = $ipaddress;
            return $oDB->executeQuery('spamfilter.deleteDeniedIP', $args);
        }

        /**
         * @brief 스팸단어 등록
         * 등록된 단어가 포함된 글은 스팸글로 간주
         **/
        function insertWord($word) {
            if(!$word) return;

            $oDB = &DB::getInstance();
            $args->word = $word;
            return $oDB->executeQuery('spamfilter.insertDeniedWord', $args);
        }

        /**
         * @brief 스팸단어 제거
         * 스팸 단어로 등록된 단어 제거
         **/
        function deleteWord($word) {
            if(!$word) return;

            $oDB = &DB::getInstance();
            $args->word = $word;
            return $oDB->executeQuery('spamfilter.deleteDeniedWord', $args);
        }

        /**
         * @brief 로그 등록
         * 현 접속 IP를 로그에 등록, 로그의 간격이 특정 시간 이내일 경우 도배로 간주하여
         * 스패머로 등록할 수 있음
         **/
        function insertLog() {
            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('spamfilter.insertLog');
            return $output;
        }

    }
?>
