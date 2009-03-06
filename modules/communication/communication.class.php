<?php
    /**
     * @class  communication 
     * @author zero (zero@nzeo.com)
     * @brief  communication module의 high class
     **/

    class communication extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // 새쪽지 알림을 위한 임시 파일 저장소 생성
            FileHandler::makeDir('./files/member_extra_info/new_message_flags');
            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            if(!is_dir("./files/member_extra_info/new_message_flags")) return true;
            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            if(!is_dir("./files/member_extra_info/new_message_flags")) 
                FileHandler::makeDir('./files/member_extra_info/new_message_flags');
            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
