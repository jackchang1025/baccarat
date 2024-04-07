<?php

namespace HyperfTests\Unit\Baccarat;

use App\Baccarat\Service\Coordinates\CalculateCoordinates;
use PHPUnit\Framework\TestCase;

class CalculateCoordinatesTest extends TestCase
{
    public function testCalculateCoordinates()
    {
        $calculateCoordinates = new CalculateCoordinates();

        $result = $calculateCoordinates->calculateCoordinates('P');

        $expectedArray = [
            ['x' => 1, 'y' => 6, 'result' => 'P'],
        ];

        $this->assertEquals($expectedArray, $result);
    }

    public function testCalculateCoordinates2()
    {
        $calculateCoordinates = new CalculateCoordinates();

        $result = $calculateCoordinates->calculateCoordinates('PPPPPPPP');

        $expectedArray = [
            ['x' => 1, 'y' => 6, 'result' => 'P'],
            ['x' => 1, 'y' => 5, 'result' => 'P'],
            ['x' => 1, 'y' => 4, 'result' => 'P'],
            ['x' => 1, 'y' => 3, 'result' => 'P'],
            ['x' => 1, 'y' => 2, 'result' => 'P'],
            ['x' => 1, 'y' => 1, 'result' => 'P'],
            ['x' => 2, 'y' => 1, 'result' => 'P'],
            ['x' => 3, 'y' => 1, 'result' => 'P'],
        ];

        $this->assertEquals($expectedArray, $result);
    }

    public function testCalculateCoordinates3()
    {
        $calculateCoordinates = new CalculateCoordinates();

        $result = $calculateCoordinates->calculateCoordinates('PTPPPPPTTPP');

        $expectedArray = [
            ['x' => 1, 'y' => 6, 'result' => 'P'],
            ['x' => 1, 'y' => 6, 'result' => 'T'],
            ['x' => 1, 'y' => 5, 'result' => 'P'],
            ['x' => 1, 'y' => 4, 'result' => 'P'],
            ['x' => 1, 'y' => 3, 'result' => 'P'],
            ['x' => 1, 'y' => 2, 'result' => 'P'],
            ['x' => 1, 'y' => 1, 'result' => 'P'],

            ['x' => 1, 'y' => 1, 'result' => 'T'],
            ['x' => 1, 'y' => 1, 'result' => 'T'],

            ['x' => 2, 'y' => 1, 'result' => 'P'],
            ['x' => 3, 'y' => 1, 'result' => 'P'],
        ];

        $this->assertEquals($expectedArray, $result);
    }
    public function testCalculateCoordinates4()
    {
        $calculateCoordinates = new CalculateCoordinates();

        $result = $calculateCoordinates->calculateCoordinates('PPPPPPPPBBBBBBBB');

        $expectedArray = [
            ['x' => 1, 'y' => 6, 'result' => 'P'],
            ['x' => 1, 'y' => 5, 'result' => 'P'],
            ['x' => 1, 'y' => 4, 'result' => 'P'],
            ['x' => 1, 'y' => 3, 'result' => 'P'],
            ['x' => 1, 'y' => 2, 'result' => 'P'],
            ['x' => 1, 'y' => 1, 'result' => 'P'],
            ['x' => 2, 'y' => 1, 'result' => 'P'],
            ['x' => 3, 'y' => 1, 'result' => 'P'],

            ['x' => 2, 'y' => 6, 'result' => 'B'],
            ['x' => 2, 'y' => 5, 'result' => 'B'],
            ['x' => 2, 'y' => 4, 'result' => 'B'],
            ['x' => 2, 'y' => 3, 'result' => 'B'],
            ['x' => 2, 'y' => 2, 'result' => 'B'],

//            ['x' => 2, 'y' => 1, 'result' => 'B'],
            ['x' => 3, 'y' => 2, 'result' => 'B'],


//            ['x' => 3, 'y' => 1, 'result' => 'B'],
            ['x' => 4, 'y' => 2, 'result' => 'B'],

//            ['x' => 3, 'y' => 1, 'result' => 'B'],
            ['x' => 4, 'y' => 1, 'result' => 'B'],
        ];

        $this->assertEquals($expectedArray, $result);
    }

    public function testCalculateCoordinates5()
    {
        $calculateCoordinates = new CalculateCoordinates();

        $result = $calculateCoordinates->calculateCoordinates('TPPPPPPPPBBBBBBBBPPPPPPP');

        $expectedArray = [
            ['x' => 1, 'y' => 6, 'result' => 'T'],
            ['x' => 1, 'y' => 6, 'result' => 'P'],
            ['x' => 1, 'y' => 5, 'result' => 'P'],
            ['x' => 1, 'y' => 4, 'result' => 'P'],
            ['x' => 1, 'y' => 3, 'result' => 'P'],
            ['x' => 1, 'y' => 2, 'result' => 'P'],
            ['x' => 1, 'y' => 1, 'result' => 'P'],
            ['x' => 2, 'y' => 1, 'result' => 'P'],
            ['x' => 3, 'y' => 1, 'result' => 'P'],

            ['x' => 2, 'y' => 6, 'result' => 'B'],
            ['x' => 2, 'y' => 5, 'result' => 'B'],
            ['x' => 2, 'y' => 4, 'result' => 'B'],
            ['x' => 2, 'y' => 3, 'result' => 'B'],
            ['x' => 2, 'y' => 2, 'result' => 'B'],

//            ['x' => 2, 'y' => 1, 'result' => 'B'],
            ['x' => 3, 'y' => 2, 'result' => 'B'],


//            ['x' => 3, 'y' => 1, 'result' => 'B'],
            ['x' => 4, 'y' => 2, 'result' => 'B'],

//            ['x' => 3, 'y' => 1, 'result' => 'B'],
            ['x' => 4, 'y' => 1, 'result' => 'B'],

            ['x' => 3, 'y' => 6, 'result' => 'P'],
            ['x' => 3, 'y' => 5, 'result' => 'P'],
            ['x' => 3, 'y' => 4, 'result' => 'P'],
            ['x' => 3, 'y' => 3, 'result' => 'P'],

//            ['x' => 3, 'y' => 2, 'result' => 'P'],
            ['x' => 4, 'y' => 3, 'result' => 'P'],

//            ['x' => 3, 'y' => 1, 'result' => 'P'],
            ['x' => 5, 'y' => 3, 'result' => 'P'],

//            ['x' => 3, 'y' => 1, 'result' => 'P'],
            ['x' => 5, 'y' => 2, 'result' => 'P'],
        ];

        $this->assertEquals($expectedArray, $result);
    }
}