<?php
// for dynamic proxy
if(!ob_start("ob_gzhandler")) ob_start();
require ("public.php");
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") $scheme = 'https';
else $scheme = 'http';
$procotolHeader = $scheme . "://";
$host = (isset($hostsFlip[$_SERVER['HTTP_HOST']]) ? $hostsFlip[$_SERVER['HTTP_HOST']] : $oHost);

//this part are partly modified from https://github.com/joshdick/miniProxy/blob/master/miniProxy.php
function makeRequest($url){
	global $host, $pHost, $oHost, $hosts, $hostsFlip, $staticResSvr;
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	$user_agent = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "Mozilla/5.0 (compatible; Gowe Mirror Image Server)");

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	$browserRequestHeaders = getallheaders();
	$browserRequestHeaders['Host'] = $host;
	unset($browserRequestHeaders['Content-Length']);//?
	unset($browserRequestHeaders['Accept-Encoding']);
	curl_setopt($ch, CURLOPT_ENCODING, "");
	$curlRequestHeaders = array();

	switch($host){
		case "www." . $oHost:
			if(!isset($_COOKIE['PREF'])){
				$LD = explode(",", explode(";", $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0])[0];
				if (strlen($LD) === 0) $LD = "zh-TW";//Default Language
				$browserRequestHeaders['Cookie'] .= "PREF=ID=1111111111111111:FF=0:CR=2:SG=1:V=1:LD=" . $LD;
			}
		break;//preset cookie in case to avoid country redirect
		default:
	}

	//Anti-abusement,record and deliver Client IP to Google:
	$browserRequestHeaders['X-Real-IP'] = (isset($browserRequestHeaders['X-Real-IP']) ? $browserRequestHeaders['X-Real-IP'] : $_SERVER['REMOTE_ADDR']);
	if(isset($browserRequestHeaders['X-Forwarded-For'])) $browserRequestHeaders['X-Forwarded-For'] .= ", " . $_SERVER['REMOTE_ADDR'];
	else $browserRequestHeaders['X-Forwarded-For'] = $_SERVER['REMOTE_ADDR'];
	foreach ($browserRequestHeaders as $name => $value) {
		$curlRequestHeaders[] = $name . ": " . $value;
	}
	$curlRequestHeaders = str_ireplace($pHost, $oHost, $curlRequestHeaders);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $curlRequestHeaders);

	switch ($_SERVER['REQUEST_METHOD']){
		case "POST":
			curl_setopt($ch, CURLOPT_POST, true);
			//For some reason, $HTTP_RAW_POST_DATA isn't working as documented at
			//http://php.net/manual/en/reserved.variables.httprawpostdata.php
			//but the php://input method works. This is likely to be flaky
			//across different server environments.
			//More info here: http://stackoverflow.com/questions/8899239/http-raw-post-data-not-being-populated-after-upgrade-to-php-5-3
			curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
		break;
		case "PUT":
			curl_setopt($ch, CURLOPT_PUT, true);
			curl_setopt($ch, CURLOPT_INFILE, fopen("php://input"));
		break;
	}

	//Other cURL options.
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); //allow 302/301 instead of following it directly
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	//Set the request URL.//
	curl_setopt($ch, CURLOPT_URL, $url);

	//Make the request.
	$response = curl_exec($ch);
	$responseInfo = curl_getinfo($ch);
	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	curl_close($ch);

	//Setting CURLOPT_HEADER to true above forces the response headers and body
	//to be output together--separate them.
	$responseHeaders = substr($response, 0, $headerSize);
	$responseBody = substr($response, $headerSize);

	return array("headers" => $responseHeaders, "body" => $responseBody, "responseInfo" => $responseInfo);
}


$requestUrl = $procotolHeader . $host . $_SERVER["REQUEST_URI"];
$response = makeRequest($requestUrl);
$response = str_ireplace(array_keys($hosts), array_keys($hostsFlip), $response);
$rawResponseHeaders = $response["headers"];
$responseBody = $response["body"];
$responseInfo = $response["responseInfo"];
//IMP:$rawResponseHeaders = str_ireplace("domain=." . $oHost, "domain=." . $pHost, $rawResponseHeaders);//cookie
$rawResponseHeaders = str_ireplace($oHost, $pHost, $rawResponseHeaders);

//cURL can make multiple requests internally (while following 302 redirects), and reports
//headers for every request it makes. Only proxy the last set of received response headers,
//corresponding to the final request made by cURL for any given call to makeRequest().
$responseHeaderBlocks = array_filter(explode("\r\n\r\n", $rawResponseHeaders));
$lastHeaderBlock = end($responseHeaderBlocks);
//IMP:$headerLines = explode("\r\n", $rawResponseHeaders);
$headerLines = explode("\r\n", $lastHeaderBlock);
$resetedContent_Length = false;
foreach ($headerLines as $header) {
	if(stripos($header, "Content-Length") !== false){
		$resetedContent_Length = true;
		continue;
	}
	if(stripos($header, "Transfer-Encoding") === false && stripos($header, "Content-Encoding") === false) { //curl has decoded content (e.g. gzip;so avoid it)
		/*if(stripos($header, "Set-Cookie") === false) {
			header($header, true);
		}
		else header($header, false);*/
		header($header, true);//allow header one more times
	}
}
if(isNeedHostReplace($responseInfo["content_type"])){
	$body = hostReplace($responseBody);
}
else {
	$body = $responseBody;
}

if(isset($resetedContent_Length) and $resetedContent_Length = true) header("Content-Length: " . strlen($body));
echo($body);
ob_end_flush();