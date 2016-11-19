<?php
namespace Esker;
/*
* 
* ODQuery_QueryService
* 
* @version $Id: queryservice.php,v 1.0 2005/12/12 20:37:19 
* @access public
*/
class ODQuery_QueryService  {

	var $client;
	var $result;
	var $soapException;
	var $Url;
	var $SessionHeaderValue;
	var $QueryHeaderValue;
	var $RESOURCE_TYPE;
    
	var $WSFILE_MODE = array(
		'MODE_UNDEFINED' => 'MODE_UNDEFINED',
		'MODE_ON_SERVER' => 'MODE_ON_SERVER',
		'MODE_INLINED'   => 'MODE_INLINED'
	); 

   
    	var $ATTACHMENTS_FILTER = array(

		'FILTER_NONE'      => 'FILTER_NONE',
		'FILTER_ALL'       => 'FILTER_ALL',
		'FILTER_CONVERTED' => 'FILTER_CONVERTED',
		'FILTER_SOURCE'    => 'FILTER_SOURCE'

    	);
    

	/*
	* constructor for ODQuery_QueryService client.
	* 
	* @access public
	*/
	function ODQuery_QueryService() {
		$this->client = new SoapClient('wsdl/QueryService2.wsdl',array(
                            'exceptions'=>true,
                            'encoding'=>'utf-8')
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
	* Query First.
	* @param object
	* 	$request QueryRequest 
	* @return object
	* 	$queryResult ODQuery_QueryResult
     	* @access public
	*/
	function QueryFirst($request) {
		$this->_CheckEndPoint();
		
		$this->setQueryHeader();
        
		$param = array('request' => (array)$request);
		try{
            
            $this->result = $this->client->__soapCall('QueryFirst', array('parameters' => $param));            
        	$queryResult = $this->getQueryResult($this->result->{'return'});
			$this->soapException = null;
		}catch(SoapFault $fault){
			$this->soapException->Message = $fault->faultstring;
            return null;
		}
		
		/* Save queryID for subsequent calls (QueryNext/QueryPrevious) */
		$this->QueryHeaderValue = new ODQuery_QueryHeader;
		$responseHeaders = $this->client->__getLastResponseHeaders();
		$pos1 = strpos($responseHeaders,'<queryID>');
		$pos2 = strpos($responseHeaders,'</queryID>');
		if($pos1 >= 0 && $pos2 > ($pos1+9))
			$this->QueryHeaderValue->queryID = substr($responseHeaders, $pos1+9, $pos2-($pos1+9));	
		else
			$this->QueryHeaderValue->queryID = "";
				
		return $queryResult;
	}

	/*
	* Query Next.
	* @param object
	* 	$request QueryRequest 
	* @return object
	* 	$queryResult ODQuery_QueryResult
     	* @access public
	*/
	function QueryNext($request) {
		$this->_CheckEndPoint();
		
		$this->setQueryHeader();
		
		$param = array('request' => $request);
		try{
            $this->result = $this->client->__soapCall('QueryNext', array('parameters' => $param));
        
            $queryResult = $this->getQueryResult($this->result->{'return'});
			$this->soapException = null;
        }catch(SoapFault $fault){
			$this->soapException->Message = $fault->faultstring;
		} 
        
		return $queryResult;
	}

	/*
	* Query Last.
	* @param object
	* 	$request QueryRequest 
	* @return object
	* 	$queryResult ODQuery_QueryResult
     	* @access public
	*/
	function QueryLast($request) {
		$this->_CheckEndPoint();
		
		$this->setQueryHeader();
		$param = array('request' => $request);
        try{
            $this->result = $this->client->__soapCall('QueryLast', array('parameters' => $param));
        
            $queryResult = $this->getQueryResult($this->result->{'return'});
			$this->soapException = null;
        }catch(SoapFault $fault){
			$this->soapException->Message = $fault->faultstring;
		} 

		/* Save queryID for subsequent calls (QueryNext/QueryPrevious) */
		$this->QueryHeaderValue = new ODQuery_QueryHeader;
		$responseHeaders = $this->client->getHeaders();
		$pos1 = strpos($responseHeaders,'<queryID>');
		$pos2 = strpos($responseHeaders,'</queryID>');
		if($pos1 >= 0 && $pos2 > ($pos1+9))
			$this->QueryHeaderValue->queryID = substr($responseHeaders, $pos1+9, $pos2-($pos1+9));	
		else
			$this->QueryHeaderValue->queryID = "";

		return $queryResult;
	}

	/*
	* Query Previous.
	* @param object
	* 	$request QueryRequest 
	* @return object
	* 	$queryResult ODQuery_QueryResult
     	* @access public
	*/
	function QueryPrevious($request) {
		$this->_CheckEndPoint();
		
		$this->setQueryHeader();
		$param = array('request' => $request);
        try{
            $this->result = $this->client->__soapCall('QueryPrevious', array('parameters' => $param));
        
            $queryResult = $this->getQueryResult($this->result->{'return'});
			$this->soapException = null;
        }catch(SoapFault $fault){
			$this->soapException->Message = $fault->faultstring;
		} 
		
		return $queryResult;
	}

	/*
	* Query Attachments.
	* @param string, string, string
	* 	$transportID transport ID 
	* 	$eFilter ATTACHMENTS_FILTER
	* 	$eMode WSFILE_MODE
	* @return object
	* 	$statisticsResult ODQuery_StatisticsResult
     	* @access public
	*/
	function QueryAttachments($transportID,$eFilter,$eMode) {
		$this->_CheckEndPoint();
		
		$this->setQueryHeader();
		$param = array('transportID' => $transportID, 'eFilter' => $eFilter, 'eMode' => $eMode);
        try{
            $this->result = $this->client->__soapCall('QueryAttachments', array('parameters' => $param));
            $attachments = null;
            
            $attachments = $this->getAttachments($this->result->{'return'});
			$this->soapException = null;
		}catch(SoapFault $fault){
			$this->soapException->Message = $fault->faultstring;
		} 
        
		return $attachments;
	}

	/*
	* Query Statistics.
	* @param string
	* 	$filter filter 
	* @return object
	* 	$statisticsResult ODQuery_StatisticsResult
     	* @access public
	*/
	function QueryStatistics($filter) {
		$this->_CheckEndPoint();
		
		$this->setQueryHeader();
		$statisticsResult = new ODQuery_StatisticsResult;
		$param = array('filter' => $filter);
        try{
            $this->result = $this->client->__soapCall('QueryStatistics', array('parameters' => $param));
            
            $wrapper = $this->result->{'return'};
			$statisticsResult->nTypes = $wrapper->nTypes;
			$statisticsResult->typeName = $wrapper->typeName;
			$statisticsResult->typeContent = $wrapper->typeContent;
			$statisticsResult->nItems = $wrapper->nItems;
			$statisticsResult->includeSubNodes = $wrapper->includeSubNodes;
			$this->soapException = null;
        }catch(SoapFault $fault){
			$this->soapException->Message = $fault->faultstring;
		} 
		
		return $statisticsResult;
	}

	/*
	* Delete Message.
	* @param string
	* 	$identifier MSN 
	* @return object
	* 	$actionResult ODQuery_ActionResult
     	* @access public
	*/
	function Delete($identifier) {
		$this->_CheckEndPoint();
		
		$this->setQueryHeader();
		$actionResult = new ODQuery_ActionResult;
		$param = array('identifier' => $identifier);
        try{
            $this->result = $this->client->__soapCall('Delete', array('parameters' => $param));
            
            $wrapper = $this->result->{'return'};
			$actionResult->nSucceeded = $wrapper->nSucceeded;
			$actionResult->nFailed = $wrapper->nFailed;
			$actionResult->nItem = $wrapper->nItem;
			$actionResult->transportIDs = $wrapper->transportIDs;
			$actionResult->errorReason = $wrapper->errorReason;
			$this->soapException = null;
        }catch(SoapFault $fault){
			$this->soapException->Message = $fault->faultstring;
		} 
        
		return $queryResult;
	}

	/*
	* Cancel Message.
	* @param string
	* 	$identifier MSN 
	* @return object
	* 	$actionResult ODQuery_ActionResult
     	* @access public
	*/
	function Cancel($identifier) {
		$this->_CheckEndPoint();
		
		$this->setQueryHeader();
		$actionResult = new ODQuery_ActionResult;
		$param = array('identifier' => $identifier);
        try{
            $this->result = $this->client->__soapCall('Cancel', array('parameters' => $param));
            $wrapper = $this->result->{'return'};
			$actionResult->nSucceeded = $wrapper->nSucceeded;
			$actionResult->nFailed = $wrapper->nFailed;
			$actionResult->nItem = $wrapper->nItem;
			$actionResult->transportIDs = $wrapper->transportIDs;
			$actionResult->errorReason = $wrapper->errorReason;
			$this->soapException = null;
        }catch(SoapFault $fault){
	            $this->soapException->Message = $fault->faultstring;
		} 
        
		return $actionResult;
	}

	/*
	* Resubmit Message.
	* @param string, object
	* 	$identifier MSN 
	* 	$params ResubmitParameters
	* @return object
	* 	$actionResult ODQuery_ActionResult
     	* @access public
	*/
	function Resubmit($identifier,$params) {
		$this->_CheckEndPoint();
		
		$this->setQueryHeader();
		$actionResult = new ODQuery_ActionResult;
		$param = array('identifier' => $identifier, 'params' => $params);
        try{
            $this->result = $this->client->__soapCall('Resubmit', array('parameters' => $param));
            
            $wrapper = $this->result->{'return'};
			$actionResult->nSucceeded = $wrapper->nSucceeded;
			$actionResult->nFailed = $wrapper->nFailed;
			$actionResult->nItem = $wrapper->nItem;
			$actionResult->transportIDs = $wrapper->transportIDs;
			$actionResult->errorReason = $wrapper->errorReason;
			$this->soapException = null;
		}catch(SoapFault $fault){
			$this->soapException->Message = $fault->faultstring;
		} 
		
		return $actionResult;
	}

	/*
	* Update Message.
	* @param string, object
	* 	$identifier MSN 
	* 	$params UpdateParameters
	* @return object
	* 	$actionResult ODQuery_ActionResult
     	* @access public
	*/
	function Update($identifier,$params) {
		$this->_CheckEndPoint();
		
		$this->setQueryHeader();
		$actionResult = new ODQuery_ActionResult;
		$param = array('identifier' => $identifier, 'params' => $params);
        try{
            $this->result = $this->client->__soapCall('Update', array('parameters' => $param));
            
            $wrapper = $this->result->{'return'};
			$actionResult->nSucceeded = $wrapper->nSucceeded;
			$actionResult->nFailed = $wrapper->nFailed;
			$actionResult->nItem = $wrapper->nItem;
			$actionResult->transportIDs = $wrapper->transportIDs;
			$actionResult->errorReason = $wrapper->errorReason;
			$this->soapException = null;
		}catch(SoapFault $fault){
			$this->soapException->Message = $fault->faultstring;
		} 
		
		return $actionResult;
	}
	
	/*
	* Approve Message.
	* @param string, string
	* 	$identifier MSN 
	* 	$reason Reason of approval 
	* @return object
	* 	$actionResult ODQuery_ActionResult
     	* @access public
	*/
	function Approve($identifier,$reason) {
		$this->_CheckEndPoint();
		
		$this->setQueryHeader();
		$actionResult = new ODQuery_ActionResult;
		$param = array('identifier' => $identifier, 'reason' => $reason);
        try{
            $this->result = $this->client->__soapCall('Approve', array('parameters' => $param));
            $wrapper = $this->result->{'return'};
			$actionResult->nSucceeded = $wrapper->nSucceeded;
			$actionResult->nFailed = $wrapper->nFailed;
			$actionResult->nItem = $wrapper->nItem;
			$actionResult->transportIDs = $wrapper->transportIDs;
			$actionResult->errorReason = $wrapper->errorReason;
			$this->soapException = null;
		}catch(SoapFault $fault){
			$this->soapException->Message = $fault->faultstring;
		} 
		
		return $actionResult;
	}

	/*
	* Reject Message.
	* @param string, string
	* 	$identifier MSN 
	* 	$reason Reason of rejection 
	* @return object
	* 	$actionResult ODQuery_ActionResult
     	* @access public
	*/
	function Reject($identifier,$reason) {
		$this->_CheckEndPoint();
		
		$this->setQueryHeader();
		$actionResult = new ODQuery_ActionResult;
		$param = array('identifier' => $identifier, 'reason' => $reason);
        try{
            $this->result = $this->client->__soapCall('Reject', array('parameters' => $param));
            
            $wrapper = $this->result->{'return'};
			$actionResult->nSucceeded = $wrapper->nSucceeded;
			$actionResult->nFailed = $wrapper->nFailed;
			$actionResult->nItem = $wrapper->nItem;
			$actionResult->transportIDs = $wrapper->transportIDs;
			$actionResult->errorReason = $wrapper->errorReason;
			$this->soapException = null;
		}catch(SoapFault $fault){
			$this->soapException->Message = $fault->faultstring;
		} 
		
		return $actionResult;
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
		
		$this->setQueryHeader();
		$param = array('wsFile' => $wsFile);
        try{
            $this->result = $this->client->__soapCall('DownloadFile', array('parameters' => $param));
            
            $resultFile = ($this->result->{'return'});
			$this->soapException = null;
		}catch(SoapFault $fault){
			$this->soapException->Message = $fault->faultstring;
		} 
		
		return $resultFile;
	}
    
    /*
	* Download a chunk of File.
	* @param object
	* 	$wsFile WSFile 
	* @return string
	* 	$resultFile chunck content
     	* @access public
	*/
	function DownloadFileChunk($wsFile) {
		$this->_CheckEndPoint();
		
		$this->setQueryHeader();		
        $param = array('wsFile' => $wsFile, 'uPos' => $uPos, 'uChunkSize' => $uChunkSize);        
        try{            
            $this->result = $this->client->__soapCall('DownloadFileChunck', array('parameters' => $param));
            
            $resultFile = ($this->result->{'return'});
			$this->soapException = null;
		}catch(SoapFault $fault){
			$this->soapException->Message = $fault->faultstring;
		} 
		
		return $resultFile;
	}

	/*
	* get Results from Query calls.
	* 
	* @param string
	*            $wrapped string returned by the call
	* @retunr object
	*            $queryresult ODQUery_QueryResult
	* @access public
	*/
	function getQueryResult($wrapper)
	{
	    $queryResult = new ODQUery_QueryResult;
           
	    $queryResult->noMoreItems = $wrapper->noMoreItems;
	    $queryResult->nTransports = $wrapper->nTransports;
	
        for($i=0;$i<$queryResult->nTransports;$i++) 
        { 	
			if ($queryResult->nTransports > 1) 
			{	
				$queryResult->transports[$i] = (object) $wrapper->transports->Transport[$i];
			}
			else
			{
		    	$queryResult->transports[$i] = (object) current($wrapper->transports);
			}

			if ($queryResult->transports[$i]->nVars > 1) 
			{
				// Convert vars from object to array type
		    	$vars = current($queryResult->transports[$i]->vars);
                $my_vars = array();
				// loop through vars                
                for($j=0;$j<$queryResult->transports[$i]->nVars;$j++) 
                {
                    array_push($my_vars,(object) current($vars));					
					next($vars);
				}
                $queryResult->transports[$i]->vars = $my_vars;
			}
			else 
			{
				$queryResult->transports[$i]->vars = array((object) $queryResult->transports[$i]->vars->{'Var'});
			}	
	    }

        return $queryResult;
	}

	/*
	* get Attachments returned by the QueryAttachment call.
	* 
	* @param string
	*            $wrapped string returned by the call
	* @return object
	*            $attachments ODQUery_Attachments
	* @access public
     	*/
	function getAttachments($wrapper){

	    $attachments = new ODQUery_Attachments;
	    $attachments->nAttachments = $wrapper->nAttachments;
	    if($attachments->nAttachments > 1)
			$attachments->attachments = $wrapper->attachments->Attachment;
		else
			$attachments->attachments[0] = (object)$wrapper->attachments->Attachment;

		
		for($i=0;$i<$attachments->nAttachments;$i++) 
		{ 
			// Convert to Object
			$attachments->attachments[$i] = (object) $attachments->attachments[$i];
			$attachments->attachments[$i]->sourceAttachment = (object) $attachments->attachments[$i]->sourceAttachment;
			if($attachments->attachments[$i]->sourceAttachment->content != null)
				$attachments->attachments[$i]->sourceAttachment->content = ($attachments->attachments[$i]->sourceAttachment->content);
			$convertedAttachments = $attachments->attachments[$i]->convertedAttachments;

			for($j=0;$j<$attachments->attachments[$i]->nConvertedAttachments;$j++) 
			{
				if (is_array($convertedAttachments)) 
				{
					$attachments->attachments[$i]->convertedAttachments[$j] = (object) current($convertedAttachments);
					if($attachments->attachments[$i]->convertedAttachments[$j]->content != null)
						$attachments->attachments[$i]->convertedAttachments[$j]->content = ($attachments->attachments[$i]->convertedAttachments[$j]->content);
					next($convertedAttachments);
				}
				else 
				{
					break;
				}	
			}
	    }
        return $attachments;
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

	function setQueryID($query){       
        $element = array('queryID' => $query);		
		$this->setHeader('QueryHeaderValue', $element);
    }
    
    function setQueryRecipientType($recipientType){
        $element = array('recipientType' => $recipientType);
		$this->setHeader('QueryRecipientTypeValue', $element);
    }
    
    function setQueryHeader(){
        $this->setSessionID($this->SessionHeaderValue->sessionID);
        $this->setQueryRecipientType($this->QueryHeaderValue->recipientType);
        if($this->QueryHeaderValue->queryID)
            $this->setQueryID($this->QueryHeaderValue->queryID);
       
       $this->client->__setSoapHeaders($this->soapHeaders);
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
            array_push($headers, new SoapHeader("urn:QueryService2", $key, new SoapVar($values, SOAP_ENC_OBJECT),false));
        }        
        
        $this->soapHeaders = $headers;
	}


}