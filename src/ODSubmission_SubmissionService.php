<?php
namespace Esker;

/*
* Esker Web Service client for use with a nuSOAP PHP library.
*/

/*
* 
* ODSubmission_SubmissionService
* 
* @version $Id: submissionservice.php,v 1.0 2005/12/12 20:37:19 
* @access public
*/



class ODSubmission_SubmissionService {

	var $client;
	var $result;
	var $soapException;
	var $Url;
	var $SessionHeaderValue;

    var $WSFILE_MODE = array(
    'MODE_UNDEFINED' => 'MODE_UNDEFINED',
	'MODE_ON_SERVER' => 'MODE_ON_SERVER',
	'MODE_INLINED'   => 'MODE_INLINED'
    ); 

    var $RESOURCE_TYPE = array(
	'TYPE_STYLESHEET' => 'TYPE_STYLESHEET',
	'TYPE_IMAGE'      => 'TYPE_IMAGE',
	'TYPE_COVER'      => 'TYPE_COVER'
    );

    var $ATTACHMENTS_FILTER = array(
	'FILTER_NONE'      => 'FILTER_NONE',
	'FILTER_ALL'       => 'FILTER_ALL',
	'FILTER_CONVERTED' => 'FILTER_CONVERTED',
	'FILTER_SOURCE'    => 'FILTER_SOURCE'
	);
	
	/*
	* constructor for ODSubmission_SessionService client.
	* 
	* @access public
	*/
	function ODSubmission_SubmissionService() {		
        $this->client = new SoapClient('wsdl/SubmissionService2.wsdl',array(
                            'exceptions'=>true,
                            'encoding'=>'utf-8',
							)
                        );
		$this->soapException = new ODSubmission_SoapException();
	}

	/*
	 * Check if endpoint/wsdl binding is specified by the client
	*/
	function _CheckEndPoint()
	{
		$this->client->__setLocation($this->Url);
	}
	
	/*
	* Submit.
	* 
	* @param string, object, object
	*	$subject subject
	*	$document ODSubmission_BusinessData
	* 	$rules ODSubmission_BusinessRules
	* @return object
	*	$submissionResult ODSubmission_SubmissionResult
	* @access public
	*/
	function Submit($subject,$document,$rules) {
		$this->_CheckEndPoint();
			
		$this->setSessionID($this->SessionHeaderValue->sessionID);
		$submissionResult = new ODSubmission_SubmissionResult;
		$param = array('subject' => $subject, 'document' => $document, 'rules' => $rules);
		try{
            $this->result = $this->client->__soapCall('Submit', array('parameters' => $param));
			$wrapper = $this->result->{'return'};
			$submissionResult->submissionID = $wrapper->submissionID;
			$submissionResult->transportID = $wrapper->transportID;
			$this->soapException = null;
		}catch(SoapFault $fault) {            
			$this->soapException = new ODSession_SoapException();
            $this->soapException->Message = $fault->faultstring;
		} 
		
		return $submissionResult;
	}

	/*
	* Submit Transport.
	* @param object
	*	$transport ODSubmission_Transport
	* @return object
	*	$submissionResult ODSubmission_SubmissionResult
	* @access public
	*/
	function SubmitTransport($transport) {
		$this->_CheckEndPoint();
		$this->setSessionID($this->SessionHeaderValue->sessionID);
		$submissionResult = new ODSubmission_SubmissionResult;
		$param = array('transport' => (array)$transport);         
        
        try{            
            $this->result = $this->client->__soapCall('SubmitTransport', array('parameters' => $param));
			$wrapper = $this->result->{'return'};
			$submissionResult->submissionID = $wrapper->submissionID;
			$submissionResult->transportID = $wrapper->transportID;
			$this->soapException = null;
        }catch(SoapFault $fault){        
			$this->soapException = new ODSession_SoapException();
            $this->soapException->Message = $fault->faultstring;            
		} 
		
		return $submissionResult;
	}

	/*
	* Submit XML.
	* @param string
	*	$xml xml string
	* @return object
	*	$submissionResult ODSubmission_SubmissionResult
	* @access public
	*/
	function SubmitXML($xml) {
		$this->_CheckEndPoint();
		
		$this->setSessionID($this->SessionHeaderValue->sessionID);
		$submissionResult = new ODSubmission_SubmissionResult;
		$param = array('xml' => $xml);
        try{
            $this->result = $this->client->__soapCall('SubmitXML', array('parameters' => $param));
			$wrapper = $this->result->{'return'};
            		$submissionResult->submissionID = $wrapper->submissionID;
			$submissionResult->transportID = $wrapper->transportID;
			$this->soapException = null;
        }catch(SoapFault $fault){        
			$this->soapException = new ODSession_SoapException();
            $this->soapException->Message = $fault->faultstring;
		}
		
		return $submissionResult;
	}

