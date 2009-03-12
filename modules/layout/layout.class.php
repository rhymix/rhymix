<?php
    /**
     * @class  layout
     * @author zero (zero@nzeo.com)
     * @brief  layout 모듈의 high class
     **/

    class layout extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // 레이아웃에서 사용할 디렉토리 생성
            FileHandler::makeDir('./files/cache/layout');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();

            // 2009. 02. 11 layout 테이블에 site_srl 추가
            if(!$oDB->isColumnExists('layouts', 'site_srl')) return true;

            // 2009. 02. 26 faceOff에 맞춰 기존 레이아웃 편집본을 이동
            $files = FileHandler::readDir('./files/cache/layout');
            for($i=0,$c=count($files);$i<$c;$i++) {
                $filename = $files[$i];
                if(preg_match('/([0-9]+)\.html/i',$filename)) return true;
            }

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oDB = &DB::getInstance();

            // 2009. 02. 11 menu 테이블에 site_srl 추가
            if(!$oDB->isColumnExists('layouts', 'site_srl')) {
                $oDB->addColumn('layouts','site_srl','number',11,0,true);
            }

            // 2009. 02. 26 faceOff에 맞춰 기존 레이아웃 편집본을 이동
            $oLayoutModel = &getModel('layout');
            $files = FileHandler::readDir('./files/cache/layout');
            for($i=0,$c=count($files);$i<$c;$i++) {
                $filename = $files[$i];
                if(!preg_match('/([0-9]+)\.html/i',$filename,$match)) continue;
                $layout_srl = $match[1];
                if(!$layout_srl) continue;
                $path = $oLayoutModel->getUserLayoutPath($layout_srl);
                if(!is_dir($path)) FileHandler::makeDir($path);
                FileHandler::copyFile('./files/cache/layout/'.$filename, $path.'layout.html');
                @unlink('./files/cache/layout/'.$filename);
            }

            return new Object(0, 'success_updated');
        }


        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // 레이아웃 캐시 삭제 (수정본은 지우지 않음)
            $path = './files/cache/layout';
            if(!is_dir($path)) {
                FileHandler::makeDir($path);
                return;
            }

            $directory = dir($path);
            while($entry = $directory->read()) {
                if ($entry == "." || $entry == ".." || preg_match('/\.html$/i',$entry) ) continue;
                FileHandler::removeFile($path."/".$entry);
            }
            $directory->close();
        }
    }
?>
