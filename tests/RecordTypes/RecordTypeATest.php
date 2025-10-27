<?php

namespace Ycdev\PhpIgcInspector\Tests\RecordTypes;

use PHPUnit\Framework\TestCase;
use Ycdev\PhpIgcInspector\RecordTypes\RecordTypeA;
use Ycdev\PhpIgcInspector\Exception\InvalidIgcException;

require_once __DIR__ . '/../../src/Data/ManufacturerCodesData.php';

class RecordTypeATest extends TestCase
{
    // Tests pour extract()
    
    public function testExtractManufacturerIdentification()
    {
        $line = 'AFG123456';
        $record = new RecordTypeA($line, 1, null);
        
        $data = $record->extract();
        
        $this->assertArrayHasKey('manufacturerId', $data);
        $this->assertEquals('FG1', $data['manufacturerId']);
        $this->assertArrayHasKey('serialNumber', $data);
        $this->assertEquals('23456', $data['serialNumber']);
    }

    public function testExtractSerialIdNumber()
    {
        $line = 'AXXX123';
        $record = new RecordTypeA($line, 1, null);
        
        $data = $record->extract();
        
        $this->assertArrayHasKey('serialNumber', $data);
        $this->assertEquals('123', $data['serialNumber']);
    }

    public function testExtractSerialIdNumberWithDash()
    {
        $line = 'AXXX123-ABC';
        $record = new RecordTypeA($line, 1, null);
        
        $data = $record->extract();
        
        $this->assertArrayHasKey('serialNumber', $data);
        $this->assertEquals('123', $data['serialNumber']);
    }

    public function testExtractAllFields()
    {
        $line = 'AXXX123-ABC';
        $record = new RecordTypeA($line, 1, null);
        
        $data = $record->extract();
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('manufacturerId', $data);
        $this->assertEquals('XXX', $data['manufacturerId']);
        $this->assertArrayHasKey('serialNumber', $data);
        $this->assertEquals('123', $data['serialNumber']);
        $this->assertArrayHasKey('additionalData', $data);
        $this->assertEquals('ABC', $data['additionalData']); // Données brutes après le tiret
    }

    public function testExtractMinimumSerialIdLength()
    {
        $line = 'AXXX123';
        $record = new RecordTypeA($line, 1, null);
        
        $data = $record->extract();
        
        $this->assertEquals('123', $data['serialNumber']);
    }

    public function testExtractWithShortLine()
    {
        $line = 'AXX'; // Short line
        $record = new RecordTypeA($line, 1, null);
        
        $data = $record->extract();
        $this->assertIsArray($data);
    }
    
    // Tests pour checkFormat()
    
    public function testCheckFormatValidData()
    {
        $line = 'AXXXXXX123ABC';
        $record = new RecordTypeA($line, 1, null);
        
        $result = $record->checkFormat();
        
        $this->assertTrue($result);
    }

    public function testCheckFormatWithShortManufacturer()
    {
        $line = 'AXXXXX'; // Only 5 chars instead of 6 for manufacturer
        $record = new RecordTypeA($line, 1, null);
        
        // Sans contrainte de longueur, cela ne devrait plus lever d'exception
        $result = $record->checkFormat();
        $this->assertTrue($result);
    }

    public function testCheckFormatWithValidLength()
    {
        $line = 'AXXXXXX123'; // Exactly 6 chars for manufacturer, 3 for serial
        $record = new RecordTypeA($line, 1, null);
        
        $result = $record->checkFormat();
        
        $this->assertTrue($result);
    }

    public function testCheckFormatThrowsExceptionWhenInvalidContent()
    {
        $this->expectException(InvalidIgcException::class);
        $this->expectExceptionMessage('format invalide');
        
        $line = 'A###XXX'; // Invalid characters for manufacturer
        $record = new RecordTypeA($line, 1, null);
        $record->checkFormat();
    }

    public function testCheckFormatWithValidManufacturerAndSerialId()
    {
        $line = 'AFLIGHTLY123';
        $record = new RecordTypeA($line, 1, null);
        
        $result = $record->checkFormat();
        
        $this->assertTrue($result);
    }

    public function testCheckFormatWithShortSerialId()
    {
        $line = 'AXXXXXX12'; // Only 2 chars for serial ID instead of 3
        $record = new RecordTypeA($line, 1, null);
        
        // Sans contrainte de longueur, cela ne devrait plus lever d'exception
        $result = $record->checkFormat();
        $this->assertTrue($result);
    }
    
    // Tests pour singleRecord
    
    public function testIsSingleRecord()
    {
        $line = 'AXXX123';
        $record = new RecordTypeA($line, 1, null);
        
        $this->assertTrue($record->isSingleRecord());
    }
    
    // Tests pour parse()
    
    public function testParseReturnsObject()
    {
        $line = 'AXXX123-ABC';
        $record = new RecordTypeA($line, 1, null);
        
        $result = $record->parse($line);
        
        $this->assertIsObject($result);
        $this->assertEquals('XXX', $result->manufacturerId);
        $this->assertEquals('123', $result->serialNumber);
        $this->assertEquals('ABC', $result->additionalData);
    }
    
    public function testParseExtractsDataCorrectly()
    {
        $line = 'AFG123456';
        $record = new RecordTypeA($line, 1, null);
        
        $result = $record->parse($line);
        
        $this->assertIsObject($result);
        $this->assertEquals('FG1', $result->manufacturerId);
        $this->assertEquals('23456', $result->serialNumber);
    }
}

