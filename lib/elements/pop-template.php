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

class PopTemplate extends Element
{
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
			$path .= 'templates';

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
		$di = new \RegexIterator(new \DirectoryIterator($path), '#\.(html|xml)(\.patron)?$#');

		foreach ($di as $file)
		{
			$pathname = $file->getPathname();
			$name = basename($pathname);
			$templates[$name] = $pathname;
		}

		return $templates;
	}
}
