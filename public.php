<?php
$Default = "";

$oHost = "google.com";
$pHost = "g.ppx.pw";
$hosts = array(
	"www." . $oHost => "www." . $pHost,
	"ipv6." . $oHost => "ipv6." . $pHost,
	"ipv4." . $oHost => "ipv4." . $pHost,
	"id." . $oHost => "id." . $pHost,
	"scholar" . $oHost => "scholar" . $pHost
);
$hostsFlip = array_flip($hosts);
$staticResSvr = "https://static.g.ppx.pw";

function hostReplace($body){
	global $host, $pHost, $oHost, $hosts, $hostsFlip, $staticResSvr;
	$result = str_ireplace(array_keys($hosts), array_keys($hostsFlip), $body);
	$result = preg_replace("((https?:)?//(?<site>[^/]+)[.]gstatic\.com)", $staticResSvr . "/__gs:$2__", $result);// //)? not perfect // (//)? cost unbearable time
 	$result = str_ireplace("www.gstatic.com", $staticResSvr . "/__gs:www__", $result);//Index page of image search needs it.
	$result = preg_replace("((https?:)?//apis\.google\.com)", $staticResSvr . "/__gapis__", $result);// gapi
	switch($host){
		case "www." . $oHost:
			if(stripos($url, "search") !== false) $result = preg_replace('<div data-jibp="h" data-jiis="uc" id="bfoot">.*?</div>', '', $result);
		break;
		default:
	}
	return $result;
}
function isNeedHostReplace($rawContentType){
	$contentType = strtolower($rawContentType);
	if(stripos($contentType, "application/javascript") !== false or stripos($contentType, "text/html") !== false or stripos($contentType, "text/css") !== false) return true;
	else return false;
}
?>