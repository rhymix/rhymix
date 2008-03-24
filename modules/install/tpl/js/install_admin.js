/**
 * @brief 설치 완료후 실행될 함수
 */
function completeInstalled(ret_obj) {
    alert(ret_obj["message"]);
    location.href = "./index.php?module=admin";
}
