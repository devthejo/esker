<?php
namespace Esker;
require_once 'vendor/autoload.php';

//////////////////////////////////////////////////////////////////////
// STEP #1 : Global Initializations
//////////////////////////////////////////////////////////////////////

// Sample parameters
$m_Username			= 'mYus3r';				// Session username
$m_Password			= 'mYpassw0rd';			// Session password

$m_CoverFile		= 'data/cover.rtf';		// cover file resource
$m_FaxAttachment1	= 'data/Azur1023.txt';	// the first attachment file
$m_FaxAttachment2	= 'data/sample.pdf';	// the second attachment file

$m_PollingInterval	= 15000;				// check fax status every 15 seconds

// Method used to read data from a file and store them in a Web Service file object.
function FileRead($filename,$submissionService)
{
	$wsFile = new ODSubmission_WSFile;
	$wsFile->mode = $submissionService->WSFILE_MODE['MODE_INLINED'];
	$wsFile->name = shortFileName($filename);
	$myfile = fopen($filename,'r');
	$wsFile->content = (fread($myfile, filesize ($filename)));
	fclose($myfile);			
	return $wsFile;
}

// Helper method to return the last position of a search string in a source string
function lastIndexOf($sourceString, $searchString) 
{
	$index = strpos(strrev($sourceString), strrev($searchString));
	$index = strlen($sourceString) - strlen($index) - $index;
	return $index;
} 

// Helper method to allocate and fill in Variable objects.
function CreateValue($AttributeName,$AttributeValue)
{
    $var = new ODSubmission_Var;
	$var->attribute = $AttributeName;
	$var->simpleValue = utf8_encode($AttributeValue);
	$var->type = 'TYPE_STRING';
	return $var;
}

