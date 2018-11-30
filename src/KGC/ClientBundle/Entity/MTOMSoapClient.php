<?php
namespace KGC\ClientBundle\Entity;

class MTOMSoapClient extends \SoapClient
{
    public function __doRequest($request, $location, $action, $version, $one_way = 0) {
		$response = parent::__doRequest($request, $location, $action, $version, $one_way);
		//if resposnse content type is mtom strip away everything but the xml.
		if (strpos($response, "Content-Type: application/xop+xml") !== false) {
			//not using stristr function twice because not supported in php 5.2 as shown below
			//$response = stristr(stristr($response, "<soap:"), "</soap:Envelope>", true) . "</soap:Envelope>";
			$tempstr = stristr($response, "<soap:");
			$response = substr($tempstr, 0, strpos($tempstr, "</soap:Envelope>")) . "</soap:Envelope>";
		}
		//log_message($response);
		return $response;
	}
}