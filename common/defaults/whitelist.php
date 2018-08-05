<?php

/**
 * Rhymix Default iframe/object/iframe Whitelist
 * 
 * Copyright (c) Rhymix Developers and Contributors
 */
return array(
	
	/**
	 * Allowed domains in <object> or <embed> tag
	 */
	'object' => array(
		// YouTube
		'www.youtube.com/',
		'www.youtube-nocookie.com/',
		// Daum
		'flvs.daum.net/flvPlayer.swf',
		'api.v.daum.net/',
		'tvpot.daum.net/playlist/playlist.swf',
		'videofarm.daum.net/',
		// Naver
		'serviceapi.nmv.naver.com/',
		'scrap.ad.naver.com/',
		'event.dn.naver.com/sbsplayer/vmplayer.xap',
		'static.campaign.naver.com/',
		'musicplayer.naver.com/naverPlayer/posting/',
		'player.music.naver.com/naverPlayer/posting/',
		// Mgoon
		'play.mgoon.com/',
		'doc.mgoon.com/player/',
		// Pandora TV
		'flvr.pandora.tv/flv2pan/',
		'imgcdn.pandora.tv/gplayer/pandora_EGplayer.swf',
		'imgcdn.pandora.tv/gplayer/flJal.swf',
		// Tagstory
		'play.tagstory.com/player/',
		'www.tagstory.com/player/basic/',
		// Cyworld
		'dbi.video.cyworld.com/v.sk/',
		// Egloos
		'v.egloos.com/v.sk/',
		// Nate
		'v.nate.com/v.sk/',
		'w.blogdoc.nate.com/',
		'blogdoc.nate.com/flash/blogdoc_widget_reco.swf',
		// KBS
		'www.kbs.co.kr/zzim/vmplayer/vmplayer.xap',
		'vmark.kbs.co.kr/zzim/vmplayer/vmplayer.xap',
		// MBC
		'onemore.imbc.com/ClientBin/oneplus.xap',
		// SBS
		'netv.sbs.co.kr/sbox/',
		'news.sbs.co.kr/',
		'wizard2.sbs.co.kr/',
		'sbsplayer.sbs.co.kr/',
	),
	
	/**
	 * Allowed domains in <iframe> tag
	 */
	'iframe' => array(
		// YouTube
		'www.youtube.com/',
		'www.youtube-nocookie.com/',
		// Google Maps
		'www.google.com/maps/embed',
		'maps.google.com/',
		'maps.google.co.kr/',
		// Daum TV Pot
		'flvs.daum.net/',
		'videofarm.daum.net/',
		// NAVER TVCAST
		'serviceapi.rmcnmv.naver.com/',
		// SBS
		'sbsplayer.sbs.co.kr/',
		// Vimeo
		'player.vimeo.com/',
		// Afreeca
		'afree.ca/',
		// Soundcloud
		'w.soundcloud.com/',
	),
);
