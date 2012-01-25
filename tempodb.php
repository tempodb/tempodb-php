<?php
class TempoDB
{
	const API_SERVER = "api.tempo-db.com";
	const API_VERSION = "v1";

	protected $api_key;
	protected $api_secret;
	protected $http_req;

	function __construct($api_key, $api_secret, $api_server=self::API_SERVER, $api_version=self::API_VERSION)
    {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->api_server = $api_server;
        $this->api_version = $api_version;
        $this->http_req = new HTTPReq($api_key, $api_secret, $api_server, $api_version);
    }

    function getAPIServer()
    {
    	return "http://".$this->api_server."/".$this->api_version;
    }

    function range($start, $end, $series_id=NULL, $series_name=NULL)
    {
    	// must provide either $series_id or $series_name
        $series_type = NULL;
    	$series_val = NULL;

    	if ($series_id) {
    		$series_type = "id";
    		$series_val = $series_id;
    	}
    	elseif ($series_name) {
    		$series_type = "name";
    		$series_val = $series_name;
    	}
    	else {
    		// TODO: throw error
    	}

        // send GET request, formatting dates in ISO 8601
    	return $this->http_req->jsonGetReq($this->getAPIServer()."/series/".$series_type."/".$series_val."/data/?start=".$start->format("c")."&end=".$end->format("c"));
    }

    function add($data, $series_id=NULL, $series_name=NULL)
    {
        // must provide either $series_id or $series_name
    	$series_type = NULL;
    	$series_val = NULL;

    	if ($series_id) {
    		$series_type = "id";
    		$series_val = $series_id;
    	}
    	elseif ($series_name) {
    		$series_type = "name";
    		$series_val = $series_name;
    	}
    	else {
    		// TODO: throw error
    	}

        // send POST request, formatting dates in ISO 8601
    	return $this->http_req->jsonPostReq($this->getAPIServer()."/series/".$series_type."/".$series_val."/data/", $data);
    }

}

class HTTPReq
{
	const GET = 'GET';
	const POST = 'POST';

	protected $api_key;
	protected $api_secret;

	function __construct($api_key, $api_secret, $api_server, $api_version)
    {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->api_server = $api_server;
        $this->api_version = $api_version;
    }

	function req($method, $path, $body=NULL)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $path);
		curl_setopt($ch, CURLOPT_USERPWD, $this->api_key . ":" . $this->api_secret);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // if body supplied, likely doing a POST
		if ($body)
		{
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			$headers = array(
				'Content-Length: ' . strlen($body),
				'Content-Type: application/json',
			);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		}

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);
		return array($response, $http_code);
	}

	function jsonReq($method, $path, $data=NULL)
	{
		$json = json_encode($data);
		$ret = self::req($method, $path, $json);
		$ret[0] = json_decode($ret[0], TRUE);
		return $ret;
	}

	function jsonGetReq($path)
	{
		return self::jsonReq(self::GET, $path);
	}

	function jsonPostReq($path, $data)
	{
		return self::jsonReq(self::POST, $path, $data);
	}
}
?>
