<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Widget;

use ArrayObject;
use Cake\Collection\Collection;
use Cake\TestSuite\TestCase;
use Cake\View\Form\NullContext;
use Cake\View\StringTemplate;
use Cake\View\Widget\SelectBoxWidget;

/**
 * SelectBox test case
 */
class SelectBoxWidgetTest extends TestCase
{
    /**
     * @var \Cake\View\Form\NullContext
     */
    protected $context;

    /**
     * @var \Cake\View\StringTemplate
     */
    protected $templates;

    /**
     * setup method.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $templates = [
            'select' => '<select name="{{name}}"{{attrs}}>{{content}}</select>',
            'selectMultiple' => '<select name="{{name}}[]" multiple="multiple"{{attrs}}>{{content}}</select>',
            'option' => '<option value="{{value}}"{{attrs}}>{{text}}</option>',
            'optgroup' => '<optgroup label="{{label}}"{{attrs}}>{{content}}</optgroup>',
        ];
        $this->context = new NullContext([]);
        $this->templates = new StringTemplate($templates);
    }

    /**
     * test render no options
     */
    public function testRenderNoOptions(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'id' => 'BirdName',
            'name' => 'Birds[name]',
            'options' => [],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test simple rendering
     */
    public function testRenderSimple(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'id' => 'BirdName',
            'name' => 'Birds[name]',
            'options' => ['a' => 'Albatross', 'b' => 'Budgie'],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            ['option' => ['value' => 'a']], 'Albatross', '/option',
            ['option' => ['value' => 'b']], 'Budgie', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render boolean options
     */
    public function testRenderBoolean(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'id' => 'enabled',
            'name' => 'enabled',
            'options' => [0 => 'No', 1 => 'Yes'],
            'val' => false,
        ];
        $result = $select->render($data, $this->context);
        $this->assertStringContainsString('<option value="0" selected="selected">No</option>', $result);

        $data['value'] = [false, 2];
        $result = $select->render($data, $this->context);
        $this->assertStringContainsString('<option value="0" selected="selected">No</option>', $result);
    }

    /**
     * Test render float options
     */
    public function testRenderFloat(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'id' => 'enabled',
            'name' => 'enabled',
            'options' => [0.5, 1.5],
            'val' => 0.5,
        ];
        $result = $select->render($data, $this->context);
        $this->assertStringContainsString('<option value="0">0.5</option>', $result);
        $this->assertStringContainsString('<option value="1">1.5</option>', $result);
    }

