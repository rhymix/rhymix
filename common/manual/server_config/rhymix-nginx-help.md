#Nginx 리라이트 설정하기
##개요
**Rewrite**는 주로 짧은 주소를 구현하기 위해 사용됩니다. 예를 들자면

    https://example.com/index.php?mid=freeboard&document_srl=181

이라는 복잡한 주소를

    https://example.com/freeboard/181

처럼 **사람이 읽고 인식하기에 편한 주소로 바꾸어 주는 기능을 담당**합니다.

아파치의 경우 .htaccess 파일에서 mod_rewrite 모듈을 이용하여 리라이트를 할 수 있습니다. 하지만 엔진엑스의 경우 엔진엑스의 설정 파일을 수정해야 합니다.

## 요약
**적용하기** 부터는 상세하게 설명하게 됩니다. 상세하게 설명하면 실제로는 전혀 어렵지 않은 이야기를 어렵게 설명하거나, 길이 때문에 지레 포기하는 경우가 생기기 때문에 간략하게 세줄 요약하도록 하겠습니다.

1. 엔진엑스의 설정 파일을 열고
2. rhymix-nginx.conf 파일을 인클루드 하고
3. 엔진엑스 재시작

## 적용하기
엔진엑스의 설정 파일은 우분투를 기준으로 하여 apt-get으로 설치했다면

    /etc/nginx/nginx.conf

에 존재합니다. 만약 자신의 엔진엑스의 설정 파일이 어디에 있는지 모른다면 아래와 같은 커맨드를 입력하여 엔진엑스의 설정파일을 찾을 수 있습니다. 이 커맨드는 **관리자 권한**을 이용하여 **nginx.conf**라는 **이름**을 가진 파일을 **찾으라**는 명령어입니다. 검색이 완료될 때 까지는 제법 긴 시간이 필요하니 기다리도록 합시다.

    # sudo find / -name nginx.conf
    
명령어의 결과는 아래와 같은 형식으로 출력됩니다.

    /etc/nginx/nginx.conf
    
눈치 채셨겠지만, nginx.conf가 위치하고 있는 디렉터리의 경로입니다.

또한, nginx.conf를 수정하기 위해서는 nginx.conf를 수정할 수 있는 에디터가 필요합니다. 따라서 초보자가 가장 사용하기 좋은 'nano'를 설치해 보도록 하겠습니다.

    # apt-get install nano
 
이 명령어를 이용하면 nano 에디터를 설치할 수 있습니다. 물론 익숙한 에디터가 있다면 익숙한 에디터를 이용하도록 합시다.

이제 본격적으로 nginx.conf를 편집해 보도록 하겠습니다. 먼저, rhymix에서 사용되는 php 쿼리 주소들을 정리한 파일이 필요합니다. 해당 파일을 만든 뒤에 nginx.conf에 인클루드 함으로서 리라이트를 진행해 보도록 하겠습니다.

먼저 nano의 기본적인 조작법을 알아볼 필요가 있습니다. 더 강력한 조작법이 분명히 있지만, 굳이 이번 가이드에서는 다루지 않겠습니다.

1. ctrl + o #저장
2. ctrl + x #닫기
3. 화살표 키 #커서 이동

이 매뉴얼과 같은 디렉터리에 존재하는 'rhymix-nginx.conf' 파일을 nginx.conf가 위치한 디렉터리(/etc/nginx)로 이동해 보도록 합시다. 방법은 FTP에서 드래그 앤 드롭으로 옮기셔도, 아니면 터미널 상에서 명령어를 이용해서 옮기셔도 좋습니다.

이제 **nginx.conf** 파일을 수정해 보도록 하겠습니다. rhymix-nginx.conf 파일을 **nginx.conf** 파일에 인클루드 하는 첫번째 과정입니다.

먼저 **nginx.conf** 파일을 열어줍니다. (이때, 엔진엑스에서 가상 서버 기능을 사용하고 있는 경우에는 각 서버의 설정 파일을 수정해 줘야 합니다.)

    nano nginx.conf

nginx.conf 설정 파일을 여실 수 있을겁니다. 설정 파일에는 크게 두가지 **블럭**이 존재합니다. 첫번째는 **http{ }** 형태로 구성되어 있는 **http 블럭**, 두번째는 **server{ }** 형태로 존재하는 **server 블럭** 입니다. **nginx.conf** 파일은

    http {
        server {}
    }

위와 같이 구조가 짜여있는걸 확인하실 수 있습니다.(물론 event, worker_processes와 같은 부분도 있습니다만, 이 부분은 여기서 다루지 않겠습니다.), 우리가 주목해야 할 것은

    server { }

블럭입니다. server 블럭에 **rhymix-nginx.conf** 파일을 인클루드 해 줘야 합니다.

server 블럭에는 다양한 정보가 들어 있습니다. 그 정보의 최하단(혹은 원하는 곳)에

    include rhymix-nginx.conf;

