<?php
//for static res
if(!ob_start("ob_gzhandler")) ob_start();
require ("public.php");

function parseHeaders($headers){
	//by MangaII on http://us2.php.net/manual/en/reserved.variables.httpresponseheader.php
		$head = array();
		foreach( $headers as $k=>$v )
		{
				$t = explode( ':', $v, 2 );
				if( isset( $t[1] ) )
						$head[ trim($t[0]) ] = trim( $t[1] );
				else
				{
						$head[] = $v;
						if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
								$head['reponse_code'] = intval($out[1]);
				}
		}
		return $head;
}
function pageErr($url, $errType="Unknown") {
	function toURL($url) {
	  if (substr($url, 0,5) !== "http:" and substr($url, 0,6) !== "https:") {
	    return ("//".$url);
	  }
	  else return ($url);
	}
	global $pHost;
	header('HTTP/1.1 404 Not Found');
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head>
			<title>Invaild</title>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
			<style type="text/css">
					body {font-size:10px; color:#777777; font-family:arial; text-align:center;}
					h1 {font-size:64px; color:#555555; margin: 70px 0 50px 0;}
					p {width:320px; text-align:center; margin-left:auto;margin-right:auto; margin-top: 30px }
					div {width:320px; text-align:center; margin-left:auto;margin-right:auto;}
					a:link {color: #34536A;}
					a:visited {color: #34536A;}
					a:active {color: #34536A;}
					a:hover {color: #34536A;}
			</style>
	</head>

	<body>
			<h1>Invaild</h1>
			<div>(Error: ' . $errType . ')</div>
			<p><div>The resource requested: <U><I><a href="' . toURL($url) . '">' . $url . '</a></I></U> cannot be fetched.</div>
			</p>
			<div>
					<a href="//' . $pHost . '">Powered by Gowe.</a>
			</div>
	</body>

	</html>';
	exit;
}


$uri = substr($_SERVER["REQUEST_URI"], 1);
if (preg_match("(__gs\:([^\s]+?)__)", $uri)) $type = 1;// "gs":must be lower
elseif (strpos($uri, "__gapis__") !== false) $type = 2;
else pageErr($uri, "Illegal URL");

switch ($type) {
	case 1:
		$url = preg_replace("(__gs\:([^\s]+?)__)", "https://$1.gstatic.com", $uri);
	break;
	case 2:
		$url = str_ireplace("__gapis__", "https://apis.google.com", $uri);
	break;
	default:
		pageErr($uri, "Inner Error 1");
}

$handle = fopen($url, "rb");
if ($handle === false) {
	pageErr($resourceURL, "Failed to Open");
}

$headers = /*(strlen($http_response_header) === 1024 ? get_headers($resourceURL,1) : */parseHeaders($http_response_header)/*)*/;
//$http_response_header has 1024 characters limit according to nicolas at toniazzi dot net on http://us2.php.net/manual/en/reserved.variables.httpresponseheader.php
if(isset($headers['Content-Type'])) header ('Content-type: ' . $headers['Content-Type']);
if(isset($headers['Content-Disposition'])) header ('Content-Disposition: ' . $headers['Content-Disposition']);


if(!isNeedHostReplace($responseInfo["content_type"])){
	do{//for big files in view of memory problems
		$data = fread($handle, 4096); 
		echo ($data);
		if (strlen($data) == 0){
		break;
		}
	}while(true);
	fclose ($handle);
}
else { // for small files -> replace URL;e.g. google.com; in view of keywords' break off
	$body = "";
	do{
		$data = fread($handle, 4096); 
		$body .= $data;
		if (strlen($data) == 0) {
			break;
		}
	}while(true);
	fclose($handle);
	$body = hostReplace($body);
	if(isset($headers['Content-Length'])) header ('Content-Length: ' . ((String) strlen($body)));
	echo($body);
}

?>