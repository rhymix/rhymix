<?php
    /**
     * @class  spamfilterAdminController
     * @author zero (zero@nzeo.com)
     * @brief  spamfilter 모듈의 admin controller class
     **/

    class spamfilterAdminController extends spamfilter {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 스팸필터 설정
         **/
        function procSpamfilterAdminInsertConfig() {
            // 기본 정보를 받음
            $args = Context::gets('interval','limit_count','check_trackback');
            if($args->check_trackback!='Y') $args->check_trackback = 'N';

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('spamfilter',$args);
            return $output;
        }
        
        /**
         * @brief 금지 IP등록
         **/
        function procSpamfilterAdminInsertDeniedIP() {
            $ipaddress = Context::get('ipaddress');

            $oSpamfilterController = &getController('spamfilter');
            return $oSpamfilterController->insertIP($ipaddress);
        }

        /**
         * @brief 금지 IP삭제
         **/
        function procSpamfilterAdminDeleteDeniedIP() {
            $ipaddress = Context::get('ipaddress');
            return $this->deleteIP($ipaddress);
        }
        
        /**
         * @brief 금지 Word등록
         **/
        function procSpamfilterAdminInsertDeniedWord() {
            $word = Context::get('word');
            return $this->insertWord($word);
        }

        /**
         * @brief 금지 Word삭제
         **/
        function procSpamfilterAdminDeleteDeniedWord() {
            $word = base64_decode(Context::get('word'));
            return $this->deleteWord($word);
        }

        /**
         * @brief IP 제거
         * 스패머로 등록된 IP를 제거
         **/
        function deleteIP($ipaddress) {
            if(!$ipaddress) return;

            $args->ipaddress = $ipaddress;
            return executeQuery('spamfilter.deleteDeniedIP', $args);
        }

        /**
         * @brief 스팸단어 등록
         * 등록된 단어가 포함된 글은 스팸글로 간주
         **/
        function insertWord($word) {
            if(!$word) return;

            $args->word = $word;
            return executeQuery('spamfilter.insertDeniedWord', $args);
        }

        /**
         * @brief 스팸단어 제거
         * 스팸 단어로 등록된 단어 제거
         **/
        function deleteWord($word) {
            if(!$word) return;

            $args->word = $word;
            return executeQuery('spamfilter.deleteDeniedWord', $args);
        }

    }
?>
