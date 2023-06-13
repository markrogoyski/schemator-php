<?php

namespace Smoren\Schemator\Tests\Unit\NestedAccessor;

use Smoren\Schemator\Components\NestedAccessor;
use Smoren\Schemator\Exceptions\PathNotExistException;

class NestedAccessorGetTest extends \Codeception\Test\Unit
{
    /**
     * @dataProvider dataProviderForExampleSingle
     * @dataProvider dataProviderForExampleMultipleIndexed
     */
    public function testExamples($source, $path, $expected)
    {
        // Given
        $accessor = new NestedAccessor($source);

        // When
        $actual = $accessor->get($path);

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function dataProviderForExampleSingle(): array
    {
        $source = [1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]];

        return [
            [
                $source,
                'a',
                ['b' => [[11, 22]], 'c' => [[33, 44]]],
            ],
            [
                $source,
                'a.*',
                [[[11, 22]], [[33, 44]]],
            ],
            [
                $source,
                'a.*.0',
                [[11, 22], [33, 44]],
            ],
            [
                $source,
                'a.*.0.0',
                [11, 33],
            ],
            [
                $source,
                'a.*.0.1',
                [22, 44],
            ],
            [
                $source,
                'a.*.0.|.0',
                [11, 22],
            ],
        ];
    }