	/*
	* Extract First.
	* @param object, object, object
	*	$document ODSubmission_BusinessData
	* 	$rules ODSubmission_BusinessRules
	* 	$param ODSubmission_ExtractionParameters
	* @return object
	*	$extractionResult ODSubmission_ExtractionResult
	* @access public
	*/
	function ExtractFirst($document,$rules,$param) {
		$this->_CheckEndPoint();
		
		$this->setSessionID($this->SessionHeaderValue->sessionID);
		$extractionResult = new ODSubmission_ExtractionResult;
		$param = array('document' => $document, 'rules' => $rules, 'param' => $param);
        try{
        $this->result = $this->client->__soapCall('ExtractFirst', array('parameters' => $param));
			$wrapper = $this->result->{'return'};
			$extraction->noMoreItems = $wrapper->noMoreItems;
			$extraction->nTransports = $wrapper->nTransports;
			$extraction->transports = $wrapper->transports;
			$this->soapException = null;
        }catch(SoapFault $fault){        
			$this->soapException = new ODSession_SoapException();
            $this->soapException->Message = $fault->faultstring;
		}
		
		return $extractionResult;
	}

	/*
	* Extract Next.
	* @param object, object, object
	*	$document ODSubmission_BusinessData
	* 	$rules ODSubmission_BusinessRules
	* 	$param ODSubmission_ExtractionParameters
	* @return object
	*	$extractionResult ODSubmission_ExtractionResult
	* @access public
	*/
	function ExtractNext($document,$rules,$param) {
		$this->_CheckEndPoint();
		
		$this->setSessionID($this->SessionHeaderValue->sessionID);
		$extractionResult = new ODSubmission_ExtractionResult;
		$param = array('document' => $document, 'rules' => $rules, 'param' => $param);
        try{
            $this->result = $this->client->__soapCall('ExtractNext', array('parameters' => $param));
			$wrapper = $this->result->{'return'};
			$extraction->noMoreItems = $wrapper->noMoreItems;
			$extraction->nTransports = $wrapper->nTransports;
			$extraction->transports = $wrapper->transports;
			$this->soapException = null;
        }catch(SoapFault $fault){        
			$this->soapException = new ODSession_SoapException();
            $this->soapException->Message = $fault->faultstring;
		}
		
		return $extractionResult;
	}

	/*
	* Convert File.
	* @param object, object
	*	$inputFile ODSubmission_WSFILE
	* 	$params ODSubmission_ConversionParameters
	* @return object
	*	$conversionResult ODSubmission_ConversionResult
	* @access public
	*/
	function ConvertFile($inputFile,$params) {
		$this->_CheckEndPoint();
		
		$this->setSessionID($this->SessionHeaderValue->sessionID);
		$conversionResult = new ODSubmission_ConversionResult;
		$param = array('inputFile' => $inputFile, 'params' => $params);
        try{
            $this->result = $this->client->__soapCall('ConvertFile', array('parameters' => $param));
			$wrapper = $this->result->{'return'};
			$conversionResult->convertedFile->name = $wrapper->convertedFile->name;
			$conversionResult->convertedFile->mode = $wrapper->convertedFile->mode;
			$content = $wrapper->convertedFile->content;
			if($content != null)
				$conversionResult->convertedFile->content = base64_decode($wrapper->convertedFile->content);
			$conversionResult->convertedFile->url = $wrapper->convertedFile->url;
			$conversionResult->convertedFile->storageID = $wrapper->convertedFile->storageID;
			$this->soapException = null;
        }catch(SoapFault $fault){        
			$this->soapException = new ODSession_SoapException();
            $this->soapException->Message = $fault->faultstring;
		}
		
		return $conversionResult;
	}
	/*
	* Download File.
	* @param object
	* 	$wsFile WSFile 
	* @return string
	* 	$resultFile file content
     	* @access public
	*/
	function DownloadFile($wsFile) {
		$this->_CheckEndPoint();
		
		$this->setSessionID($this->SessionHeaderValue->sessionID);
		$param = array('wsFile' => $wsFile);
        $resultFile = null;
        try{
            $this->result = $this->client->__soapCall('DownloadFile', array('parameters' => $param));
            $resultFile = base64_decode($this->result->{'return'});
			$this->soapException = null;
		}catch(SoapFault $fault) {
            $this->soapException = new ODSession_SoapException();
            $this->soapException->Message = $fault->faultstring;
		}
		return $resultFile;
	}


