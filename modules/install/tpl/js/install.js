/**
 * @file install.js
 * @author zero (zero@nzeo.com)
 * @brief 초기 설치시 사용되는 script file
 **/

/**
 * @function installCompleted
 * @param ret_obj ajax로 서버 call을 한후 return 받는 변수명
 * @brief 설치 성공 후 서버에서 보내주는 메세지를 출력한 후 / 로 redirect
 **/
function installCompleted(ret_obj) {
  var error = ret_obj["error"];
  var message = ret_obj["message"];
  alert(message);
  location.href = "./";
}
