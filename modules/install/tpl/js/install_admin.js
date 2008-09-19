/**
 * @brief 설치 완료후 실행될 함수
 */
function completeInstalled(ret_obj) {
    alert(ret_obj["message"]);
    location.href = "./index.php?module=admin";
}

/**
 * @brief FTP 정보 입력
 **/
function doInstallFTPInfo(fo_obj) {
    var oFilter = new XmlJsFilter(fo_obj, "install", "procInstallFTP", completeInstallFTPInfo);
    oFilter.addResponseItem("error");
    oFilter.addResponseItem("message");
    return oFilter.proc();
}

function completeInstallFTPInfo(ret_obj) {
    location.href = current_url;
}

function doCheckFTPInfo() {
    var fo_obj = xGetElementById("ftp_form");
    var oFilter = new XmlJsFilter(fo_obj, "install", "procInstallCheckFTP", completeInstallCheckFtpInfo);
    oFilter.addResponseItem("error");
    oFilter.addResponseItem("message");
    return oFilter.proc();
}

function completeInstallCheckFtpInfo(ret_obj) {
    alert(ret_obj['message']);
}
