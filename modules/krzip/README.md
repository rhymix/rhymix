# 한국 우편번호 모듈
공개 API를 이용해 우편번호 검색 서비스를 이용합니다.

## 지원하는 API
1. 다음 우편번호 API
2. 인터넷 우체국 우편번호 API
3. Postcodify API

## 인증키 발급
인터넷 우체국 우편번호 API를 사용하기 위해서는 인증키 발급이 필요합니다.  
다음 안내에 따라 인증키를 발급 받은 후 모듈 설정에 입력하세요.

1. [인증키 발급 페이지](http://biz.epost.go.kr/openapi/openapi_request.jsp?subGubun=sub_3&subGubun_1=cum_38&gubun=m07)를 방문한 후 양식을 작성합니다.
2. 인증키 발급 신청을 완료한 후 보여지는 인증키를 복사하거나 [인증키 확인 페이지](http://biz.epost.go.kr/openapi/openapi_reqresult_chk.jsp?subGubun=sub_3&subGubun_1=cum_39&gubun=m07)를 방문한 후 작성했던 양식을 입력해 확인합니다.
3. XE 모듈 목록에서 **한국 우편번호 모듈**을 선택해 모듈 설정 페이지를 띄우고 **우체국 우편번호 API**를 선택한 후 활성화된 인증키 입력 칸에 발급 받은 인증키를 입력하고 설정을 저장합니다.
