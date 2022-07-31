<?php

namespace CoreLib\Tests\Core;

use CoreLib\Tests\Mocking\Other\MockClass;
use CoreLib\Utils\CoreHelper;
use CoreLib\Utils\XmlDeserializer;
use CoreLib\Utils\XmlSerializer;
use Exception;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    public function testXmlSerialization()
    {
        $xmlSerializer = new XMLSerializer(['formatOutput' => true]);
        $res = $xmlSerializer->serialize('mockClass', new MockClass([34, 'asad']));
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

    public function testXmlDeserialization()
    {
        $xmlDeSerializer = new XmlDeserializer();
        $input = "<?xml version=\"1.0\"?>\n<root>23</root>";
        $res = $xmlDeSerializer->deserialize($input, 'root', 'int');
        $this->assertEquals(23, $res);
        $res = $xmlDeSerializer->deserialize($input, 'root', '?int');
        $this->assertEquals(23, $res);
        $input = "<?xml version=\"1.0\"?>\n<root>true</root>";
        $res = $xmlDeSerializer->deserialize($input, 'root', 'bool');
        $this->assertEquals(true, $res);
        $input = "<?xml version=\"1.0\"?>\n<root>2.3</root>";
        $res = $xmlDeSerializer->deserialize($input, 'root', 'float');
        $this->assertEquals(2.3, $res);

        $input = "<?xml version=\"1.0\"?>\n<root></root>";
        $res = $xmlDeSerializer->deserialize($input, 'abc', '?int');
        $this->assertNull($res);
        $res = $xmlDeSerializer->deserializeToArray($input, 'abc', 'item', '?float');
        $this->assertNull($res);
        $res = $xmlDeSerializer->deserializeToMap($input, 'abc', '?float');
        $this->assertNull($res);
    }

    public function testXmlDeserializationFailure1()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Required value not found at XML path "/root[1]" during deserialization.');

        $xmlDeSerializer = new XmlDeserializer();
        $input = "<?xml version=\"1.0\"?>\n<abc>23</abc>";
        $xmlDeSerializer->deserialize($input, 'root', 'int');
    }

    public function testXmlDeserializationFailure2()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Expected value of type "bool" but got value "2.3" at XML path ' .
            '"/root" during deserialization.');

        $xmlDeSerializer = new XmlDeserializer();
        $input = "<?xml version=\"1.0\"?>\n<root>2.3</root>";
        $xmlDeSerializer->deserialize($input, 'root', 'bool');
    }

    public function testCoreHelperDeserialize()
    {
        $input = '{"key": "my value"}';
        $res = CoreHelper::deserialize($input);
        $this->assertIsArray($res);
        $this->assertEquals("my value", $res['key']);
    }
}
