<?php
namespace Esker;
require_once 'vendor/autoload.php';

///////////////////////////////////////////////////////////////////////////////
//
//		Important! Please read before running the code below
//  
//	The sample below needs to be customized before it can be run safely. 
//	It requires that you set the following variables to proper values:
//	
//	- $m_Username: username you have been provided
//	- $m_Password: password you have been provided
//	- $m_mailOnDemandAttachment1: path to a document in the format corresponding 
//		to your mail provider (e.g. Letter for US, or A4 for France). 
//		Refer to the documentation to learn more about document formats for mail.
//
///////////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////////////////////
// STEP #1 : Global Initializations
//////////////////////////////////////////////////////////////////////

// Sample parameters - Please make sure you have changed them to proper values before running the sample!

$m_Username			= 'mYus3r';				// Session username
$m_Password			= 'mYpassw0rd';			// Session password
$m_MailOnDemandAttachment1 = "data/invoice.pdf";	// Document sent - Change it by a path to a local document

$m_PollingInterval	   = 15000;			// check fax status every 15 seconds

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
	global $m_Username,$m_Password,$m_MailOnDemandAttachment1;

	//////////////////////////////////////////////////////////////////////
	// STEP #2 : Initialization + Authentication
	//////////////////////////////////////////////////////////////////////

	Console_WriteLine('Retrieving bindings');
	
	$session = new ODSession_SessionService();

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
	// STEP #3 : Simple Mail On Demand MOD submission
	//////////////////////////////////////////////////////////////////////

	// Creating and initializing a SubmissionService object.
	$submissionService = new ODSubmission_SubmissionService();		

	// Set the service URL with the location retrieved above with GetBindings()
	$submissionService->Url = $bindings->submissionServiceLocation;
				
	// Set the sessionID with the one retrieved above with Login()
	// Every action performed on this object will now use the authenticated context created in step 1
	$submissionService->SessionHeaderValue = new ODSubmission_SessionHeader;
	$submissionService->SessionHeaderValue->sessionID = $login->sessionID;

	Console_WriteLine('Sending Mail On Demand Request');

	// Now allocate a transport with transportName = 'MODEsker'
	$transport = new ODSubmission_Transport;
	$transport->recipientType = "";
	$transport->transportIndex = 0;
	$transport->transportName = 'MODEsker';
	
	// Specifies MailOnDemand variables (see documentation for their meanings)
	$transport->vars = new ODSubmission_TransportVars;
	$transport->vars->Var = array();
	$transport->vars->Var[0] = CreateValue('Subject', 'Sample Mail On Demand');
	$transport->vars->Var[1] = CreateValue('FromName', 'John DOE');
	$transport->vars->Var[2] = CreateValue('FromCompany', 'Dummy Inc.');
	$transport->vars->Var[3] = CreateValue('ToBlockAddress', 'ADERTY firm' .  chr(10) . 'Jaco Aderti' .  chr(10) . '17 Bella Villa Roma' .  chr(10) . '12666 Querbo' .  chr(10) . 'FRANCE');
	$transport->vars->Var[4] = CreateValue('Color', 'Y');
	$transport->vars->Var[5] = CreateValue('Cover', 'Y');
	$transport->vars->Var[6] = CreateValue('BothSided', 'Y');
	$transport->vars->Var[7] = CreateValue('MaxRetry', '3'); 

	// Specify a text attachment to append to the MailOnDemand.
	// The attachment content is inlined in the transport description
	$transport->attachments = new ODSubmission_TransportAttachments;
	$transport->attachments->Attachment = array();
	$transport->attachments->Attachment[0] = new ODSubmission_Attachment;
	$transport->attachments->Attachment[0]->sourceAttachment = FileRead($m_MailOnDemandAttachment1,$submissionService);

	// Submit the complete transport description to the Application Server
	$result = $submissionService->SubmitTransport($transport);
	
	if($ex = $submissionService->soapException)
	{		
		Console_WriteLine('Call to SubmitTransport() failed with message: ' . $ex->Message);
		return;
	} 

	Console_WriteLine('Request submitted with transportID ' . $result->transportID);


	//////////////////////////////////////////////////////////////////////
	// STEP #4 : MailOnDemand tracking
	//////////////////////////////////////////////////////////////////////

	// Creating and initializing a QueryService object.
	$queryService = new ODQuery_QueryService();		

	// Set the service url with the location retrieved above with GetBindings()
	$queryService->Url = $bindings->queryServiceLocation;

	// Set the sessionID with the one retrieved above with Login()
	// Every action performed on this object will now use the authenticated context created in step 1
	$queryService->SessionHeaderValue = new ODQUery_SessionHeader;
	$queryService->SessionHeaderValue->sessionID = $login->sessionID;
    
    // Set the QueryRecipientTypeValue with a comma separated list of RecipientType
    // The following page lists the available recipient types and the corresponding transport names.
    // http://doc.esker.com/eskerondemand/cv_ly/en/webservices/index.asp?page=References/Common/RecipientTypes.html
    // Instead, the following page lists the variables common to all transports.
    // http://doc.esker.com/eskerondemand/cv_ly/en/webservices/index.asp?page=References/Fields/defaulttransportprintable.html
    $queryService->QueryHeaderValue = new ODQuery_QueryHeader;
	$queryService->QueryHeaderValue->recipientType = "MOD";

	// Build a request on the newly submitted fax transport using its unique identifier
	// We also specify the variables (attributes) we want to retrieve.
	$request = new ODQUery_QueryRequest;
	$request->nItems = 1;
	$request->attributes = 'State,ShortStatus,CompletionDateTime';
	$request->filter = '(ruidex=' . $result->transportID . ')';
			
	echo 'Checking for your MailOnDemand status...<BR>';

	$state = 0;
	$status = '';
	$date = '';

	while( true )
	{
		// Ask the Application Server
		$qresult = $queryService->QueryFirst($request);
		if($ex = $queryService->soapException)
		{		
			Console_WriteLine('Call to QueryFirst() failed with message: ' . $ex->Message);
			return;
		} 

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

			if( $state >= 90 )
				break;
					
			Console_WriteLine('MailOnDemand pending...');
		}
		else
		{
			Console_WriteLine('Error !! MailOnDemand not found in database');
			return;
		}

		// Wait 5 seconds, then try again...
		sleep(5);

	}

	if( $state >= 90 && $state <= 100 )
	{
		Console_WriteLine('MailOnDemand successfully sent with transportID ' . $result->transportID);
	}
	else
		Console_WriteLine('MailOnDemand failed at ' . $date . ', reason: ' . $status);		
		


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