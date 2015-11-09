<?php
$Default = "";

//The 2 variables below just make it easier to set $hosts, they should not be used in other scripts
$oHost = "google.com";
$pHost = "g.ppx.pw";


$defaultHost = "www.google.com";
$hosts = array(
	"domain=" . $oHost => "domain=" . $pHost,//google.com cookie
	"domain=." . $oHost => "domain=." . $pHost,//*.google.com cookie
	"www." . $oHost => "www." . $pHost,
	"ipv6." . $oHost => "ipv6." . $pHost,
	"ipv4." . $oHost => "ipv4." . $pHost,
	"id." . $oHost => "id." . $pHost,
	"scholar." . $oHost => "scholar." . $pHost,
	"apis." . $oHost => "apis." . $pHost,
	"(([a-z0-9\-]+)\.gstatic\.com)" => "gs-$1." . $pHost, //the re in php should have "()"" or '//''
	//"(([a-z0-9\-]+)\.google\.com)" => "$1." . $pHost,
);
$hostsFlip = array(
	"domain:" . $pHost => "domain:" . $oHost,
	"domain:." . $pHost => "domain:." . $oHost,
	"www." . $pHost => "www." . $oHost,
	"ipv6." . $pHost => "ipv6." . $oHost,
	"ipv4." . $pHost => "ipv4." . $oHost,
	"id." . $pHost => "id." . $oHost,
	"scholar." . $pHost => "scholar." . $oHost,
	"apis." . $pHost => "apis." . $oHost,
	"(gs\-([a-z0-9\-]+)\." . preg_quote($pHost) . ")" => "$1.gstatic.com",
	//"(([a-z0-9\-]+)\." . preg_quote($pHost) . ")" => "$1.google.com",
);
//var_dump($hosts, $hostsFlip);
/*just add domain here like the added to make it support more subdomain of google
such as 
"news." . $oHost => "news." . $pHost,
for Google News
and you can add more domains not restricted in Google then it will perform as
a big mirror system
*/


//$secureKey = "a nice cat"; //for hash

function hostReplace($hosts, $subject){
	$retval = $subject;
	foreach($hosts as $key => $value){
		if(substr($key, 0, 1) === "("){ //if regex
			$retval = preg_replace($key, $value, $retval);
		}
		else $retval = str_ireplace($key, $value, $retval);
	}
	return $retval;
}
function doesNeedHostReplace($rawContentType){
	$contentType = strtolower($rawContentType);	
	function startsWith($haystack, $needle) {
	    // search backwards starting from haystack length characters from the end
	    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}//https://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php/10473026#10473026
	if(startsWith($contentType, "text/html") or startsWith($contentType, "text/javascript") or startsWith($contentType, "text/css") or startsWith($contentType, "application/javascript")) return true;
	else return false;//http://www.w3.org/Protocols/rfc1341/4_Content-Type.html
}

function matchHost($currentHost, $hosts){
	if(isset($hosts[$currentHost])){
		//plain text
		return $hosts[$currentHost];
	}
	else{
		foreach($hosts as $key => $value){
			if(substr($key, 0, 1) === "("){//if regex
				$host = preg_replace($key, $value, $currentHost, 1, $count);
				if($count !== 0) return $host;
			}
		}
		return false;
	}
}


?>