#Nginx 리라이트 설정하기
##개요
**Rewrite**는 주로 짧은 주소를 구현하기 위해 사용됩니다. 예를 들자면


```
https://example.com/index.php?mid=freeboard&document_srl=181
```


이라는 복잡한 주소를


```
https://example.com/freeboard/181
```

처럼 **사람이 읽고 인식하기에 편한 주소로 바꾸어 주는 기능을 담당**합니다.

아파치의 경우 .htaccess 파일에서 mod_rewrite 모듈을 이용하여 리라이트를 할 수 있습니다. 하지만 엔진엑스의 경우 엔진엑스의 설정 파일을 수정해야 합니다.

## 요약
**적용하기** 부터는 상세하게 설명하게 됩니다. 상세하게 설명하면 실제로는 전혀 어렵지 않은 이야기를 어렵게 설명하거나, 길이 때문에 지레 포기하는 경우가 생기기 때문에 간략하게 세줄 요약하도록 하겠습니다.

1. 엔진엑스의 설정 파일을 열고
2. rhymix-nginx.conf 파일을 인클루드 하고
3. 엔진엑스 재시작

##적용하기
엔진엑스의 설정 파일은 우분투를 기준으로 하여

```
/etc/nginx/nginx.conf
```


에 존재합니다. 만약 자신의 엔진엑스의 설정 파일이 다른 곳에 있다면, 어디에 있는지 미리 찾아서 경로를 메모해 두도록 합시다.

```
sudo find / -name nginx.conf
```

커맨드를 이용하여 엔진엑스의 설정 파일을 검색할 수 있습니다. 관리자 권한으로 nginx.conf라는 이름을 가진 파일을 최상위(/) 디렉터리부터 검색하라는 의미입니다.

또한, nginx.conf를 수정하기 위해서는 nginx.conf를 수정할 수 있는 에디터가 필요합니다. 따라서 초보자가 가장 사용하기 좋은 'nano'를 설치해 보도록 하겠습니다.


```
apt-get install nano
```
    
    
이 명령어를 이용하면 nano 에디터를 설치할 수 있습니다. 물론 익숙한 에디터가 있다면 익숙한 에디터를 이용하도록 합시다.

이제 본격적으로 nginx.conf를 편집해 보도록 하겠습니다. 먼저, rhymix에서 사용되는 php 쿼리 주소들을 정리한 파일이 필요합니다. 해당 파일을 만든 뒤에 nginx.conf에 인클루드 함으로서 리라이트를 진행해 보도록 하겠습니다.

먼저 nano의 기본적인 조작법을 알아볼 필요가 있습니다. 더 강력한 조작법이 분명히 있지만, 굳이 이번 가이드에서는 다루지 않겠습니다.


```
ctrl + o #저장
ctrl + x #닫기
화살표 키 #커서 이동
```


이 매뉴얼과 같은 디렉터리에 존재하는 'rhymix-nginx.conf' 파일을 nginx.conf가 위치한 디렉터리(/etc/nginx)로 이동해 보도록 합시다. 방법은 FTP에서 드래그 앤 드롭으로 옮기셔도, 아니면 터미널 상에서 명령어를 이용해서 옮기셔도 좋습니다.

이제 **nginx.conf** 파일을 수정해 보도록 하겠습니다. rhymix-nginx.conf 파일을 **nginx.conf** 파일에 인클루드 하는게 기본적인 과정입니다.

먼저 **nginx.conf** 파일을 열어줍니다. (이때, 엔진엑스에서 가상 서버 기능을 사용하고 있는 경우에는 각 서버의 설정 파일을 수정해 줘야 합니다.)


```
nano nginx.conf
```


nginx.conf 설정 파일을 여실 수 있을겁니다. 설정 파일에는 크게 두가지 **블럭**이 존재합니다. 첫번째는 **http{}** 형태로 구성되어 있는 **http 블럭**, 두번째는 **server{}** 형태로 존재하는 **server 블럭** 입니다. **nginx.conf** 파일은


```
http {
server {}
}
```

위와 같이 구조가 짜여있는걸 확인하실 수 있는데(물론 event, worker_processes와 같은 부분도 있습니다만, 이 부분은 여기서 다루지 않겠습니다.), 우리가 주목해야 할 것은


```
server {}
```


블럭입니다. server 블럭에 **rhymix-nginx.conf** 파일을 인클루드 해 줘야 합니다.

server 블럭에는 다양한 정보가 들어 있습니다. 그 정보의 최하단(혹은 원하는 곳)에


```
include rhymix-nginx.conf
```


