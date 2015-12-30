<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

include _XE_PATH_ . 'classes/security/phphtmlparser/src/htmlparser.inc';

class EmbedFilter
{

	/**
	 * allow script access list
	 * @var array
	 */
	var $allowscriptaccessList = array();

	/**
	 * allow script access key
	 * @var int
	 */
	var $allowscriptaccessKey = 0;
	var $whiteUrlXmlFile = './classes/security/conf/embedWhiteUrl.xml';
	var $whiteUrlCacheFile = './files/cache/embedfilter/embedWhiteUrl.php';
	var $whiteUrlList = array();
	var $whiteIframeUrlList = array();
	var $parser = NULL;
	var $mimeTypeList = array('application/andrew-inset' => 1, 'application/applixware' => 1, 'application/atom+xml' => 1, 'application/atomcat+xml' => 1, 'application/atomsvc+xml' => 1,
		'application/ccxml+xml' => 1, 'application/cdmi-capability' => 1, 'application/cdmi-container' => 1, 'application/cdmi-domain' => 1, 'application/cdmi-object' => 1,
		'application/cdmi-queue' => 1, 'application/cu-seeme' => 1, 'application/davmount+xml' => 1, 'application/docbook+xml' => 1, 'application/dssc+der' => 1, 'application/dssc+xml' => 1,
		'application/ecmascript' => 1, 'application/emma+xml' => 1, 'application/epub+zip' => 1, 'application/exi' => 1, 'application/font-tdpfr' => 1, 'application/gml+xml' => 1,
		'application/gpx+xml' => 1, 'application/gxf' => 1, 'application/hyperstudio' => 1, 'application/inkml+xml' => 1, 'application/inkml+xml' => 1, 'application/ipfix' => 1,
		'application/java-archive' => 1, 'application/java-serialized-object' => 1, 'application/java-vm' => 1, 'application/javascript' => 1, 'application/json' => 1,
		'application/jsonml+json' => 1, 'application/lost+xml' => 1, 'application/mac-binhex40' => 1, 'application/mac-compactpro' => 1, 'application/mads+xml' => 1,
		'application/marc' => 1, 'application/marcxml+xml' => 1, 'application/mathematica' => 1, 'application/mathematica' => 1, 'application/mathematica' => 1, 'application/mathml+xml' => 1,
		'application/mbox' => 1, 'application/mediaservercontrol+xml' => 1, 'application/metalink+xml' => 1, 'application/metalink4+xml' => 1, 'application/mets+xml' => 1,
		'application/mods+xml' => 1, 'application/mp21' => 1, 'application/mp4' => 1, 'application/msword' => 1, 'application/mxf' => 1, 'application/octet-stream' => 1,
		'application/octet-stream' => 1, 'application/octet-stream' => 1, 'application/octet-stream' => 1, 'application/octet-stream' => 1, 'application/octet-stream' => 1,
		'application/octet-stream' => 1, 'application/octet-stream' => 1, 'application/octet-stream' => 1, 'application/octet-stream' => 1, 'application/octet-stream' => 1,
		'application/octet-stream' => 1, 'application/oda' => 1, 'application/oebps-package+xml' => 1, 'application/ogg' => 1, 'application/omdoc+xml' => 1, 'application/onenote' => 1,
		'application/onenote' => 1, 'application/onenote' => 1, 'application/onenote' => 1, 'application/oxps' => 1, 'application/patch-ops-error+xml' => 1, 'application/pdf' => 1,
		'application/pgp-encrypted' => 1, 'application/pgp-signature' => 1, 'application/pgp-signature' => 1, 'application/pics-rules' => 1, 'application/pkcs10' => 1,
		'application/pkcs7-mime' => 1, 'application/pkcs7-mime' => 1, 'application/pkcs7-signature' => 1, 'application/pkcs8' => 1, 'application/pkix-attr-cert' => 1,
		'application/pkix-cert' => 1, 'application/pkix-crl' => 1, 'application/pkix-pkipath' => 1, 'application/pkixcmp' => 1, 'application/pls+xml' => 1,
		'application/postscript' => 1, 'application/postscript' => 1, 'application/postscript' => 1, 'application/prs.cww' => 1, 'application/pskc+xml' => 1,
		'application/rdf+xml' => 1, 'application/reginfo+xml' => 1, 'application/relax-ng-compact-syntax' => 1, 'application/resource-lists+xml' => 1,
		'application/resource-lists-diff+xml' => 1, 'application/rls-services+xml' => 1, 'application/rpki-ghostbusters' => 1, 'application/rpki-manifest' => 1,
		'application/rpki-roa' => 1, 'application/rsd+xml' => 1, 'application/rss+xml' => 1, 'application/rtf' => 1, 'application/sbml+xml' => 1, 'application/scvp-cv-request' => 1,
		'application/scvp-cv-response' => 1, 'application/scvp-vp-request' => 1, 'application/scvp-vp-response' => 1, 'application/sdp' => 1, 'application/set-payment-initiation' => 1,
		'application/set-registration-initiation' => 1, 'application/shf+xml' => 1, 'application/smil+xml' => 1, 'application/smil+xml' => 1, 'application/sparql-query' => 1,
		'application/sparql-results+xml' => 1, 'application/srgs' => 1, 'application/srgs+xml' => 1, 'application/sru+xml' => 1, 'application/ssdl+xml' => 1,
		'application/ssml+xml' => 1, 'application/tei+xml' => 1, 'application/tei+xml' => 1, 'application/thraud+xml' => 1, 'application/timestamped-data' => 1,
		'application/vnd.3gpp.pic-bw-large' => 1, 'application/vnd.3gpp.pic-bw-small' => 1, 'application/vnd.3gpp.pic-bw-var' => 1, 'application/vnd.3gpp2.tcap' => 1,
		'application/vnd.3m.post-it-notes' => 1, 'application/vnd.accpac.simply.aso' => 1, 'application/vnd.accpac.simply.imp' => 1, 'application/vnd.acucobol' => 1,
		'application/vnd.acucorp' => 1, 'application/vnd.acucorp' => 1, 'application/vnd.adobe.air-application-installer-package+zip' => 1, 'application/vnd.adobe.formscentral.fcdt' => 1,
		'application/vnd.adobe.fxp' => 1, 'application/vnd.adobe.fxp' => 1, 'application/vnd.adobe.xdp+xml' => 1, 'application/vnd.adobe.xfdf' => 1, 'application/vnd.ahead.space' => 1,
		'application/vnd.airzip.filesecure.azf' => 1, 'application/vnd.airzip.filesecure.azs' => 1, 'application/vnd.amazon.ebook' => 1, 'application/vnd.americandynamics.acc' => 1,
		'application/vnd.amiga.ami' => 1, 'application/vnd.android.package-archive' => 1, 'application/vnd.anser-web-certificate-issue-initiation' => 1,
		'application/vnd.anser-web-funds-transfer-initiation' => 1, 'application/vnd.antix.game-component' => 1, 'application/vnd.apple.installer+xml' => 1,
		'application/vnd.apple.mpegurl' => 1, 'application/vnd.aristanetworks.swi' => 1, 'application/vnd.astraea-software.iota' => 1, 'application/vnd.audiograph' => 1,
		'application/vnd.blueice.multipass' => 1, 'application/vnd.bmi' => 1, 'application/vnd.businessobjects' => 1, 'application/vnd.chemdraw+xml' => 1,
		'application/vnd.chipnuts.karaoke-mmd' => 1, 'application/vnd.cinderella' => 1, 'application/vnd.claymore' => 1, 'application/vnd.cloanto.rp9' => 1,
		'application/vnd.clonk.c4group' => 1, 'application/vnd.clonk.c4group' => 1, 'application/vnd.clonk.c4group' => 1, 'application/vnd.clonk.c4group' => 1,
		'application/vnd.clonk.c4group' => 1, 'application/vnd.cluetrust.cartomobile-config' => 1, 'application/vnd.cluetrust.cartomobile-config-pkg' => 1,
		'application/vnd.commonspace' => 1, 'application/vnd.contact.cmsg' => 1, 'application/vnd.cosmocaller' => 1, 'application/vnd.crick.clicker' => 1,
		'application/vnd.crick.clicker.keyboard' => 1, 'application/vnd.crick.clicker.palette' => 1, 'application/vnd.crick.clicker.template' => 1,
		'application/vnd.crick.clicker.wordbank' => 1, 'application/vnd.criticaltools.wbs+xml' => 1, 'application/vnd.ctc-posml' => 1, 'application/vnd.cups-ppd' => 1,
		'application/vnd.curl.car' => 1, 'application/vnd.curl.pcurl' => 1, 'application/vnd.dart' => 1, 'application/vnd.data-vision.rdz' => 1, 'application/vnd.dece.data' => 1,
		'application/vnd.dece.data' => 1, 'application/vnd.dece.data' => 1, 'application/vnd.dece.data' => 1, 'application/vnd.dece.ttml+xml' => 1, 'application/vnd.dece.ttml+xml' => 1,
		'application/vnd.dece.unspecified' => 1, 'application/vnd.dece.unspecified' => 1, 'application/vnd.dece.zip' => 1, 'application/vnd.dece.zip' => 1,
		'application/vnd.denovo.fcselayout-link' => 1, 'application/vnd.dna' => 1, 'application/vnd.dolby.mlp' => 1, 'application/vnd.dpgraph' => 1, 'application/vnd.dreamfactory' => 1,
		'application/vnd.ds-keypoint' => 1, 'application/vnd.dvb.ait' => 1, 'application/vnd.dvb.service' => 1, 'application/vnd.dynageo' => 1, 'application/vnd.ecowin.chart' => 1,
		'application/vnd.enliven' => 1, 'application/vnd.epson.esf' => 1, 'application/vnd.epson.msf' => 1, 'application/vnd.epson.quickanime' => 1, 'application/vnd.epson.salt' => 1,
		'application/vnd.epson.ssf' => 1, 'application/vnd.eszigno3+xml' => 1, 'application/vnd.eszigno3+xml' => 1, 'application/vnd.ezpix-album' => 1, 'application/vnd.ezpix-package' => 1,
		'application/vnd.fdf' => 1, 'application/vnd.fdsn.mseed' => 1, 'application/vnd.fdsn.seed' => 1, 'application/vnd.fdsn.seed' => 1, 'application/vnd.flographit' => 1,
		'application/vnd.fluxtime.clip' => 1, 'application/vnd.framemaker' => 1, 'application/vnd.framemaker' => 1, 'application/vnd.framemaker' => 1, 'application/vnd.framemaker' => 1,
		'application/vnd.frogans.fnc' => 1, 'application/vnd.frogans.ltf' => 1, 'application/vnd.fsc.weblaunch' => 1, 'application/vnd.fujitsu.oasys' => 1,
		'application/vnd.fujitsu.oasys2' => 1, 'application/vnd.fujitsu.oasys3' => 1, 'application/vnd.fujitsu.oasysgp' => 1, 'application/vnd.fujitsu.oasysprs' => 1,
		'application/vnd.fujixerox.ddd' => 1, 'application/vnd.fujixerox.docuworks' => 1, 'application/vnd.fujixerox.docuworks.binder' => 1, 'application/vnd.fuzzysheet' => 1,
		'application/vnd.genomatix.tuxedo' => 1, 'application/vnd.geogebra.file' => 1, 'application/vnd.geogebra.tool' => 1, 'application/vnd.geometry-explorer' => 1,
		'application/vnd.geometry-explorer' => 1, 'application/vnd.geonext' => 1, 'application/vnd.geoplan' => 1, 'application/vnd.geospace' => 1, 'application/vnd.gmx' => 1,
		'application/vnd.google-earth.kml+xml' => 1, 'application/vnd.google-earth.kmz' => 1, 'application/vnd.grafeq' => 1, 'application/vnd.grafeq' => 1,
		'application/vnd.groove-account' => 1, 'application/vnd.groove-help' => 1, 'application/vnd.groove-identity-message' => 1, 'application/vnd.groove-injector' => 1,
		'application/vnd.groove-tool-message' => 1, 'application/vnd.groove-tool-template' => 1, 'application/vnd.groove-vcard' => 1, 'application/vnd.hal+xml' => 1,
		'application/vnd.handheld-entertainment+xml' => 1, 'application/vnd.hbci' => 1, 'application/vnd.hhe.lesson-player' => 1, 'application/vnd.hp-hpgl' => 1,
		'application/vnd.hp-hpid' => 1, 'application/vnd.hp-hps' => 1, 'application/vnd.hp-jlyt' => 1, 'application/vnd.hp-pcl' => 1, 'application/vnd.hp-pclxl' => 1,
		'application/vnd.hydrostatix.sof-data' => 1, 'application/vnd.ibm.minipay' => 1, 'application/vnd.ibm.modcap' => 1, 'application/vnd.ibm.modcap' => 1, 'application/vnd.ibm.modcap' => 1,
		'application/vnd.ibm.rights-management' => 1, 'application/vnd.ibm.secure-container' => 1, 'application/vnd.iccprofile' => 1, 'application/vnd.iccprofile' => 1,
		'application/vnd.igloader' => 1, 'application/vnd.immervision-ivp' => 1, 'application/vnd.immervision-ivu' => 1, 'application/vnd.insors.igm' => 1, 'application/vnd.intercon.formnet' => 1,
		'application/vnd.intercon.formnet' => 1, 'application/vnd.intergeo' => 1, 'application/vnd.intu.qbo' => 1, 'application/vnd.intu.qfx' => 1, 'application/vnd.ipunplugged.rcprofile' => 1,
		'application/vnd.irepository.package+xml' => 1, 'application/vnd.is-xpr' => 1, 'application/vnd.isac.fcs' => 1, 'application/vnd.jam' => 1, 'application/vnd.jcp.javame.midlet-rms' => 1,
		'application/vnd.jisp' => 1, 'application/vnd.joost.joda-archive' => 1, 'application/vnd.kahootz' => 1, 'application/vnd.kahootz' => 1, 'application/vnd.kde.karbon' => 1,
		'application/vnd.kde.kchart' => 1, 'application/vnd.kde.kformula' => 1, 'application/vnd.kde.kivio' => 1, 'application/vnd.kde.kontour' => 1, 'application/vnd.kde.kpresenter' => 1,
		'application/vnd.kde.kpresenter' => 1, 'application/vnd.kde.kspread' => 1, 'application/vnd.kde.kword' => 1, 'application/vnd.kde.kword' => 1, 'application/vnd.kenameaapp' => 1,
		'application/vnd.kidspiration' => 1, 'application/vnd.kinar' => 1, 'application/vnd.kinar' => 1, 'application/vnd.koan' => 1, 'application/vnd.koan' => 1, 'application/vnd.koan' => 1,
		'application/vnd.koan' => 1, 'application/vnd.kodak-descriptor' => 1, 'application/vnd.las.las+xml' => 1, 'application/vnd.llamagraphics.life-balance.desktop' => 1,
		'application/vnd.llamagraphics.life-balance.exchange+xml' => 1, 'application/vnd.lotus-1-2-3' => 1, 'application/vnd.lotus-approach' => 1, 'application/vnd.lotus-freelance' => 1,
		'application/vnd.lotus-notes' => 1, 'application/vnd.lotus-organizer' => 1, 'application/vnd.lotus-screencam' => 1, 'application/vnd.lotus-wordpro' => 1,
		'application/vnd.macports.portpkg' => 1, 'application/vnd.mcd' => 1, 'application/vnd.medcalcdata' => 1, 'application/vnd.mediastation.cdkey' => 1, 'application/vnd.mfer' => 1,
		'application/vnd.mfmp' => 1, 'application/vnd.micrografx.flo' => 1, 'application/vnd.micrografx.igx' => 1, 'application/vnd.mif' => 1, 'application/vnd.mobius.daf' => 1,
		'application/vnd.mobius.dis' => 1, 'application/vnd.mobius.mbk' => 1, 'application/vnd.mobius.mqy' => 1, 'application/vnd.mobius.msl' => 1, 'application/vnd.mobius.plc' => 1,
		'application/vnd.mobius.txf' => 1, 'application/vnd.mophun.application' => 1, 'application/vnd.mophun.certificate' => 1, 'application/vnd.mozilla.xul+xml' => 1,
		'application/vnd.ms-artgalry' => 1, 'application/vnd.ms-cab-compressed' => 1, 'application/vnd.ms-excel' => 1, 'application/vnd.ms-excel' => 1, 'application/vnd.ms-excel' => 1,
		'application/vnd.ms-excel' => 1, 'application/vnd.ms-excel' => 1, 'application/vnd.ms-excel' => 1, 'application/vnd.ms-excel.addin.macroenabled.12' => 1,
		'application/vnd.ms-excel.sheet.binary.macroenabled.12' => 1, 'application/vnd.ms-excel.sheet.macroenabled.12' => 1, 'application/vnd.ms-excel.template.macroenabled.12' => 1,
		'application/vnd.ms-fontobject' => 1, 'application/vnd.ms-htmlhelp' => 1, 'application/vnd.ms-ims' => 1, 'application/vnd.ms-lrm' => 1, 'application/vnd.ms-officetheme' => 1,
		'application/vnd.ms-pki.seccat' => 1, 'application/vnd.ms-pki.stl' => 1, 'application/vnd.ms-powerpoint' => 1, 'application/vnd.ms-powerpoint' => 1,
		'application/vnd.ms-powerpoint' => 1, 'application/vnd.ms-powerpoint.addin.macroenabled.12' => 1, 'application/vnd.ms-powerpoint.presentation.macroenabled.12' => 1,
		'application/vnd.ms-powerpoint.slide.macroenabled.12' => 1, 'application/vnd.ms-powerpoint.slideshow.macroenabled.12' => 1,
		'application/vnd.ms-powerpoint.template.macroenabled.12' => 1, 'application/vnd.ms-project' => 1, 'application/vnd.ms-project' => 1,
		'application/vnd.ms-word.document.macroenabled.12' => 1, 'application/vnd.ms-word.template.macroenabled.12' => 1, 'application/vnd.ms-works' => 1,
		'application/vnd.ms-works' => 1, 'application/vnd.ms-works' => 1, 'application/vnd.ms-works' => 1, 'application/vnd.ms-wpl' => 1, 'application/vnd.ms-xpsdocument' => 1,
		'application/vnd.mseq' => 1, 'application/vnd.musician' => 1, 'application/vnd.muvee.style' => 1, 'application/vnd.mynfc' => 1, 'application/vnd.neurolanguage.nlu' => 1,
		'application/vnd.nitf' => 1, 'application/vnd.nitf' => 1, 'application/vnd.noblenet-directory' => 1, 'application/vnd.noblenet-sealer' => 1, 'application/vnd.noblenet-web' => 1,
		'application/vnd.nokia.n-gage.data' => 1, 'application/vnd.nokia.n-gage.symbian.install' => 1, 'application/vnd.nokia.radio-preset' => 1, 'application/vnd.nokia.radio-presets' => 1,
		'application/vnd.novadigm.edm' => 1, 'application/vnd.novadigm.edx' => 1, 'application/vnd.novadigm.ext' => 1, 'application/vnd.oasis.opendocument.chart' => 1,
		'application/vnd.oasis.opendocument.chart-template' => 1, 'application/vnd.oasis.opendocument.database' => 1, 'application/vnd.oasis.opendocument.formula' => 1,
		'application/vnd.oasis.opendocument.formula-template' => 1, 'application/vnd.oasis.opendocument.graphics' => 1, 'application/vnd.oasis.opendocument.graphics-template' => 1,
		'application/vnd.oasis.opendocument.image' => 1, 'application/vnd.oasis.opendocument.image-template' => 1, 'application/vnd.oasis.opendocument.presentation' => 1,
		'application/vnd.oasis.opendocument.presentation-template' => 1, 'application/vnd.oasis.opendocument.spreadsheet' => 1, 'application/vnd.oasis.opendocument.spreadsheet-template' => 1,
		'application/vnd.oasis.opendocument.text' => 1, 'application/vnd.oasis.opendocument.text-master' => 1, 'application/vnd.oasis.opendocument.text-template' => 1,
		'application/vnd.oasis.opendocument.text-web' => 1, 'application/vnd.olpc-sugar' => 1, 'application/vnd.oma.dd2+xml' => 1, 'application/vnd.openofficeorg.extension' => 1,
		'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 1, 'application/vnd.openxmlformats-officedocument.presentationml.slide' => 1,
		'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => 1, 'application/vnd.openxmlformats-officedocument.presentationml.template' => 1,
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 1, 'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => 1,
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 1, 'application/vnd.openxmlformats-officedocument.wordprocessingml.template' => 1,
		'application/vnd.osgeo.mapguide.package' => 1, 'application/vnd.osgi.dp' => 1, 'application/vnd.osgi.subsystem' => 1, 'application/vnd.palm' => 1, 'application/vnd.palm' => 1,
		'application/vnd.palm' => 1, 'application/vnd.pawaafile' => 1, 'application/vnd.pg.format' => 1, 'application/vnd.pg.osasli' => 1, 'application/vnd.picsel' => 1, 'application/vnd.pmi.widget' => 1,
		'application/vnd.pocketlearn' => 1, 'application/vnd.powerbuilder6' => 1, 'application/vnd.previewsystems.box' => 1, 'application/vnd.proteus.magazine' => 1,
		'application/vnd.publishare-delta-tree' => 1, 'application/vnd.pvi.ptid1' => 1, 'application/vnd.quark.quarkxpress' => 1, 'application/vnd.quark.quarkxpress' => 1,
		'application/vnd.quark.quarkxpress' => 1, 'application/vnd.quark.quarkxpress' => 1, 'application/vnd.quark.quarkxpress' => 1, 'application/vnd.quark.quarkxpress' => 1,
		'application/vnd.realvnc.bed' => 1, 'application/vnd.recordare.musicxml' => 1, 'application/vnd.recordare.musicxml+xml' => 1, 'application/vnd.rig.cryptonote' => 1,
		'application/vnd.rim.cod' => 1, 'application/vnd.rn-realmedia' => 1, 'application/vnd.rn-realmedia-vbr' => 1, 'application/vnd.route66.link66+xml' => 1, 'application/vnd.sailingtracker.track' => 1,
		'application/vnd.seemail' => 1, 'application/vnd.sema' => 1, 'application/vnd.semd' => 1, 'application/vnd.semf' => 1, 'application/vnd.shana.informed.formdata' => 1,
		'application/vnd.shana.informed.formtemplate' => 1, 'application/vnd.shana.informed.interchange' => 1, 'application/vnd.shana.informed.package' => 1, 'application/vnd.simtech-mindmapper' => 1,
		'application/vnd.simtech-mindmapper' => 1, 'application/vnd.smaf' => 1, 'application/vnd.smart.teacher' => 1, 'application/vnd.solent.sdkm+xml' => 1, 'application/vnd.solent.sdkm+xml' => 1,
		'application/vnd.spotfire.dxp' => 1, 'application/vnd.spotfire.sfs' => 1, 'application/vnd.stardivision.calc' => 1, 'application/vnd.stardivision.draw' => 1,
		'application/vnd.stardivision.impress' => 1, 'application/vnd.stardivision.math' => 1, 'application/vnd.stardivision.writer' => 1, 'application/vnd.stardivision.writer' => 1,
		'application/vnd.stardivision.writer-global' => 1, 'application/vnd.stepmania.package' => 1, 'application/vnd.stepmania.stepchart' => 1, 'application/vnd.sun.xml.calc' => 1,
		'application/vnd.sun.xml.calc.template' => 1, 'application/vnd.sun.xml.draw' => 1, 'application/vnd.sun.xml.draw.template' => 1, 'application/vnd.sun.xml.impress' => 1,
		'application/vnd.sun.xml.impress.template' => 1, 'application/vnd.sun.xml.math' => 1, 'application/vnd.sun.xml.writer' => 1, 'application/vnd.sun.xml.writer.global' => 1,
		'application/vnd.sun.xml.writer.template' => 1, 'application/vnd.sus-calendar' => 1, 'application/vnd.sus-calendar' => 1, 'application/vnd.svd' => 1, 'application/vnd.symbian.install' => 1,
		'application/vnd.symbian.install' => 1, 'application/vnd.syncml+xml' => 1, 'application/vnd.syncml.dm+wbxml' => 1, 'application/vnd.syncml.dm+xml' => 1,
		'application/vnd.tao.intent-module-archive' => 1, 'application/vnd.tcpdump.pcap' => 1, 'application/vnd.tcpdump.pcap' => 1, 'application/vnd.tcpdump.pcap' => 1,
		'application/vnd.tmobile-livetv' => 1, 'application/vnd.trid.tpt' => 1, 'application/vnd.triscape.mxs' => 1, 'application/vnd.trueapp' => 1, 'application/vnd.ufdl' => 1, 'application/vnd.ufdl' => 1,
		'application/vnd.uiq.theme' => 1, 'application/vnd.umajin' => 1, 'application/vnd.unity' => 1, 'application/vnd.uoml+xml' => 1, 'application/vnd.vcx' => 1, 'application/vnd.visio' => 1,
		'application/vnd.visio' => 1, 'application/vnd.visio' => 1, 'application/vnd.visio' => 1, 'application/vnd.visionary' => 1, 'application/vnd.vsf' => 1, 'application/vnd.wap.wbxml' => 1,
		'application/vnd.wap.wmlc' => 1, 'application/vnd.wap.wmlscriptc' => 1, 'application/vnd.webturbo' => 1, 'application/vnd.wolfram.player' => 1, 'application/vnd.wordperfect' => 1,
		'application/vnd.wqd' => 1, 'application/vnd.wt.stf' => 1, 'application/vnd.xara' => 1, 'application/vnd.xfdl' => 1, 'application/vnd.yamaha.hv-dic' => 1, 'application/vnd.yamaha.hv-script' => 1,
		'application/vnd.yamaha.hv-voice' => 1, 'application/vnd.yamaha.openscoreformat' => 1, 'application/vnd.yamaha.openscoreformat.osfpvg+xml' => 1, 'application/vnd.yamaha.smaf-audio' => 1,
		'application/vnd.yamaha.smaf-phrase' => 1, 'application/vnd.yellowriver-custom-menu' => 1, 'application/vnd.zul' => 1, 'application/vnd.zul' => 1, 'application/vnd.zzazz.deck+xml' => 1,
		'application/voicexml+xml' => 1, 'application/widget' => 1, 'application/winhlp' => 1, 'application/wsdl+xml' => 1, 'application/wspolicy+xml' => 1, 'application/x-7z-compressed' => 1,
		'application/x-abiword' => 1, 'application/x-ace-compressed' => 1, 'application/x-apple-diskimage' => 1, 'application/x-authorware-bin' => 1, 'application/x-authorware-bin' => 1,
		'application/x-authorware-bin' => 1, 'application/x-authorware-bin' => 1, 'application/x-authorware-map' => 1, 'application/x-authorware-seg' => 1, 'application/x-bcpio' => 1,
		'application/x-bittorrent' => 1, 'application/x-blorb' => 1, 'application/x-blorb' => 1, 'application/x-bzip' => 1, 'application/x-bzip2' => 1, 'application/x-bzip2' => 1, 'application/x-cbr' => 1,
		'application/x-cbr' => 1, 'application/x-cbr' => 1, 'application/x-cbr' => 1, 'application/x-cbr' => 1, 'application/x-cdlink' => 1, 'application/x-cfs-compressed' => 1, 'application/x-chat' => 1,
		'application/x-chess-pgn' => 1, 'application/x-conference' => 1, 'application/x-cpio' => 1, 'application/x-csh' => 1, 'application/x-debian-package' => 1, 'application/x-debian-package' => 1,
		'application/x-dgc-compressed' => 1, 'application/x-director' => 1, 'application/x-director' => 1, 'application/x-director' => 1, 'application/x-director' => 1, 'application/x-director' => 1,
		'application/x-director' => 1, 'application/x-director' => 1, 'application/x-director' => 1, 'application/x-director' => 1, 'application/x-doom' => 1, 'application/x-dtbncx+xml' => 1,
		'application/x-dtbook+xml' => 1, 'application/x-dtbresource+xml' => 1, 'application/x-dvi' => 1, 'application/x-envoy' => 1, 'application/x-eva' => 1, 'application/x-font-bdf' => 1,
		'application/x-font-ghostscript' => 1, 'application/x-font-linux-psf' => 1, 'application/x-font-otf' => 1, 'application/x-font-pcf' => 1, 'application/x-font-snf' => 1,
		'application/x-font-ttf' => 1, 'application/x-font-ttf' => 1, 'application/x-font-type1' => 1, 'application/x-font-type1' => 1, 'application/x-font-type1' => 1, 'application/x-font-type1' => 1,
		'application/x-font-woff' => 1, 'application/x-freearc' => 1, 'application/x-futuresplash' => 1, 'application/x-gca-compressed' => 1, 'application/x-glulx' => 1, 'application/x-gnumeric' => 1,
		'application/x-gramps-xml' => 1, 'application/x-gtar' => 1, 'application/x-hdf' => 1, 'application/x-install-instructions' => 1, 'application/x-iso9660-image' => 1,
		'application/x-java-jnlp-file' => 1, 'application/x-latex' => 1, 'application/x-lzh-compressed' => 1, 'application/x-lzh-compressed' => 1, 'application/x-mie' => 1,
		'application/x-mobipocket-ebook' => 1, 'application/x-mobipocket-ebook' => 1, 'application/x-ms-application' => 1, 'application/x-ms-shortcut' => 1, 'application/x-ms-wmd' => 1,
		'application/x-ms-wmz' => 1, 'application/x-ms-xbap' => 1, 'application/x-msaccess' => 1, 'application/x-msbinder' => 1, 'application/x-mscardfile' => 1, 'application/x-msclip' => 1,
		'application/x-msdownload' => 1, 'application/x-msdownload' => 1, 'application/x-msdownload' => 1, 'application/x-msdownload' => 1, 'application/x-msdownload' => 1, 'application/x-msmediaview' => 1,
		'application/x-msmediaview' => 1, 'application/x-msmediaview' => 1, 'application/x-msmetafile' => 1, 'application/x-msmetafile' => 1, 'application/x-msmetafile' => 1, 'application/x-msmetafile' => 1,
		'application/x-msmoney' => 1, 'application/x-mspublisher' => 1, 'application/x-msschedule' => 1, 'application/x-msterminal' => 1, 'application/x-mswrite' => 1, 'application/x-netcdf' => 1,
		'application/x-netcdf' => 1, 'application/x-nzb' => 1, 'application/x-pkcs12' => 1, 'application/x-pkcs12' => 1, 'application/x-pkcs7-certificates' => 1, 'application/x-pkcs7-certificates' => 1,
		'application/x-pkcs7-certreqresp' => 1, 'application/x-rar-compressed' => 1, 'application/x-research-info-systems' => 1, 'application/x-sh' => 1, 'application/x-shar' => 1,
		'application/x-shockwave-flash' => 1, 'application/x-silverlight-app' => 1, 'application/x-silverlight-2' => 1, 'application/x-sql' => 1, 'application/x-stuffit' => 1, 'application/x-stuffitx' => 1,
		'application/x-subrip' => 1, 'application/x-sv4cpio' => 1, 'application/x-sv4crc' => 1, 'application/x-t3vm-image' => 1, 'application/x-tads' => 1, 'application/x-tar' => 1, 'application/x-tcl' => 1,
		'application/x-tex' => 1, 'application/x-tex-tfm' => 1, 'application/x-texinfo' => 1, 'application/x-texinfo' => 1, 'application/x-tgif' => 1, 'application/x-ustar' => 1, 'application/x-wais-source' => 1,
		'application/x-x509-ca-cert' => 1, 'application/x-x509-ca-cert' => 1, 'application/x-xfig' => 1, 'application/x-xliff+xml' => 1, 'application/x-xpinstall' => 1, 'application/x-xz' => 1,
		'application/x-zmachine' => 1, 'application/x-zmachine' => 1, 'application/x-zmachine' => 1, 'application/x-zmachine' => 1, 'application/x-zmachine' => 1, 'application/x-zmachine' => 1,
		'application/x-zmachine' => 1, 'application/x-zmachine' => 1, 'application/xaml+xml' => 1, 'application/xcap-diff+xml' => 1, 'application/xenc+xml' => 1, 'application/xhtml+xml' => 1,
		'application/xhtml+xml' => 1, 'application/xml' => 1, 'application/xml' => 1, 'application/xml-dtd' => 1, 'application/xop+xml' => 1, 'application/xproc+xml' => 1, 'application/xslt+xml' => 1,
		'application/xspf+xml' => 1, 'application/xv+xml' => 1, 'application/xv+xml' => 1, 'application/xv+xml' => 1, 'application/xv+xml' => 1, 'application/yang' => 1, 'application/yin+xml' => 1,
		'application/zip' => 1, 'audio/adpcm' => 1, 'audio/basic' => 1, 'audio/basic' => 1, 'audio/midi' => 1, 'audio/midi' => 1, 'audio/midi' => 1, 'audio/midi' => 1, 'audio/mp4' => 1, 'audio/mpeg' => 1,
		'audio/mpeg' => 1, 'audio/mpeg' => 1, 'audio/mpeg' => 1, 'audio/mpeg' => 1, 'audio/mpeg' => 1, 'audio/ogg' => 1, 'audio/ogg' => 1, 'audio/ogg' => 1, 'audio/s3m' => 1, 'audio/silk' => 1,
		'audio/vnd.dece.audio' => 1, 'audio/vnd.dece.audio' => 1, 'audio/vnd.digital-winds' => 1, 'audio/vnd.dra' => 1, 'audio/vnd.dts' => 1, 'audio/vnd.dts.hd' => 1, 'audio/vnd.lucent.voice' => 1,
		'audio/vnd.ms-playready.media.pya' => 1, 'audio/vnd.nuera.ecelp4800' => 1, 'audio/vnd.nuera.ecelp7470' => 1, 'audio/vnd.nuera.ecelp9600' => 1, 'audio/vnd.rip' => 1, 'audio/webm' => 1,
		'audio/x-aac' => 1, 'audio/x-aiff' => 1, 'audio/x-aiff' => 1, 'audio/x-aiff' => 1, 'audio/x-caf' => 1, 'audio/x-flac' => 1, 'audio/x-matroska' => 1, 'audio/x-mpegurl' => 1, 'audio/x-ms-wax' => 1,
		'audio/x-ms-wma' => 1, 'audio/x-pn-realaudio' => 1, 'audio/x-pn-realaudio' => 1, 'audio/x-pn-realaudio-plugin' => 1, 'audio/x-wav' => 1, 'audio/xm' => 1, 'chemical/x-cdx' => 1, 'chemical/x-cif' => 1,
		'chemical/x-cmdf' => 1, 'chemical/x-cml' => 1, 'chemical/x-csml' => 1, 'chemical/x-xyz' => 1, 'image/bmp' => 1, 'image/cgm' => 1, 'image/g3fax' => 1, 'image/gif' => 1, 'image/ief' => 1, 'image/jpeg' => 1,
		'image/jpeg' => 1, 'image/jpeg' => 1, 'image/ktx' => 1, 'image/png' => 1, 'image/prs.btif' => 1, 'image/sgi' => 1, 'image/svg+xml' => 1, 'image/svg+xml' => 1, 'image/tiff' => 1, 'image/tiff' => 1,
		'image/vnd.adobe.photoshop' => 1, 'image/vnd.dece.graphic' => 1, 'image/vnd.dece.graphic' => 1, 'image/vnd.dece.graphic' => 1, 'image/vnd.dece.graphic' => 1, 'image/vnd.dvb.subtitle' => 1,
		'image/vnd.djvu' => 1, 'image/vnd.djvu' => 1, 'image/vnd.dwg' => 1, 'image/vnd.dxf' => 1, 'image/vnd.fastbidsheet' => 1, 'image/vnd.fpx' => 1, 'image/vnd.fst' => 1, 'image/vnd.fujixerox.edmics-mmr' => 1,
		'image/vnd.fujixerox.edmics-rlc' => 1, 'image/vnd.ms-modi' => 1, 'image/vnd.ms-photo' => 1, 'image/vnd.net-fpx' => 1, 'image/vnd.wap.wbmp' => 1, 'image/vnd.xiff' => 1, 'image/webp' => 1,
		'image/x-3ds' => 1, 'image/x-cmu-raster' => 1, 'image/x-cmx' => 1, 'image/x-freehand' => 1, 'image/x-freehand' => 1, 'image/x-freehand' => 1, 'image/x-freehand' => 1, 'image/x-freehand' => 1,
		'image/x-icon' => 1, 'image/x-mrsid-image' => 1, 'image/x-pcx' => 1, 'image/x-pict' => 1, 'image/x-pict' => 1, 'image/x-portable-anymap' => 1, 'image/x-portable-bitmap' => 1,
		'image/x-portable-graymap' => 1, 'image/x-portable-pixmap' => 1, 'image/x-rgb' => 1, 'image/x-tga' => 1, 'image/x-xbitmap' => 1, 'image/x-xpixmap' => 1, 'image/x-xwindowdump' => 1,
		'message/rfc822' => 1, 'message/rfc822' => 1, 'model/iges' => 1, 'model/iges' => 1, 'model/mesh' => 1, 'model/mesh' => 1, 'model/mesh' => 1, 'model/vnd.collada+xml' => 1, 'model/vnd.dwf' => 1,
		'model/vnd.gdl' => 1, 'model/vnd.gtw' => 1, 'model/vnd.mts' => 1, 'model/vnd.vtu' => 1, 'model/vrml' => 1, 'model/vrml' => 1, 'model/x3d+binary' => 1, 'model/x3d+binary' => 1, 'model/x3d+vrml' => 1,
		'model/x3d+vrml' => 1, 'model/x3d+xml' => 1, 'model/x3d+xml' => 1, 'video/3gpp' => 1, 'video/3gpp2' => 1, 'video/h261' => 1, 'video/h263' => 1, 'video/h264' => 1, 'video/jpeg' => 1, 'video/jpm' => 1,
		'video/jpm' => 1, 'video/mj2' => 1, 'video/mj2' => 1, 'video/mp4' => 1, 'video/mp4' => 1, 'video/mp4' => 1, 'video/mpeg' => 1, 'video/mpeg' => 1, 'video/mpeg' => 1, 'video/mpeg' => 1, 'video/mpeg' => 1,
		'video/ogg' => 1, 'video/quicktime' => 1, 'video/quicktime' => 1, 'video/vnd.dece.hd' => 1, 'video/vnd.dece.hd' => 1, 'video/vnd.dece.mobile' => 1, 'video/vnd.dece.mobile' => 1, 'video/vnd.dece.pd' => 1,
		'video/vnd.dece.pd' => 1, 'video/vnd.dece.sd' => 1, 'video/vnd.dece.sd' => 1, 'video/vnd.dece.video' => 1, 'video/vnd.dece.video' => 1, 'video/vnd.dvb.file' => 1, 'video/vnd.fvt' => 1,
		'video/vnd.mpegurl' => 1, 'video/vnd.mpegurl' => 1, 'video/vnd.ms-playready.media.pyv' => 1, 'video/vnd.uvvu.mp4' => 1, 'video/vnd.uvvu.mp4' => 1, 'video/vnd.vivo' => 1, 'video/webm' => 1,
		'video/x-f4v' => 1, 'video/x-fli' => 1, 'video/x-flv' => 1, 'video/x-m4v' => 1, 'video/x-matroska' => 1, 'video/x-matroska' => 1, 'video/x-matroska' => 1, 'video/x-mng' => 1, 'video/x-ms-asf' => 1,
		'video/x-ms-asf' => 1, 'video/x-ms-vob' => 1, 'video/x-ms-wm' => 1, 'video/x-ms-wmv' => 1, 'video/x-ms-wmx' => 1, 'video/x-ms-wvx' => 1, 'video/x-msvideo' => 1, 'video/x-sgi-movie' => 1,
		'video/x-smv' => 1, 'x-conference/x-cooltalk' => 1
	);
	var $extList = array('ez' => 1, 'aw' => 1, 'atom' => 1, 'atomcat' => 1, 'atomsvc' => 1, 'ccxml' => 1, 'cdmia' => 1, 'cdmic' => 1, 'cdmid' => 1, 'cdmio' => 1, 'cdmiq' => 1, 'cu' => 1, 'davmount' => 1,
		'dbk' => 1, 'dssc' => 1, 'xdssc' => 1, 'ecma' => 1, 'emma' => 1, 'epub' => 1, 'exi' => 1, 'pfr' => 1, 'gml' => 1, 'gpx' => 1, 'gxf' => 1, 'stk' => 1, 'ink' => 1, 'inkml' => 1, 'ipfix' => 1, 'jar' => 1,
		'ser' => 1, 'class' => 1, 'js' => 1, 'json' => 1, 'jsonml' => 1, 'lostxml' => 1, 'hqx' => 1, 'cpt' => 1, 'mads' => 1, 'mrc' => 1, 'mrcx' => 1, 'ma' => 1, 'nb' => 1, 'mb' => 1, 'mathml' => 1, 'mbox' => 1,
		'mscml' => 1, 'metalink' => 1, 'meta4' => 1, 'mets' => 1, 'mods' => 1, 'm21 mp21' => 1, 'mp4s' => 1, 'doc dot' => 1, 'mxf' => 1, 'bin' => 1, 'dms' => 1, 'lrf' => 1, 'mar' => 1, 'so' => 1, 'dist' => 1,
		'distz' => 1, 'pkg' => 1, 'bpk' => 1, 'dump' => 1, 'elc' => 1, 'deploy' => 1, 'oda' => 1, 'opf' => 1, 'ogx' => 1, 'omdoc' => 1, 'onetoc' => 1, 'onetoc2' => 1, 'onetmp' => 1, 'onepkg' => 1, 'oxps' => 1,
		'xer' => 1, 'pdf' => 1, 'pgp' => 1, 'asc' => 1, 'sig' => 1, 'prf' => 1, 'p10' => 1, 'p7m' => 1, 'p7c' => 1, 'p7s' => 1, 'p8' => 1, 'ac' => 1, 'cer' => 1, 'crl' => 1, 'pkipath' => 1, 'pki' => 1, 'pls' => 1,
		'ai' => 1, 'eps' => 1, 'ps' => 1, 'cww' => 1, 'pskcxml' => 1, 'rdf' => 1, 'rif' => 1, 'rnc' => 1, 'rl' => 1, 'rld' => 1, 'rs' => 1, 'gbr' => 1, 'mft' => 1, 'roa' => 1, 'rsd' => 1, 'rss' => 1, 'rtf' => 1,
		'sbml' => 1, 'scq' => 1, 'scs' => 1, 'spq' => 1, 'spp' => 1, 'sdp' => 1, 'setpay' => 1, 'setreg' => 1, 'shf' => 1, 'smi' => 1, 'smil' => 1, 'rq' => 1, 'srx' => 1, 'gram' => 1, 'grxml' => 1, 'sru' => 1,
		'ssdl' => 1, 'ssml' => 1, 'tei' => 1, 'teicorpus' => 1, 'tfi' => 1, 'tsd' => 1, 'plb' => 1, 'psb' => 1, 'pvb' => 1, 'tcap' => 1, 'pwn' => 1, 'aso' => 1, 'imp' => 1, 'acu' => 1, 'atc' => 1, 'acutc' => 1,
		'air' => 1, 'fcdt' => 1, 'fxp' => 1, 'fxpl' => 1, 'xdp' => 1, 'xfdf' => 1, 'ahead' => 1, 'azf' => 1, 'azs' => 1, 'azw' => 1, 'acc' => 1, 'ami' => 1, 'apk' => 1, 'cii' => 1, 'fti' => 1, 'atx' => 1, 'mpkg' => 1,
		'm3u8' => 1, 'swi' => 1, 'iota' => 1, 'aep' => 1, 'mpm' => 1, 'bmi' => 1, 'rep' => 1, 'cdxml' => 1, 'mmd' => 1, 'cdy' => 1, 'cla' => 1, 'rp9' => 1, 'c4g' => 1, 'c4d' => 1, 'c4f' => 1, 'c4p' => 1, 'c4u' => 1,
		'c11amc' => 1, 'c11amz' => 1, 'csp' => 1, 'cdbcmsg' => 1, 'cmc' => 1, 'clkx' => 1, 'clkk' => 1, 'clkp' => 1, 'clkt' => 1, 'clkw' => 1, 'wbs' => 1, 'pml' => 1, 'ppd' => 1, 'car' => 1, 'pcurl' => 1, 'dart' => 1,
		'rdz' => 1, 'uvf' => 1, 'uvvf' => 1, 'uvd' => 1, 'uvvd' => 1, 'uvt' => 1, 'uvvt' => 1, 'uvx' => 1, 'uvvx' => 1, 'uvz' => 1, 'uvvz' => 1, 'fe_launch' => 1, 'dna' => 1, 'mlp' => 1, 'dpg' => 1, 'dfac' => 1,
		'kpxx' => 1, 'ait' => 1, 'svc' => 1, 'geo' => 1, 'mag' => 1, 'nml' => 1, 'esf' => 1, 'msf' => 1, 'qam' => 1, 'slt' => 1, 'ssf' => 1, 'es3' => 1, 'et3' => 1, 'ez2' => 1, 'ez3' => 1, 'fdf' => 1, 'mseed' => 1,
		'seed' => 1, 'dataless' => 1, 'gph' => 1, 'ftc' => 1, 'fm' => 1, 'frame' => 1, 'maker' => 1, 'book' => 1, 'fnc' => 1, 'ltf' => 1, 'fsc' => 1, 'oas' => 1, 'oa2' => 1, 'oa3' => 1, 'fg5' => 1, 'bh2' => 1, 'ddd' => 1,
		'xdw' => 1, 'xbd' => 1, 'fzs' => 1, 'txd' => 1, 'ggb' => 1, 'ggt' => 1, 'gex' => 1, 'gre' => 1, 'gxt' => 1, 'g2w' => 1, 'g3w' => 1, 'gmx' => 1, 'kml' => 1, 'kmz' => 1, 'gqf' => 1, 'gqs' => 1, 'gac' => 1, 'ghf' => 1,
		'gim' => 1, 'grv' => 1, 'gtm' => 1, 'tpl' => 1, 'vcg' => 1, 'hal' => 1, 'zmm' => 1, 'hbci' => 1, 'les' => 1, 'hpgl' => 1, 'hpid' => 1, 'hps' => 1, 'jlt' => 1, 'pcl' => 1, 'pclxl' => 1, 'sfd-hdstx' => 1, 'mpy' => 1,
		'afp' => 1, 'listafp' => 1, 'list3820' => 1, 'irm' => 1, 'sc' => 1, 'icc' => 1, 'icm' => 1, 'igl' => 1, 'ivp' => 1, 'ivu' => 1, 'igm' => 1, 'xpw' => 1, 'xpx' => 1, 'i2g' => 1, 'qbo' => 1, 'qfx' => 1,
		'rcprofile' => 1, 'irp' => 1, 'xpr' => 1, 'fcs' => 1, 'jam' => 1, 'rms' => 1, 'jisp' => 1, 'joda' => 1, 'ktz' => 1, 'ktr' => 1, 'karbon' => 1, 'chrt' => 1, 'kfo' => 1, 'flw' => 1, 'kon' => 1, 'kpr' => 1, 'kpt' => 1,
		'ksp' => 1, 'kwd' => 1, 'kwt' => 1, 'htke' => 1, 'kia' => 1, 'kne' => 1, 'knp' => 1, 'skp' => 1, 'skd' => 1, 'skt' => 1, 'skm' => 1, 'sse' => 1, 'lasxml' => 1, 'lbd' => 1, 'lbe' => 1, '123' => 1, 'apr' => 1,
		'pre' => 1, 'nsf' => 1, 'org' => 1, 'scm' => 1, 'lwp' => 1, 'portpkg' => 1, 'mcd' => 1, 'mc1' => 1, 'cdkey' => 1, 'mwf' => 1, 'mfm' => 1, 'flo' => 1, 'igx' => 1, 'mif' => 1, 'daf' => 1, 'dis' => 1, 'mbk' => 1,
		'mqy' => 1, 'msl' => 1, 'plc' => 1, 'txf' => 1, 'mpn' => 1, 'mpc' => 1, 'xul' => 1, 'cil' => 1, 'cab' => 1, 'xls' => 1, 'xlm' => 1, 'xla' => 1, 'xlc' => 1, 'xlt' => 1, 'xlw' => 1, 'xlam' => 1, 'xlsb' => 1, 'xlsm' => 1,
		'xltm' => 1, 'eot' => 1, 'chm' => 1, 'ims' => 1, 'lrm' => 1, 'thmx' => 1, 'cat' => 1, 'stl' => 1, 'ppt' => 1, 'pps' => 1, 'pot' => 1, 'ppam' => 1, 'pptm' => 1, 'sldm' => 1, 'ppsm' => 1, 'potm' => 1, 'mpp' => 1,
		'mpt' => 1, 'docm' => 1, 'dotm' => 1, 'wps' => 1, 'wks' => 1, 'wcm' => 1, 'wdb' => 1, 'wpl' => 1, 'xps' => 1, 'mseq' => 1, 'mus' => 1, 'msty' => 1, 'taglet' => 1, 'nlu' => 1, 'nitf' => 1, 'nitf' => 1, 'nnd' => 1,
		'nns' => 1, 'nnw' => 1, 'ngdat' => 1, 'n-gage' => 1, 'rpst' => 1, 'rpss' => 1, 'edm' => 1, 'edx' => 1, 'ext' => 1, 'odc' => 1, 'otc' => 1, 'odb' => 1, 'odf' => 1, 'odft' => 1, 'odg' => 1, 'otg' => 1, 'odi' => 1,
		'oti' => 1, 'odp' => 1, 'otp' => 1, 'ods' => 1, 'ots' => 1, 'odt' => 1, 'odm' => 1, 'ott' => 1, 'oth' => 1, 'xo' => 1, 'dd2' => 1, 'oxt' => 1, 'pptx' => 1, 'sldx' => 1, 'ppsx' => 1, 'potx' => 1, 'xlsx' => 1, 'xltx' => 1,
		'docx' => 1, 'dotx' => 1, 'mgp' => 1, 'dp' => 1, 'esa' => 1, 'pdb' => 1, 'pqa' => 1, 'oprc' => 1, 'paw' => 1, 'str' => 1, 'ei6' => 1, 'efif' => 1, 'wg' => 1, 'plf' => 1, 'pbd' => 1, 'box' => 1, 'mgz' => 1, 'qps' => 1,
		'ptid' => 1, 'qxd' => 1, 'qxt' => 1, 'qwd' => 1, 'qwt' => 1, 'qxl' => 1, 'qxb' => 1, 'bed' => 1, 'mxl' => 1, 'musicxml' => 1, 'cryptonote' => 1, 'cod' => 1, 'rm' => 1, 'rmvb' => 1, 'link66' => 1, 'st' => 1, 'see' => 1,
		'sema' => 1, 'semd' => 1, 'semf' => 1, 'ifm' => 1, 'itp' => 1, 'iif' => 1, 'ipk' => 1, 'twd' => 1, 'twds' => 1, 'mmf' => 1, 'teacher' => 1, 'sdkm' => 1, 'sdkd' => 1, 'dxp' => 1, 'sfs' => 1, 'sdc' => 1, 'sda' => 1,
		'sdd' => 1, 'smf' => 1, 'sdw' => 1, 'vor' => 1, 'sgl' => 1, 'smzip' => 1, 'sm' => 1, 'sxc' => 1, 'stc' => 1, 'sxd' => 1, 'std' => 1, 'sxi' => 1, 'sti' => 1, 'sxm' => 1, 'sxw' => 1, 'sxg' => 1, 'stw' => 1, 'sus' => 1,
		'susp' => 1, 'svd' => 1, 'sis' => 1, 'sisx' => 1, 'xsm' => 1, 'bdm' => 1, 'xdm' => 1, 'tao' => 1, 'pcap' => 1, 'cap' => 1, 'dmp' => 1, 'tmo' => 1, 'tpt' => 1, 'mxs' => 1, 'tra' => 1, 'ufd' => 1, 'ufdl' => 1, 'utz' => 1,
		'umj' => 1, 'unityweb' => 1, 'uoml' => 1, 'vcx' => 1, 'vsd' => 1, 'vst' => 1, 'vss' => 1, 'vsw' => 1, 'vis' => 1, 'vsf' => 1, 'wbxml' => 1, 'wmlc' => 1, 'wmlsc' => 1, 'wtb' => 1, 'nbp' => 1, 'wpd' => 1, 'wqd' => 1,
		'stf' => 1, 'xar' => 1, 'xfdl' => 1, 'hvd' => 1, 'hvs' => 1, 'hvp' => 1, 'osf' => 1, 'osfpvg' => 1, 'saf' => 1, 'spf' => 1, 'cmp' => 1, 'zir' => 1, 'zirz' => 1, 'zaz' => 1, 'vxml' => 1, 'wgt' => 1, 'hlp' => 1, 'wsdl' => 1,
		'wspolicy' => 1, '7z' => 1, 'abw' => 1, 'ace' => 1, 'dmg' => 1, 'aab' => 1, 'x32' => 1, 'u32' => 1, 'vox' => 1, 'aam' => 1, 'aas' => 1, 'bcpio' => 1, 'torrent' => 1, 'blb' => 1, 'blorb' => 1, 'bz' => 1, 'bz2' => 1,
		'boz' => 1, 'cbr' => 1, 'cba' => 1, 'cbt' => 1, 'cbz' => 1, 'cb7' => 1, 'vcd' => 1, 'cfs' => 1, 'chat' => 1, 'pgn' => 1, 'nsc' => 1, 'cpio' => 1, 'csh' => 1, 'deb' => 1, 'udeb' => 1, 'dgc' => 1, 'dir' => 1, 'dcr' => 1,
		'dxr' => 1, 'cst' => 1, 'cct' => 1, 'cxt' => 1, 'w3d' => 1, 'fgd' => 1, 'swa' => 1, 'wad' => 1, 'ncx' => 1, 'dtb' => 1, 'res' => 1, 'dvi' => 1, 'evy' => 1, 'eva' => 1, 'bdf' => 1, 'gsf' => 1, 'psf' => 1, 'otf' => 1,
		'pcf' => 1, 'snf' => 1, 'ttf' => 1, 'ttc' => 1, 'pfa' => 1, 'pfb' => 1, 'pfm' => 1, 'afm' => 1, 'woff' => 1, 'arc' => 1, 'spl' => 1, 'gca' => 1, 'ulx' => 1, 'gnumeric' => 1, 'gramps' => 1, 'gtar' => 1, 'hdf' => 1,
		'install' => 1, 'iso' => 1, 'jnlp' => 1, 'latex' => 1, 'lzh' => 1, 'lha' => 1, 'mie' => 1, 'prc' => 1, 'mobi' => 1, 'application' => 1, 'lnk' => 1, 'wmd' => 1, 'wmz' => 1, 'xbap' => 1, 'mdb' => 1, 'obd' => 1,
		'crd' => 1, 'clp' => 1, 'exe' => 1, 'dll' => 1, 'com' => 1, 'bat' => 1, 'msi' => 1, 'mvb' => 1, 'm13' => 1, 'm14' => 1, 'wmf' => 1, 'wmz' => 1, 'emf' => 1, 'emz' => 1, 'mny' => 1, 'pub' => 1, 'scd' => 1, 'trm' => 1,
		'wri' => 1, 'nc' => 1, 'cdf' => 1, 'nzb' => 1, 'p12' => 1, 'pfx' => 1, 'p7b' => 1, 'spc' => 1, 'p7r' => 1, 'rar' => 1, 'ris' => 1, 'sh' => 1, 'shar' => 1, 'swf' => 1, 'xap' => 1, 'sql' => 1, 'sit' => 1, 'sitx' => 1,
		'srt' => 1, 'sv4cpio' => 1, 'sv4crc' => 1, 't3' => 1, 'gam' => 1, 'tar' => 1, 'tcl' => 1, 'tex' => 1, 'tfm' => 1, 'texinfo' => 1, 'texi' => 1, 'obj' => 1, 'ustar' => 1, 'src' => 1, 'der' => 1, 'crt' => 1, 'fig' => 1,
		'xlf' => 1, 'xpi' => 1, 'xz' => 1, 'z1' => 1, 'z2' => 1, 'z3' => 1, 'z4' => 1, 'z5' => 1, 'z6' => 1, 'z7' => 1, 'z8' => 1, 'xaml' => 1, 'xdf' => 1, 'xenc' => 1, 'xhtml' => 1, 'xht' => 1, 'xml' => 1, 'xsl' => 1, 'dtd' => 1,
		'xop' => 1, 'xpl' => 1, 'xslt' => 1, 'xspf' => 1, 'mxml' => 1, 'xhvml' => 1, 'xvml' => 1, 'xvm' => 1, 'yang' => 1, 'yin' => 1, 'zip' => 1, 'adp' => 1, 'au' => 1, 'snd' => 1, 'mid' => 1, 'midi' => 1, 'kar' => 1, 'rmi' => 1,
		'mp4a' => 1, 'mpga' => 1, 'mp2' => 1, 'mp2a' => 1, 'mp3' => 1, 'm2a' => 1, 'm3a' => 1, 'oga' => 1, 'ogg' => 1, 'spx' => 1, 's3m' => 1, 'sil' => 1, 'uva' => 1, 'uvva' => 1, 'eol' => 1, 'dra' => 1, 'dts' => 1, 'dtshd' => 1,
		'lvp' => 1, 'pya' => 1, 'ecelp4800' => 1, 'ecelp7470' => 1, 'ecelp9600' => 1, 'rip' => 1, 'weba' => 1, 'aac' => 1, 'aif' => 1, 'aiff' => 1, 'aifc' => 1, 'caf' => 1, 'flac' => 1, 'mka' => 1, 'm3u' => 1, 'wax' => 1,
		'wma' => 1, 'ram' => 1, 'ra' => 1, 'rmp' => 1, 'wav' => 1, 'xm' => 1, 'cdx' => 1, 'cif' => 1, 'cmdf' => 1, 'cml' => 1, 'csml' => 1, 'xyz' => 1, 'bmp' => 1, 'cgm' => 1, 'g3' => 1, 'gif' => 1, 'ief' => 1, 'jpeg' => 1,
		'jpg' => 1, 'jpe' => 1, 'ktx' => 1, 'png' => 1, 'btif' => 1, 'sgi' => 1, 'svg' => 1, 'svgz' => 1, 'tiff' => 1, 'tif' => 1, 'psd' => 1, 'uvi' => 1, 'uvvi' => 1, 'uvg' => 1, 'uvvg' => 1, 'sub' => 1, 'djvu' => 1, 'djv' => 1,
		'dwg' => 1, 'dxf' => 1, 'fbs' => 1, 'fpx' => 1, 'fst' => 1, 'mmr' => 1, 'rlc' => 1, 'mdi' => 1, 'wdp' => 1, 'npx' => 1, 'wbmp' => 1, 'xif' => 1, 'webp' => 1, '3ds' => 1, 'ras' => 1, 'cmx' => 1, 'fh' => 1, 'fhc' => 1,
		'fh4' => 1, 'fh5' => 1, 'fh7' => 1, 'ico' => 1, 'sid' => 1, 'pcx' => 1, 'pic' => 1, 'pct' => 1, 'pnm' => 1, 'pbm' => 1, 'pgm' => 1, 'ppm' => 1, 'rgb' => 1, 'tga' => 1, 'xbm' => 1, 'xpm' => 1, 'xwd' => 1, 'eml' => 1,
		'mime' => 1, 'igs' => 1, 'iges' => 1, 'msh' => 1, 'mesh' => 1, 'silo' => 1, 'dae' => 1, 'dwf' => 1, 'gdl' => 1, 'gtw' => 1, 'mts' => 1, 'vtu' => 1, 'wrl' => 1, 'vrml' => 1, 'x3db' => 1, 'x3dbz' => 1, 'x3dv' => 1,
		'x3dvz' => 1, 'x3d' => 1, 'x3dz' => 1, '3gp' => 1, '3g2' => 1, 'h261' => 1, 'h263' => 1, 'h264' => 1, 'jpgv' => 1, 'jpm' => 1, 'jpgm' => 1, 'mj2' => 1, 'mjp2' => 1, 'mp4' => 1, 'mp4v' => 1, 'mpg4' => 1, 'mpeg' => 1,
		'mpg' => 1, 'mpe' => 1, 'm1v' => 1, 'm2v' => 1, 'ogv' => 1, 'qt' => 1, 'mov' => 1, 'uvh' => 1, 'uvvh' => 1, 'uvm' => 1, 'uvvm' => 1, 'uvp' => 1, 'uvvp' => 1, 'uvs' => 1, 'uvvs' => 1, 'uvv' => 1, 'uvvv' => 1, 'dvb' => 1,
		'fvt' => 1, 'mxu' => 1, 'm4u' => 1, 'pyv' => 1, 'uvu' => 1, 'uvvu' => 1, 'viv' => 1, 'webm' => 1, 'f4v' => 1, 'fli' => 1, 'flv' => 1, 'm4v' => 1, 'mkv' => 1, 'mk3d' => 1, 'mks' => 1, 'mng' => 1, 'asf' => 1, 'asx' => 1,
		'vob' => 1, 'wm' => 1, 'wmv' => 1, 'wmx' => 1, 'wvx' => 1, 'avi' => 1, 'movie' => 1, 'smv' => 1, 'ice' => 1,
	);

