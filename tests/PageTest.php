<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Pages;

class PageTest extends \PHPUnit_Framework_TestCase
{
	static private $instance;

	static public function setupBeforeClass()
	{
		self::$instance = new Page;
	}

	/**
	 * @dataProvider provide_test_readonly_properties
	 * @expectedException \ICanBoogie\PropertyNotWritable
	 * @param string $property Property name.
	 */
	public function test_readonly_properties($property)
	{
		self::$instance->$property = null;
	}

	public function provide_test_readonly_properties()
	{
		$properties = 'children_count depth descendants_count description document_title'
		. ' extension has_child home is_accessible is_active is_home is_trail location';

		return array_map(function($v) { return (array) $v; }, explode(' ', $properties));
	}

	/**
	 * @dataProvider provide_test_fallback_properties
	 */
	public function test_fallback_properties($property, $fixture, $expected)
	{
		$page = Page::from($fixture);
		$this->assertSame($expected, $page->$property);

		if (array_key_exists($property, $fixture))
		{
			$this->assertArrayHasKey($property, $page->to_array());
			$this->assertArrayHasKey($property, $page->__sleep());
		}
		else
		{
			$this->assertArrayNotHasKey($property, $page->to_array());
			$this->assertArrayNotHasKey($property, $page->__sleep());
		}
	}

	public function provide_test_fallback_properties()
	{
		return [

			[ 'label', [], null ],
			[ 'label', [ 'title' => 'madonna' ], 'madonna' ],
			[ 'label', [ 'title' => 'madonna', 'label' => 'lady gaga' ], 'lady gaga' ],

			[ 'template', [], 'page.html' ],
			[ 'template', [ 'template' => 'example.html' ], 'example.html' ],
			[ 'template', [ 'weight' => 0, 'is_online' => true ], 'home.html' ],
			[ 'template', [ 'parent' => Page::from([ 'template' => 'example.html' ]) ], 'example.html' ],
			[ 'template', [ 'parent' => Page::from([ 'weight' => 0, 'is_online' => true, 'template' => 'example.html' ]) ], 'page.html' ]

		];
	}

	/**
	 * @dataProvider provide_test_get_property
	 */
	public function test_get_property($property, $fixture, $expected)
	{
		$page = Page::from($fixture);

		$this->assertSame($expected, $page->$property);
	}

	public function provide_test_get_property()
	{
		return [

			[ 'is_home', [], false ],
			[ 'is_home', [ 'is_online' => true ], true ],
			[ 'is_home', [ 'is_online' => true, 'weight' => 1 ], false ],
			[ 'is_home', [ 'is_online' => true, 'weight' => 0, 'parentid' => 1 ], false ],

			[ 'extension', [], '.html' ],
			[ 'extension', [ 'template' => 'example.xml' ], '.xml' ]

		];
	}
}