	/*
     	* Register Resource.
	* @param object, string, boolean, boolean
	*	$resource ODSubmission_WSFILE
	* 	$type RESOURCE_TYPE
	* 	$published true/false
	* 	$overwritePrevious true/false
	* @return none
	* @access public
	*/
	function RegisterResource($resource,$type,$published,$overwritePrevious) {
		$this->_CheckEndPoint();
		
		$resource->content = $resource->content;
		$this->setSessionID($this->SessionHeaderValue->sessionID);
		$param = array('resource' => $resource, 'type' => $type, 'published' => $published, 'overwritePrevious' => $overwritePrevious);
        try{
            $this->result = $this->client->__soapCall('RegisterResource', array('parameters' => $param));
            $this->soapException = null;
		}catch(SoapFault $fault) {
			$this->soapException->Message = $this->client->getError();
		}
	}
  
	/*
	* List Resources.
	* @param string, boolean
	* 	$type RESOURCE_TYPE
	* 	$published true/false
	* @return object
	*	$resources ODSubmission_Resources
	* @access public
	*/
	function ListResources($type,$published) {
		$this->_CheckEndPoint();
		
		$this->setSessionID($this->SessionHeaderValue->sessionID);
		$resources = new ODSubmission_Resources;
		$param = array('type' => $type, 'published' => $published);
        try{
            $this->result = $this->client->__soapCall('ListResources', array('parameters' => $param));
            $wrapper = $this->result->{'return'};
            $resources->nResources = $wrapper->nResources;
            if($resources->nResources > 1)
            {
                $resources->resources = $wrapper->resources->{'string'};
            }
            else
            {
                $resources->resources = (array)$wrapper->resources->{'string'};
            }
            $this->soapException = null;		
        }catch(SoapFault $fault) {
			$this->soapException->Message = $this->client->getError();
		} 
        
		return $resources;
	}
  
	/*
	* Delete Resource.
	* @param string, string, boolean
	* 	$resourceName Resource Name
	* 	$type RESOURCE_TYPE
	* 	$published true/false
	* @return none
	* @access public
	*/
	function DeleteResource($resourceName,$type,$published) {
		$this->_CheckEndPoint();
		
		$this->setSessionID($this->SessionHeaderValue->sessionID);
		$param = array('resourceName' => $resourceName, 'type' => $type, 'published' => $published);
        try{
            $this->result = $this->client->__soapCall('DeleteResource', array('parameters' => $param));
            $this->soapException = null;
        }catch(SoapFault $fault) {
			$this->soapException->Message = $this->client->getError();
		}
	}
   
	/*
	* Upload File.
	* @param string, string
	* 	$fileContent File Content
	* 	$name File Name
	* @return object
	*	$wsfile ODSubmission_WSFile
	* @access public
	*/
	function UploadFile($fileContent,$name) {
		$this->_CheckEndPoint();
		
		$this->setSessionID($this->SessionHeaderValue->sessionID);
		$wsfile = new ODSubmission_WSFile;
		$param = array('fileContent' => $fileContent, 'name' => $name);
        try{
            $this->result = $this->client->__soapCall('UploadFile', array('parameters' => $param));
			$wrapper = $this->result->{'return'};
			$wsfile->name = $wrapper->name;
			$wsfile->mode = $wrapper->mode;
			$wsfile->content = $wrapper->content;
			$wsfile->url = $wrapper->url;
			$wsfile->storageID = $wrapper->storageID;
			$this->soapException = null;
        }catch(SoapFault $fault){        
			$this->soapException = new ODSession_SoapException();
            $this->soapException->Message = $fault->faultstring;
		} 
		
		return $wsfile;
    	}

	/*
	* Upload File Append.
	* @param string, string
	* 	$fileContent File Content
	* 	$destWSFile WSFile
	* @return object
	*	$wsfile ODSubmission_WSFile
	* @access public
	*/
	function UploadFileAppend($fileContent,$destWSFile) {
		$this->_CheckEndPoint();
		
		$this->setSessionID($this->SessionHeaderValue->sessionID);
		$wsfile = new ODSubmission_WSFile;
		$param = array('fileContent' => $fileContent, 'destWSFile' => $destWSFile);
        try{
            $this->result = $this->client->__soapCall('UploadFileAppend', array('parameters' => $param));
			$wrapper = $this->result->{'return'};
			$wsfile->name = $wrapper->name;
			$wsfile->mode = $wrapper->mode;
			$wsfile->content = $wrapper->content;
			$wsfile->url = $wrapper->url;
			$wsfile->storageID = $wrapper->storageID;
			$this->soapException = null;
        }catch(SoapFault $fault){        
			$this->soapException = new ODSession_SoapException();
            $this->soapException->Message = $fault->faultstring;
		} 
		
		return $wsfile;
    	}

	/*
	* Set session of client. Called in login, and accessible for manual
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
            array_push($headers, new SoapHeader("urn:SubmissionService2", $key, new SoapVar($values, SOAP_ENC_OBJECT)));
        }
        $this->client->__setSoapHeaders($headers);
	}
	
}