	/**
	 * @constructor
	 * @return void
	 */
	function __construct()
	{
		$this->_makeWhiteDomainList();

		include FileHandler::getRealPath($this->whiteUrlCacheFile);
		$this->whiteUrlList = $whiteUrlList;
		$this->whiteIframeUrlList = $whiteIframeUrlList;
	}

	/**
	 * Return EmbedFilter object
	 * This method for singleton
	 * @return EmbedFilter
	 */
	function getInstance()
	{
		if(!isset($GLOBALS['__EMBEDFILTER_INSTANCE__']))
		{
			$GLOBALS['__EMBEDFILTER_INSTANCE__'] = new EmbedFilter();
		}
		return $GLOBALS['__EMBEDFILTER_INSTANCE__'];
	}

	public function getWhiteUrlList()
	{
		return $this->whiteUrlList;
	}

	public function getWhiteIframeUrlList()
	{
		return $this->whiteIframeUrlList;
	}

	/**
	 * Check the content.
	 * @return void
	 */
	function check(&$content)
	{
		$content = preg_replace_callback('/<(object|param|embed)[^>]*/is', array($this, '_checkAllowScriptAccess'), $content);
		$content = preg_replace_callback('/<object[^>]*>/is', array($this, '_addAllowScriptAccess'), $content);

		$this->checkObjectTag($content);
		$this->checkEmbedTag($content);
		$this->checkIframeTag($content);
		$this->checkParamTag($content);
	}

