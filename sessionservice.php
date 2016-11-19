<?php
    
/*
* Esker Web Service client for use with a nuSOAP PHP library. 
*/
class SoapClientDebug extends SoapClient
{
  public function __doRequest($request, $location, $action, $version, $one_way = 0) {
      // Add code to inspect/dissect/debug/adjust the XML given in $request here
      var_dump($request);
      // Uncomment the following line, if you actually want to do the request
      return parent::__doRequest($request, $location, $action, $version, $one_way);
  }
}
/*
* 
* ODSession_SessionService
* 
* @version $Id: sessionservice.php,v 1.0 2005/12/12 20:37:19 
* @access public
*/
class ODSession_SessionService  {

	var $client;
	var $result;
	var $soapException;
	var $Url;
	var $SessionHeaderValue;
    
	/*
	* constructor for ODSession_SessionService client.
	* 
	* @access public
	*/
	function ODSession_SessionService() {
		$this->client = new SoapClient('wsdl/SessionService2.wsdl',array(
                            'exceptions'=>true,
                            'encoding'=>'utf-8')
                        );
                        
        $this->soapException = new ODSubmission_SoapException();	}
	
	/*
	 * Check if endpoint/wsdl binding is specified by the client
	*/
	function _CheckEndPoint()
	{        
		/*if( $this->Url != $this->client->forceEndpoint )
		{	
			$this->client->setEndpoint($this->Url);
			$this->client->useHTTPPersistentConnection();			
		}*/	
	}
	
	/*
	* return Bindings info.
	* 
	* @param string
	*	$reserved Reserved Value
	* @return object
	*	$bindingResult 
	* @access public ODSession_BindingResult
	*/
	function GetBindings($reserved) {
		$this->_CheckEndPoint();
		
		$bindingResult = new ODSession_BindingResult;
		$param = array('reserved' => $reserved);
		try {
            $this->result = $this->client->__soapCall('GetBindings', array('parameters' => $param)); 
			$wrapper = $this->result->{'return'};
            
			$bindingResult->sessionServiceLocation = $wrapper->sessionServiceLocation;
			$bindingResult->submissionServiceLocation = $wrapper->submissionServiceLocation;
			$bindingResult->queryServiceLocation = $wrapper->queryServiceLocation;
			$bindingResult->sessionServiceWSDL = $wrapper->sessionServiceWSDL;
			$bindingResult->submissionServiceWSDL = $wrapper->submissionServiceWSDL;
			$bindingResult->queryServiceWSDL = $wrapper->queryServiceWSDL;
			$this->soapException = null;
        } catch (SoapFault $fault) {
            $this->soapException = new ODSession_SoapException();
            $this->soapException->Message = $fault->faultstring;
        }
            
		return $bindingResult;

	}

	/*
	* login with username and password. will set SessionId header upon
	* successful login.
	* 
	* @param string, string
	*            $username username for login.
	*            $password password for login.
	* @return mixed LoginResult complex type. (See WSDL.)
	* @access public
	*/
	function login($username, $password){
		$this->_CheckEndPoint();
	
		$loginResult = new ODSession_LoginResult();
		$param = array('userName' => $username, 'password' => $password);
        
        try {
            $this->client->__setLocation($this->Url);
            $this->result = $this->client->__soapCall('Login', array('parameters' => $param));
            $wrapper = $this->result->{'return'};
			$loginResult->sessionID = $wrapper->sessionID;
			// Save Session ID in header
			$this->SessionHeaderValue = new ODSession_SessionHeader;
			$this->SessionHeaderValue->sessionID = $loginResult->sessionID;
			$this->soapException = null;
		} catch (SoapFault $fault) {
            $this->soapException = new ODSession_SoapException();
            $this->soapException->Message = $fault->faultstring;			
		}
		return $loginResult;
	}

	/*
	* logout.
	*
	* @param none
	* @return none
	* @access public
	*/
	function logout(){
		$this->_CheckEndPoint();
		
		$this->setSessionID($this->SessionHeaderValue->sessionID);
        $param = array('' => '');
        try {
            $this->result = $this->client->__soapCall('Logout', array('parameters' => $param));
            $this->soapException = null;
 		} catch (SoapFault $fault) {
			$this->soapException->Message = $this->result->faultstring;
		}
	}

	/*
	* Set session of client. called in login, and accessible for manual
	* setting if session already available. 
	*
	* @param string
	* 	$session Session string 
	* @return none
	* @access public
	*/
	function setSessionID($session){				
		$element = array('sessionID' => $session);		
		$this->setHeader('SessionHeaderValue', $element);
	}
   
	/*
	* Set header on client.
	* 
	* @param string, array
	*            $headerName Name of header
	*            $headerValue Array of soapvals of values
	* @return none
	* @access public
	*/
	function setHeader($headerName, $headerValue){       
        if(!isset($this->requestHeaders)){
            $this->requestHeaders = array($headerName => $headerValue);
        }else{
            if (array_key_exists ($headerName, $this->requestHeaders )){
                $this->requestHeaders[$headerName] = array_merge($this->requestHeaders[$headerName],$headerValue);
            }else{
                $this->requestHeaders[$headerName] = $headerValue;
            }
        }
        $headers = array();
        foreach($this->requestHeaders as $key => $values){
            array_push($headers, new SoapHeader("urn:SessionService2", $key, new SoapVar($values, SOAP_ENC_OBJECT)));
        }
        $this->client->__setSoapHeaders($headers);
	}
}

/*
* ODSession_SoapException Object.
* 
* @access public
*/
class ODSession_SoapException{

	var $Message;
 
}

/*
* Esker ODSession_LoginResult Object.
* 
* @access public
*/
class ODSession_LoginResult {

	var $sessionID;

}

/*
* Esker ODSession_BindingResult Object.
* 
* @access public
*/
class ODSession_BindingResult {

	var $sessionServiceLocation;
	var $submissionServiceLocation;
	var $queryServiceLocation;
	var $sessionServiceWSDL;
	var $submissionServiceWSDL;
	var $queryServiceWSDL;

}

/*
* Esker ODSession_SessionHeader Object.
* 
* @access public
*/
class ODSession_SessionHeader {

        var $sessionID;

}

?>
