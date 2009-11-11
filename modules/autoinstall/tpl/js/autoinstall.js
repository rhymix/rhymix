function completeUpdate(ret_obj) {
    alert(ret_obj['message']);
    location.reload();
}

function doUpdate() {
    var params = new Array();
    exec_xml('autoinstall', 'procAutoinstallAdminUpdateinfo', params, completeUpdate);
}

function doInstallPackage(package_srl) {
    var params = new Array();
    params['package_srl'] = package_srl;
    exec_xml('autoinstall', 'procAutoinstallAdminPackageinstall', params, completeInstall);
}

function completeUpdateNoMsg(ret_obj) {
    location.reload();
}

function completeInstall(ret_obj) {
    alert(ret_obj['message']);
    var params = new Array();
    exec_xml('autoinstall', 'procAutoinstallAdminUpdateinfo', params, completeUpdateNoMsg);
}