구문을 추가해서 rhymix-nginx.conf 파일을 인클루드 하도록 합니다. 만약 nginx.conf 파일을 XE에 맞게 설정하지 않은 상태라면 아래와 같이 바꿔주도록 합시다. 이때, # 뒤에 붙는 글자는 '주석'처리 된 글자입니다. 즉 nginx는 #뒤에 붙는 글자를 인식하지 않습니다.

```
http {
	include	mime.types;
	default_type application/octet_stream;
	
	server {
		listen 80;
		#만약 SSL을 사용하고 싶다면 80; 대신 443 ssl;을 이용하면 됩니다.
		
		server_name example.com
		#example.com은 자신의 도메인으로 바꾸면 됩니다. www.example.com과 example.com이 서로 다름에 유의해 주세요.
		
		root /var/www/html;
		#이때, 원하는 디렉터리로 홈 디렉터리를 수정해도 됩니다. 만약 /var/lol/lol로 홈 디렉터리를 사용하고 싶다면 /var/lol/lol;을 홈 디렉터리로 사용하면 됩니다.
		
		index index.php;
		#index.php가 기본 페이지가 된다는 의미입니다. Rhymix는 index.php를 사용하니 index.php를 넣는 것입니다.
		
		include rhymix-nginx.conf
		#위에서 설명드렸다시피 rhymix의 리라이트 규칙을 인클루드 하는 코드입니다.
		
	}
}
		
```
하나의 서버에 하나의 웹사이트만 돌릴 생각이라면, 이 가이드의 최하단으로 이동하시기 바랍니다.


만약, 하나의 서버에 여러개의 웹사이트를 돌리고 싶다면(=가상 서버를 사용하고 싶다면) **/etc/nginx** 디렉터리 아래에 새로운 하위 디렉터리를 만든 뒤 그 곳에 존재하는 모든 파일을 인클루드 해 버리면 됩니다. 그리고 그곳에서 가상 서버 파일을 생성하여 rhymix-nginx.conf를 인클루드 하면 됩니다. 예를 들면 이렇습니다.

이때, 하위 디렉터리는 conf.d 를 생성했다고 가정합니다. 즉, 하위 디렉터리의 경로는 /etc/nginx/conf.d 입니다.


```
http {
	include mime.types;
	default_type application/octet_stream;
	
	include /etc/nginx/conf.d*;
	#conf.d 디렉터리 내에 모든 파일을 인클루드 하겠다는 의미입니다.
}
```

conf.d 디렉터리 아래에는 **server 블럭**만 들어가야 합니다. conf.d의 example.conf 파일을 생성하는 케이스를 예로 들어 설명하겠습니다.

```
	server {
		listen 80;
		#만약 SSL을 사용하고 싶다면 80; 대신 443 ssl;을 이용하면 됩니다.
		
		server_name example.com
		#example.com은 자신의 도메인으로 바꾸면 됩니다. www.example.com과 example.com이 서로 다름에 유의해 주세요.
		
		root /var/www/html;
		#이때, 원하는 디렉터리로 홈 디렉터리를 수정해도 됩니다. 만약 /var/lol/lol로 홈 디렉터리를 사용하고 싶다면 /var/lol/lol;을 홈 디렉터리로 사용하면 됩니다.
		
		index index.php;
		#index.php가 기본 페이지가 된다는 의미입니다. Rhymix는 index.php를 사용하니 index.php를 넣는 것입니다.
		
		include rhymix-nginx.conf
		#위에서 설명드렸다시피 rhymix의 리라이트 규칙을 인클루드 하는 코드입니다.
		
	}
```

위는 conf.d 디렉터리 내에 존재하는 example.conf 파일입니다. nginx.conf 파일에서 conf.d 디렉터리 내에 존재하는 모든 파일(*)를 인클루드 하겠다고 설정하였기 때문에 nginx.conf 파일에 자동적으로 인클루드 되게 됩니다.
또한, 이미 http 블럭은 nginx.conf에서 설정이 완료되었기 때문에 nginx.conf에 인클루드 되는 conf.d의 파일은 http 블럭 없이 server 블럭으로만 구성되어야 합니다.


##엔진엑스 재시작하기
엔진엑스의 기본적인 설정을 마쳤습니다. 이제 엔진엑스가 변경된 설정을 로딩할 수 있도록 재시작해줘야 합니다.

터미널을 열고


```
sudo service nginx reload
```

혹은


```
sudo service nginx restart
```

커맨드를 실행하도록 합니다. 이 커맨드는 **nginx**라는 **서비스**를 **리로드 하거나 재시작** 하도록 하라는 의미를 담고 있습니다. 보통 reload를 권장하며, restart는 권장하지 않습니다.

##마치며...
이로서 엔진엑스의 리라이트 설정을 마쳤습니다. 이를 통해 읽기 복잡한 긴 주소를 짧은 주소로 변환할 수 있을 것입니다.
