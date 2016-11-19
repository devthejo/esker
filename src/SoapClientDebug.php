<?php
namespace Esker;
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