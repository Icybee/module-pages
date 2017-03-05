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

use Brickrouge\Element;

use ICanBoogie\Binding\PrototypedBindings;
use ICanBoogie\Render\TemplateName;

class PopTemplate extends Element
{
	use PrototypedBindings;

	public function __construct(array $attributes = [])
	{
		parent::__construct('select', $attributes);
	}

	public function render()
	{
		$names = $this->collect_template_names();

		if (!$names)
		{
			return '<p class="warn">There is no template available.</p>';
		}

		$this[self::OPTIONS] = [ null => '<auto>' ] + array_combine($names, $names);

		return parent::render();
	}

	private function collect_template_names()
	{
		$names = [];

		foreach (array_keys($this->collect_all_templates()) as $basename)
		{
			$names[] = basename($basename, '.patron');
		}

		return $names;
	}

	private function collect_all_templates()
	{
		$templates = [];
		$paths = \ICanBoogie\get_autoconfig()['app-paths'];

		foreach ($paths as $path)
		{
			$path .= 'templates/pages';

			if (!file_exists($path))
			{
				continue;
			}

			$templates = array_merge($templates, $this->collect_templates($path));
		}

		return $templates;
	}

	private function collect_templates($path)
	{
		$templates = [];
		$extensions = $this->get_extensions();
		$extensions = array_map(function($extension) {

			return '\\' . $extension;

		}, $extensions);

		$pattern = implode('|', $extensions);

		$di = new \DirectoryIterator($path);
		$di = new \RegexIterator($di, '#^[^\\' . TemplateName::TEMPLATE_PREFIX_LAYOUT . '\\' . TemplateName::TEMPLATE_PREFIX_PARTIAL . '].+(' . $pattern . ')$#');

		foreach ($di as $file)
		{
			$pathname = $file->getPathname();
			$name = basename($pathname);
			$templates[$name] = $pathname;
		}

		return $templates;
	}

	private function get_extensions()
	{
		/* @var \ICanBoogie\Application $app */

		$app = $this->app;

		return $app->template_engines->extensions;
	}
}
