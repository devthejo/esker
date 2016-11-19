<?php
//////////////////////////////////////////////////////////////////////
// STEP #1 : Global Initializations
//////////////////////////////////////////////////////////////////////

require_once('sessionservice.php');
require_once('submissionservice.php');
require_once('queryservice.php');

// Sample parameters
$m_Username			= 'mYus3r';				// Session username
$m_Password			= 'mYpassw0rd';			// Session password

// Method used to read data from a file and store them in a Web Service file object.
function FileRead($filename,$submissionService)
{
	$wsFile = new ODSubmission_WSFile;
	$wsFile->mode = $submissionService->WSFILE_MODE['MODE_INLINED'];
	$wsFile->name = shortFileName($filename);
	$myfile = fopen($filename,'r');
	$wsFile->content = base64_encode(fread($myfile, filesize ($filename)));
	fclose($myfile);			
	return $wsFile;
}

// Helper method to allocate and fill in Variable objects.
function CreateValue($AttributeName,$AttributeValue)
{
    $var = new ODQuery_Var;
	$var->attribute = $AttributeName;
	$var->simpleValue = $AttributeValue;
	$var->type = 'TYPE_STRING';
	return $var;
}

// Helper method to display a string on the page
function Console_WriteLine($displayString)
{
	echo $displayString . '<BR>' . chr(10);
	flush();	
}

