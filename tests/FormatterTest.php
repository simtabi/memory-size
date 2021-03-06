<?php

declare(strict_types=1);

use gugglegum\MemorySize\Formatter;
use gugglegum\MemorySize\NumberFormat;
use gugglegum\MemorySize\Standards\IEC;
use gugglegum\MemorySize\Standards\JEDEC;
use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase
{
    public function testIecStandard()
    {
        // IEC standard is default, so we don't need to pass it as option
        $formatter = new Formatter();

        $this->assertEquals('64 KiB', $formatter->format(65536));
        $this->assertEquals('512 B', $formatter->format(512));
        $this->assertEquals('1.5 KiB', $formatter->format(1536));
        $this->assertEquals('1.21 KiB', $formatter->format(1234));
        $this->assertEquals('976.56 KiB', $formatter->format(1000000));
        $this->assertEquals('1 MiB', $formatter->format(1048576));
        $this->assertEquals('4.38 GiB', $formatter->format(4707319808)); // DVD-R(W)
        $this->assertEquals('4.38 GiB', $formatter->format(4700372992)); // DVD+R(W)
    }

    public function testJedecStandard()
    {
        $formatter = new Formatter([
            'standard' => new JEDEC(),
        ]);

        $this->assertEquals('64 KB', $formatter->format(65536));
        $this->assertEquals('512 B', $formatter->format(512));
        $this->assertEquals('1.5 KB', $formatter->format(1536));
        $this->assertEquals('1.21 KB', $formatter->format(1234));
        $this->assertEquals('976.56 KB', $formatter->format(1000000));
        $this->assertEquals('1 MB', $formatter->format(1048576));
        $this->assertEquals('4.38 GB', $formatter->format(4707319808)); // DVD-R(W)
        $this->assertEquals('4.38 GB', $formatter->format(4700372992)); // DVD+R(W)
    }

    public function testMaxDecimals()
    {
        $formatter = new Formatter([
            'maxDecimals' => 3,
        ]);
        // test maxDecimals passed through constructor
        $this->assertEquals('4.384 GiB', $formatter->format(4707319808)); // DVD-R(W)

        // test maxDecimals passed through setOptions
        $formatter->setOptions(['maxDecimals' => 1]);
        $this->assertEquals('4.4 GiB', $formatter->format(4700372992, ['maxDecimals' => 1])); // DVD+R(W)

        // test maxDecimals passed through getOptions()->setMaxDeciamls()
        $formatter->getOptions()->setMaxDecimals(3);
        $this->assertEquals('4.384 GiB', $formatter->format(4707319808)); // DVD-R(W)

        // test maxDecimals passed through override options
        $this->assertEquals('4.4 GiB', $formatter->format(4700372992, ['maxDecimals' => 1])); // DVD+R(W)
    }

    public function testMinDecimals()
    {
        $formatter = new Formatter();
        $this->assertEquals('1.0 MiB', $formatter->format(1048576, ['minDecimals' => 1])); // DVD-R(W)
        $this->assertEquals('1.50 GiB', $formatter->format(1610612736, ['minDecimals' => 2])); // DVD+R(W)
    }

    public function testFixedDecimals()
    {
        $formatter = new Formatter();
        $this->assertEquals('1.000 MiB', $formatter->format(1048576, ['fixedDecimals' => 3])); // DVD-R(W)
        $this->assertEquals('2 GiB', $formatter->format(1610612736, ['fixedDecimals' => 0])); // DVD+R(W)
    }

    public function testDecimalPoint()
    {
        $formatter = new Formatter(['numberFormat' => ['decimalPoint' => ',']]);
        $this->assertEquals('1,21 KiB', $formatter->format(1234));
    }

    public function testThousandsSeparator()
    {
        // We use JEDEC standard to test thousands separator because JEDEC supports only GB as maximum unit. IEC
        // supports TiB, PiB, etc.
        $formatter = new Formatter([
            'standard' => new JEDEC(),
        ]);
        // No thousands separator -- default
        $this->assertEquals('4384.03 GB', $formatter->format(4707319808000)); // 1000 x DVD-R(W)

        // With space (" ") thousands separator using setOptions()
        $formatter->setOptions(['numberFormat' => ['thousandsSeparator' => ' ']]);
        $this->assertEquals('4 384.03 GB', $formatter->format(4707319808000)); // 1000 x DVD-R(W)

        // With comma (",") thousands separator using override options
        $this->assertEquals('4,384.03 GB', $formatter->format(4707319808000, ['numberFormat' => ['thousandsSeparator' => ',']])); // 1000 x DVD-R(W)
    }

    public function testUnitSeparator()
    {
        $formatter = new Formatter(['unitSeparator' => '']);
        $this->assertEquals('1.21KiB', $formatter->format(1234));
        $formatter->getOptions()->setUnitSeparator(' ');
        $this->assertEquals('1.21 KiB', $formatter->format(1234));
        $this->assertEquals('1.21_KiB', $formatter->format(1234, ['unitSeparator' => '_']));
    }

    public function testNegative()
    {
        $formatter = new Formatter();
        $this->assertEquals('-1.21 KiB', $formatter->format(-1234));
        $this->assertEquals('-4.28 TiB', $formatter->format(-4707319808000)); // -1000 x DVD-R(W)
    }

    public function testSetOptions()
    {
        // Set all options through constructor (setFromArray) to non-default values
        $formatter = new Formatter([
            'standard' => new JEDEC(),
            'minDecimals' => 2,
            'maxDecimals' => 4,
            'numberFormat' => [
                'decimalPoint' => ',',
                'thousandsSeparator' => ' ',
            ],
            'unitSeparator' => "\t",
        ]);

        // Check all options set correctly via getters
        $this->assertInstanceOf(JEDEC::class, $formatter->getOptions()->getStandard());
        $this->assertEquals(2, $formatter->getOptions()->getMinDecimals());
        $this->assertEquals(4, $formatter->getOptions()->getMaxDecimals());
        $this->assertEquals(',', $formatter->getOptions()->getNumberFormat()->getDecimalPoint());
        $this->assertEquals(' ', $formatter->getOptions()->getNumberFormat()->getThousandsSeparator());
        $this->assertEquals("\t", $formatter->getOptions()->getUnitSeparator());

        // Change all previously defined options via setter methods on options instance
        $formatter->getOptions()
            ->setStandard(new IEC())
            ->setMinDecimals(0)
            ->setMaxDecimals(2)
            ->setNumberFormat((new NumberFormat())
                ->setDecimalPoint('.')
                ->setThousandsSeparator(',')
            )
            ->setUnitSeparator(' ');

        // Check once again that all set correctly
        $this->assertInstanceOf(IEC::class, $formatter->getOptions()->getStandard());
        $this->assertEquals(0, $formatter->getOptions()->getMinDecimals());
        $this->assertEquals(2, $formatter->getOptions()->getMaxDecimals());
        $this->assertEquals('.', $formatter->getOptions()->getNumberFormat()->getDecimalPoint());
        $this->assertEquals(',', $formatter->getOptions()->getNumberFormat()->getThousandsSeparator());
        $this->assertEquals(' ', $formatter->getOptions()->getUnitSeparator());
    }

    public function testExceptionOnUnknownOption1()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown memory-size formatter option "unknownOption"');
        new Formatter(['unknownOption' => 'value']);
    }

    public function testExceptionOnUnknownOption2()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown memory-size formatter option "unknownOption"');
        $formatter = new Formatter();
        $formatter->setOptions(['unknownOption' => 'value']);
    }
}
