<?php
if(!ob_start("ob_gzhandler")) ob_start();
require ("public.php");
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") $scheme = 'https';
else $scheme = 'http';
$procotolHeader = $scheme . "://";
$host = matchHost($_SERVER['HTTP_HOST'], $hostsFlip);
$host = ($host ? $host : $defaultHost);

//this part are partly modified from https://github.com/joshdick/miniProxy/blob/master/miniProxy.php
function makeRequest($url){
	function getRawPostData(){
		$eol = "\r\n";
		//e.g. multipart/form-data; boundary=----WebKitFormBoundarymBorAQvghrThkwiU
		$BOUNDARY = substr(ltrim(explode(";", $_SERVER["CONTENT_TYPE"])[1]), strlen("boundary="));//"----GoweWebBoundary" . substr(base64_encode(substr(md5(time()), rand(0, 19), 12)), 0, -1);
		$postData = "";
		foreach($_POST as $name => $value){
		    $postData .= "--" . $BOUNDARY . $eol;
		    $postData .= 'Content-Disposition: form-data; name="' . $name . '"' . $eol . $eol;
		    $postData .= $value . $eol;
		}
		foreach($_FILES as $name => $info){
		    if(is_array($info['tmp_name'])){//name[]
		        foreach($info['tmp_name'] as $index => $tmpName)
		        {
		            if(!empty($info['error'][$index]) and $info['error'][$index] !== UPLOAD_ERR_NO_FILE/*4*/)
		            {
		                continue;
		            }
		            if(empty($tmpName) or is_uploaded_file($tmpName)){
		                $postData .= "--" . $BOUNDARY . $eol;
		                $postData .= 'Content-Disposition: form-data; name="' . $name . '[]"; filename="' . $info['name'][$index] . '"' . $eol ;
		                $postData .= "Content-Type: application/octet-stream" . $eol . $eol;
		                $postData .= (empty($tmpName) ? "" : file_get_contents($tmpName)) . $eol;
		            }
		        }

		    }
		    else{
		            if(empty($info['tmp_name']) or is_uploaded_file($info['tmp_name'])){
		                $postData .= "--" . $BOUNDARY . $eol;
		                $postData .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $info['name'] . '"' . $eol ;
		                $postData .= (empty($info['tmp_name']) ? "" : "Content-Type: " . (empty($info['type']) ? "application/octet-stream" : $info['type'])) . $eol . $eol;
						$postData .= (empty($info['tmp_name']) ? "" : file_get_contents($info['tmp_name'])) . $eol;
		            }
		    }
		}
		$postData .= '--' . $BOUNDARY  . '--' . $eol . $eol;
		return $postData;
	}
	global $host, $hosts, $hostsFlip;
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	$user_agent = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "Mozilla/5.0 (compatible; Gowe Mirror Image Server)");

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	$browserRequestHeaders = getallheaders();
	$browserRequestHeaders['Host'] = $host;//not necessary. hostReplace will be completed below
	unset($browserRequestHeaders['Content-Length']);//?
	unset($browserRequestHeaders['Accept-Encoding']);
	curl_setopt($ch, CURLOPT_ENCODING, "");
	$curlRequestHeaders = array();

	//for google only
	switch($host){
		case "google.com":
		case "www.google.com":
			if(!isset($_COOKIE['PREF'])){
				$LD = explode(",", explode(";", $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0])[0];
				if (strlen($LD) === 0) $LD = "zh-TW";//Default Language
				$browserRequestHeaders['Cookie'] .= "PREF=ID=1111111111111111:FF=0:CR=2:SG=1:V=1:LD=" . $LD . ";";
			}
		break;//preset cookie in case to avoid country redirect
		default:
	}

	//Anti-abusement,record and deliver client IP to original server:
	$browserRequestHeaders['X-Real-IP'] = (isset($browserRequestHeaders['X-Real-IP']) ? $browserRequestHeaders['X-Real-IP'] : $_SERVER['REMOTE_ADDR']);
	if(isset($browserRequestHeaders['X-Forwarded-For'])){
		$X_Forwarded_For = str_replace(" ", "", explode(",", $browserRequestHeaders['X-Forwarded-For']));
		if(count($X_Forwarded_For) >= 1 and $X_Forwarded_For[count($X_Forwarded_For)-1] !== $_SERVER['REMOTE_ADDR']){
			$browserRequestHeaders['X-Forwarded-For'] .= ", " . $_SERVER['REMOTE_ADDR'];
		}
	}
	else $browserRequestHeaders['X-Forwarded-For'] = $_SERVER['REMOTE_ADDR'];

	foreach ($browserRequestHeaders as $name => $value) {
		$curlRequestHeaders[] = $name . ": " . $value;
	}

	$curlRequestHeaders = hostReplace($hostsFlip, $curlRequestHeaders);

	curl_setopt($ch, CURLOPT_HTTPHEADER, $curlRequestHeaders);

	switch ($_SERVER['REQUEST_METHOD']){
		case "POST":
			curl_setopt($ch, CURLOPT_POST, true);

			$postData = file_get_contents("php://input");
			//"php://input" is invaild for Content-Type: multipart/form-data unless enable-post-data-reading in php.ini is off (PHP 5.4+)
			if(empty($postData)){ 
				$postData = getRawPostData();
			}
			//For some reason, $HTTP_RAW_POST_DATA isn't working as documented at
			//http://php.net/manual/en/reserved.variables.httprawpostdata.php
			//but the php://input method works. This is likely to be flaky
			//across different server environments.
			//More info here: http://stackoverflow.com/questions/8899239/http-raw-post-data-not-being-populated-after-upgrade-to-php-5-3
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

		break;
		case "PUT":
			curl_setopt($ch, CURLOPT_PUT, true);
			curl_setopt($ch, CURLOPT_INFILE, fopen("php://input", 'r'));
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

	$responseHeaders = hostReplace($hosts, $responseHeaders);
	
	$responseBody = substr($response, $headerSize);

	return array("headers" => $responseHeaders, "body" => $responseBody, "responseInfo" => $responseInfo);
}


$requestUrl = $procotolHeader . $host . $_SERVER['REQUEST_URI'];
$response = makeRequest($requestUrl);
//$response = str_ireplace(array_keys($hosts), array_keys($hostsFlip), $response); //now it will be comleted in function->makeRequest
$rawResponseHeaders = $response['headers'];
$responseBody = $response['body'];
$responseInfo = $response['responseInfo'];

//$rawResponseHeaders = str_ireplace($oHost, $pHost, $rawResponseHeaders);//now it will be comleted in function->makeRequest

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
		header($header, false);//allow set header one more times
	}
}

if(doesNeedHostReplace($responseInfo['content_type'])){
	$body = hostReplace($hosts, $responseBody);
}
else {
	$body = $responseBody;
}

if(isset($resetedContent_Length) and $resetedContent_Length = true) header("Content-Length: " . strlen($body));
echo($body);
ob_end_flush();