<?php
class TempoDB {
    const API_SERVER = "api.tempo-db.com";
    const API_VERSION = "v1";

    protected $api_key;
    protected $api_secret;
    protected $http_req;

    function __construct($api_key, $api_secret, $api_server=self::API_SERVER, $api_version=self::API_VERSION) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->api_server = $api_server;
        $this->api_version = $api_version;
        $this->http_req = new HTTPReq($api_key, $api_secret, $api_server, $api_version);
    }

    function getAPIServer() {
        return "https://".$this->api_server."/".$this->api_version;
    }

    function read_id($series_id, $start, $end, $interval=NULL, $function=NULL) {
        $series_type = "id";
        $series_val = $series_id;
        return $this->read($series_type, $series_val, $start, $end, $interval, $function);
    }

    function read_key($series_key, $start, $end, $interval=NULL, $function=NULL) {
        $series_type = "key";
        $series_val = $series_key;
        return $this->read($series_type, $series_val, $start, $end, $interval, $function);
    }

    function read($series_type, $series_val, $start, $end, $interval=NULL, $function=NULL) {
        // send GET request, formatting dates in ISO 8601
        $params = array(
            "start" => $start->format("c"),
            "end" => $end->format("c"),
            "interval" => $interval,
            "function" => $function);

        $querystring = http_build_query($params, null, '&');

        return $this->http_req->jsonGetReq($this->getAPIServer()."/series/".$series_type."/".$series_val."/data/?".$querystring);
    }

    function write_id($series_id, $data) {
        $series_type = "id";
        $series_val = $series_id;
        return $this->write($series_type, $series_val, $data);
    }

    function write_key($series_key, $data) {
        $series_type = "key";
        $series_val = $series_key;
        return $this->write($series_type, $series_val, $data);
    }

    function write($series_type, $series_val, $data) {
        // send POST request, formatting dates in ISO 8601
        return $this->http_req->jsonPostReq($this->getAPIServer()."/series/".$series_type."/".$series_val."/data/", $data);
    }

    function write_bulk($data) {
        // send POST request, formatting dates in ISO 8601
        return $this->http_req->jsonPostReq($this->getAPIServer()."/data/", $data);
    }

}

class HTTPReq {
    const GET = 'GET';
    const POST = 'POST';

    protected $api_key;
    protected $api_secret;

    function __construct($api_key, $api_secret, $api_server, $api_version) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->api_server = $api_server;
        $this->api_version = $api_version;
    }

    function req($method, $path, $body=NULL) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
        curl_setopt($ch, CURLOPT_USERPWD, $this->api_key . ":" . $this->api_secret);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // if body supplied, likely doing a POST
        if ($body) {
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

    function jsonReq($method, $path, $data=NULL) {
        $json = json_encode($data);
        $ret = self::req($method, $path, $json);
        $ret[0] = json_decode($ret[0], TRUE);
        return $ret;
    }

    function jsonGetReq($path) {
        return self::jsonReq(self::GET, $path);
    }

    function jsonPostReq($path, $data) {
        return self::jsonReq(self::POST, $path, $data);
    }
}
?>
