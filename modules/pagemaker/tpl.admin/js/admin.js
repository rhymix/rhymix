/* 글쓰기 작성후 */
function completeDocumentInserted(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var page = ret_obj['page'];
  var document_srl = ret_obj['document_srl'];
  var url =  "./?module=admin&mo=pagemaker&act=dispWrite&document_srl="+document_srl;
  if(page) url += "&page="+page;
  location.href = url;
}

/* 글 삭제 */
function completeDeleteDocument(ret_obj) {
  var error = ret_obj['error'];
  var message = ret_obj['message'];
  var page = ret_obj['page'];

  alert(message);

  var url =  "./?module=admin&mo=pagemaker&act=dispWrite&document_srl="+document_srl;
  if(page) url += "&page="+page;
  location.href = url;
}
