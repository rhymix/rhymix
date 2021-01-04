/* 데이터 삭제 */
function deleteDate(date_srl)
{
	get_by_id('date_srl').value = date_srl;
    var dF = get_by_id('deleteForm');
	dF.submit();
}