	/**
	 * Check object tag in the content.
	 * @return void
	 */
	function checkObjectTag(&$content)
	{
		preg_match_all('/<\s*object\s*[^>]+(?:\/?>?)/is', $content, $m);
		$objectTagList = $m[0];
		if($objectTagList)
		{
			foreach($objectTagList AS $key => $objectTag)
			{
				$isWhiteDomain = true;
				$isWhiteMimetype = true;
				$isWhiteExt = true;
				$ext = '';

				$parser = new HtmlParser($objectTag);
				while($parser->parse())
				{
					if(is_array($parser->iNodeAttributes))
					{
						foreach($parser->iNodeAttributes AS $attrName => $attrValue)
						{
							// data url check
							if($attrValue && strtolower($attrName) == 'data')
							{
								$ext = strtolower(substr(strrchr($attrValue, "."), 1));
								$isWhiteDomain = $this->isWhiteDomain($attrValue);
							}

							// mime type check
							if(strtolower($attrName) == 'type' && $attrValue)
							{
								$isWhiteMimetype = $this->isWhiteMimetype($attrValue);
							}
						}
					}
				}

				if(!$isWhiteDomain || !$isWhiteMimetype)
				{
					$content = str_replace($objectTag, htmlspecialchars($objectTag, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), $content);
				}
			}
		}
	}

