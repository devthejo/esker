Esker On Demand/FlyDoc Web Services for PHP

Thank you for downloading the Esker On Demand/FlyDoc Web Services for PHP application sample.
This download contains code designed to help you begin using Esker On Demand/FlyDoc Web Services.
This sample is provided as-is.

REQUIREMENTS

- PHP 5.3 or higher

CONTENTS

This sample contains:
    
    index.php                (PHP samples menu)
    sendfax.php              (Sample of fax sending)
    sendsmtp.php             (Sample of Email sending)
    sendsms.php              (Sample of SMS sending)
    sendmailondemand.php     (Sample of Mail sending)
    sessionservice.php       (Proxy classes for Esker SessionService Web Service)
    submissionservice.php    (Proxy classes for Esker SubmissionService Web Service)
    queryservice.php         (Proxy classes for Esker QueryService Web Service)
    data\azur1023.txt        (Required file for executing the samples)
    data\sample.pdf          (Required file for executing the samples)
    data\cover.rtf           (Required file for executing the samples)
    wsdl\*.wsdl              (WSDL files used by the Proxy classes)

To obtain the wsdl files for your application, refer to the Esker On Demand/FlyDoc Web Services documentation.

DOCUMENTATION & SUPPORT

Documentation is available at: http://doc.esker.com/eskerondemand/cv_ly/en/webservices/

This sample is provided as-is.

RUNNING THE SAMPLE APPLICATION

1. Install and configure PHP on your web server (Important: with safe_mode disabled if applicable)
2. Update the php.ini file

    Change the following parameter (Default 30) :

    max_execution_time = 300     ; Maximum execution time of each script, in seconds

    This change is required for executing the samples as we wait for documents to
    be completed on the server. This loop can take a few minutes. This is not required if
    you don't need to wait for documents to be completed in your PHP scrips.
 
3. Install and configure PHP/CURL (required for HTTPS)

    Remove the semicolon from the following line in the php.ini file:

	;extension=php_curl.dll

    libcurl is statically linked in, but the SSL libraries are not. The two SSL-related DLLs 
    from the OpenSSL project (libeay32.dll and ssleay32.dll) are bundled with the Windows PHP 
    package. You must copy libeay32.dll and ssleay32.dll from the DLL folder of the PHP/ binary 
    package to the SYSTEM folder of your Windows machine. (Ex: C:\WINNT\SYSTEM32 or 
    C:\WINDOWS\SYSTEM). 

To test your installation run the following command in the php install directory:

php.exe -i

if you get any warning messages check the following:

    * The extensions directory has not been set correctly in the php.ini file 
      to fix it - make sure there is the following line in your php.ini file 
      extension_dir="c:\php\extensions\" (or the relevant directory string) 
    * Make sure the php_curl.dll file is in that directory. Note: the php_curl.dll 
      is the PHP/CURL binding DLL and is included in the binary PHP download package 
      for Windows.
    * Also make sure that the files necessary for curl to run are in the SYSTEM folder 
      if your Windows machine. (Ex: C:\WINNT\SYSTEM32 or C:\WINDOWS\SYSTEM).     

4. Edit the sample files (sendfax.php, sendsmtp.php, sendsms.php and sendmailondemand.php):

    In all sample files:
    
    Change the value of the $m_Username variable with your Esker On Demand/FlyDoc Username.
    Change the value of the $m_Password variable with your Esker On Demand/FlyDoc Password.

    sendfax.php - Change the following fax number with your fax number:
    $transport->vars[1] = CreateValue('FaxNumber', '+33472834697');

    sendsmtp.php - Change the following email address with your email:
    $transport->vars[1] = CreateValue('EmailAddress', 'customer@someplace.net');

    sendsms.php - Change the following mobile number with your mobile number:
    $transport->vars[2] = CreateValue('SMSNumber', '+33672335425');

    sendmailondemand.php - Change the following postal address with your address:
    $transport->vars[3] = CreateValue('ToBlockAddress', 'ADERTY firm' .  chr(10) . 'Jaco Aderti' .  chr(10) . '17 Bella Villa Roma' .  chr(10) . '12666 Querbo' .  chr(10) . 'FRANCE');


5. Create a Virtual Directory or make you web server points to the sample folder.
6. Run the main sample file (index.php) from your browser.	
