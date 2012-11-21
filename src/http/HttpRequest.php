<?php

interface HttpRequest {
    public function setOption($name, $value);
    public function setUrl($url);
    public function execute();
    public function getInfo($name);
    public function close();
}
