function doClearSession() {
	if (!confirm(xe.lang.confirm_run)) return;
    exec_json('session.procSessionAdminClear', {}, function(data) {
		alert(data.result);
	});
}
