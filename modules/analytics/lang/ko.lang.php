<?php
	$lang->analytics = 'Analytics';
	$lang->about_analytics = 'Analytics모듈은 사이트에 접근한 방문자의 패턴을 분석하여 차트로 노출합니다. 다양한 분석결과를 제공하므로 사이트 구성 및 운영에 참고할 수 있습니다.';

	$lang->detail_info = '상세정보';

	$lang->week_name = array();
	$lang->week_name['0'] = '일요일';
	$lang->week_name['1'] = '월요일';
	$lang->week_name['2'] = '화요일';
	$lang->week_name['3'] = '수요일';
	$lang->week_name['4'] = '목요일';
	$lang->week_name['5'] = '금요일';
	$lang->week_name['6'] = '토요일';

	$lang->after_day = '일 후';
	$lang->sum_total = '합계';

	$lang->minute = '분';
	$lang->second = '초';
	$lang->etc = '기타';

	$lang->cmd_analytics_info = 'Analytics정보';
	$lang->cmd_analytics_visit_info = '방문자 분석';
	$lang->cmd_analytics_come_info = '유입경로 분석';
	$lang->cmd_analytics_page_info = '페이지 분석';
	$lang->cmd_analytics_date_set = '기간 설정';
	$lang->cmd_analytics_end_date_set = '날짜';

	$lang->analytics_api_key = 'API KEY 정보';
	$lang->cmd_check_analytics_api_key = 'API KEY 확인';
	$lang->about_analytics_api_key = '공식사이트에서 발급받은 API KEY를 등록합니다.';
	$lang->analytics_visit_info_config = '방문자 분석 설정';
	$lang->analytics_come_info_config = '유입경로 분석 설정';
	$lang->analytics_page_info_config = '페이지 분석 설정';

	$lang->analytics_api_method = array(
										'visit' => '방문현황'
										,'visitPageView' => '페이지뷰'
										,'visitTime' => '시간대별 방문 분포'
										,'visitDay' => '요일별 방문 분포'
										,'visitBack' => '재방문 간격'
										,'visitStayTime' => '방문 체류시간'
										,'visitPath' => '방문 경로 깊이'
										,'comeEngine' => '유입검색엔진'
										,'comeSearchText' => '유입검색어'
										,'comeUrl' => '유입상세URL'
										,'pagePop' => '인기페이지'
										,'pageDrillDown' => '페이지 드릴다운'
										,'pageStart' => '방문시작페이지'
										,'pageEnd' => '종료페이지'
										,'pageReturn' => '반송페이지');

	$lang->analytics_api_valuname = array('visit' => array('day' =>'날짜'
														  ,'uv' => '방문자수' 
														  ,'visit_count' => '방문횟수'
														  ,'newvisit_uv'=> '신규 방문자수'
														  , 'revisit_uv'=>'재방문자수')
										 ,'visitPageView' => array('day' => '날짜'
																  ,'pv' => '페이지뷰'
																  ,'pv_per_visit'=>'방문당 페이지뷰'
																  ,'newvisit_pv' => '신규 방문자 페이지뷰'
																  ,'revisit_pv' =>'재방문자 페이지뷰')
										 ,'visitTime' => array('timezone' => '시간대'
															  ,'sumvisit_count' => '방문횟수 평균'
														 	  ,'sumvisitor' => '방문자수 평균'
															  , 'sumrevisitor' => '재방문자수 평균'
															  , 'sumnewvisitor' => '신규방문자수 평균'
															  , 'sumpv' => '페이지뷰 평균')
										 ,'visitDay' => array('week' => '요일'
															 ,'avgvisit' => '평균방문횟수'
															 ,'avguv' => '평균방문자수'
															 ,'avgnewvisit' => '평균신규방문자수'
															 ,'avgrevisit' => '평균재방문자수'
															 ,'avgpv' => '평균페이지뷰')
										 ,'visitBack' => array('freq' => '재방문주기'
															  ,'vc' => '방문횟수'
															  ,'1day' => '1일'
															  ,'2day' => '2일'
															  ,'4day' => '4일'
															  ,'5day' => '5일'
															  ,'6day' => '6일'
															  ,'etc' => '기타')
										 ,'visitStayTime' => array('day'=>'날짜'
																  ,'avgStayTime' => '평균체류시간(초)'
																  ,'vc' => '방문횟수' 
																  ,'vc_under_30sec' =>'30초이하'
															      ,'vc_31_60' => '31초~60초'
																  ,'vc_2min' => '2분'
																  ,'vc_3min' => '3분'
																  ,'vc_4min' => '4분'
																  ,'vc_5min' => '5분'
																  ,'vc_10min' => '10분'
																  ,'vc_30min' => '30분'
																  ,'vc_60min' => '1시간이하'
																  ,'vc_60high' =>'1시간이상')
										 ,'visitPath' => array('day'=>'날짜'
															  ,'visit' =>'방문횟수'
															  ,'pv' =>'페이지뷰'
															  ,'one'=>'1페이지'
													 		  ,'four'=>'2-4페이지'
															  ,'ten'=>'5-10페이지'
															  ,'fifteen'=>'11-15페이지'
															  ,'twenty'=>'16-20페이지'
															  ,'thirty'=>'21-30페이지'
															  ,'fourty'=>'31-40페이지'
															  ,'fifty'=>'41-50페이지'
															  ,'high'=>'51페이지이상')
										 ,'comeEngine' => array('rank'=>'순위'
															   ,'searchengine'=>'검색엔진'
															   ,'sumqc'=>'유입수'
															   ,'percent'=>'유입률')
										 ,'comeSearchText' => array('rank'=>'순위'
																	,'query'=>'검색어'
																	,'sumqc'=>'유입수'
																	,'percent'=>'유입률')
										 ,'comeUrl' => array('rank'=>'순위'
															,'url'=>'페이지 URL'
														    ,'sumpv'=>'페이지뷰'
															,'percent'=>'비율')
									      ,'pagePop' => array('rank'=>'순위'
															,'url'=>'페이지 URL'
														    ,'sumpv'=>'페이지뷰'
															,'percent'=>'비율')
										  ,'pageDrillDown' => array('rank'=>'순위'
																	,'url'=>'페이지 URL'
																	,'sumpv'=>'페이지뷰'
																	,'sumunqpv'=>'Uniq 페이지뷰'
																	,'sumdutime'=>'체류시간'
																	,'sumbounce'=>'반송률'
																	,'sumexit'=>'유출률'
																	,'sumstart'=>'페이지뷰 비율')
										  ,'pageStart' => array('rank'=>'순위'
																,'url'=>'페이지 URL'
																,'sumpv'=>'페이지뷰'
																,'percent'=>'비율')
										  ,'pageEnd' => array('rank'=>'순위'
																,'url'=>'페이지 URL'
																,'sumpv'=>'페이지뷰'
																,'percent'=>'비율')
										  ,'pageReturn' => array('rank'=>'순위'
																,'url'=>'페이지 URL'
																,'sumpv'=>'페이지뷰'
																,'percent'=>'비율'));

	$lang->about_analytics_method = array('visit' => '설정된 기간내에 방문한 사용자의 통계 정보입니다.<br/>방문횟수 : 사이트 방문자 중 30분이내 재방문자를 제외한 사이트의 전체 방문자수를 의미 합니다.<br/>방문자수 : 방문횟수에서 중복 방문자수를 제외한 순수 방문자수를 의미합니다.<br/>신규방문자수 : 1개월 동안 한번도 사이트에 방문하지 않았던 방문자 수입니다.<br/>재방문자수 : 1개월 이내에 다시 사이트에 방문한 방문자 수 입니다.'
										 ,'visitPageView' => '설정된 기간내에 방문한 사용자가 확인한 페이지의 통계 데이터입니다.<br/>페이지뷰 : 방문자들이 사이트에 방문하여 열어 본 페이지 수(중복페이지 포함)의 총합입니다.<br/>방문당페이지뷰 : 방문당 페이지뷰의 평균 데이터 입니다.<br/>신규 방문자 페이지뷰 : 1개월 동안 한번도 사이트에 방문하지 않았던 방문자의 페이지뷰 총합입니다.<br/>재방문자 페이지뷰 : 1개월 이내에 다시 사이트에 방문한 방문자의 페이지뷰 총합입니다.'
										 ,'visitTime' => '설정된 기간내의 특정 시간대별 방문자 수 통계 정보입니다.'
										 ,'visitDay' => '설정된 기간내의 요일별 방문자 수 통계 정보입니다.'
										 ,'visitBack' => '특정 날짜를 중심으로 재방문한 방문자수 통계 정보입니다.'
										 ,'visitStayTime' => '설정된 기간내에 방문한 사용자의 사이트 체류 시간 통계 데이터입니다.'
										 ,'visitPath' => '방문시 열람한 페이지(중복 페이지 포함)의 통계 데이터입니다.'
										 ,'comeUrl' => '방문자가 어떤 URL을 통해 유입되는지에 대한 통계 데이터입니다.'
										 ,'pagePop' =>'방문자가 많이 열어본 페이지 데이터 입니다.'
										 ,'pageDrillDown' =>'사이트의 페이지별 페이지뷰, 방문자 체류시간, 반송율(해당 페이지가 시작페이지이면서 동시에 종료페이지인 경우), 유출율에 대한 통계 데이터입니다.'
										 ,'pageStart' =>'사이트의 방문이 시작된 페이지의 통계 데이터입니다.'
										 ,'pageEnd' =>'사이트의 방문이 종료된 페이지의 통계 데이터입니다.'
										 ,'pageReturn' =>'사이트의 방문이 시작되고 동시에 종료된 페이지의 통계 데이터입니다.');
?>
