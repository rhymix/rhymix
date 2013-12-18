# Tools

## Build
XE패키징을 위해 grunt.js를 이용한 Task로 작성되어 있으며 *nix 환경에서 Node를 구동할 수 있어야 한다.

### 의존 패키지 설치 (Mac)

### 1. brew
http://brew.sh 페이지의 'Install Homebrew'를 참고하여 설치

### 2. node.js
> brew install node

### 3. grunt-cli
> npm install -g grunt-cli

## node 모듈 설치
grunt task 수행에 필요한 node 모듈의 설치를 먼저 수행한다.

XE 패키지의 루트… index.php 또는 Gruntfile.js 파일이 있는 곳에서 다음 명령으로 의존 모듈이 자동 설치된다.

> npm install

## Build
build 수행 전 Minified 파일이 갱신되도록 패키징 하려는 브랜치에서 아래 Task 중 `grunt minify`를 반드시 먼저 수행하도록 한다.

build 명령으로 zip, tgz 포맷으로 패키징을 수행하며 지정한 대상의 변경된 파일만을 묶은 changed 파일을 함께 생성한다.

> grunt build`:from`:`to`

`from`, `to`에는 commit hash 또는 tag, branch를 지정할 수 있다. 패키지는 `to`에 지정한 대상을 기준으로하며 `from`과 `to`사이에 변경된 파일들 changed 파일을 별도로 생성한다.

> grunt build:`old_tag`:`current_tag`

이와 같이 지정하면 `old_tag`로부터 `current_tag` 사이의 변경된 파일만을 묶은 `xe.current_tag.changed.*` 파일과 `xe.current_tag.*`파일을 생성한다.

`from`을 생략하여 `build:master`(master는 branch이다)와 같이 지정하면 `master`의 최신 상태로 빌드하며 changed 파일을 생성하지 않는다.

### Build 수행 시 포함하는 패키지
Build 수행 시 일부 확장 기능을 가져와 함게 패키징한다. 지정한 각 저장소의 master 브랜치로부터 코드를 가져오므로 **Build 수행 전에 각 저장소의 master 상태를 확인하도록 한다.**

* board 모듈
 *  https://github.com/xpressengine/xe-module-board
* krzip 모듈
 *  https://github.com/xpressengine/xe-module-krzip
* syndication 모듈
 *  https://github.com/xpressengine/xe-module-syndication

## Task
.js, .css, .php 파일들에 대해 문법 검사 및 권장 코드를 확인할 수 있으며 minify 등의 작업을 수행할 수 있다.

### Lint
.js, .css, .php 파일에 대해 문법 검사 등을 수행한다.
> grunt lint

다음과 같이 선택적으로 수행할 수 있다.

#### JS Lint (jshint)
`lint`가 아닌 `hint`임에 주의.
> grunt jshint

#### CSS Lint
> grunt csslint

### Minify
.js, .css 파일의 공백을 지우는 등 minify 동작을 수행할 수 있으며 대상은 Gruntfile.js 파일에 정의되어 있다.

 > grunt minify
