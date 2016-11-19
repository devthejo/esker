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

$m_PollingInterval	= 15000;			// check SMS status every 15 seconds

// Helper method to allocate and fill in Variable objects.
function CreateValue($AttributeName,$AttributeValue)
{
    $var = new ODSubmission_Var;
	$var->attribute = $AttributeName;
	$var->simpleValue = utf8_encode($AttributeValue);
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
	global $m_Username,$m_Password;

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
	// STEP #3 : Simple SMS submission
	//////////////////////////////////////////////////////////////////////

	// Creating and initializing a SubmissionService object.
	$submissionService = new ODSubmission_SubmissionService();		

	// Set the service URL with the location retrieved above with GetBindings()
	$submissionService->Url = $bindings->submissionServiceLocation;
				
	// Set the sessionID with the one retrieved above with Login()
	// Every action performed on this object will now use the authenticated context created in step 1
	$submissionService->SessionHeaderValue = new ODSession_SessionHeader;
	$submissionService->SessionHeaderValue->sessionID = $login->sessionID;


	Console_Writeline('Sending SMS Request');

	// Now allocate a transport with transportName = 'SMS'
	$transport = new ODSubmission_Transport;
	$transport->transportName = 'SMS';

	// Specifies SMS variables (see documentation for their meanings)
	$transport->vars = new ODSubmission_TransportVars;
	$transport->vars->Var = array();
	$transport->vars->Var[0] = CreateValue('Subject', 'Sample SMS');
	$transport->vars->Var[1] = CreateValue('FromName', 'John DOE');
	$transport->vars->Var[2] = CreateValue('SMSNumber', '+33672335425');
	$transport->vars->Var[3] = CreateValue('Message', 'Everything\'s OK');

	// Submit the complete transport description to the Application Server
	$result = $submissionService->SubmitTransport($transport);
	if($ex = $submissionService->soapException)
	{		
		Console_Writeline('Call to SubmitTransport() failed with message: ' . $ex->Message);
		return;
	} 

	Console_Writeline('Request submitted with transportID ' . $result->transportID);


	//////////////////////////////////////////////////////////////////////
	// STEP #4 : SMS tracking
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
	$queryService->QueryHeaderValue->recipientType = "SMS";

	// Build a request on the newly submitted fax transport using its unique identifier
	// We also specify the variables (attributes) we want to retrieve.
	$request = new ODQuery_QueryRequest;
	$request->nItems = 1;
	$request->attributes = 'State,ShortStatus,CompletionDateTime';
	$request->filter = '(ruidex=' . $result->transportID . ')';

	Console_Writeline('Checking for your SMS status...');

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
					
			Console_Writeline('SMS pending...');
		}
		else
		{
			Console_Writeline('Error !! SMS not found in database');
			return;
		}

		// Wait 5 seconds, then try again...
		sleep(5);

	}

	if( $state == 100 )
	{
		Console_Writeline('SMS successfully sent at ' . $date);
	}
	else
		Console_Writeline('SMS failed at ' . $date . ', reason: ' . $status);


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
