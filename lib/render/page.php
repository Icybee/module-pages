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

class PageRenderer
{
	public function __invoke($page)
	{
		global $core;

		require_once \ICanBoogie\DOCUMENT_ROOT . 'user-startup.php';

		#
		# The page body is rendered before the template is parsed.
		#

		if ($page->body && is_callable(array($page->body, 'render')))
		{
			$page->body->render();
		}

		# template

		$template_pathname = $this->resolve_template_pathname($page->template);
		$template = file_get_contents($template_pathname);
		$document = $core->document;
		$engine = $this->resolve_engine($template);
		$engine->context['document'] = $document;

		$html = $engine($template, $page, [ 'file' => $template_pathname ]);

		#
		# late replace
		#

		$html = preg_replace('#\<\!-- document-css-placeholder-[^\s]+ --\>#', (string) $document->css, $html);

		#

		$markup = '<!-- $document.js -->';
		$pos = strpos($html, $markup);

		if ($pos !== false)
		{
			$html = substr($html, 0, $pos) . $document->js . substr($html, $pos + strlen($markup));
		}
		else
		{
			$html = str_replace('</body>', PHP_EOL . PHP_EOL . $document->js . PHP_EOL . '</body>', $html);
		}

		return $html;
	}

	protected function resolve_template_pathname($name)
	{
		global $core;

		$root = \ICanBoogie\DOCUMENT_ROOT;
		$pathname = $core->site->resolve_path('templates/' . $name);

		if (!$pathname)
		{
			throw new Exception('Unable to resolve path for template: %template', array('%template' => $pathname));
		}

		return $root . $pathname;
	}

	protected function resolve_engine($template)
	{
		return new \Patron\Engine;
	}
}