function Run()
{
	global $m_Username,$m_Password,$m_CoverFile;
	global $m_FaxAttachment1,$m_FaxAttachment2,$m_PollingInterval;

	//////////////////////////////////////////////////////////////////////
	// STEP #2 : Initialization + Authentication
	//////////////////////////////////////////////////////////////////////

	Console_WriteLine('Retrieving bindings');
	
	$session = new ODSession_SessionService();

	// uncomment these lines if you want to use a proxy server
	//$session->client->proxyhost 	= "myproxy.company.com";
	//$session->client->proxyport 	= "8080";
	//$session->client->proxyusername = "proxy_username";
	//$session->client->proxypassword = "proxy_password";
	
	// Retrieve the bindings on the Application Server (location of the Web Services)
	$bindings = new ODSession_BindingResult;
	$bindings = $session->GetBindings($m_Username);
	if($ex = $session->soapException)  
	{
		Console_Writeline('Call to GetBindings() failed with message: ' . $ex->Message);
		return; 
	}

	Console_Writeline('Binding = ' . $bindings->sessionServiceLocation);

	// Now uses the returned URL with our session object, in case the Application Server redirected us.
	$session->Url = $bindings->sessionServiceLocation;

	Console_Writeline('Authenticating session');

	// Authenticate the user on this session object to retrieve a sessionID
	$login = new ODSession_LoginResult;
	$login = $session->login($m_Username, $m_Password);

	if($ex = $session->soapException)  
	{    
		Console_Writeline('Call to Login() failed with message: ' . $ex->Message);
		return;
	} 

	// This sessionID is an impersonation token representing the logged on user
	// You can use it with other Web Services objects, until you call Logout (which releases the
	// current sessionID and it's associated resources), or until the session times out (default is 10
	// minutes on the Application Server).
	Console_Writeline('SessionID = ' . $login->sessionID);

	//////////////////////////////////////////////////////////////////////
	// STEP #3 : Fax retrieving
	//////////////////////////////////////////////////////////////////////

	// Creating and initializing a QueryService object.
	$queryService = new ODQuery_QueryService();		

	// Set the service url with the location retrieved above with GetBindings()
	$queryService->Url = $bindings->queryServiceLocation;

	// Set the sessionID with the one retrieved above with Login()
	// Every action performed on this object will now use the authenticated context created in step 1
	$queryService->SessionHeaderValue = new ODQuery_SessionHeader;
	$queryService->SessionHeaderValue->sessionID = $login->sessionID;

    // Set the QueryRecipientTypeValue with a comma separated list of RecipientType
    // The following page lists the available recipient types and the corresponding transport names.
    // http://doc.esker.com/eskerondemand/cv_ly/en/webservices/index.asp?page=References/Common/RecipientTypes.html
    // Instead, the following page lists the variables common to all transports.
    // http://doc.esker.com/eskerondemand/cv_ly/en/webservices/index.asp?page=References/Fields/defaulttransportprintable.html
    $queryService->QueryHeaderValue = new ODQuery_QueryHeader;
	$queryService->QueryHeaderValue->recipientType = "FGFAXIN";

	// Build a request on the newly submitted fax transport using its unique identifier
	// We also specify the variables (attributes) we want to retrieve.
	$request = new ODQuery_QueryRequest;
	$request->nItems = 5;
	$request->attributes = 'CompletionDateTime';
	$request->filter = '(&(RecipientType=FGFaxIn)(State=100)(!(Identifier=RETRIEVED)))';
	
	Console_Writeline('Checking for new inbound faxes...');

	// Ask the Application Server
	$qresult = $queryService->QueryFirst($request);
	if($ex = $queryService->soapException)
	{		
		Console_Writeline('Call to QueryFirst() failed with message: ' . $ex->Message);
		return;
	} 

	//Console_Writeline("DEBUG: " . $queryService->client->varDump($qresult));
	
	for($i=0; $i<$qresult->nTransports; $i++)
	{	
		$uniqueID = $qresult->transports[$i]->transportID;

		if( $uniqueID == '' )
		{
			Console_Writeline('Fax received #???');
			continue;
		}
		
		Console_Writeline('Fax received #' . $uniqueID);
		
		$atts = $queryService->QueryAttachments($uniqueID, $queryService->ATTACHMENTS_FILTER['FILTER_CONVERTED'], $queryService->WSFILE_MODE['MODE_INLINED']);
		if($ex = $queryService->soapException)
		{		
			Console_Writeline('Call to QueryAttachments() failed with message: ' . $ex->Message);
			return;
		}

		// The final fax image is known to be the first converted attachment of the first available attachments, 
		// so retrieve this one.		
		if( $atts->nAttachments > 0 && $atts->attachments[0]->nConvertedAttachments > 0 )
		{
			$faxImage = $atts->attachments[0]->convertedAttachments[0];
			
			if( strlen($faxImage->content) > 0 )
			{
				Console_Writeline('Fax image data retrieve, size = ' . strlen($faxImage->content));
				
				$myfile = fopen($uniqueID . '.tif','w');
				fwrite($myfile, $faxImage->content);
				fclose($myfile);
			}
			else
				Console_Writeline('Error !! no valid attachments found');
		}
		else
			Console_Writeline('Error !! no valid attachments found');
			
		$prms = new ODQuery_UpdateParameters;
		$prms->vars->Var[0] = CreateValue('Identifier', 'RETRIEVED');

		$r = $queryService->Update('(&(RecipientType=FGFaxIn)(msn=' . $uniqueID . '))', $prms);
		if($ex = $queryService->soapException)
		{		
			Console_Writeline('Call to Update() failed with message: ' . $ex->Message);
			return;
		}		
		
		if( $r->nFailed > 0 )
			Console_Writeline('failed to update item #' . $uniqueID . ', error = ' . $r->errorReason[0]);
		else
			Console_Writeline('successfully updated item #' . $uniqueID);				
	}

	//////////////////////////////////////////////////////////////////////
	// STEP #4 : Release the session and its allocated resources
	//////////////////////////////////////////////////////////////////////

	// As soon as you call Logout(), the files allocated on the server during this session won't be available
	// anymore, so keep in mind that former urls are now useless...
	
	Console_Writeline('Releasing session and server files');

	$session->Logout();
	if($ex = $session->soapException)
	{		
		Console_Writeline('Call to Logout() failed with message: ' . $ex->Message);
		return;
	} 
}

Run();

echo '<a href="index.php">Back to sample menu</a>';

?>
