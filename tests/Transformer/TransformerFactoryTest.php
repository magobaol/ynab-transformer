<?php

namespace Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Transformer\TransformerFactory;
use Transformer\Fineco;
use Transformer\Revolut;
use Transformer\Nexi;
use Transformer\Popso;
use Transformer\Poste;
use Transformer\Telepass;
use Transformer\Isybank;

class TransformerFactoryTest extends TestCase
{
    public function test_detectFormat_returns_fineco_for_fineco_file()
    {
        $format = TransformerFactory::detectFormat(__DIR__ . '/../Fixtures/movimenti-fineco.xlsx');
        $this->assertEquals('fineco', $format);
    }

    public function test_detectFormat_returns_revolut_for_revolut_file()
    {
        $format = TransformerFactory::detectFormat(__DIR__ . '/../Fixtures/movimenti-revolut.csv');
        $this->assertEquals('revolut', $format);
    }

    public function test_detectFormat_returns_nexi_for_nexi_file()
    {
        $format = TransformerFactory::detectFormat(__DIR__ . '/../Fixtures/movimenti-nexi.xlsx');
        $this->assertEquals('nexi', $format);
    }

    public function test_detectFormat_returns_popso_for_popso_file()
    {
        $format = TransformerFactory::detectFormat(__DIR__ . '/../Fixtures/movimenti-popso.csv');
        $this->assertEquals('popso', $format);
    }

    public function test_detectFormat_returns_poste_for_poste_file()
    {
        $format = TransformerFactory::detectFormat(__DIR__ . '/../Fixtures/movimenti-poste.xlsx');
        $this->assertEquals('poste', $format);
    }

    public function test_detectFormat_returns_telepass_for_telepass_file()
    {
        $format = TransformerFactory::detectFormat(__DIR__ . '/../Fixtures/movimenti-telepass.xls');
        $this->assertEquals('telepass', $format);
    }

    public function test_detectFormat_returns_isybank_for_isybank_file()
    {
        $format = TransformerFactory::detectFormat(__DIR__ . '/../Fixtures/movimenti-isybank.xlsx');
        $this->assertEquals('isybank', $format);
    }

    public function test_detectFormat_throws_exception_for_unknown_format()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No supported format detected');
        TransformerFactory::detectFormat(__DIR__ . '/../Fixtures/filename.xlsx');
    }

    public function test_detectFormat_throws_exception_for_nonexistent_file()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No supported format detected');
        TransformerFactory::detectFormat(__DIR__ . '/../Fixtures/nonexistent.xlsx');
    }

    public function test_create_returns_fineco_transformer_for_fineco_file()
    {
        $transformer = TransformerFactory::create(__DIR__ . '/../Fixtures/movimenti-fineco.xlsx');
        $this->assertInstanceOf(Fineco::class, $transformer);
    }

    public function test_create_returns_revolut_transformer_for_revolut_file()
    {
        $transformer = TransformerFactory::create(__DIR__ . '/../Fixtures/movimenti-revolut.csv');
        $this->assertInstanceOf(Revolut::class, $transformer);
    }

    public function test_create_returns_nexi_transformer_for_nexi_file()
    {
        $transformer = TransformerFactory::create(__DIR__ . '/../Fixtures/movimenti-nexi.xlsx');
        $this->assertInstanceOf(Nexi::class, $transformer);
    }

    public function test_create_returns_popso_transformer_for_popso_file()
    {
        $transformer = TransformerFactory::create(__DIR__ . '/../Fixtures/movimenti-popso.csv');
        $this->assertInstanceOf(Popso::class, $transformer);
    }

    public function test_create_returns_poste_transformer_for_poste_file()
    {
        $transformer = TransformerFactory::create(__DIR__ . '/../Fixtures/movimenti-poste.xlsx');
        $this->assertInstanceOf(Poste::class, $transformer);
    }

    public function test_create_returns_telepass_transformer_for_telepass_file()
    {
        $transformer = TransformerFactory::create(__DIR__ . '/../Fixtures/movimenti-telepass.xls');
        $this->assertInstanceOf(Telepass::class, $transformer);
    }

    public function test_create_returns_isybank_transformer_for_isybank_file()
    {
        $transformer = TransformerFactory::create(__DIR__ . '/../Fixtures/movimenti-isybank.xlsx');
        $this->assertInstanceOf(Isybank::class, $transformer);
    }

    public function test_create_throws_exception_for_unknown_format()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No supported format detected');
        TransformerFactory::create(__DIR__ . '/../Fixtures/filename.xlsx');
    }

    public function test_create_throws_exception_for_nonexistent_file()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No supported format detected');
        TransformerFactory::create(__DIR__ . '/../Fixtures/nonexistent.xlsx');
    }

    public function test_getSupportedFormats_returns_all_supported_formats()
    {
        $formats = TransformerFactory::getSupportedFormats();
        $expected = ['fineco', 'revolut', 'nexi', 'popso', 'poste', 'telepass', 'isybank'];
        $this->assertEquals($expected, $formats);
    }
}