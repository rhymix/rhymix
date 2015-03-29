# Contribution Guide

## Issue 작성
Issue 작성 시 참고해주세요.

* 작성하려는 이슈가 이미 있는지 검색 후 등록해주세요. 비슷한 이슈가 있다면 댓글로 추가 내용을 덧붙일 수 있습니다.
* 이슈에는 하나의 문제 또는 제안을 작성해주세요. 절대 하나의 이슈에 2개 이상의 내용을 적지마세요.
* 이슈는 가능한 상세하고 간결하게 작성해주세요.
	* 필요하다면 화면을 캡처하여 이미지를 업로드할 수 있습니다.

## Pull request(PR)
* `master` 브랜치의 코드는 수정하지마세요.
* PR은 `develop` 브랜치만 허용합니다.
* `develop` 브랜치를 부모로 한 토픽 브랜치를 활용하면 편리합니다.


## Coding Guidelines
코드를 기여할 때 Coding conventions을 따라야합니다.

* 모든 text 파일의 charset은 BOM이 없는 UTF-8입니다.
* newline은 UNIX type을 사용합니다. 일부 파일이 다른 type을 사용하더라도 절대 고치지 마세요!
* 들여쓰기는 1개의 탭으로 합니다.
* class 선언과 function, if, foreach, for, while 등 중괄호의 `{}`는 다음 줄에 있어야 합니다.
	* 마찬가지로 선언 다음에는 공백을 두지 않습니다. ex) CORRECT `if(...)`, INCORRECT `if (...)`
* **Coding convention에 맞지 않는 코드를 발견 하더라도 목적과 관계 없는 코드는 절대 고치지 마세요.**