// Helper method to extract the short file name from a full file path
function shortFileName($filename)
{
	$i = LastIndexOf($filename,'/');
	if($i < 0 ) $i= LastIndexOf($filename,'\\');
	if($i < 0 ) return $filename;
	return substr($filename,$i+1);
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
	// STEP #3 : Simple fax submission
	//////////////////////////////////////////////////////////////////////

	// Creating and initializing a SubmissionService object.
	$submissionService = new ODSubmission_SubmissionService();		

	// Set the service URL with the location retrieved above with GetBindings()
	$submissionService->Url = $bindings->submissionServiceLocation;
				
	// Set the sessionID with the one retrieved above with Login()
	// Every action performed on this object will now use the authenticated context created in step 1
	$submissionService->SessionHeaderValue = new ODSubmission_SessionHeader;
	$submissionService->SessionHeaderValue->sessionID = $login->sessionID;
    
	// Cover file resource is now made available on the server for the current user
	// The cover resource file is specific to the fax transport, and should be ignored when submitting
	// other transport types.
	// Once it is registered on the server, you should not have to upload it each time a transport is submitted.
	// Unlike other files, resources are permanently stored on the server even after a call to Logout()

	Console_Writeline('Registering cover resource');

	$cover = FileRead($m_CoverFile,$submissionService);
	$submissionService->RegisterResource($cover,$submissionService->RESOURCE_TYPE['TYPE_COVER'],false,true);
	if($ex = $submissionService->soapException)
	{		
		Console_Writeline('Call to RegisterResources() failed with message: ' . $ex->Message);
		return;
	} 

	// Uploading a file on the Application Server. The file reference will be used later
	Console_Writeline('Uploading attachment file on the server');
	
	$myfile = fopen($m_FaxAttachment2,'r'); 
	$data = fread($myfile, 64*1024);
	while( strlen($data) > 0 )
	{
		if( !isset($uploadedFile) )
			$uploadedFile = $submissionService->UploadFile($data, shortFileName($m_FaxAttachment2));
		else
			$uploadedFile = $submissionService->UploadFileAppend($data, $uploadedFile);

		if($ex = $submissionService->soapException)  
		{ 
			Console_Writeline('Call to UploadFile() failed with message: ' . $ex->Message);
			return;
		}
		
		$data = fread($myfile, 64*1024);
	}

	Console_Writeline('Uploaded file available on the server at ' . $uploadedFile->url);

	Console_Writeline('Sending Fax Request');

	// Now allocate a transport with transportName = 'Fax'
	$transport = new ODSubmission_Transport;
	$transport->transportName = 'Fax';

	// Specifies fax variables (see documentation for their definitions)
	$transport->vars = new ODSubmission_TransportVars;
	$transport->vars->Var = array();
	$transport->vars->Var[0] = CreateValue('Subject', 'Sample fax');
	$transport->vars->Var[1] = CreateValue('FaxNumber', '+33472834697');
	$transport->vars->Var[2] = CreateValue('Message', 'This is a sample fax, including two attachments');
	$transport->vars->Var[3] = CreateValue('FromName', 'John DOE');
	$transport->vars->Var[4] = CreateValue('FromCompany', 'Dummy Inc.');
	$transport->vars->Var[5] = CreateValue('FromFax', '+33472834688');
	$transport->vars->Var[6] = CreateValue('ToName', 'Jay TOUCHAMPS');
	$transport->vars->Var[7] = CreateValue('ToCompany', 'Touchamps SA');
	$transport->vars->Var[8] = CreateValue('CoverTemplate', 'cover.rtf'); // shortFileName($m_CoverFile));

	// Specify a pdf attachment to append to the fax.
	// The attachment content is inlined in the transport description
	$transport->attachments = new ODSubmission_TransportAttachments;
	$transport->attachments->Attachment = array();
	$transport->attachments->Attachment[0] = new ODSubmission_Attachment;
	$transport->attachments->Attachment[0]->sourceAttachment = FileRead($m_FaxAttachment1,$submissionService);

	// Specify another attachment, but this one is already available on the server
	// (the file uploaded above)
	$transport->attachments->Attachment[1] = new ODSubmission_Attachment;
	$transport->attachments->Attachment[1]->sourceAttachment = $uploadedFile;

	// Submit the complete transport description to the Application Server
	$result = $submissionService->SubmitTransport($transport);
	if($ex = $submissionService->soapException)
	{		
		Console_Writeline('Call to SubmitTransport() failed with message: ' . $ex->Message);
		return;
	} 

	Console_Writeline('Request submitted with transportID ' . $result->transportID);


	//////////////////////////////////////////////////////////////////////
	// STEP #4 : Fax tracking
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
	$queryService->QueryHeaderValue->recipientType = "FGFAXOUT";

	// Build a request on the newly submitted fax transport using its unique identifier
	// We also specify the variables (attributes) we want to retrieve.
	$request = new ODQuery_QueryRequest;
	$request->nItems = 1;
	$request->attributes = 'State,ShortStatus,CompletionDateTime';
	$request->filter = '(ruidex=' . $result->transportID . ')';
	
	Console_Writeline('Checking for your fax status...');

	$state = 0;
	$status = '';
	$date = '';

	while( true )
	{
		// Ask the Application Server
		$qresult = $queryService->QueryFirst($request);
		if($ex = $queryService->soapException)
		{		
			Console_Writeline('Call to QueryFirst() failed with message: ' . $ex->Message);
			return;
		} 

		// dump result structure.
		// Console_Writeline("DEBUG: " . $queryService->client->varDump($qresult));

		if( $qresult->nTransports == 1 )
		{	
			// Hopefully, we found it
			// Parse the returned variables
			for($iVar=0; $iVar<$qresult->transports[0]->nVars; $iVar++)
			{
				if( strtolower( $qresult->transports[0]->vars[$iVar]->attribute ) == 'state' )
				{
					$state = $qresult->transports[0]->vars[$iVar]->simpleValue;
				}
				else if( strtolower( $qresult->transports[0]->vars[$iVar]->attribute ) == 'shortstatus' )
				{
					$status = $qresult->transports[0]->vars[$iVar]->simpleValue;
				}
				else if( strtolower( $qresult->transports[0]->vars[$iVar]->attribute ) == 'completiondatetime' )
				{
					$date = $qresult->transports[0]->vars[$iVar]->simpleValue;
				}
			}

			if( $state >= 100 )
				break;
					
			Console_Writeline('Fax pending...');
		}
		else
		{
			Console_Writeline('Error !! Fax not found in database');
			return;
		}

		// Wait 5 seconds, then try again...
		sleep(5);

	}

	// If the fax is successfully sent (state=100), retrieves the final fax image
	// for remote display (using a web browser), and also download the image data for local processing
	if( $state == 100 )
	{
		Console_Writeline('Fax successfully sent at ' . $date);

		
		$atts = $queryService->QueryAttachments($result->transportID, $queryService->ATTACHMENTS_FILTER['FILTER_CONVERTED'], $queryService->WSFILE_MODE['MODE_ON_SERVER']);
		if($ex = $queryService->soapException)
		{		
			Console_Writeline('Call to QueryAttachments() failed with message: ' . $ex->Message);
			return;
		}

		// The final fax image is known to be the first converted attachment of the last available attachments, 
		// so retrieve this one.
		if( $atts->nAttachments > 0 && $atts->attachments[$atts->nAttachments-1]->nConvertedAttachments > 0 )
		{
			$faxImage = $atts->attachments[$atts->nAttachments-1]->convertedAttachments->WSFile;

			Console_Writeline('Fax image available at ' . $faxImage->url);

			// Download the file for local use

			$imagedata = $queryService->DownloadFile($faxImage);				
			if($ex = $queryService->soapException)
			{		

				Console_Writeline('Call to DownloadFile() failed with message: ' . $ex->Message);
				return;
			}

			Console_Writeline('Fax image data retrieve, size = ' . strlen($imagedata));
			
		}
		else
			Console_Writeline('Error !! no valid attachments found');
	}
	else
		Console_Writeline('Fax failed at ' . $date . ', reason: ' . $status);


	//////////////////////////////////////////////////////////////////////
	// STEP #5 : Release the session and its allocated resources
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