구문을 추가해서 rhymix-nginx.conf 파일을 인클루드 하도록 합니다. server {} 내부의 형태가 아래와 같이 설정되었다면 제대로 설정된 것입니다. 자신이 원하는 서버의 형태에 따라서 server 블럭의 형태는 자유롭게 설정할 수 있습니다.

    	server {
    		listen 80;
    		#만약 SSL을 사용하고 싶다면 80; 대신 443 ssl;을 이용하면 됩니다. SSL을 이용하는 경우 추가적인 세팅이 필요하므로, 관련 팁을 참조하시길 바랍니다.
    		
    		server_name example.com;
    		#example.com은 자신의 도메인으로 바꾸면 됩니다. www.example.com과 example.com이 서로 다름에 유의해주세요.
    		
    		root /var/www/html;
    		#이때, 원하는 디렉터리로 홈 디렉터리를 수정해도 됩니다. 만약 /var/lol/lol로 홈 디렉터리를 사용하고 싶다면 /var/lol/lol;을 홈 디렉터리로 사용하면 됩니다.
    		
    		index index.php;
    		#index.php가 기본 페이지가 된다는 의미입니다. Rhymix는 index.php를 사용하니 index.php를 넣는 것입니다. 이 부분은 변경할 수 없습니다.
    		
    		include rhymix-nginx.conf;
    		#가장 중요한 부분입니다. 위에서 설명드렸다시피 RhymiX의 리라이트 규칙을 인클루드 하는 코드입니다.    		
    	}


하나의 서버에 하나의 웹사이트만 돌릴 생각이라면, 이 가이드의 최하단으로 이동하시기 바랍니다.

만약, 하나의 서버에 여러개의 웹사이트를 돌리고 싶다면(=가상 서버를 사용하고 싶다면) **/etc/nginx/site-enabled** 디렉터리에 **server 블럭**을 가지는 파일을 생성하면 됩니다. 우분투의 기본 nginx.conf를 살펴 보면 아래와 같은데요(다른 리눅스 배포판이라면 nginx.conf의 내용도 다를 수 있습니다. 기본적으로 include를 이용하여 하위 디렉터리의 설정 파일을 인클루드 한다는 점은 동일합니다.),

    http {
    	(...중략...)
    	
    	include /etc/nginx/conf.d/*.conf;
    	include /etc/nginx/site-enabled/*;
    }

**/etc/nginx/conf.d** 디렉터리 내에 있는 .conf 확장자를 모두 인클루드 하며, **/etc/nginx/site-enabled**에 들어 있는 모든 파일을 인클루드 한다는 의미입니다. 따라서, site-enabled 디렉터리에 아래와 같은 서버 설정을 작성합니다. 용도에 따라서 서버 파일의 내용은 얼마든지 바뀔 수 있습니다. 아래 서버 파일은

1. 80 포트를 이용하여 보안 연결 없는 일반 HTTP 연결을 하고
2. example.com 도메인을 사용하며
3. /var/www/html 디렉터리에 RhymiX 파일이 위치하는 서버의 설정입니다.

파일의 이름은 'example.conf'라고 가정하도록 하겠습니다.

	server {
		listen 80;
		#만약 SSL을 사용하고 싶다면 80; 대신 443 ssl;을 이용하면 됩니다. SSL을 이용하는 경우 추가적인 설정이 필요하므로, 관련 팁을 참조하시기 바랍니다.
		
		server_name example.com;
		#example.com은 자신의 도메인으로 바꾸면 됩니다. www.example.com과 example.com이 서로 다름에 유의해 주세요.
		
		root /var/www/html;
		#이때, 원하는 디렉터리로 홈 디렉터리를 수정해도 됩니다. 만약 /var/lol/lol로 홈 디렉터리를 사용하고 싶다면 /var/lol/lol;을 홈 디렉터리로 사용하면 됩니다.
		
		index index.php;
		#index.php가 기본 페이지가 된다는 의미입니다. Rhymix는 index.php를 사용하니 index.php를 넣는 것입니다.
		
		include /etc/nginx/rhymix-nginx.conf;
		#위에서 설명드렸다시피 rhymix의 리라이트 규칙을 인클루드 하는 코드입니다.
		
	}

가상 호스트 설정 파일에 include /etc/nginx/rhymix-nginx.conf 파일을 인클루드 하도록 합니다.

혹은, 추가하는 include 구문을 아래와 같이 추가할 수도 있지만, **절대로 이런 방식으로 추가해서는 안됩니다.** **반면교사를 위한 보여주기입니다.**

    include /XE_설치경로/common/manual/server_config/rhymix-nginx.conf;
    
XE의 설치 경로가 **/var/www/html**이라면,

    include /var/www/html/common/manual/server_config/rhymix-nginx.conf;
    
를 추가하면 됩니다. 하지만, 위와 같은 형식의 추가는 웹서버의 디렉터리를 참조하기 때문에 rhymix-nginx.conf 파일이 변조되었을 때 nginx의 rewrite 설정도 함께 바뀌기 때문에 **절대로 사용해서는 안됩니다.**

##엔진엑스 재시작하기
엔진엑스의 기본적인 설정을 마쳤습니다. 이제 엔진엑스가 변경된 설정을 로딩할 수 있도록 재시작해줘야 합니다.

터미널을 열고

    # sudo service nginx reload

혹은

    # sudo service nginx restart

커맨드를 실행하도록 합니다. 이 커맨드는 **nginx**라는 **서비스**를 **리로드 하거나 재시작** 하도록 하라는 의미를 담고 있습니다. 보통 reload를 권장하며, restart는 권장하지 않습니다.

##마치며...
이로서 엔진엑스의 리라이트 설정을 마쳤습니다. 이를 통해 읽기 복잡한 긴 주소를 짧은 주소로 변환할 수 있을 것입니다.