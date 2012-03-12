<?php

class Series {

    function __construct($id, $key, $name="", $tags=array(), $attributes=array()) {
        $this->id = $id;
        $this->key = $key;
        $this->name = $name;
        $this->tags = $tags;
        $this->attributes = $attributes;
    }

    function to_json() {
        $json = array(
            "id" => $this->id,
            "key" => $this->key,
            "name" => $this->name,
            "tags" => $this->tags,
            "attributes" => $this->attributes
        );
        return $json;
    }

    static function from_json($json) {
        $id = isset($json["id"]) ? $json["id"] : "";
        $key = isset($json["key"]) ? $json["key"] : "";
        $name = isset($json["name"]) ? $json["name"] : "";
        $tags = isset($json["tags"]) ? $json["tags"] : array();
        $attributes = isset($json["attributes"]) ? $json["attributes"] : array();
        return new Series($id, $key, $name, $tags, $attributes);
    }
}


class TempoDB {
    const API_HOST = "api.tempo-db.com";
    const API_PORT = 443;
    const API_VERSION = "v1";

    protected $api_key;
    protected $api_secret;
    protected $http_req;

    function __construct($key, $secret, $host=self::API_HOST, $port=self::API_PORT, $secure=true) {
        $this->key = $key;
        $this->secret = $secret;
        $this->host = $host;
        $this->port = $port;
        $this->secure = $secure;

        $this->http_req = new HTTPReq($key, $secret, $host, self::API_VERSION);
    }

    function getAPIServer() {
        return "https://".$this->host."/".self::API_VERSION;
    }

    function get_series() {
        $json = $this->request("/series/");
        return array_map("Series::from_json", $json[0]);
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

    function request($target, $method="GET", $params=array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERPWD, $this->key . ":" . $this->secret);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($this->secure) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
        }

        if ($method == "POST") {
            $path = $this->build_full_url($target);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            $headers = array(
                'Content-Length: ' . strlen($body),
                'Content-Type: application/json',
            );

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        else if ($method == "PUT") {
            $path = $this->build_full_url($target);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            $headers = array(
                'Content-Length: ' . strlen($body),
                'Content-Type: application/json',
            );

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        else {
            $path = $this->build_full_url($target, $params);
        }

        curl_setopt($ch, CURLOPT_URL, $path);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array(json_decode($response, true), $http_code);
    }

    function build_full_url($target, $params=array()) {
        $port = $this->port == 80 ? "" : ":" . $this->port;
        $protocol = $this->secure ? "https://" : "http://";
        $base_full_url = $protocol . $this->host . $port;
        return $base_full_url . $this->build_url($target, $params);
    }

    function build_url($url, $params=array()) {
        $target_path = $url;

        if (empty($params)) {
            return "/" . self::API_VERSION . $target_path;
        }
        else {
            return "/" . self::API_VERSION . $target_path . "?" . $this->urlencode($params);
        }
    }

    function urlencode($params) {
        return http_build_query($params, null, '&');
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