	/**
	 * Check embed tag in the content.
	 * @return void
	 */
	function checkEmbedTag(&$content)
	{
		preg_match_all('/<\s*embed\s*[^>]+(?:\/?>?)/is', $content, $m);
		$embedTagList = $m[0];
		if($embedTagList)
		{
			foreach($embedTagList AS $key => $embedTag)
			{
				$isWhiteDomain = TRUE;
				$isWhiteMimetype = TRUE;
				$isWhiteExt = TRUE;
				$ext = '';

				$parser = new HtmlParser($embedTag);
				while($parser->parse())
				{
					if(is_array($parser->iNodeAttributes))
					{
						foreach($parser->iNodeAttributes AS $attrName => $attrValue)
						{
							// src url check
							if($attrValue && strtolower($attrName) == 'src')
							{
								$ext = strtolower(substr(strrchr($attrValue, "."), 1));
								$isWhiteDomain = $this->isWhiteDomain($attrValue);
							}

							// mime type check
							if(strtolower($attrName) == 'type' && $attrValue)
							{
								$isWhiteMimetype = $this->isWhiteMimetype($attrValue);
							}
						}
					}
				}

				if(!$isWhiteDomain || !$isWhiteMimetype)
				{
					$content = str_replace($embedTag, htmlspecialchars($embedTag, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), $content);
				}
			}
		}
	}

