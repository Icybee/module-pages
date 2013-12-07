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
	 * @expectedException ICanBoogie\PropertyNotWritable
	 * @param string $property Property name.
	 */
	public function test_readonly_properties($property)
	{
		self::$instance->$property = null;
	}

	public function provide_test_readonly_properties()
	{
		$properties = 'children_count depth descendents_count description document_title'
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
		return array
		(
			array('label', array(), null),
			array('label', array('title' => 'madonna'), 'madonna'),
			array('label', array('title' => 'madonna', 'label' => 'lady gaga'), 'lady gaga'),

			array('template', array(), 'page.html'),
			array('template', array('template' => 'example.html'), 'example.html'),
			array('template', array('weight' => 0, 'is_online' => true), 'home.html'),
			array('template', array('parent' => Page::from(array('template' => 'example.html'))), 'example.html'),
			array('template', array('parent' => Page::from(array('weight' => 0, 'is_online' => true, 'template' => 'example.html'))), 'page.html')
		);
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
		return array
		(
			array('is_home', array(), false),
			array('is_home', array('is_online' => true), true),
			array('is_home', array('is_online' => true, 'weight' => 1), false),
			array('is_home', array('is_online' => true, 'weight' => 0, 'parentid' => 1), false),

			array('extension', array(), '.html'),
			array('extension', array('template' => 'example.xml'), '.xml')
		);
	}
}