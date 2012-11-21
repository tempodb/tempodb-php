<?php

require("HttpRequest.php");

class CurlRequest implements HttpRequest
{
    private $handle = null;

    public function __construct() {
        $this->handle = curl_init();
    }

    public function setUrl($url) {
        curl_setopt($this->handle, CURLOPT_URL, $path);
    }

    public function setOption($name, $value) {
        curl_setopt($this->handle, $name, $value);
    }

    public function execute() {
        return curl_exec($this->handle);
    }

    public function getInfo($name) {
        return curl_getinfo($this->handle, $name);
    }

    public function close() {
        curl_close($this->handle);
    }
}
