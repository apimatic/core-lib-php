<?php

namespace CoreLib\Tests\Core;

use CoreLib\Tests\Mocking\Other\MockClass;
use CoreLib\Utils\XmlDeserializer;
use CoreLib\Utils\XmlSerializer;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    public function testXmlSerialization()
    {
        $xmlSerializer = new XMLSerializer(['formatOutput' => true]);
        $res = $xmlSerializer->serialize('mockClass', new MockClass([34,'asad']));
        $this->assertEquals("<?xml version=\"1.0\"?>\n" .
            "<mockClass attr=\"this is attribute\">\n" .
            "  <body>34</body>\n" .
            "  <body>asad</body>\n" .
            "  <new1>this is new</new1>\n" .
            "  <new2>\n" .
            "    <entry key=\"key1\">val1</entry>\n" .
            "    <entry key=\"key2\">val2</entry>\n" .
            "  </new2>\n" .
            "</mockClass>\n", $res);
    }

    public function testXmlDeSerialization()
    {
        $xmlDeSerializer = new XmlDeserializer();
        $input = "<?xml version=\"1.0\"?>\n" .
            "<mockClass attr=\"this is attribute\">\n" .
            "  <body>34</body>\n" .
            "  <body>asad</body>\n" .
            "  <new1>this is new</new1>\n" .
            "  <new2>\n" .
            "    <entry key=\"key1\">val1</entry>\n" .
            "    <entry key=\"key2\">val2</entry>\n" .
            "  </new2>\n" .
            "</mockClass>\n";
        $res = $xmlDeSerializer->deserialize($input, 'mockClass', MockClass::class);
        $this->assertInstanceOf(MockClass::class, $res);
        $this->assertEquals(
            ["34","asad", "this is new", ["key1" => "val1", "key2" => "val2"], "this is attribute"],
            $res->body
        );
    }
}
