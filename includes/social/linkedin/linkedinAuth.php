<?php
error_log("In linked in auth.php");
session_name('linkedin');
session_start();
$api_key='';
$api_secret='';
$plugin_url='';
if(isset($_REQUEST['k'])){
	$_SESSION['liApiKey']=$api_key=$_REQUEST['k'];
	$_SESSION['liApiSecret']=$api_secret=$_REQUEST['s'];
	$_SESSION['plugin_url']=$plugin_url=$_REQUEST['plugin_url'].'userpro/lib/linkedin-auth/linkedinAuth.php';
}
else {
	$api_key=$_SESSION['liApiKey'];
	$api_secret=$_SESSION['liApiSecret'];
	$plugin_url=$_SESSION['plugin_url'];
	
}

define('API_KEY',     $api_key                                         );
define('API_SECRET',  $api_secret                                      );
define('REDIRECT_URI', ''.$plugin_url);
define('SCOPE',        'r_basicprofile r_emailaddress'                 );
 
 
// OAuth 2 Control Flow
if (isset($_GET['error'])) {
    // LinkedIn returned an error
    print $_GET['error'] . ': ' . $_GET['error_description'];
    exit;
} elseif (isset($_GET['code'])) {
    // User authorized your application
    if ($_SESSION['state'] == $_GET['state']) {
        // Get token so you can make API calls
        getAccessToken();
    } else {
        // CSRF attack? Or did you mix up your states?
        exit;
    }
} else {
	getAuthorizationCode();
}
 

 
function getAuthorizationCode() {
    $params = array('response_type' => 'code',
                    'client_id' => API_KEY,
                    'scope' => SCOPE,
                    'state' => uniqid('', true), // unique long string
                    'redirect_uri' => REDIRECT_URI,
              );
 
    // Authentication request
    $url = 'https://www.linkedin.com/uas/oauth2/authorization?' . http_build_query($params);
     
    // Needed to identify request when it returns to us
    $_SESSION['state'] = $params['state'];
// 	error_log('url = '.$url);
    // Redirect user to authenticate
    header("Location: $url");
    exit;
}
     
function getAccessToken() {
    $params = array('grant_type' => 'authorization_code',
                    'client_id' => API_KEY,
                    'client_secret' => API_SECRET,
                    'code' => $_GET['code'],
                    'redirect_uri' => REDIRECT_URI,
              );
     
    // Access Token request
    $url = 'https://www.linkedin.com/uas/oauth2/accessToken?' . http_build_query($params);
    error_log('url1 = '.$url);
    // Tell streams to make a POST request
    $context = stream_context_create(
                    array('http' =>
                        array('method' => 'POST',
                        )
                    )
                );
 
    // Retrieve access token information
    $response = file_get_contents($url, false, $context);
 
    // Native PHP object, please
    $token = json_decode($response);
 
    // Store access token and expiration time
    $_SESSION['access_token'] = $token->access_token; // guard this!
    $_SESSION['expires_in']   = $token->expires_in; // relative time (in seconds)
    $_SESSION['expires_at']   = time() + $_SESSION['expires_in']; // absolute time
     
    return true;
}
 
function fetch($method, $resource, $body = '') {
    $params = array('oauth2_access_token' => $_SESSION['access_token'],
                    'format' => 'json',
              );
     
    // Need to use HTTPS
    $url = 'https://api.linkedin.com' . $resource . '?' . http_build_query($params);
    // Tell streams to make a (GET, POST, PUT, or DELETE) request
    $context = stream_context_create(
                    array('http' =>
                        array('method' => $method,
                        )
                    )
                );
 
 
    // Hocus Pocus
    $response = file_get_contents($url, false, $context);
 
    // Native PHP object, please
    error_log(print_r($response,true));
    return json_decode($response);
    
}

// Congratulations! You have a valid token. Now fetch your profile
$user = fetch('GET', '/v1/people/~:(firstName,lastName,emailAddress,id)');
//print "Hello123 $user->firstName $user->lastName <br> $user->emailAddress<br> $user->id";

?>
<html>
	<head>
		<script>
			function assignData(){
		     	window.opener.wpl_lUserName='<?php echo $user->firstName.' '.$user->lastName;?>';
				window.opener.wpl_lUserId='<?php echo 'li_'.$user->id;?>';
				window.opener.wpl_lUserEmail='<?php echo $user->emailAddress;?>';
				window.opener.wpl_set_linkedin_data();
				window.opener.wpl_linkedin_auth_window.close();
			}
		</script>
	</head>
	<body onload="assignData();">
		
	</body>
</html>