    public function dataProviderForExampleMultipleIndexed(): array
    {
        $source = [
            ['a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
            ['a' => ['b' => [[12, 23]], 'd' => [[34, 45]]]],
            ['a' => ['b' => [[13, 24]], 'e' => [[35, 46]]]],
        ];

        return [
            [
                $source,
                '*.a',
                [
                    ['b' => [[11, 22]], 'c' => [[33, 44]]],
                    ['b' => [[12, 23]], 'd' => [[34, 45]]],
                    ['b' => [[13, 24]], 'e' => [[35, 46]]],
                ],
            ],
            [
                $source,
                '*.a.b',
                [
                    [[11, 22]],
                    [[12, 23]],
                    [[13, 24]],
                ],
            ],
            [
                $source,
                '*.a.b.0',
                [
                    [11, 22],
                    [12, 23],
                    [13, 24],
                ],
            ],
            [
                $source,
                '*.a.b.|.0',
                [[11, 22]],
            ],
            [
                $source,
                '*.a.b.*',
                [
                    [11, 22],
                    [12, 23],
                    [13, 24],
                ],
            ],
            [
                $source,
                '*.a.b.*.0',
                [11, 12, 13],
            ],
            [
                $source,
                '*.a.b.*.1',
                [22, 23, 24],
            ],
            [
                $source,
                '*.a.b.*.*',
                [11, 22, 12, 23, 13, 24],
            ],
            [
                $source,
                '*.a.b.*.|.0',
                [11, 22],
            ],
            [
                $source,
                '*.a.b.*.|.1',
                [12, 23],
            ],
            [
                $source,
                '*.a.b.*.|.2',
                [13, 24],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForStrictSuccessArray
     * @dataProvider dataProviderForStrictSuccessArrayObject
     * @dataProvider dataProviderForStrictSuccessStdClass
     * @dataProvider dataProviderForStrictSuccessCitiesExample
     */
    public function testStrictSuccess($source, $path, $expected)
    {
        // Given
        $accessor = new NestedAccessor($source);

        // When
        $actual = $accessor->get($path);

        // Then
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dataProviderForStrictErrorArray
     */
    public function testStrictError($source, $path, $expected)
    {
        // Given
        $accessor = new NestedAccessor($source);

        try {
            // When
            $accessor->get($path);
            $this->fail();
        } catch (PathNotExistException $e) {
            // Then
            $this->assertSame("Key '{$expected[0]}' is not found on path '{$expected[1]}'", $e->getMessage());
            $this->assertSame($expected, [$e->getKey(), $e->getPathString()]);
        }
    }

    /**
     * @dataProvider dataProviderForStrictSuccessArray
     * @dataProvider dataProviderForStrictSuccessArrayObject
     * @dataProvider dataProviderForStrictSuccessStdClass
     * @dataProvider dataProviderForNonStrictArray
     * @dataProvider dataProviderForNonStrictArrayObject
     * @dataProvider dataProviderForNonStrictStdClass
     * @dataProvider dataProviderForStrictSuccessCitiesExample
     * @dataProvider dataProviderForNonStrictCitiesExample
     */
    public function testNonStrict($source, $path, $expected)
    {
        // Given
        $accessor = new NestedAccessor($source);

        // When
        $actual = $accessor->get($path, false);

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testBadPathError()
    {
        // Given
        $accessor = new NestedAccessor($source);

        $source = ['a' => 1];
        $path = (object)['a', 'b', 'c'];

        try {
            // When
            $accessor->get($path);
            $this->fail();
        } catch (\InvalidArgumentException $e) {
            // Then
            $this->assertSame('Path must be numeric, string or array, object given', $e->getMessage());
        }
    }

    public function dataProviderForStrictSuccessArray(): array
    {
        return [
            [
                [],
                [],
                [],
            ],
            [
                [],
                null,
                [],
            ],
            [
                [],
                '*',
                [],
            ],
            [
                ['a' => 1],
                [],
                ['a' => 1],
            ],
            [
                [1, 2, 3],
                null,
                [1, 2, 3],
            ],
            [
                [1, 2, 3],
                '*',
                [1, 2, 3],
            ],
            [
                [1, 2, 3, 'a' => 4],
                '*',
                [1, 2, 3, 4],
            ],
            [
                [1, 2, 3, 'a' => 4],
                0,
                1,
            ],
            [
                [1, 2, 3, 'a' => 4],
                '0',
                1,
            ],
            [
                [1, 2, 3, 'a' => 4],
                '2',
                3,
            ],
            [
                [1, 2, 3, 'a' => 4],
                2,
                3,
            ],
            [
                [1, 2, 3, 'a' => 4],
                'a',
                4,
            ],
            [
                [1, 2, 3, 'a' => [1, 2, 'a' => 3]],
                'a',
                [1, 2, 'a' => 3],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [11, 12], 'c' => [22, 23]]],
                'a.*',
                [[11, 12], [22, 23]],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [11, 12], 'c' => [22, 23]]],
                'a.*.|.0',
                [11, 12],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]],
                'a.*.*',
                [11, 22],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [11, 22], 'c' => [33, 44]]],
                'a.*',
                [[11, 22], [33, 44]],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [11, 22], 'c' => [33, 44]]],
                'a.*.*',
                [11, 22, 33, 44],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [11, 22], 'c' => [33, 44]]],
                'a.*.0',
                [11, 33],
            ],
            [
                [1, 2, 3, 'a' => [[11, 22], [33, 44]]],
                'a.*',
                [[11, 22], [33, 44]],
            ],
            [
                [1, 2, 3, 'a' => [[11, 22], [33, 44]]],
                'a.*.*',
                [11, 22, 33, 44],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.0.0',
                [11, 33],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.0.*',
                [11, 22, 33, 44],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.0.1',
                [22, 44],
            ],
            [
                [1, 2, 3, 'a' => [1, 2, 'b' => ['c', 'd', 'e']]],
                'a.b',
                ['c', 'd', 'e'],
            ],
            [
                [1, 2, 3, 'a' => [1, 2, 'b' => ['c', 'd', 'e']]],
                'a.b.*',
                ['c', 'd', 'e'],
            ],
            [
                [1, 2, 3, 'a' => ['b' => ['c', 'd', 'e'], [11, 22]]],
                'a.*.*',
                ['c', 'd', 'e', 11, 22],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [['c'], ['d'], ['e']], [[11], [22, 33]]]],
                'a.*.*.*',
                ['c', 'd', 'e', 11, 22, 33],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [['c'], ['d'], ['e']], [[11], [22, 33]]]],
                'a.*.*.0',
                ['c', 'd', 'e', 11, 22],
            ],
            [
                [1, 2, 3, 'a' => ['b' => ['c', 'd', 'e'], [11, 22]]],
                ['a', '*', '*'],
                ['c', 'd', 'e', 11, 22],
            ],
            [
                [
                    'a' => [1, 2, 3],
                    'b' => [11, 22, 33],
                    'c' => [111, 222, 333],
                ],
                '*.0',
                [1, 11, 111],
            ],
            [
                [
                    'a' => [1, 2, 3],
                    'b' => [11, 22, 33],
                    'c' => [111, 222, 333],
                ],
                ['*', 0],
                [1, 11, 111],
            ],
            [
                [
                    'a' => [1, 2, 3],
                    'b' => [11, 22, 33],
                    'c' => [111, 222, 333],
                ],
                ['*', '2'],
                [3, 33, 333],
            ],
            [
                [
                    'a' => [1, 2, [3]],
                    'b' => [11, 22, [33]],
                    'c' => [111, 222, [333]],
                ],
                ['*', '2'],
                [[3], [33], [333]],
            ],
            [
                [
                    'a' => [1, 2, [3]],
                    'b' => [11, 22, [33]],
                    'c' => [111, 222, [333]],
                ],
                ['*', '2', '*'],
                [3, 33, 333],
            ],
            [
                [
                    'a' => [1, 2, 3],
                    'b' => [11, 22, 33],
                    'c' => [111, 222, 333],
                ],
                '*.*',
                [1, 2, 3, 11, 22, 33, 111, 222, 333],
            ],
            [
                [
                    [
                        'a' => [1, 2, 3],
                        'b' => [11, 22, 33],
                        'c' => [111, 222, 333],
                    ],
                ],
                '*.*.0',
                [1, 11, 111],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.*.0',
                [1, 11, 111, 4, 44, 444],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.a',
                [[1, 2, 3], [4, 5]],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.a.0',
                [1, 4],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.a.*',
                [1, 2, 3, 4, 5],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.b.|.1',
                [44, 55],
            ],
            [
                [
                    'first' => [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    'second' => [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                        ],
                    ],
                ],
                '*.*.*.1',
                [2, 22, 222, 5, 55],
            ],
            [
                [
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => [
                        [
                            [
                                [1, 2, 3],
                                [11, 22, 33],
                                [111, 222, 333],
                            ],
                            [
                                [1111],
                                [11111],
                            ],
                        ],
                        [
                            [
                                [111111],
                                [1111111],
                            ],
                        ],
                    ],
                ],
                'second.*.*.*.0',
                [1, 11, 111, 1111, 11111, 111111, 1111111],
            ],
            [
                [
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => [
                        [
                            [
                                [1, 2, 3],
                                [11, 22, 33],
                                [111, 222, 333],
                            ],
                            [
                                [1111],
                                [11111],
                            ],
                        ],
                        [
                            [
                                [111111],
                                [1111111],
                            ],
                        ],
                    ],
                ],
                'second.*.*.0.0',
                [1, 1111, 111111],
            ],
            [
                [
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => [
                        [
                            [
                                [1, 2, 3],
                                [11, 22, 33],
                                [111, 222, 333],
                            ],
                            [
                                [1111],
                                [11111],
                            ],
                        ],
                        [
                            [
                                [111111],
                                [1111111],
                            ],
                        ],
                    ],
                ],
                'second.*.*.0.*',
                [1, 2, 3, 1111, 111111],
            ],
            [
                [
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => [
                        [
                            [
                                [1, 2, 3],
                                [11, 22, 33],
                                [111, 222, 333],
                            ],
                            [
                                [1111],
                                [2222],
                            ],
                        ],
                        [
                            [
                                [11111],
                                [222222],
                            ],
                        ],
                    ],
                ],
                'second.0.0.0',
                [1, 2, 3],
            ],
            [
                [
                    'a' => [
                        [
                            'b' => [
                                [
                                    'c' => [
                                        [
                                            'd' => 1,
                                            'e' => [1, 2, 3],
                                        ]
                                    ],
                                    'f' => [
                                        [
                                            'd' => 2,
                                            'e' => [4, 5, 6],
                                        ]
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                        [
                                            'd' => 3,
                                            'e' => [7, 8, 9],
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'a.*****.d',
                [1, 2, 3],
            ],
            [
                [
                    'a' => [
                        [
                            'b' => [
                                [
                                    'c' => [
                                        [
                                            'd' => 1,
                                            'e' => [1, 2, 3],
                                        ]
                                    ],
                                    'f' => [
                                        [
                                            'd' => 2,
                                            'e' => [4, 5, 6],
                                        ]
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                        [
                                            'd' => 3,
                                            'e' => [7, 8, 9],
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'a.*****.e',
                [[1, 2, 3], [4, 5, 6], [7, 8, 9]],
            ],
            [
                [
                    'a' => [
                        [
                            'b' => [
                                [
                                    'c' => [
                                        [
                                            'd' => 1,
                                            'e' => [1, 2, 3],
                                        ]
                                    ],
                                    'f' => [
                                        [
                                            'd' => 2,
                                            'e' => [4, 5, 6],
                                        ]
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                        [
                                            'd' => 3,
                                            'e' => [7, 8, 9],
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'a.*****.|.1.e',
                [4, 5, 6],
            ],
        ];
    }

    public function dataProviderForStrictSuccessArrayObject(): array
    {
        return [
            [
                new \ArrayObject([]),
                [],
                new \ArrayObject([]),
            ],
            [
                new \ArrayObject([]),
                null,
                new \ArrayObject([]),
            ],
            [
                [],
                '*',
                [],
            ],
            [
                new \ArrayObject(['a' => 1]),
                [],
                new \ArrayObject(['a' => 1]),
            ],
            [
                new \ArrayObject([1, 2, 3]),
                null,
                new \ArrayObject([1, 2, 3]),
            ],
            [
                new \ArrayObject([1, 2, 3]),
                '*',
                [1, 2, 3],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => 4]),
                '*',
                [1, 2, 3, 4],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => 4]),
                0,
                1,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => 4]),
                '0',
                1,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => 4]),
                '2',
                3,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => 4]),
                2,
                3,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => 4]),
                'a',
                4,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => [1, 2, 'a' => 3]]),
                'a',
                [1, 2, 'a' => 3],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => [1, 2, 'a' => 3]]),
                'a.*',
                [1, 2, 3],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => 11, 'c' => 22]]),
                'a.*',
                [11, 22],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]]),
                'a.*',
                [[11], [22]],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]]),
                'a.*.0',
                [11, 22],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]]),
                'a.*.*',
                [11, 22],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [11, 22], 'c' => [33, 44]]]),
                'a.*.*',
                [11, 22, 33, 44],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]]),
                'a.*.0.0',
                [11, 33],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]]),
                'a.*.0.*',
                [11, 22, 33, 44],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]]),
                'a.*.0.1',
                [22, 44],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => [1, 2, 'b' => ['c', 'd', 'e']]]),
                'a.b',
                ['c', 'd', 'e'],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => [1, 2, 'b' => ['c', 'd', 'e']]]),
                'a.b.*',
                ['c', 'd', 'e'],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => ['c', 'd', 'e'], [11, 22]]]),
                'a.*.*',
                ['c', 'd', 'e', 11, 22],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [['c'], ['d'], ['e']], [[11], [22, 33]]]]),
                'a.*.*.*',
                ['c', 'd', 'e', 11, 22, 33],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [['c'], ['d'], ['e']], [[11], [22, 33]]]]),
                'a.*.*.0',
                ['c', 'd', 'e', 11, 22],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => ['c', 'd', 'e'], [11, 22]]]),
                ['a', '*', '*'],
                ['c', 'd', 'e', 11, 22],
            ],
            [
                new \ArrayObject([
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => new \ArrayObject([
                        [
                            [
                                new \ArrayObject([1, 2, 3]),
                                [11, 22, 33],
                                [111, 222, 333],
                            ],
                            [
                                [1111],
                                [11111],
                            ],
                        ],
                        [
                            [
                                [111111],
                                [1111111],
                            ],
                        ],
                    ]),
                ]),
                'second.*.*.*.0',
                [1, 11, 111, 1111, 11111, 111111, 1111111],
            ],
            [
                [
                    'a' => new \ArrayObject([
                        [
                            'b' => [
                                [
                                    'c' => [
                                        [
                                            'd' => 1,
                                            'e' => [1, 2, 3],
                                        ]
                                    ],
                                    'f' => [
                                        new \ArrayObject([
                                            'd' => 2,
                                            'e' => new \ArrayObject([4, 5, 6]),
                                        ])
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                        [
                                            'd' => 3,
                                            'e' => [7, 8, 9],
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ]),
                ],
                'a.*****.|.1.e',
                new \ArrayObject([4, 5, 6]),
            ],
        ];
    }

    public function dataProviderForStrictSuccessStdClass(): array
    {
        return [
            [
                (object)[],
                [],
                (object)[],
            ],
            [
                (object)[],
                null,
                (object)[],
            ],
            [
                (object)['a' => 1],
                [],
                (object)['a' => 1],
            ],
            [
                [
                    'a' => (object)[1, 2, 3],
                    'b' => (object)[11, 22, 33],
                    'c' => (object)[111, 222, 333],
                ],
                '*.0',
                [1, 11, 111],
            ],
            [
                [
                    'a' => (object)[1, 2, 3],
                    'b' => (object)[11, 22, 33],
                    'c' => (object)[111, 222, 333],
                ],
                ['*', 0],
                [1, 11, 111],
            ],
            [
                [
                    'a' => (object)[1, 2, 3],
                    'b' => (object)[11, 22, 33],
                    'c' => (object)[111, 222, 333],
                ],
                ['*', '2'],
                [3, 33, 333],
            ],
            [
                [
                    'a' => (object)[1, 2, (object)[3]],
                    'b' => (object)[11, 22, (object)[33]],
                    'c' => (object)[111, 222, (object)[333]],
                ],
                ['*', '2'],
                [(object)[3], (object)[33], (object)[333]],
            ],
            [
                [
                    'a' => (object)[1, 2, [3]],
                    'b' => (object)[11, 22, [33]],
                    'c' => (object)[111, 222, [333]],
                ],
                ['*', '2', '*'],
                [3, 33, 333],
            ],
            [
                [
                    [
                        'a' => (object)[1, 2, 3],
                        'b' => (object)[11, 22, 33],
                        'c' => (object)[111, 222, 333],
                    ],
                ],
                '*.*.0',
                [1, 11, 111],
            ],
            [
                [
                    [
                        [
                            'a' => (object)[1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => (object)[4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.a',
                [(object)[1, 2, 3], (object)[4, 5]],
            ],
            [
                [
                    [
                        (object)[
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        (object)[
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.a.0',
                [1, 4],
            ],
            [
                [
                    [
                        (object)[
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        (object)[
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.a.1',
                [2, 5],
            ],
            [
                [
                    [
                        (object)[
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        (object)[
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.b.|.1',
                [44, 55],
            ],
            [
                (object)[
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => [
                        [
                            [
                                (object)[1, 2, 3],
                                (object)[11, 22, 33],
                                (object)[111, 222, 333],
                            ],
                            [
                                [1111],
                                [11111],
                            ],
                        ],
                        [
                            [
                                [111111],
                                [1111111],
                            ],
                        ],
                    ],
                ],
                'second.*.*.*.0',
                [1, 11, 111, 1111, 11111, 111111, 1111111],
            ],
            [
                (object)[
                    'a' => [
                        [
                            'b' => [
                                [
                                    'c' => [
                                        (object)[
                                            'd' => 1,
                                            'e' => [1, 2, 3],
                                        ]
                                    ],
                                    'f' => [
                                        (object)[
                                            'd' => 2,
                                            'e' => [4, 5, 6],
                                        ]
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                        (object)[
                                            'd' => 3,
                                            'e' => [7, 8, 9],
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'a.*****.d',
                [1, 2, 3],
            ],
            [
                [
                    'a' => [
                        [
                            'b' => [
                                [
                                    'c' => [
                                        [
                                            'd' => 1,
                                            'e' => [1, 2, 3],
                                        ]
                                    ],
                                    'f' => [
                                        [
                                            'd' => 2,
                                            'e' => [4, 5, 6],
                                        ]
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                        [
                                            'd' => 3,
                                            'e' => [7, 8, 9],
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'a.*****.e',
                [[1, 2, 3], [4, 5, 6], [7, 8, 9]],
            ],
            [
                [
                    'a' => [
                        [
                            'b' => [
                                [
                                    'c' => [
                                        (object)[
                                            'd' => 1,
                                            'e' => [1, 2, 3],
                                        ]
                                    ],
                                    'f' => [
                                        (object)[
                                            'd' => 2,
                                            'e' => [4, 5, 6],
                                        ]
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                        (object)[
                                            'd' => 3,
                                            'e' => [7, 8, 9],
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'a.*****.|.1.e',
                [4, 5, 6],
            ],
        ];
    }

    public function dataProviderForStrictErrorArray(): array
    {
        return [
            [
                [],
                [''],
                ['', ''],
            ],
            [
                [],
                'test',
                ['test', ''],
            ],
            [
                [],
                'foo.bar',
                ['foo', ''],
            ],
            [
                [],
                'foo.bar.*',
                ['foo', ''],
            ],
            [
                [],
                'foo.*.bar',
                ['foo', ''],
            ],
            [
                [1, 2, 3, 'a' => 4],
                3,
                ['3', ''],
            ],
            [
                [1, 2, 3, 'a' => 4],
                'a.c',
                ['c', 'a'],
            ],
            [
                [1, 2, 3, 'a' => 4],
                'a.*',
                ['*', 'a'],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]],
                'a.*.1',
                ['1', 'a.*'],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]],
                'a.*.a',
                ['a', 'a.*'],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]],
                'a.*.test',
                ['test', 'a.*'],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]],
                'a.*.*.*',
                ['*', 'a.*.*'],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.1.0',
                ['1', 'a.*'],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.0.2',
                ['2', 'a.*.0'],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.0.*.a',
                ['a', 'a.*.0.*'],
            ],
            [
                [1, 2, 3, 'a' => [1, 2, 'b' => ['c', 'd', 'e']]],
                'a.c',
                ['c', 'a'],
            ],
            [
                [1, 2, 3, 'a' => [1, 2, 'b' => ['c', 'd', 'e']]],
                'a.c.*',
                ['c', 'a'],
            ],
            [
                [1, 2, 3, 'a' => ['b' => ['c', 'd', 'e'], [11, 22]]],
                'a.*.x.*',
                ['x', 'a.*'],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [['c'], ['d'], ['e']], [[], [22, 33]]]],
                'a.*.*.0',
                ['0', 'a.*.*'],
            ],
            [
                [1, 2, 3, 'a' => ['b' => ['c', 'd', 'e'], [11, 22]]],
                ['a', '*', 'y'],
                ['y', 'a.*'],
            ],
            [
                [
                    'a' => [1, 2, 3],
                    'b' => [11],
                    'c' => [111, 222, 333],
                ],
                '*.1',
                ['1', '*'],
            ],
            [
                [
                    'a' => [1],
                    'b' => [11, 22],
                    'c' => [111, 222, 333],
                ],
                ['*', 2],
                ['2', '*'],
            ],
            [
                [
                    'a' => [1, 2, [3]],
                    'b' => [11, 22, 33],
                    'c' => [111, 222, [333]],
                ],
                ['*', '2', '*'],
                ['*', '*.2'],
            ],
            [
                [
                    'a' => [1, 2, 3],
                    'b' => [11, 22, 33],
                    'c' => '[111, 222, 333]',
                ],
                '*.*',
                ['*', '*'],
            ],
            [
                [
                    [
                        'a' => [1, 2, 3],
                        'b' => '[11, 22, 33]',
                        'c' => [111, 222, 333],
                    ],
                ],
                '*.*.0',
                ['0', '*.*'],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.*.0',
                ['0', '*.*.*'],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.a',
                ['a', '*.*'],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.a.2',
                ['2', '*.*.a'],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.a.*.1',
                ['1', '*.*.a.*'],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.b.|.2',
                ['2', '*.*.b.|'],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4, 5],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.b.|.1',
                ['b', '*.*'],
            ],
            [
                [
                    'first' => [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    'second' => [
                        [
                            'a' => [4],
                            'b' => [44, 55],
                        ],
                    ],
                ],
                '*.*.*.1',
                ['1', '*.*.*'],
            ],
            [
                [
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => [
                        [
                            [
                                [1, 2, 3],
                                [11, 22, 33],
                                [111, 222, 333],
                            ],
                            [
                                [1111],
                                [11111],
                            ],
                        ],
                        [
                            [
                                [111111],
                                [],
                            ],
                        ],
                    ],
                ],
                'second.*.*.*.0',
                ['0', 'second.*.*.*'],
            ],
            [
                [
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => [
                        [
                            [
                                [1, 2, 3],
                                [11, 22, 33],
                                [111, 222, 333],
                            ],
                            [
                                [],
                                [11111],
                            ],
                        ],
                        [
                            [
                                [111111],
                                [1111111],
                            ],
                        ],
                    ],
                ],
                'second.*.*.0.*.0',
                ['0', 'second.*.*.0.*'],
            ],
            [
                [
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => [
                        [
                            [
                                [1, 2, 3],
                                [11, 22, 33],
                                [111, 222, 333],
                            ],
                            [
                                [1111],
                                [11111],
                            ],
                        ],
                        [
                            [],
                        ],
                    ],
                ],
                'second.*.*.0.*.*',
                ['0', 'second.*.*'],
            ],
            [
                [
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => [
                        [
                            [],
                            [
                                [1111],
                                [2222],
                            ],
                        ],
                        [
                            [
                                [11111],
                                [222222],
                            ],
                        ],
                    ],
                ],
                'second.0.0.0',
                ['0', 'second.0.0'],
            ],
            [
                [
                    'a' => [
                        [
                            'b' => [
                                [
                                    'c' => [
                                        [
                                            'd' => 1,
                                            'e' => [1, 2, 3],
                                        ]
                                    ],
                                    'f' => [
                                        [
                                            'e' => [4, 5, 6],
                                        ]
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                        [
                                            'd' => 3,
                                            'e' => [7, 8, 9],
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'a.*****.d',
                ['d', 'a.*.*.*.*.*'],
            ],
            [
                [
                    'a' => [
                        [
                            'b' => [
                                [
                                    'c' => [
                                        [
                                            'd' => 1,
                                        ]
                                    ],
                                    'f' => [
                                        [
                                            'd' => 2,
                                        ]
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                        [
                                            'd' => 3,
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'a.*****.e',
                ['e', 'a.*.*.*.*.*'],
            ],
            [
                [
                    'a' => [
                        [
                            'b' => [
                                [
                                    'c' => [
                                        [
                                            'd' => 1,
                                            'e' => [1, 2, 3],
                                        ]
                                    ],
                                    'f' => [
                                        [
                                            'd' => 2,
                                        ]
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                        [
                                            'd' => 3,
                                            'e' => [7, 8, 9],
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'a.*****.|.1.e',
                ['e', 'a.*.*.*.*.*.|.1'],
            ],
            [
                [
                    'a' => [
                        [
                            'b' => [
                                [
                                    'c' => [
                                        [
                                            'd' => 1,
                                            'e' => [1, 2, 3],
                                        ]
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'a.*****.|.1.e',
                ['1', 'a.*.*.*.*.*.|'],
            ],
        ];
    }

    public function dataProviderForNonStrictArray(): array
    {
        return [
            [
                [1, 2, 3, 'a' => 4],
                3,
                null,
            ],
            [
                [1, 2, 3, 'a' => 4],
                '3',
                null,
            ],
            [
                [1, 2, 3, 'a' => 4],
                'b',
                null,
            ],
            [
                [1, 2, 3, 'a' => [1, 2, 'a' => 3]],
                'b.*',
                null,
            ],
            [
                [1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]],
                'b.*.0',
                null,
            ],
            [
                [1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]],
                'b.*.*',
                null,
            ],
            [
                [1, 2, 3, 'a' => ['b' => [11, 22], 'c' => [33, 44]]],
                'b.*.*',
                null,
            ],
            [
                [1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'b.*.0.0',
                null,
            ],
            [
                [1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.0.2',
                [],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.1.0',
                [],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.111.1',
                [],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.0.*.111',
                [],
            ],
            [
                [1, 2, 3, 'a' => [1, 2, 'b' => ['c', 'd', 'e']]],
                'a.c',
                null,
            ],
            [
                [1, 2, 3, 'a' => [1, 2, 'b' => ['c', 'd', 'e']]],
                'a.c.*',
                null,
            ],
            [
                [1, 2, 3, 'a' => ['b' => ['c', 'd', 'e'], [11, 22]]],
                'a.*.e',
                [],
            ],
            [
                [1, 2, 3, 'a' => ['b' => [['c'], ['d'], ['e']], [[11], [22, 33]]]],
                'a.*.e.*',
                [],
            ],
            [
                [1, 2, 3, 'a' => [
                    'b' => [['c'], ['d'], ['e']],
                    [[11], [22, 33]]],
                ],
                'a.*.*.1',
                [33],
            ],
            [
                [1, 2, 3, 'a' => [
                    'b' => [['c'], ['d'], ['e']],
                    [[11], [22, 33]]],
                ],
                'a.**.1',
                [33],
            ],
            [
                [1, 2, 3, 'a' => [
                    'b' => [['c'], ['d'], ['e']],
                    [[11], [22, 33]]],
                ],
                'a.**.2',
                [],
            ],
            [
                [1, 2, 3, 'a' => [
                    'b' => [['c'], ['d'], ['e']],
                    [[11], [22, 33]]],
                ],
                'a.**.test',
                [],
            ],
            [
                [
                    'a' => [1, 2, 3],
                    'b' => [11, 22, 33],
                    'c' => [111, 222, 333],
                ],
                '*.3',
                [],
            ],
            [
                [
                    'a' => [1, 2, 3],
                    'b' => [11, 22, 33],
                    'c' => [111, 222, 333],
                ],
                ['*', 3],
                [],
            ],
            [
                [
                    'a' => [1, 2],
                    'b' => [11, 22, 33],
                    'c' => [111, 222, 333],
                ],
                ['*', '2'],
                [33, 333],
            ],
            [
                [
                    'a' => [1, 2],
                    'b' => [11, 22, [33]],
                    'c' => [111, 222, [333]],
                ],
                ['*', '2'],
                [[33], [333]],
            ],
            [
                [
                    'a' => [1, 2, [3]],
                    'b' => [11, 22],
                    'c' => [111, 222, [333]],
                ],
                ['*', '2', '*'],
                [3, 333],
            ],
            [
                [
                    [
                        'a' => [1, 2, 3],
                        'b' => [11, 22, 33],
                        'c' => [111, 222, 333],
                    ],
                ],
                '*.*.5',
                [],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.*.3',
                [],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.z',
                [],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.z.0',
                [],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.z.1',
                [],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.a.2',
                [3],
            ],
            [
                [
                    [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                            'c' => [444, 555],
                        ],
                    ],
                ],
                '*.*.b.|.z',
                null,
            ],
            [
                [
                    'first' => [
                        [
                            'a' => [1, 2, 3],
                            'b' => [11, 22, 33],
                            'c' => [111, 222, 333],
                        ],
                    ],
                    'second' => [
                        [
                            'a' => [4, 5],
                            'b' => [44, 55],
                        ],
                    ],
                ],
                '*.*.*.2',
                [3, 33, 333],
            ],
            [
                [
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => [
                        [
                            [
                                [1, 2, 3],
                                [11, 22, 33],
                                [111, 222, 333],
                            ],
                            [
                                [1111],
                                [11111],
                            ],
                        ],
                        [
                            [
                                [111111],
                                [1111111],
                            ],
                        ],
                    ],
                ],
                'second.*.*.*.2',
                [3, 33, 333],
            ],
            [
                [
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => [
                        [
                            [
                                [1, 2, 3],
                                [11, 22, 33],
                                [111, 222, 333],
                            ],
                            [
                                [1111],
                                [11111],
                            ],
                        ],
                        [
                            [
                                [111111, 999],
                                [1111111],
                            ],
                        ],
                    ],
                ],
                'second.*.*.0.1',
                [2, 999],
            ],
            [
                [
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => [
                        [
                            [
                                [1, 2, 3],
                                [11, 22, 33],
                                [111, 222, 333],
                            ],
                            [
                                [1111],
                                [11111],
                            ],
                        ],
                        [
                            [
                                [111111],
                                [1111111],
                            ],
                        ],
                    ],
                ],
                'second.*.*.2',
                [[111, 222, 333]],
            ],
            [
                [
                    'first' => [
                        [
                            [
                                'a' => [],
                                'b' => ['aaa'],
                                'c' => ['bbb'],
                            ],
                        ],
                    ],
                    'second' => [
                        [
                            [
                                [1, 2, 3],
                                [11, 22, 33],
                                [111, 222, 333],
                            ],
                            [
                                [1111],
                                [11111],
                            ],
                        ],
                        [
                            [
                                [111111],
                                [1111111],
                            ],
                        ],
                    ],
                ],
                'second.*.*.2.*',
                [111, 222, 333],
            ],
            [
                [
                    'a' => [
                        [
                            'b' => [
                                [
                                    'c' => [
                                        [
                                            'd' => 1,
                                            'e' => [1, 2, 3],
                                        ]
                                    ],
                                    'f' => [
                                        [
                                            'e' => [4, 5, 6],
                                        ]
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                        [
                                            'd' => 3,
                                            'e' => [7, 8, 9],
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'a.*****.d',
                [1, 3],
            ],
            [
                [
                    'a' => [
                        [
                            'b' => [
                                [
                                    'c' => [
                                        [
                                            'd' => 1,
                                        ]
                                    ],
                                    'f' => [
                                        [
                                            'd' => 2,
                                            'e' => [4, 5, 6],
                                        ]
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                        [
                                            'd' => 3,
                                            'e' => [7, 8, 9],
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'a.*****.e',
                [[4, 5, 6], [7, 8, 9]],
            ],
            [
                [
                    'a' => [
                        [
                            'b' => [
                                [
                                    'c' => [
                                        [
                                            'd' => 1,
                                            'e' => [1, 2, 3],
                                        ]
                                    ],
                                    'f' => [
                                        [
                                            'd' => 2,
                                            'e' => [4, 5, 6],
                                        ]
                                    ],
                                ],
                            ],
                            'i' => [
                                [
                                    'j' => [
                                        [
                                            'd' => 3,
                                            'e' => [7, 8, 9],
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                'a.*****.|.1.f',
                null,
            ],
        ];
    }

    public function dataProviderForNonStrictArrayObject(): array
    {
        return [
            [
                new \ArrayObject([1, 2, 3, 'a' => 4]),
                3,
                null,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => 4]),
                '3',
                null,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => 4]),
                'b',
                null,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => [1, 2, 'a' => 3]]),
                'b.*',
                null,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]]),
                'b.*.0',
                null,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]]),
                'b.*.*',
                null,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [11, 22], 'c' => [33, 44]]]),
                'b.*.*',
                null,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]]),
                'b.*.0.0',
                null,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]]),
                'a.*.0.2',
                [],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]]),
                'a.*.1.0',
                [],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]]),
                'a.*.111.1',
                [],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]]),
                'a.*.0.*.111',
                [],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => [1, 2, 'b' => ['c', 'd', 'e']]]),
                'a.c',
                null,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => [1, 2, 'b' => ['c', 'd', 'e']]]),
                'a.c.*',
                null,
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => ['c', 'd', 'e'], [11, 22]]]),
                'a.*.e',
                [],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => ['b' => [['c'], ['d'], ['e']], [[11], [22, 33]]]]),
                'a.*.e.*',
                [],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => [
                    'b' => [['c'], ['d'], ['e']],
                    [[11], [22, 33]]],
                ]),
                'a.*.*.1',
                [33],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => [
                    'b' => [['c'], ['d'], ['e']],
                    [[11], [22, 33]]],
                ]),
                'a.**.1',
                [33],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => [
                    'b' => [['c'], ['d'], ['e']],
                    [[11], [22, 33]]],
                ]),
                'a.**.2',
                [],
            ],
            [
                new \ArrayObject([1, 2, 3, 'a' => [
                    'b' => [['c'], ['d'], ['e']],
                    [[11], [22, 33]]],
                ]),
                'a.**.test',
                [],
            ],
            [
                new \ArrayObject([
                    'a' => [1, 2, 3],
                    'b' => [11, 22, 33],
                    'c' => [111, 222, 333],
                ]),
                '*.3',
                [],
            ],
            [
                new \ArrayObject([
                    'a' => [1, 2, 3],
                    'b' => [11, 22, 33],
                    'c' => [111, 222, 333],
                ]),
                ['*', 3],
                [],
            ],
            [
                new \ArrayObject([
                    'a' => [1, 2],
                    'b' => [11, 22, 33],
                    'c' => [111, 222, 333],
                ]),
                ['*', '2'],
                [33, 333],
            ],
            [
                new \ArrayObject([
                    'a' => [1, 2],
                    'b' => [11, 22, [33]],
                    'c' => [111, 222, [333]],
                ]),
                ['*', '2'],
                [[33], [333]],
            ],
            [
                new \ArrayObject([
                    'a' => [1, 2, [3]],
                    'b' => [11, 22],
                    'c' => [111, 222, [333]],
                ]),
                ['*', '2', '*'],
                [3, 333],
            ],
            [
                new \ArrayObject([
                    new \ArrayObject([
                        'a' => new \ArrayObject([1, 2, 3]),
                        'b' => new \ArrayObject([11, 22, 33]),
                        'c' => new \ArrayObject([111, 222, 333]),
                    ]),
                ]),
                '*.*.5',
                [],
            ],
            [
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        new \ArrayObject([
                            'b' => new \ArrayObject([
                                new \ArrayObject([
                                    'c' => new \ArrayObject([
                                        new \ArrayObject([
                                            'd' => 1,
                                            'e' => [1, 2, 3],
                                        ])
                                    ]),
                                    'f' => new \ArrayObject([
                                        new \ArrayObject([
                                            'e' => [4, 5, 6],
                                        ])
                                    ]),
                                ]),
                            ]),
                            'i' => new \ArrayObject([
                                new \ArrayObject([
                                    'j' => new \ArrayObject([
                                        new \ArrayObject([
                                            'd' => 3,
                                            'e' => [7, 8, 9],
                                        ])
                                    ])
                                ]),
                            ]),
                        ]),
                    ]),
                ]),
                'a.*****.d',
                [1, 3],
            ],
        ];
    }

    public function dataProviderForNonStrictStdClass(): array
    {
        return [
            [
                (object)[1, 2, 3, 'a' => 4],
                3,
                null,
            ],
            [
                (object)[1, 2, 3, 'a' => 4],
                '3',
                null,
            ],
            [
                (object)[1, 2, 3, 'a' => 4],
                'b',
                null,
            ],
            [
                (object)[1, 2, 3, 'a' => [1, 2, 'a' => 3]],
                'b.*',
                null,
            ],
            [
                (object)[1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]],
                'b.*.0',
                null,
            ],
            [
                (object)[1, 2, 3, 'a' => ['b' => [11], 'c' => [22]]],
                'b.*.*',
                null,
            ],
            [
                (object)[1, 2, 3, 'a' => ['b' => [11, 22], 'c' => [33, 44]]],
                'b.*.*',
                null,
            ],
            [
                (object)[1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'b.*.0.0',
                null,
            ],
            [
                (object)[1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.0.2',
                [],
            ],
            [
                (object)[1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.1.0',
                [],
            ],
            [
                (object)[1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.111.1',
                [],
            ],
            [
                (object)[1, 2, 3, 'a' => ['b' => [[11, 22]], 'c' => [[33, 44]]]],
                'a.*.0.*.111',
                [],
            ],
            [
                (object)[1, 2, 3, 'a' => [1, 2, 'b' => ['c', 'd', 'e']]],
                'a.c',
                null,
            ],
            [
                (object)[1, 2, 3, 'a' => [1, 2, 'b' => ['c', 'd', 'e']]],
                'a.c.*',
                null,
            ],
            [
                (object)[1, 2, 3, 'a' => ['b' => ['c', 'd', 'e'], [11, 22]]],
                'a.*.e',
                [],
            ],
            [
                (object)[1, 2, 3, 'a' => ['b' => [['c'], ['d'], ['e']], [[11], [22, 33]]]],
                'a.*.e.*',
                [],
            ],
        ];
    }

    public function dataProviderForStrictSuccessCitiesExample(): array
    {
        $cities = [
            [
                'name' => 'London',
                'country' => [
                    'id' => 111,
                    'name' => 'UK',
                ],
                'streets' => [
                    [
                        'id' => 1000,
                        'name' => 'Carnaby Street',
                        'houses' => [1, 5, 9],
                    ],
                    [
                        'id' => 1002,
                        'name' => 'Abbey Road',
                        'houses' => [22, 35, 49],
                    ],
                    [
                        'id' => 1003,
                        'name' => 'Brick Lane',
                        'houses' => [11, 12, 15],
                    ],
                ],
            ],
            [
                'name' => 'Berlin',
                'country' => [
                    'id' => 222,
                    'name' => 'Germany',
                ],
                'streets' => [
                    [
                        'id' => 2000,
                        'name' => 'Oderbergerstrasse',
                        'houses' => [2, 6, 12],
                    ],
                ],
            ],
            [
                'name' => 'Madrid',
                'country' => [
                    'id' => 333,
                    'name' => 'Spain',
                ],
                'streets' => [],
            ],
        ];

        return [
            [
                $cities,
                '*.name',
                ['London', 'Berlin', 'Madrid'],
            ],
            [
                $cities,
                '*.country.name',
                ['UK', 'Germany', 'Spain'],
            ],
            [
                $cities,
                '*.streets.*.name',
                ['Carnaby Street', 'Abbey Road', 'Brick Lane', 'Oderbergerstrasse'],
            ],
            [
                $cities,
                '*.streets.*.houses.*',
                [1, 5, 9, 22, 35, 49, 11, 12, 15, 2, 6, 12],
            ],
            [
                $cities,
                '*.streets.*.houses',
                [[1, 5, 9], [22, 35, 49], [11, 12, 15], [2, 6, 12]],
            ],
        ];
    }

    public function dataProviderForNonStrictCitiesExample(): array
    {
        $cities = [
            [
                'name' => 'London',
                'country' => [
                    'id' => 111,
                    'name' => 'UK',
                ],
                'streets' => [
                    [
                        'id' => 1000,
                        'name' => 'Carnaby Street',
                        'houses' => [1, 5, 9],
                    ],
                    [
                        'id' => 1002,
                        'name' => 'Abbey Road',
                        'houses' => [22, 35, 49],
                    ],
                    [
                        'id' => 1003,
                        'name' => 'Brick Lane',
                    ],
                ],
            ],
            [
                'name' => 'Berlin',
                'country' => [
                    'id' => 222,
                    'name' => 'Germany',
                ],
                'streets' => [
                    [
                        'id' => 2000,
                        'name' => 'Oderbergerstrasse',
                        'houses' => [2, 6, 12],
                    ],
                ],
            ],
            [
                'name' => 'Madrid',
                'country' => [
                    'id' => 333,
                    'name' => 'Spain',
                ],
            ],
        ];

        return [
            [
                $cities,
                '*.name',
                ['London', 'Berlin', 'Madrid'],
            ],
            [
                $cities,
                '*.country.name',
                ['UK', 'Germany', 'Spain'],
            ],
            [
                $cities,
                '*.streets.*.name',
                ['Carnaby Street', 'Abbey Road', 'Brick Lane', 'Oderbergerstrasse'],
            ],
            [
                $cities,
                '*.streets.*.houses.*',
                [1, 5, 9, 22, 35, 49, 2, 6, 12],
            ],
            [
                $cities,
                '*.streets.*.houses',
                [[1, 5, 9], [22, 35, 49], [2, 6, 12]],
            ],
            [
                $cities,
                '*.streets.*.test',
                [],
            ],
            [
                $cities,
                'streets.*.test',
                null,
            ],
            [
                $cities,
                '*.name.*.test',
                [],
            ],
            [
                $cities,
                '0.name.*',
                null,
            ],
        ];
    }
}
