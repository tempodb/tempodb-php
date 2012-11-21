<?php

class TempoDBTest extends PHPUnit_Framework_TestCase {

    protected $client;

    protected function setUp() {
        $this->client = new TempoDB('key', 'secret', 'example.com', 443, true);
        $this->client->curl = $this->getMock('HttpRequest');
        date_default_timezone_set("UTC");
    }

    public function testInit() {
        $client = new TempoDB('key', 'secret', 'example.com', 443, true);

        $this->assertEquals($client->key, 'key');
        $this->assertEquals($client->secret, 'secret');
        $this->assertEquals($client->host, 'example.com');
        $this->assertEquals($client->port, 443);
        $this->assertEquals($client->secure, true);
    }

    public function testDefaults() {
        $client = new TempoDB('key', 'secret');

        $this->assertEquals($client->host, 'api.tempo-db.com');
        $this->assertEquals($client->port, 443);
        $this->assertEquals($client->secure, true);
    }

    public function testGetSeries() {
        $body = '[{
            "id": "id",
            "key": "key",
            "name": "name",
            "tags": ["tag1", "tag2"],
            "attributes": {"key1": "value1"}
        }]';
        $this->expectRequest("https://example.com:443/v1/series/", 200, $body);
        $series = $this->client->get_series();
        $expected = array(new Series('id', 'key', 'name', array('tag1', 'tag2'), array('key1' => 'value1')));
        $this->assertEquals($series, $expected);
    }

    private function expectRequest($url, $response_code, $returns) {
        $this->client->curl->expects($this->once())
                           ->method('setUrl')
                           ->with($this->equalTo($url));
        $this->client->curl->expects($this->once())
                           ->method('execute')
                           ->will($this->returnValue($returns));
        $this->client->curl->expects($this->once())
                           ->method('getInfo')
                           ->with(CURLINFO_HTTP_CODE)
                           ->will($this->returnValue($response_code));
    }

    public function testReadId() {
        $body = '{
            "series": {
                "id": "id",
                "key": "key",
                "name": "",
                "tags": [],
                "attributes": {}
            },
            "start": "2012-03-27T00:00:00.000",
            "end": "2012-03-28T00:00:00.000",
            "data": [{"t": "2012-03-27T00:00:00.000", "v": 12.34}],
            "summary": {}
        }';

        $start = new DateTime("2012-03-27");
        $end = new DateTime("2012-03-28");

        $url = 'https://example.com:443/v1/series/id/id/data/?start=2012-03-27T00%3A00%3A00%2B00%3A00&end=2012-03-28T00%3A00%3A00%2B00%3A00&interval=&function=&tz=';
        $this->expectRequest($url, 200, $body);
        $dataset = $this->client->read_id("id", $start, $end);
        $expected = new DataSet(new Series('id', 'key'), $start, $end, array(new DataPoint($start, 12.34)), new Summary());
        $this->assertEquals($dataset, $expected);
    }
}

?>