	/**
	 * Check iframe tag in the content.
	 * @return void
	 */
	function checkIframeTag(&$content)
	{
		// check in Purifier class
		return;

		preg_match_all('/<\s*iframe\s*[^>]+(?:\/?>?)/is', $content, $m);
		$iframeTagList = $m[0];
		if($iframeTagList)
		{
			foreach($iframeTagList AS $key => $iframeTag)
			{
				$isWhiteDomain = TRUE;
				$ext = '';

				$parser = new HtmlParser($iframeTag);
				while($parser->parse())
				{
					if(is_array($parser->iNodeAttributes))
					{
						foreach($parser->iNodeAttributes AS $attrName => $attrValue)
						{
							// src url check
							if(strtolower($attrName) == 'src' && $attrValue)
							{
								$ext = strtolower(substr(strrchr($attrValue, "."), 1));
								$isWhiteDomain = $this->isWhiteIframeDomain($attrValue);
							}
						}
					}
				}

				if(!$isWhiteDomain)
				{
					$content = str_replace($iframeTag, htmlspecialchars($iframeTag, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), $content);
				}
			}
		}
	}

	/**
	 * Check param tag in the content.
	 * @return void
	 */
	function checkParamTag(&$content)
	{
		preg_match_all('/<\s*param\s*[^>]+(?:\/?>?)/is', $content, $m);
		$paramTagList = $m[0];
		if($paramTagList)
		{
			foreach($paramTagList AS $key => $paramTag)
			{
				$isWhiteDomain = TRUE;
				$isWhiteExt = TRUE;
				$ext = '';

				$parser = new HtmlParser($paramTag);
				while($parser->parse())
				{
					if($parser->iNodeAttributes['name'] && $parser->iNodeAttributes['value'])
					{
						$name = strtolower($parser->iNodeAttributes['name']);
						if($name == 'movie' || $name == 'src' || $name == 'href' || $name == 'url' || $name == 'source')
						{
							$ext = strtolower(substr(strrchr($parser->iNodeAttributes['value'], "."), 1));
							$isWhiteDomain = $this->isWhiteDomain($parser->iNodeAttributes['value']);

							if(!$isWhiteDomain)
							{
								$content = str_replace($paramTag, htmlspecialchars($paramTag, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), $content);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Check white domain in object data attribute or embed src attribute.
	 * @return string
	 */
	function isWhiteDomain($urlAttribute)
	{
		if(is_array($this->whiteUrlList))
		{
			foreach($this->whiteUrlList AS $key => $value)
			{
				if(preg_match('@^' . preg_quote($value) . '@i', $urlAttribute))
				{
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Check white domain in iframe src attribute.
	 * @return string
	 */
	function isWhiteIframeDomain($urlAttribute)
	{
		if(is_array($this->whiteIframeUrlList))
		{
			foreach($this->whiteIframeUrlList AS $key => $value)
			{
				if(preg_match('@^' . preg_quote($value) . '@i', $urlAttribute))
				{
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Check white mime type in object type attribute or embed type attribute.
	 * @return string
	 */
	function isWhiteMimetype($mimeType)
	{
		if(isset($this->mimeTypeList[$mimeType]))
		{
			return TRUE;
		}
		return FALSE;
	}

	function isWhiteExt($ext)
	{
		if(isset($this->extList[$ext]))
		{
			return TRUE;
		}
		return FALSE;
	}

	function _checkAllowScriptAccess($m)
	{
		if($m[1] == 'object')
		{
			$this->allowscriptaccessList[] = 1;
		}

		if($m[1] == 'param')
		{
			if(stripos($m[0], 'allowscriptaccess'))
			{
				$m[0] = '<param name="allowscriptaccess" value="never"';
				if(substr($m[0], -1) == '/')
				{
					$m[0] .= '/';
				}
				$this->allowscriptaccessList[count($this->allowscriptaccessList) - 1]--;
			}
		}
		else if($m[1] == 'embed')
		{
			if(stripos($m[0], 'allowscriptaccess'))
			{
				$m[0] = preg_replace('/always|samedomain/i', 'never', $m[0]);
			}
			else
			{
				$m[0] = preg_replace('/\<embed/i', '<embed allowscriptaccess="never"', $m[0]);
			}
		}
		return $m[0];
	}

	function _addAllowScriptAccess($m)
	{
		if($this->allowscriptaccessList[$this->allowscriptaccessKey] == 1)
		{
			$m[0] = $m[0] . '<param name="allowscriptaccess" value="never"></param>';
		}
		$this->allowscriptaccessKey++;
		return $m[0];
	}

	/**
	 * Make white domain list cache file from xml config file.
	 * @param $whitelist array
	 * @return void
	 */
	function _makeWhiteDomainList($whitelist = NULL)
	{
		$whiteUrlXmlFile = FileHandler::getRealPath($this->whiteUrlXmlFile);
		$whiteUrlCacheFile = FileHandler::getRealPath($this->whiteUrlCacheFile);

		$isMake = FALSE;
		if(!file_exists($whiteUrlCacheFile))
		{
			$isMake = TRUE;
		}
		if(file_exists($whiteUrlCacheFile) && filemtime($whiteUrlCacheFile) < filemtime($whiteUrlXmlFile))
		{
			$isMake = TRUE;
		}

		if(gettype($whitelist) == 'array' && gettype($whitelist['object']) == 'array' && gettype($whitelist['iframe']) == 'array')
		{
			$isMake = FALSE;
		}

		if(isset($whitelist) && gettype($whitelist) == 'object')
		{
			$isMake = TRUE;
		}

		if($isMake)
		{
			$whiteUrlList = array();
			$whiteIframeUrlList = array();

			if(gettype($whitelist->object) == 'array' && gettype($whitelist->iframe) == 'array')
			{
				$whiteUrlList = $whitelist->object;
				$whiteIframeUrlList = $whitelist->iframe;
			}
			else
			{
				$xmlBuff = FileHandler::readFile($this->whiteUrlXmlFile);

				$xmlParser = new XmlParser();
				$domainListObj = $xmlParser->parse($xmlBuff);
				$embedDomainList = $domainListObj->whiteurl->embed->domain;
				$iframeDomainList = $domainListObj->whiteurl->iframe->domain;
				if(!is_array($embedDomainList)) $embedDomainList = array();
				if(!is_array($iframeDomainList)) $iframeDomainList = array();

				foreach($embedDomainList AS $key => $value)
				{
					$patternList = $value->pattern;
					if(is_array($patternList))
					{
						foreach($patternList AS $key => $value)
						{
							$whiteUrlList[] = $value->body;
						}
					}
					else
					{
						$whiteUrlList[] = $patternList->body;
					}
				}

				foreach($iframeDomainList AS $key => $value)
				{
					$patternList = $value->pattern;
					if(is_array($patternList))
					{
						foreach($patternList AS $key => $value)
						{
							$whiteIframeUrlList[] = $value->body;
						}
					}
					else
					{
						$whiteIframeUrlList[] = $patternList->body;
					}
				}
			}

			$db_info = Context::getDBInfo();

			if($db_info->embed_white_object)
			{
				$whiteUrlList = array_merge($whiteUrlList, $db_info->embed_white_object);
			}

			if($db_info->embed_white_iframe)
			{
				$whiteIframeUrlList = array_merge($whiteIframeUrlList, $db_info->embed_white_iframe);
			}

			$whiteUrlList = array_unique($whiteUrlList);
			$whiteIframeUrlList = array_unique($whiteIframeUrlList);
			asort($whiteUrlList);
			asort($whiteIframeUrlList);

			$buff = array();
			$buff[] = '<?php if(!defined("__XE__")) exit();';
			$buff[] = '$whiteUrlList = ' . var_export($whiteUrlList, TRUE) . ';';
			$buff[] = '$whiteIframeUrlList = ' . var_export($whiteIframeUrlList, TRUE) . ';';

			FileHandler::writeFile($this->whiteUrlCacheFile, implode(PHP_EOL, $buff));
		}
	}

}
/* End of file : EmbedFilter.class.php */
/* Location: ./classes/security/EmbedFilter.class.php */
