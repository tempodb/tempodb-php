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
        $this->expectRequest("https://example.com:443/v1/series/", 'GET', 200, $body);
        $series = $this->client->get_series();
        $expected = array(new Series('id', 'key', 'name', array('tag1', 'tag2'), array('key1' => 'value1')));
        $this->assertEquals($series, $expected);
    }

    public function testCreateSeries() {
        $url = 'https://example.com:443/v1/series/';
        $body = '{"key":"my-key.tag1.1"}';
        $returns = '{
            "id": "id",
            "key": "my-key.tag1.1",
            "name": "",
            "tags": ["my-key", "tag1"],
            "attributes": {}
        }';

        $this->expectRequestWithBody($url, 'POST', $body, 200, $returns);
        $series = $this->client->create_series("my-key.tag1.1");
        $expected = new Series('id', 'my-key.tag1.1', '', array('my-key', 'tag1'), array());
        $this->assertEquals($series, $expected);
    }

    public function testDeleteSeries() {
        $url = 'https://example.com:443/v1/series/?tag=delete_me';
        $returns = '{"deleted":2}';

        $this->expectRequest($url, 'DELETE', 200, $returns);
        $summary = $this->client->delete_series(array("tags" => array("delete_me")));
        $expected = new DeleteSummary(2);
        $this->assertEquals($summary, $expected);
    }

    public function testUpdateSeries() {
        $update = new Series('id', 'key', 'name', array('tag1'), array('key1' => 'value1'));
        $url = 'https://example.com:443/v1/series/id/id/';
        $body = json_encode(Series::to_json($update));
        $returns = json_encode(Series::to_json($update));

        $this->expectRequestWithBody($url, 'PUT', $body, 200, $returns);
        $series = $this->client->update_series($update);
        $this->assertEquals($series, $update);
    }

    public function testReadId() {
        $url = 'https://example.com:443/v1/series/id/id/data/?start=2012-03-27T00%3A00%3A00%2B00%3A00&end=2012-03-28T00%3A00%3A00%2B00%3A00&interval=&function=&tz=';
        $returns = '{
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

        $this->expectRequest($url, 'GET', 200, $returns);
        $dataset = $this->client->read_id("id", $start, $end);
        $expected = new DataSet(new Series('id', 'key'), $start, $end, array(new DataPoint($start, 12.34)), new Summary());
        $this->assertEquals($dataset, $expected);
    }

    public function testReadKey() {
        $url = 'https://example.com:443/v1/series/key/key/data/?start=2012-03-27T00%3A00%3A00%2B00%3A00&end=2012-03-28T00%3A00%3A00%2B00%3A00&interval=&function=&tz=';
        $returns = '{
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

        $this->expectRequest($url, 'GET', 200, $returns);
        $dataset = $this->client->read_key("key", $start, $end);
        $expected = new DataSet(new Series('id', 'key'), $start, $end, array(new DataPoint($start, 12.34)), new Summary());
        $this->assertEquals($dataset, $expected);
    }

    public function testRead() {
        $url = 'https://example.com:443/v1/data/?start=2012-03-27T00%3A00%3A00%2B00%3A00&end=2012-03-28T00%3A00%3A00%2B00%3A00&key=key1';
        $returns = '[{
            "series": {
                "id": "id",
                "key": "key1",
                "name": "",
                "tags": [],
                "attributes": {}
            },
            "start": "2012-03-27T00:00:00.000",
            "end": "2012-03-28T00:00:00.000",
            "data": [{"t": "2012-03-27T00:00:00.000", "v": 12.34}],
            "summary": {}
        }]';

        $start = new DateTime("2012-03-27");
        $end = new DateTime("2012-03-28");

        $this->expectRequest($url, 'GET', 200, $returns);
        $dataset = $this->client->read($start, $end, array('keys' => 'key1'));
        $expected = array(new DataSet(new Series('id', 'key1'), $start, $end, array(new DataPoint($start, 12.34)), new Summary()));
        $this->assertEquals($dataset, $expected);
    }

    public function testWriteId() {
        $url = 'https://example.com:443/v1/series/id/id1/data/';
        $body = '[{"t":"2012-03-27T00:00:00+00:00","v":12.34}]';
        $returns = '';

        $this->expectRequestWithBody($url, 'POST', $body, 200, $returns);
        $this->client->write_id("id1", array(new DataPoint(new DateTime("2012-03-27"), 12.34)));
    }

    public function testWriteKey() {
        $url = 'https://example.com:443/v1/series/key/key1/data/';
        $body = '[{"t":"2012-03-27T00:00:00+00:00","v":12.34}]';
        $returns = '';

        $this->expectRequestWithBody($url, 'POST', $body, 200, $returns);
        $this->client->write_key("key1", array(new DataPoint(new DateTime("2012-03-27"), 12.34)));
    }

    public function testWriteBulk() {
        $url = 'https://example.com:443/v1/data/';
        $data = array(
            array('id' => '01868c1a2aaf416ea6cd8edd65e7a4b8', 'v' => 4.164),
            array('id' => '38268c3b231f1266a392931e15e99231', 'v' => 73.13),
            array('key' => 'your-custom-key', 'v' => 55.423),
            array('key' => 'foo', 'v' => 324.991),
        );
        $body = '{"t":"2012-03-27T00:00:00+00:00","data":' . json_encode($data) . '}';
        $returns = '';

        $this->expectRequestWithBody($url, 'POST', $body, 200, $returns);
        $this->client->write_bulk(new DateTime("2012-03-27"), $data);
    }

    public function testIncrementId() {
        $url = 'https://example.com:443/v1/series/id/id1/increment/';
        $body = '[{"t":"2012-03-27T00:00:00+00:00","v":1}]';
        $returns = '';

        $this->expectRequestWithBody($url, 'POST', $body, 200, $returns);
        $this->client->increment_id("id1", array(new DataPoint(new DateTime("2012-03-27"), 1)));
    }

    public function testIncrementKey() {
        $url = 'https://example.com:443/v1/series/key/key1/increment/';
        $body = '[{"t":"2012-03-27T00:00:00+00:00","v":2}]';
        $returns = '';

        $this->expectRequestWithBody($url, 'POST', $body, 200, $returns);
        $this->client->increment_key("key1", array(new DataPoint(new DateTime("2012-03-27"), 2)));
    }

    public function testIncrementBulk() {
        $url = 'https://example.com:443/v1/increment/';
        $data = array(
            array('id' => '01868c1a2aaf416ea6cd8edd65e7a4b8', 'v' => 4),
            array('id' => '38268c3b231f1266a392931e15e99231', 'v' => 2),
            array('key' => 'your-custom-key', 'v' => 2),
            array('key' => 'foo', 'v' => 2),
        );
        $body = '{"t":"2012-03-27T00:00:00+00:00","data":' . json_encode($data) . '}';
        $returns = '';

        $this->expectRequestWithBody($url, 'POST', $body, 200, $returns);
        $this->client->increment_bulk(new DateTime("2012-03-27"), $data);
    }

    public function testDeleteId() {
        $url = 'https://example.com:443/v1/series/id/id1/data/?start=2012-03-27T00%3A00%3A00%2B00%3A00&end=2012-03-28T00%3A00%3A00%2B00%3A00';
        $returns = '';

        $start = new DateTime("2012-03-27");
        $end = new DateTime("2012-03-28");

        $this->expectRequest($url, 'DELETE', 200, $returns);
        $dataset = $this->client->delete_id("id1", $start, $end);
    }

    public function testDeleteKey() {
        $url = 'https://example.com:443/v1/series/key/key1/data/?start=2012-03-27T00%3A00%3A00%2B00%3A00&end=2012-03-28T00%3A00%3A00%2B00%3A00';
        $returns = '';

        $start = new DateTime("2012-03-27");
        $end = new DateTime("2012-03-28");

        $this->expectRequest($url, 'DELETE', 200, $returns);
        $dataset = $this->client->delete_key("key1", $start, $end);
    }


    private function expectRequest($url, $method, $response_code, $returns) {
        $this->client->curl->expects($this->once())
                           ->method('setUrl')
                           ->with($this->equalTo($url));
        $this->client->curl->expects($this->once())
                           ->method('setMethod')
                           ->with($this->equalTo($method));
        $this->client->curl->expects($this->once())
                           ->method('execute')
                           ->will($this->returnValue($returns));
        $this->client->curl->expects($this->once())
                           ->method('getInfo')
                           ->with(CURLINFO_HTTP_CODE)
                           ->will($this->returnValue($response_code));
    }

    private function expectRequestWithBody($url, $method, $body, $response_code, $returns) {
        $this->expectRequest($url, $method, $response_code, $returns);
        $this->client->curl->expects($this->once())
                           ->method('setBody')
                           ->with($this->equalTo($body));
    }
}

?>
