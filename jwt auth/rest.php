<?php
require_once('constants.php');
class Rest{
//when user click if first come constructor then other method one by one.
protected $request;
protected $serviceName;
protected $parm;
protected $dbConn;
//protected $userId;
public function __construct()
{
  //read the raw data;
  //$handler=fopen('php://input','r');
  //check method

  if($_SERVER['REQUEST_METHOD']!=='POST')
  {
  //  echo'not valid';
    $this->throwError(REQUEST_METHOD_NOT_VALID,'Request method is not valid');
  }
   //echo $request=stream_get_contents($handler);


 $file = fopen('php://input', 'r');
	$this->request = stream_get_contents($file);
  $this->validateRequest($this->request);
  $db = new DbConnect;
			$this->dbConn = $db->connect();
      if( 'ahsan' != strtolower( $this->serviceName) ) {
				$this->validateToken();
			}


}
//validate request
public function validateRequest($request)
{
  //application typle not like application/json
if($_SERVER['CONTENT_TYPE']!=='application/json')
{
  $this->throwError(REQUEST_CONTENTTYPE_NOT_VALID,'Request content type is not valid');
}
//array moto dekhabe
$data=json_decode($request,true);
if(!isset($data['name']) || $data['name']=="")
{
  $this->throwError(API_NAME_REQUIRED,'Api name required');
}
$this->serviceName=$data['name'];
if(!is_array($data['param']))
{
  $this->throwError(API_PARAM_REQUIRED,'Api param required');
}
$this->param=$data['param'];

}
//process or excute API
public function processApi()
{
try {
				$api = new Api;
				$rMethod = new reflectionMethod('Api', $this->serviceName);
				if(!method_exists($api, $this->serviceName)) {
					$this->throwError(API_DOST_NOT_EXIST, "API does not exist.");
				}
				$rMethod->invoke($api);
			} catch (Exception $e) {
				$this->throwError(API_DOST_NOT_EXIST, "APi does not exist.");
			}


}
//validate parameter or VALIDATE_PARAMETER_DATATYPE
public function validateParameter($fieldName,$value,$datatype,$required=true)

{
  if($required==true && empty($value)==true )
  {
    $this->throwError(VALIDATE_PARAMETER_REQUIRED,$fieldName."VALIDATE_PARAMETER_REQUIRED");
  }
    switch ($datatype) {
    case BOOLEAN:
      if(!is_bool($value))
      {
        $this->throwError(VALIDATE_PARAMETER_REQUIRED,$fieldName."should be BOOLEAN");
      }
      break;
    case INTEGER:
      if(!is_numeric($value))
      {
        $this->throwError(VALIDATE_PARAMETER_REQUIRED,$fieldName."should be INTEGER");
      }
        break;
        case STRING:
        if(!is_string($value))
        {
          $this->throwError(VALIDATE_PARAMETER_REQUIRED,$fieldName."should be string");
        }
          break;

      default:
        // code...
        break;
    }

  return $value;

}

		/**
	    * Get hearder Authorization
	    * */
 public function getAuthorizationHeader(){
	        $headers = null;
	        if (isset($_SERVER['Authorization'])) {
	            $headers = trim($_SERVER["Authorization"]);
	        }
	        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
	            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
	        } elseif (function_exists('apache_request_headers')) {
	            $requestHeaders = apache_request_headers();
	            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
	            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
	            if (isset($requestHeaders['Authorization'])) {
	                $headers = trim($requestHeaders['Authorization']);
	            }
	        }
	        return $headers;
	    }

      /**
	     * get access token from header
	     * */
 public function getBearerToken() {
	        $headers = $this->getAuthorizationHeader();
	        // HEADER: Get the access token from the header
	        if (!empty($headers)) {
	            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
	                return $matches[1];
	            }
	        }
	        $this->throwError( ATHORIZATION_HEADER_NOT_FOUND, 'Access Token Not found');
	    }
  public function validateToken() {
  try {
    $token = $this->getBearerToken();
    $payload = JWT::decode($token, SECRETE_KEY, ['HS256']);

    $stmt = $this->dbConn->prepare("SELECT * FROM user WHERE id = :userId");
    $stmt->bindParam(":userId", $payload->userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!is_array($user)) {
      $this->returnResponse(INVALID_USER_PASS, "This user is not found in our database.");
    }

    if( $user['active'] == 0 ) {
      $this->returnResponse(USER_NOT_ACTIVE, "This user may be decactived. Please contact to admin.");
    }
    $this->userId = $payload->userId;
  } catch (Exception $e) {
    $this->throwError(ACCESS_TOKEN_ERRORS, $e->getMessage());
  }
}

public function throwError($code,$message)
{
  header("content-type: application/json");
  $errormsg=json_encode(['status'=>$code,'message'=>$message]);
  echo $errormsg;
  exit;

}
//after sucessful excution throw response
public function returnResponse($code,$data)
{
  header("content-type: application/json");
  $response=json_encode(['response'=>['status'=>$code,$data]]);
  echo $response;
  exit;

}


}

 ?>
