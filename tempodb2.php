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


class DataPoint {

    function __construct($ts, $value) {
        $this->ts = $ts;
        $this->value = $value;
    }

    function to_json() {
        $json = array(
            "t" => $this->ts->format("c"),
            "v" => $this->value
        );
        return $json;
    }

    static function from_json($json) {
        $ts = isset($json["t"]) ? new DateTime($json["t"]) : NULL;
        $value = isset($json["v"]) ? $json["v"] : NULL;
        return new DataPoint($ts, $value);
    }
}


class DataSet {

    function __construct($series, $start, $end, $data=array(), $summary=NULL) {
        $this->series = $series;
        $this->start = $start;
        $this->end = $end;
        $this->data = $data;
        $this->summary = $summary;
    }

    function to_json() {
        $json = array(
            "series" => $this->series->to_json(),
            "start" => $this->start->format("c"),
            "end" => $this->end->format("c"),
            "data" => array_map(array($this, $this->to_json()), $json[0]),
            "summary" => isset($summary) ? $summary->to_json : array()
        );
        return $json;
    }

    static function from_json($json) {
        $series = isset($json["series"]) ? Series::from_json($json["series"]) : NULL;
        $start = isset($json["start"]) ? new DateTime($json["start"]) : NULL;
        $end = isset($json["end"]) ? new DateTime($json["end"]) : NULL;
        $data = isset($json["data"]) ? array_map("DataPoint::from_json", $json["data"]) : array();
        $summary = isset($json["summary"]) ? Summary::from_json($json["summary"]) : NULL;
        return new DataSet($series, $start, $end, $data, $summary);
    }
}


class Summary {
    private $data = array();

    public function __get($member) {
        if (isset($this->data[$member])) {
            return $this->data[$member];
        }
        return null;
    }

    public function __isset($member) {
        return isset($this->data[$member]);
    }

    public function __set($member, $value) {
        $this->data[$member] = $value;
    }

    function to_json() {
        return $data;
    }

    static function from_json($json) {
        $summary = new Summary();
        $data = isset($json) ? $json : array();

        foreach ($data as $key => $value) {
            $summary->__set($key, $value);
        }
        return $summary;
    }
}

class TempoDB {
    const API_HOST = "api.tempo-db.com";
    const API_PORT = 443;
    const API_VERSION = "v1";

    function __construct($key, $secret, $host=self::API_HOST, $port=self::API_PORT, $secure=true) {
        $this->key = $key;
        $this->secret = $secret;
        $this->host = $host;
        $this->port = $port;
        $this->secure = $secure;
    }

    function get_series() {
        $json = $this->request("/series/");
        return array_map("Series::from_json", $json[0]);
    }

    function read_id($series_id, $start, $end, $interval=NULL, $function=NULL) {
        $series_type = "id";
        $series_val = $series_id;
        return $this->_read($series_type, $series_val, $start, $end, $interval, $function);
    }

    function read_key($series_key, $start, $end, $interval=NULL, $function=NULL) {
        $series_type = "key";
        $series_val = $series_key;
        return $this->_read($series_type, $series_val, $start, $end, $interval, $function);
    }

    private function _read($series_type, $series_val, $start, $end, $interval=NULL, $function=NULL) {
        // send GET request, formatting dates in ISO 8601
        $params = array(
            "start" => $start->format("c"),
            "end" => $end->format("c"),
            "interval" => $interval,
            "function" => $function);

        $url = "/series/" . $series_type . "/" . $series_val . "/data/";
        $json = $this->request($url, "GET", $params);
        return DataSet::from_json($json[0]);
    }

    private function request($target, $method="GET", $params=array()) {
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

    private function build_full_url($target, $params=array()) {
        $port = $this->port == 80 ? "" : ":" . $this->port;
        $protocol = $this->secure ? "https://" : "http://";
        $base_full_url = $protocol . $this->host . $port;
        return $base_full_url . $this->build_url($target, $params);
    }

    private function build_url($url, $params=array()) {
        $target_path = $url;

        if (empty($params)) {
            return "/" . self::API_VERSION . $target_path;
        }
        else {
            return "/" . self::API_VERSION . $target_path . "?" . $this->urlencode($params);
        }
    }

    private function urlencode($params) {
        return http_build_query($params, null, '&');
    }
}

?>
