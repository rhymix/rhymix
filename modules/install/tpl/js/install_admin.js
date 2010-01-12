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
function doInstallFTPInfo(form) {
    var params={}, data=jQuery(form).serializeArray();
    jQuery.each(data, function(i, field){ params[field.name] = field.value });
    exec_xml('install', 'procInstallFTP', params, completeInstallFTPInfo, ['error', 'message'], params, form);
    return false;
}

function completeInstallFTPInfo(ret_obj) {
    location.href = current_url;
}

function doCheckFTPInfo() {
    var form = jQuery("#ftp_form").get(0);
    var params={}, data=jQuery(form).serializeArray();
    jQuery.each(data, function(i, field){ params[field.name] = field.value });

    exec_xml('install', 'procInstallCheckFTP', params, completeInstallCheckFtpInfo, ['error', 'message'], params, form);
    return false;
}

function completeInstallCheckFtpInfo(ret_obj) {
    alert(ret_obj['message']);
}

function completeFtpPath(ret_obj){
   location.reload(); 
}
