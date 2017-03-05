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

use ICanBoogie\ActiveRecord;

use Brickrouge\Element;
use Brickrouge\ElementIsEmpty;

use ICanBoogie\Binding\PrototypedBindings;
use Patron\Engine as Patron;

class LanguagesElement extends Element
{
	use PrototypedBindings;

	static public function markup(array $args, Patron $patron, $template)
	{
		if ($template)
		{
			throw new \Exception('Templates are currently not supported :(');
		}

		return new static();
	}

	public function __construct(array $attributes = [])
	{
		parent::__construct('div', [

			'class' => 'btn-group i18n-languages'

		]);
	}

	protected function render_inner_html()
	{
		$page = $this->app->request->context->page;
		$translations_by_language = $this->collect();

		new LanguagesElement\CollectEvent($this, [ 'languages' => &$translations_by_language ]);

		if (count($translations_by_language) == 1)
		{
			throw new ElementIsEmpty;
		}

		$page_language = $page->language;
		$links = [];

		foreach ($translations_by_language as $language => $record)
		{
			$link = new Element('a', [

				Element::INNER_HTML => $language,

				'class' => 'btn language--' . \Brickrouge\normalize($language),
				'href' => $record->url

			]);

			if ($language == $page_language)
			{
				$link->add_class('active');
			}

			$links[$language] = $link;
		}

		new LanguagesElement\AlterEvent($this, [ 'links' => &$links, 'languages' => &$translations_by_language, 'page' => $page ]);

		return implode('', $links);
	}

	protected function collect()
	{
		$app = $this->app;
		$page = $app->request->context->page;
		$source = $page->node ?: $page;
		$translations = $source->translations;
		$translations_by_language = [];

		if ($translations)
		{
			$translations[$source->nid] = $source;
			$translations_by_language = array_flip
			(
				$app->models['sites']->select('language')->where('status = 1')->order('weight, site_id')->all(\PDO::FETCH_COLUMN)
			);

			if ($source instanceof Page)
			{
				foreach ($translations as $translation)
				{
					if (!$translation->is_accessible)
					{
						continue;
					}

					$translations_by_language[$translation->language] = $translation;
				}
			}
			else // nodes
			{
				foreach ($translations as $translation)
				{
					if (!$translation->is_online)
					{
						continue;
					}

					$translations_by_language[$translation->language] = $translation;
				}
			}

			foreach ($translations_by_language as $language => $translation)
			{
				if (is_object($translation))
				{
					continue;
				}

				unset($translations_by_language[$language]);
			}
		}

		if (!$translations_by_language)
		{
			$translations_by_language = [

				($source->language ? $source->language : $page->language) => $source

			];
		}

		return $translations_by_language;
	}
}
