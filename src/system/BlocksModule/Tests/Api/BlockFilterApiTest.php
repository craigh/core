<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\BlocksModule\Tests\Api;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Zikula\BlocksModule\Api\ApiInterface\BlockFilterApiInterface;
use Zikula\BlocksModule\Api\BlockFilterApi;
use Zikula\BlocksModule\Entity\BlockEntity;

class BlockFilterApiTest extends TestCase
{
    /**
     * @var BlockFilterApiInterface
     */
    private $api;

    protected function setUp(): void
    {
        $request = new Request([
            // query params
            'q-a' => 1,
            'q-b' => 2
        ], [], [
            // attributes
            'foo' => 'bar',
            'fee' => 'bee',
            'fii' => 'bii',
            'int' => 9,
            '_route_params' => [
                'zee' => 'zar'
            ]
        ]);
        $request->setLocale('en');
        $requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestStack
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->api = new BlockFilterApi($requestStack);
    }

    /**
     * @covers BlockFilterApi::getFilterAttributeChoices
     */
    public function testGetFilterAttributeChoices(): void
    {
        $expected = [
            'foo' => 'foo',
            'fee' => 'fee',
            'fii' => 'fii',
            'int' => 'int',
            '_route_params' => '_route_params',
            'query param' => 'query param'
        ];
        $this->assertEquals($expected, $this->api->getFilterAttributeChoices());
    }

    /**
     * @covers BlockFilterApi::isDisplayable
     * @dataProvider filterProvider
     */
    public function testIsDisplayable(array $filter, bool $expected): void
    {
        $blockEntity = $this->getMockBuilder(BlockEntity::class)
            ->getMock();
        $blockEntity
            ->method('getLanguage')
            ->willReturn('en');
        $blockEntity
            ->method('getFilters')
            ->willReturn($filter);
        $this->assertEquals($expected, $this->api->isDisplayable($blockEntity));
    }

    public function filterProvider(): array
    {
        return [
            [[], true],
            [[[
                'attribute' => 'foo',
                'comparator' => '==',
                'value' => 'bar'
            ]], true],
            [[[
                'attribute' => 'foo',
                'comparator' => '==',
                'value' => 'bee'
            ]], false],
            [[[
                'attribute' => 'foo',
                'comparator' => '!=',
                'value' => 'bee'
            ]], true],
            [[[
                'attribute' => 'int',
                'comparator' => '==',
                'value' => 9
            ]], true],
            [[[
                'attribute' => 'int',
                'comparator' => '>=',
                'value' => 9
            ]], true],
            [[[
                'attribute' => 'int',
                'comparator' => '<=',
                'value' => 9
            ]], true],
            [[[
                'attribute' => 'int',
                'comparator' => '>',
                'value' => 8
            ]], true],
            [[[
                'attribute' => 'int',
                'comparator' => '<',
                'value' => 10
            ]], true],
            [[[
                'attribute' => 'int',
                'comparator' => 'in_array',
                'value' => '8,9,10'
            ]], true],
            [[[
                'attribute' => 'int',
                'comparator' => '!in_array',
                'value' => '10,11,12'
            ]], true],
            [[[
                'attribute' => 'int',
                'comparator' => 'in_array',
                'value' => ' 8,9     ,     10   '
            ]], true],
            [[
                [
                    'attribute' => 'foo',
                    'comparator' => '==',
                    'value' => 'bar'
                ],
                [
                    'attribute' => 'fee',
                    'comparator' => '==',
                    'value' => 'bee'
                ],
            ], true],
            [[
                [
                    'attribute' => 'foo',
                    'comparator' => '==',
                    'value' => 'bar'
                ],
                [
                    'attribute' => 'fee',
                    'comparator' => 'in_array',
                    'value' => ',baz,bee,bum'
                ],
                [
                    'attribute' => 'int',
                    'comparator' => '>=',
                    'value' => '3'
                ],
            ], true],
            [[
                [
                    'attribute' => 'query param',
                    'queryParameter' => 'q-a',
                    'comparator' => '==',
                    'value' => '1'
                ]
            ], true],
            [[
                [
                    'attribute' => 'query param',
                    'queryParameter' => 'q-b',
                    'comparator' => '==',
                    'value' => '2'
                ]
            ], true],
            [[
                [
                    'attribute' => 'q-b',
                    'comparator' => '==',
                    'value' => '2'
                ]
            ], false],
            [[
                [
                    'attribute' => '_route_params',
                    'queryParameter' => 'zee',
                    'comparator' => '==',
                    'value' => 'zar'
                ]
            ], true],
            [[
                [
                    'attribute' => '_route_params',
                    'queryParameter' => 'foo',
                    'comparator' => '==',
                    'value' => 'zar'
                ]
            ], false],
        ];
    }
}