    /**
     * test simple iterator rendering
     */
    public function testRenderSimpleIterator(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $options = new ArrayObject(['a' => 'Albatross', 'b' => 'Budgie']);
        $data = [
            'name' => 'Birds[name]',
            'options' => $options,
            'empty' => true,
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]'],
            ['option' => ['value' => '']], '/option',
            ['option' => ['value' => 'a']], 'Albatross', '/option',
            ['option' => ['value' => 'b']], 'Budgie', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test simple iterator rendering with empty option
     */
    public function testRenderSimpleIteratorWithEmpty(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $options = new Collection(['a' => 'Albatross', 'b' => 'Budgie']);
        $data = [
            'name' => 'Birds[name]',
            'options' => $options,
            'empty' => 'Pick one',
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]'],
            ['option' => ['value' => '']], 'Pick one', '/option',
            ['option' => ['value' => 'a']], 'Albatross', '/option',
            ['option' => ['value' => 'b']], 'Budgie', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test complex option rendering
     */
    public function testRenderComplex(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'id' => 'BirdName',
            'name' => 'Birds[name]',
            'options' => [
                ['value' => 'a', 'text' => 'Albatross'],
                ['value' => 'b', 'text' => 'Budgie', 'data-foo' => 'bar'],
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            ['option' => ['value' => 'a']],
            'Albatross',
            '/option',
            ['option' => ['value' => 'b', 'data-foo' => 'bar']],
            'Budgie',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test rendering with a selected value
     */
    public function testRenderSelected(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'id' => 'BirdName',
            'name' => 'Birds[name]',
            'val' => '1',
            'options' => [
                1 => 'one',
                '1x' => 'one x',
                '2' => 'two',
                '2x' => 'two x',
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            ['option' => ['value' => '1', 'selected' => 'selected']], 'one', '/option',
            ['option' => ['value' => '1x']], 'one x', '/option',
            ['option' => ['value' => '2']], 'two', '/option',
            ['option' => ['value' => '2x']], 'two x', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $data['val'] = 2;
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            ['option' => ['value' => '1']], 'one', '/option',
            ['option' => ['value' => '1x']], 'one x', '/option',
            ['option' => ['value' => '2', 'selected' => 'selected']], 'two', '/option',
            ['option' => ['value' => '2x']], 'two x', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test complex option rendering with a selected value
     */
    public function testRenderComplexSelected(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'id' => 'BirdName',
            'name' => 'Birds[name]',
            'val' => 'a',
            'options' => [
                ['value' => 'a', 'text' => 'Albatross'],
                ['value' => 'b', 'text' => 'Budgie', 'data-foo' => 'bar'],
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            ['option' => ['value' => 'a', 'selected' => 'selected']],
            'Albatross',
            '/option',
            ['option' => ['value' => 'b', 'data-foo' => 'bar']],
            'Budgie',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test rendering a multi select
     */
    public function testRenderMultipleSelect(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'id' => 'BirdName',
            'name' => 'Birds[name]',
            'multiple' => true,
            'options' => ['a' => 'Albatross', 'b' => 'Budgie'],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'Birds[name][]',
                'id' => 'BirdName',
                'multiple' => 'multiple',
            ],
            ['option' => ['value' => 'a']], 'Albatross', '/option',
            ['option' => ['value' => 'b']], 'Budgie', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test rendering multi select & selected values
     */
    public function testRenderMultipleSelected(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'multiple' => true,
            'id' => 'BirdName',
            'name' => 'Birds[name]',
            'val' => ['1', '2', 'burp'],
            'options' => [
                1 => 'one',
                '1x' => 'one x',
                '2' => 'two',
                '2x' => 'two x',
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'Birds[name][]',
                'multiple' => 'multiple',
                'id' => 'BirdName',
            ],
            ['option' => ['value' => '1', 'selected' => 'selected']], 'one', '/option',
            ['option' => ['value' => '1x']], 'one x', '/option',
            ['option' => ['value' => '2', 'selected' => 'selected']], 'two', '/option',
            ['option' => ['value' => '2x']], 'two x', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test rendering with option groups
     */
    public function testRenderOptionGroups(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'name' => 'Birds[name]',
            'options' => [
                'Mammal' => [
                    'beaver' => 'Beaver',
                    'elk' => 'Elk',
                ],
                'Bird' => [
                    'budgie' => 'Budgie',
                    'eagle' => 'Eagle',
                ],
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'Birds[name]',
            ],
            ['optgroup' => ['label' => 'Mammal']],
            ['option' => ['value' => 'beaver']],
            'Beaver',
            '/option',
            ['option' => ['value' => 'elk']],
            'Elk',
            '/option',
            '/optgroup',
            ['optgroup' => ['label' => 'Bird']],
            ['option' => ['value' => 'budgie']],
            'Budgie',
            '/option',
            ['option' => ['value' => 'eagle']],
            'Eagle',
            '/option',
            '/optgroup',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test rendering with numeric option group keys
     */
    public function testRenderOptionGroupsIntegerKeys(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'name' => 'Year[key]',
            'options' => [
                2014 => [
                    'key' => 'value',
                ],
                2013 => [
                    'text' => '2013-text',
                    'options' => [
                        'key2' => 'value2',
                    ],
                ],
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'Year[key]',
            ],
            ['optgroup' => ['label' => '2014']],
            ['option' => ['value' => 'key']],
            'value',
            '/option',
            '/optgroup',
            ['optgroup' => ['label' => '2013-text']],
            ['option' => ['value' => 'key2']],
            'value2',
            '/option',
            '/optgroup',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test rendering with option groups and escaping
     */
    public function testRenderOptionGroupsEscape(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'name' => 'Birds[name]',
            'options' => [
                '>XSS<' => [
                    '1' => 'One>',
                ],
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'Birds[name]',
            ],
            ['optgroup' => ['label' => '&gt;XSS&lt;']],
            ['option' => ['value' => '1']],
            'One&gt;',
            '/option',
            '/optgroup',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $data['escape'] = false;
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'Birds[name]',
            ],
            ['optgroup' => ['label' => '>XSS<']],
            ['option' => ['value' => '1']],
            'One>',
            '/option',
            '/optgroup',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test rendering with option groups
     */
    public function testRenderOptionGroupsWithAttributes(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'name' => 'Birds[name]',
            'options' => [
                [
                    'text' => 'Mammal',
                    'data-foo' => 'bar',
                    'options' => [
                        'beaver' => 'Beaver',
                        'elk' => 'Elk',
                    ],
                ],
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'Birds[name]',
            ],
            ['optgroup' => ['data-foo' => 'bar', 'label' => 'Mammal']],
            ['option' => ['value' => 'beaver']],
            'Beaver',
            '/option',
            ['option' => ['value' => 'elk']],
            'Elk',
            '/option',
            '/optgroup',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test rendering with option groups with traversable nodes
     */
    public function testRenderOptionGroupsTraversable(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $mammals = new ArrayObject(['beaver' => 'Beaver', 'elk' => 'Elk']);
        $data = [
            'name' => 'Birds[name]',
            'options' => [
                'Mammal' => $mammals,
                'Bird' => [
                    'budgie' => 'Budgie',
                    'eagle' => 'Eagle',
                ],
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'Birds[name]',
            ],
            ['optgroup' => ['label' => 'Mammal']],
            ['option' => ['value' => 'beaver']],
            'Beaver',
            '/option',
            ['option' => ['value' => 'elk']],
            'Elk',
            '/option',
            '/optgroup',
            ['optgroup' => ['label' => 'Bird']],
            ['option' => ['value' => 'budgie']],
            'Budgie',
            '/option',
            ['option' => ['value' => 'eagle']],
            'Eagle',
            '/option',
            '/optgroup',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test rendering option groups and selected values
     */
    public function testRenderOptionGroupsSelectedAndDisabled(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'name' => 'Birds[name]',
            'val' => ['1', '2', 'burp'],
            'disabled' => ['1x', '2x', 'nope'],
            'options' => [
                'ones' => [
                    1 => 'one',
                    '1x' => 'one x',
                ],
                'twos' => [
                    '2' => 'two',
                    '2x' => 'two x',
                ],
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'Birds[name]',
            ],

            ['optgroup' => ['label' => 'ones']],
            ['option' => ['value' => '1', 'selected' => 'selected']], 'one', '/option',
            ['option' => ['value' => '1x', 'disabled' => 'disabled']], 'one x', '/option',
            '/optgroup',
            ['optgroup' => ['label' => 'twos']],
            ['option' => ['value' => '2', 'selected' => 'selected']], 'two', '/option',
            ['option' => ['value' => '2x', 'disabled' => 'disabled']], 'two x', '/option',
            '/optgroup',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test rendering a totally disabled element
     */
    public function testRenderDisabled(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'disabled' => true,
            'name' => 'Birds[name]',
            'options' => ['a' => 'Albatross', 'b' => 'Budgie'],
            'val' => 'a',
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'Birds[name]',
                'disabled' => 'disabled',
            ],
            ['option' => ['value' => 'a', 'selected' => 'selected']], 'Albatross', '/option',
            ['option' => ['value' => 'b']], 'Budgie', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $select = new SelectBoxWidget($this->templates);
        $data = [
            'disabled' => [1],
            'name' => 'numbers',
            'options' => ['1' => 'One', '2' => 'Two'],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'numbers',
            ],
            ['option' => ['value' => '1', 'disabled' => 'disabled']], 'One', '/option',
            ['option' => ['value' => '2']], 'Two', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test rendering a disabled element
     */
    public function testRenderDisabledMultiple(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'disabled' => ['a', 'c'],
            'val' => 'a',
            'name' => 'Birds[name]',
            'options' => [
                'a' => 'Albatross',
                'b' => 'Budgie',
                'c' => 'Canary',
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'Birds[name]',
            ],
            ['option' => ['value' => 'a', 'selected' => 'selected', 'disabled' => 'disabled']],
            'Albatross',
            '/option',
            ['option' => ['value' => 'b']],
            'Budgie',
            '/option',
            ['option' => ['value' => 'c', 'disabled' => 'disabled']],
            'Canary',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test complex option rendering with a disabled element
     */
    public function testRenderComplexDisabled(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'disabled' => ['b'],
            'id' => 'BirdName',
            'name' => 'Birds[name]',
            'options' => [
                ['value' => 'a', 'text' => 'Albatross'],
                ['value' => 'b', 'text' => 'Budgie', 'data-foo' => 'bar'],
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            ['option' => ['value' => 'a']],
            'Albatross',
            '/option',
            ['option' => ['value' => 'b', 'data-foo' => 'bar', 'disabled' => 'disabled']],
            'Budgie',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test rendering with an empty value
     */
    public function testRenderEmptyOption(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'id' => 'BirdName',
            'name' => 'Birds[name]',
            'empty' => true,
            'options' => ['a' => 'Albatross', 'b' => 'Budgie'],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            ['option' => ['value' => '']], '/option',
            ['option' => ['value' => 'a']], 'Albatross', '/option',
            ['option' => ['value' => 'b']], 'Budgie', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $data['empty'] = 'empty';
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            ['option' => ['value' => '']], 'empty', '/option',
            ['option' => ['value' => 'a']], 'Albatross', '/option',
            ['option' => ['value' => 'b']], 'Budgie', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $data['empty'] = ['99' => '(choose one)'];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            ['option' => ['value' => '99']], '(choose one)', '/option',
            ['option' => ['value' => 'a']], 'Albatross', '/option',
            ['option' => ['value' => 'b']], 'Budgie', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $data['empty'] = 'empty';
        $data['val'] = '';
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            ['option' => ['value' => '', 'selected' => 'selected']], 'empty', '/option',
            ['option' => ['value' => 'a']], 'Albatross', '/option',
            ['option' => ['value' => 'b']], 'Budgie', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering with disabling escaping.
     */
    public function testRenderEscapingOption(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'name' => 'Birds[name]',
            'options' => [
                'a' => '>Albatross',
                'b' => '>Budgie',
                'c' => '>Canary',
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'Birds[name]',
            ],
            ['option' => ['value' => 'a']],
            '&gt;Albatross',
            '/option',
            ['option' => ['value' => 'b']],
            '&gt;Budgie',
            '/option',
            ['option' => ['value' => 'c']],
            '&gt;Canary',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $data = [
            'escape' => false,
            'name' => 'Birds[name]',
            'options' => [
                '>a' => '>Albatross',
            ],
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'Birds[name]',
            ],
            ['option' => ['value' => '>a']],
            '>Albatross',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test render with null options
     */
    public function testRenderNullOptions(): void
    {
        $select = new SelectBoxWidget($this->templates);
        $data = [
            'id' => 'BirdName',
            'name' => 'Birds[name]',
            'options' => null,
        ];
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $data['empty'] = true;
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            ['option' => ['value' => '']], '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $data['empty'] = 'empty';
        $result = $select->render($data, $this->context);
        $expected = [
            'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
            ['option' => ['value' => '']], 'empty', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Ensure templateVars option is hooked up.
     */
    public function testRenderTemplateVars(): void
    {
        $this->templates->add([
            'select' => '<select custom="{{custom}}" name="{{name}}"{{attrs}}>{{content}}</select>',
            'option' => '<option opt="{{opt}}" value="{{value}}"{{attrs}}>{{text}}</option>',
        ]);

        $input = new SelectBoxWidget($this->templates);
        $data = [
            'templateVars' => ['custom' => 'value', 'opt' => 'option'],
            'name' => 'birds',
            'options' => [
                ['value' => 'a', 'text' => 'Albatross', 'templateVars' => ['opt' => 'opt-1']],
                'b' => 'Budgie',
                'c' => 'Canary',
            ],
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            'select' => [
                'name' => 'birds',
                'custom' => 'value',
            ],
            ['option' => ['value' => 'a', 'opt' => 'opt-1']],
            'Albatross',
            '/option',
            ['option' => ['value' => 'b', 'opt' => 'option']],
            'Budgie',
            '/option',
            ['option' => ['value' => 'c', 'opt' => 'option']],
            'Canary',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }
}
