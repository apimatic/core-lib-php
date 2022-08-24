<?php

namespace CoreLib\Tests;

use CoreLib\Tests\Mocking\Other\MockClass;
use CoreLib\Utils\CoreHelper;
use CoreLib\Utils\DateHelper;
use CoreLib\Utils\XmlDeserializer;
use CoreLib\Utils\XmlSerializer;
use DateTime;
use Exception;
use InvalidArgumentException;
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

    public function testCoreHelperValidateUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Url format.');
        CoreHelper::validateUrl('some/invalid/url/format');
    }

    public function testCoreHelperCheckValueOrValuesInList()
    {
        $list = ['string', 'int', 'float', '1'];
        $this->assertTrue(CoreHelper::checkValueOrValuesInList(null, $list));

        $this->assertFalse(CoreHelper::checkValueOrValuesInList(1, $list));
        $this->assertTrue(CoreHelper::checkValueOrValuesInList('float', $list));

        $this->assertFalse(CoreHelper::checkValueOrValuesInList(['int', 'unknown'], $list));
        $this->assertTrue(CoreHelper::checkValueOrValuesInList(['float', 'int'], $list));

        $this->assertFalse(CoreHelper::checkValueOrValuesInList(['int', ['float','unknown']], $list));
        $this->assertTrue(CoreHelper::checkValueOrValuesInList(['float', ['int', 'string']], $list));
    }

    public function testCoreHelperClone()
    {
        $mockClass = new MockClass([]);
        $list = ['some string', 1, [false, $mockClass]];

        $newList = $list;
        $this->assertEquals($list, $newList);
        $newList[2][1]->addAdditionalProperty('real', 214);
        $this->assertEquals($list, $newList);

        $clonedList = CoreHelper::clone($list);
        $this->assertEquals($list, $clonedList);
        $clonedList[2][1]->addAdditionalProperty('newValue', 12);
        $list[2][1]->addAdditionalProperty('real2', 14);
        $this->assertNotEquals($list, $clonedList);
        $this->assertEquals(
            '["some string",1,[false,{"body":[],"real":214,"real2":14}]]',
            CoreHelper::serialize($list)
        );
        $this->assertEquals(
            '["some string",1,[false,{"body":[],"real":214,"newValue":12}]]',
            CoreHelper::serialize($clonedList)
        );
    }

    public function testFromSimpleDateFailure()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Incorrect format.');
        DateHelper::fromSimpleDate('---');
    }

    public function testFromRFC1123DateFailure()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Incorrect format.');
        DateHelper::fromRfc1123DateTime('---');
    }

    public function testFromRFC3339DateFailure()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Incorrect format.');
        DateHelper::fromRfc3339DateTime('---');
    }

    public function testFromUnixDateFailure()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Incorrect format.');
        DateHelper::fromUnixTimestamp('-0-');
    }

    public function testFromSimpleDateString()
    {
        $this->assertNull(DateHelper::fromSimpleDateMapOfArray(null));
        $this->assertNull(DateHelper::fromSimpleDateArrayOfMap(null));
        $res = DateHelper::fromSimpleDateMapOfArray((object) [
            'A' => ['2021-10-01', '2021-09-30'],
            'B' => [null, '2021-09-29'],
            'C' => null]);
        $this->assertEquals([
            'A' => ['2021-10-01', '2021-09-30'],
            'B' => [null, '2021-09-29'],
            'C' => null], DateHelper::toSimpleDate2DArray($res));
        $res = DateHelper::fromSimpleDateArrayOfMap([
            (object)['key1' => '2021-10-01', 'key2' => '2021-09-30'],
            (object)['keyA' => null, 'keyB' => '2021-09-29'],
            null]);
        $this->assertEquals([
            ['key1' => '2021-10-01', 'key2' => '2021-09-30'],
            ['keyA' => null, 'keyB' => '2021-09-29'],
            null], DateHelper::toSimpleDate2DArray($res));
    }

    public function testFromRFC1123DateString()
    {
        $this->assertNull(DateHelper::fromRfc1123DateTimeMapOfArray(null));
        $this->assertNull(DateHelper::fromRfc1123DateTimeArrayOfMap(null));
        $res = DateHelper::fromRfc1123DateTimeMapOfArray((object) [
            'A' => ['Fri, 01 Oct 2021 00:00:00 GMT', 'Thu, 30 Sep 2021 00:00:00 GMT'],
            'B' => [null, 'Wed, 29 Sep 2021 00:00:00 GMT'],
            'C' => null]);
        $this->assertEquals([
            'A' => [new DateTime("2021-09-31"), new DateTime("2021-09-30")],
            'B' => [null, new DateTime("2021-09-29")],
            'C' => null], $res);
        $res = DateHelper::fromRfc1123DateTimeArrayOfMap([
            (object)['key1' => 'Fri, 01 Oct 2021 00:00:00 GMT', 'key2' => 'Thu, 30 Sep 2021 00:00:00 GMT'],
            (object)['keyA' => null, 'keyB' => 'Wed, 29 Sep 2021 00:00:00 GMT'],
            null]);
        $this->assertEquals([
            ['key1' => new DateTime("2021-09-31"), 'key2' => new DateTime("2021-09-30")],
            ['keyA' => null, 'keyB' => new DateTime("2021-09-29")],
            null], $res);
    }

    public function testFromRFC3339DateString()
    {
        $this->assertNull(DateHelper::fromRfc3339DateTimeMapOfArray(null));
        $this->assertNull(DateHelper::fromRfc3339DateTimeArrayOfMap(null));
        $res = DateHelper::fromRfc3339DateTimeMapOfArray((object) [
            'A' => ['2021-10-01T00:00:00+00:00', '2021-09-30T00:00:00+00:00'],
            'B' => [null, '2021-09-29T00:00:00+00:00'],
            'C' => null]);
        $this->assertEquals([
            'A' => [new DateTime("2021-09-31"), new DateTime("2021-09-30")],
            'B' => [null, new DateTime("2021-09-29")],
            'C' => null], $res);
        $res = DateHelper::fromRfc3339DateTimeArrayOfMap([
            (object)['key1' => '2021-10-01T00:00:00+00:00', 'key2' => '2021-09-30T00:00:00+00:00'],
            (object)['keyA' => null, 'keyB' => '2021-09-29T00:00:00+00:00'],
            null]);
        $this->assertEquals([
            ['key1' => new DateTime("2021-09-31"), 'key2' => new DateTime("2021-09-30")],
            ['keyA' => null, 'keyB' => new DateTime("2021-09-29")],
            null], $res);
        $this->assertEquals(new DateTime("2021-09-31"), DateHelper::fromRfc3339DateTime('2021-10-01T00:00:00'));
        $this->assertEquals(
            new DateTime("2021-09-31"),
            DateHelper::fromRfc3339DateTime('2021-10-01T00:00:00.000000')
        );
        $this->assertEquals(
            new DateTime("2021-09-31"),
            DateHelper::fromRfc3339DateTime('2021-10-01T00:00:00.000000000000')
        );
    }

    public function testFromUnixDateString()
    {
        $this->assertNull(DateHelper::fromUnixTimestampMapOfArray(null));
        $this->assertNull(DateHelper::fromUnixTimestampArrayOfMap(null));
        $res = DateHelper::fromUnixTimestampMapOfArray((object) [
            'A' => [1633046400, 1632960000],
            'B' => [null, 1632873600],
            'C' => null]);
        $this->assertEquals([
            'A' => [new DateTime("2021-09-31"), new DateTime("2021-09-30")],
            'B' => [null, new DateTime("2021-09-29")],
            'C' => null], $res);
        $res = DateHelper::fromUnixTimestampArrayOfMap([
            (object)['key1' => 1633046400, 'key2' => 1632960000],
            (object)['keyA' => null, 'keyB' => 1632873600],
            null]);
        $this->assertEquals([
            ['key1' => new DateTime("2021-09-31"), 'key2' => new DateTime("2021-09-30")],
            ['keyA' => null, 'keyB' => new DateTime("2021-09-29")],
            null], $res);
    }

    public function testToDateStringConversions()
    {
        $input = [
            'A' => ['key1' => new DateTime("2021-09-31"), 'key2' => new DateTime("2021-09-30")],
            'B' => ['keyA' => null, 'keyB' => new DateTime("2021-09-29")],
            'C' => null
        ];
        $this->assertNull(DateHelper::toSimpleDate2DArray(null));
        $res = DateHelper::toSimpleDate2DArray($input);
        $this->assertEquals([
            'A' => ['key1' => '2021-10-01', 'key2' => '2021-09-30'],
            'B' => ['keyA' => null, 'keyB' => '2021-09-29'],
            'C' => null], $res);
        $this->assertNull(DateHelper::toRfc1123DateTime2DArray(null));
        $res = DateHelper::toRfc1123DateTime2DArray($input);
        $this->assertEquals([
            'A' => ['key1' => 'Fri, 01 Oct 2021 00:00:00 GMT', 'key2' => 'Thu, 30 Sep 2021 00:00:00 GMT'],
            'B' => ['keyA' => null, 'keyB' => 'Wed, 29 Sep 2021 00:00:00 GMT'],
            'C' => null], $res);
        $this->assertNull(DateHelper::toRfc3339DateTime2DArray(null));
        $res = DateHelper::toRfc3339DateTime2DArray($input);
        $this->assertEquals([
            'A' => ['key1' => '2021-10-01T00:00:00+00:00', 'key2' => '2021-09-30T00:00:00+00:00'],
            'B' => ['keyA' => null, 'keyB' => '2021-09-29T00:00:00+00:00'],
            'C' => null], $res);
        $this->assertNull(DateHelper::toUnixTimestamp2DArray(null));
        $res = DateHelper::toUnixTimestamp2DArray($input);
        $this->assertEquals([
            'A' => ['key1' => 1633046400, 'key2' => 1632960000],
            'B' => ['keyA' => null, 'keyB' => 1632873600],
            'C' => null], $res);
    }

    public function testIsProperSubset()
    {
        $obj1 = CoreHelper::deserialize('{"key1":23,"key2":true,"key3":"my string"}', false);
        $obj2 = CoreHelper::deserialize('{"key1":23,"key3":"my string","key2":true,"key4":23.56}', false);
        $obj3 = [23, "my string"];
        $obj4 = [$obj3, "my string"];

        $this->assertFalse(CoreHelper::isProperSubset($obj1, $obj2, false)); // not allowing extra
        $this->assertTrue(CoreHelper::isProperSubset(null, null)); // both are null
        // not equal but not checking for values
        $this->assertTrue(CoreHelper::isProperSubset($obj1, null, true, false, false));
        $this->assertFalse(CoreHelper::isProperSubset($obj1, 234)); // matching object with primitive
        $this->assertFalse(CoreHelper::isProperSubset($obj2, $obj1)); // actual obj missing a key
        // actual obj does not follow same order
        $this->assertFalse(CoreHelper::isProperSubset($obj1, $obj2, true, true));
        // inner actual is not array like inner expected value
        $this->assertFalse(CoreHelper::isProperSubset($obj4, $obj3));
        // inner expected is not array like inner actual value
        $this->assertFalse(CoreHelper::isProperSubset($obj3, $obj4));
    }
}
