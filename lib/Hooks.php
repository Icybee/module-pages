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

use function ICanBoogie\app;
use ICanBoogie\AppConfig;
use ICanBoogie\Application;
use ICanBoogie\FileCache;
use ICanBoogie\HTTP\RequestDispatcher;

use Brickrouge\Element;

use Patron\Engine as Patron;

use Icybee\Element\Document;
use Icybee\Modules\Files\File;
use Icybee\Modules\Sites\Site;

class Hooks
{
	/**
	 * Adds the `pages` dispatcher, which serves pages managed by the module.
	 *
	 * @param RequestDispatcher\AlterEvent $event
	 * @param RequestDispatcher $target
	 */
	static public function on_http_dispatcher_alter(RequestDispatcher\AlterEvent $event, RequestDispatcher $target)
	{
		$event->insert('page', PageDispatcher::class);
	}

	/**
	 * The callback is called when the `Icybee\Modules\Files\File::move` event is triggered,
	 * allowing us to update content to the changed path of resource.
	 *
	 * @param File\MoveEvent $event
	 * @param File $target
	 */
	static public function on_file_move(File\MoveEvent $event, File $target)
	{
		app()->models['pages/contents']->execute
		(
			'UPDATE {self} SET content = REPLACE(content, ?, ?)', [ $event->from, $event->to ]
		);
	}

	/**
	 * The callback is called when the `Icybee\Modules\Pages\Page::move` event is triggered,
	 * allowing us to update content to the changed url of the page.
	 *
	 * Note that *only* url within something that looks like a HTML attribute are updated, the
	 * matching pattern is ~="<url>("|/)~
	 *
	 * @param Page\MoveEvent $event
	 * @param Page $target
	 */
	static public function on_page_move(Page\MoveEvent $event, Page $target)
	{
		try
		{
			$model = app()->models['pages/contents'];
		}
		catch (\Exception $e) { return; }

		$old = $event->from;
		$new = $event->to;

		if (!$old)
		{
			return;
		}

		foreach ($model->where('content LIKE ?', '%' . $old . '%') as $record)
		{
			$content = $record->content;
			$content = preg_replace('~=\"' . preg_quote($old, '~') . '(\"|\/)~', '="' . $new . '$1', $content);

			if ($content == $record->content)
			{
				continue;
			}

			$model->execute('UPDATE {self} SET content = ? WHERE page_id = ? AND content_id = ?', [

				$content, $record->page_id, $record->content_id

			]);
		}
	}

	/**
	 * An operation (save, delete, online, offline) has invalidated the cache, thus we have to
	 * reset it.
	 */
	static public function invalidate_cache()
	{
		$cache = new FileCache([

			FileCache::T_REPOSITORY => app()->config[AppConfig::REPOSITORY_CACHE] . '/pages'

		]);

		return $cache->clear();
	}

	/**
	 * Returns the current page.
	 *
	 * This getter is a shortcut for the `request->context->page` property.
	 *
	 * @param Application $app
	 *
	 * @return Page
	 */
	static public function get_page(Application $app)
	{
		return $app->request->context->page;
	}

	/**
	 * Returns the home page of the target site.
	 *
	 * @param \Icybee\Modules\Sites\Site $site
	 *
	 * @return Page|null The home page of the target site or `null` if there is none.
	 */
	static public function get_home(Site $site)
	{
		return app()->models['pages']->find_home($site->site_id);
	}

	/**
	 * Render a {@link Page} instance into a string using a {@link PageRenderer} instance.
	 *
	 * @param Page $page
	 *
	 * @return string
	 */
	static public function render_page(Page $page)
	{
		$renderer = new PageRenderer;

		return $renderer($page);
	}

	/*
	 * Events
	 */

	static public function before_document_render_title(Document\BeforeRenderTitleEvent $event, Document $target)
	{
		$page = self::get_request_page();

		$event->title = $page->title . ' − ' . $page->site->title;
	}

	/*
	 * Markups
	 */

	static public function markup_page_region(array $args, Patron $patron, $template)
	{
		$id = $args['id'];
		$page = self::get_request_page();
		$element = new Element('div', [ 'id' => $id, 'class' => "region region-$id" ]);
		$html = null;

		new Page\RenderRegionEvent($page, [

			'id' => $id,
			'page' => $page,
			'element' => $element,
			'html' => &$html

		]);

		if (!$html)
		{
			return null;
		}

		$element[Element::INNER_HTML] = $html;

		return $element;
	}

	static public function markup_page_title(array $args, Patron $engine, $template)
	{
		$page = self::get_request_page();
		$title = $page->title;
		$html = \ICanBoogie\escape($title);

		new Page\RenderTitleEvent($page, [ 'title' => $title, 'html' => &$html ]);

		return $template ? $engine($template, $html) : $html;
	}

	/**
	 * Defines an editable page content in a template.
	 *
	 * <pre>
	 * <p:page:content
	 *     id = qname
	 *     title = string
	 *     editor = string
	 *     inherit = boolean>
	 *     <!-- Content: with-param*, template? -->
	 * </p:page:content>
	 * </pre>
	 *
	 * The `id` attribute specifies the identifier of the content, it is required and must be
	 * unique in the template. The `title` attribute specifies the label of the editor in the
	 * page editor, it is required. The `editor` attribute specifies the editor to use to edit
	 * the content, it is optional. The `inherit` attribute specifies that the content can be
	 * inherited.
	 *
	 * Example:
	 *
	 * <pre>
	 * <p:page:content id="body" title="Page body" />
	 *
	 * <p:page:content id="picture" title="Decorating picture" editor="image" inherit="inherit">
	 * <img src="#{@path}" alt="#{@alt}" />
	 * </p>
	 *
	 * </pre>
	 *
	 * @param array $args
	 * @param \Patron\Engine $patron
	 * @param mixed $template
	 *
	 * @return mixed
	 */
	static public function markup_page_content(array $args, Patron $patron, $template)
	{
		$render = $args['render'];

		if ($render === 'none')
		{
			return null;
		}

		$page = self::get_request_page();
		$content_id = $args['id'];
		$contents = array_key_exists($content_id, $page->contents)
			? $page->contents[$content_id]
			: null;

		if (!$contents && !empty($args['inherit']))
		{
			$node = $page->parent;

			while ($node)
			{
				$node_contents = $node->contents;

				if (empty($node_contents[$content_id]))
				{
					$node = $node->parent;

					continue;
				}

				$contents = $node_contents[$content_id];

				break;
			}

			#
			# maybe the home page define the contents, but because the home page is not the parent
			# of pages on single language sites, we have to check it now.
			#

			if (!$contents)
			{
				$node_contents = $page->home->contents;

				if (isset($node_contents[$content_id]))
				{
					$contents = $node_contents[$content_id];
				}
			}
		}

		$editor = null;
		$rendered = null;

		if (is_string($contents))
		{
			$rendered = $contents;
		}
		else if ($contents)
		{
			$editor = $contents->editor;
			$rendered = $contents->render();
		}

		if (!$rendered)
		{
			return null;
		}

		$element = new Element('div', [

			'id' => 'content-' . $content_id,
			'class' => 'editor-' . \ICanBoogie\normalize($editor)

		]);

		$patron->context['self']['element'] = $element;

		$rc = $template ? $patron($template, $rendered) : $rendered;

		if (!$rc)
		{
			return null;
		}

		if (preg_match('#\.html$#', $page->template) && empty($args['no-wrapper']))
		{
			$element[Element::INNER_HTML] = $rc;
			$rc = $element;
		}

		$rc = self::handle_external_anchors($rc);

		return $rc;
	}

	/**
	 * Adds a blank target to external href.
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	static protected function handle_external_anchors($html)
	{
		return preg_replace_callback
		(
			'#<a\s+[^>]+>#', function($matches)
			{
				$str = array_shift($matches);

				preg_match_all('#([a-zA-Z0-9\-]+)\="([^"]+)#', $str, $matches, 0, PREG_SET_ORDER);

				if (empty($matches[1]))
				{
					return $str;
				}

				$attributes = array_combine($matches[1], $matches[2]);

				if (isset($attributes['href']))
				{
					if (preg_match('#^http(s)?://#', $attributes['href']))
					{
						$attributes['target'] = '_blank';
					}
				}

				$str = '<a';

				foreach ($attributes as $attribute => $value)
				{
					$str .= ' ' . $attribute . '="' . $value . '"';
				}

				$str .= '>';

				return $str;
			},

			$html
		);
	}

	/**
	 * Render a navigation element.
	 *
	 * <pre>
	 * <p:navigation
	 *     css-class-name = string
	 *     depth = int
	 *     from-level = int
	 *     min-children = int
	 *     parent = int|string|Page>
	 *     <!-- Content: p:with-param*, template? -->
	 * </p:navigation>
	 * </pre>
	 *
	 * The CSS class names to use by the navigation branch can be specified with the
	 * `css-class-names` parameter. The default is "'-constructor -slug -template'", which
	 * removes the constructor, slug, and template names. The maximum depth of the navigation is
	 * specified by the `depth` parameter. The starting level of the navigation is specified by
	 * the `from-level` parameter. Using the `min-children` parameter, navigation branches can be
	 * discarted if they don't include enough direct children. Finally, the `parent` parameter can
	 * be used to specify the parent of the navigation, which can be specified as a {@link Page}
	 * instance, an identifier, or a path.
	 *
	 * Note: By default a NavigationElement is returned, unless the element as a template, in which
	 * case ordered nodes from the blueprint are returned.
	 *
	 * @param array $args
	 * @param Patron $engine
	 * @param mixed $template
	 *
	 * @return bool|NavigationElement|void
	 *
	 * @throws \Exception
	 */
	static public function markup_navigation(array $args, Patron $engine, $template)
	{
		/* @var $model PageModel */

		$app = app();
		$page = self::get_request_page();
		$model = $app->models['pages'];
		$depth = $args['depth'];

		if ($args['from-level'])
		{
			$node = $page;
			$from_level = $args['from-level'];

			#
			# The current page level is smaller than the page level requested, the navigation is
			# canceled.
			#

			if ($node->depth < $from_level)
			{
				return null;
			}

			while ($node->depth > $from_level)
			{
				$node = $node->parent;
			}

			$parent_id = $node->nid;
		}
		else
		{
			$parent_id = $args['parent'];

			if ($parent_id instanceof Page)
			{
				$parent_id = $parent_id->nid;
			}
			else if ($parent_id && !is_numeric($parent_id))
			{
				$parent = $model->find_by_path($parent_id);

				if (!$parent)
				{
					throw new \Exception(\ICanBoogie\format("Unable to locate parent with path %path", [

						'path' => $parent_id

					]));
				}

				$parent_id = $parent->nid;
			}
		}

		$min_children = $args['min-children'];

		$blueprint = $model
		->blueprint($page->site_id)
		->subset($parent_id, $depth === null ? null : $depth - 1, function(BlueprintNode $node) use($min_children) {

			/* @var $node BlueprintNode|Page */

			if ($min_children && $min_children > count($node->children))
			{
				return true;
			}

			return (!$node->is_online || $node->is_navigation_excluded || $node->pattern);

		});

		$blueprint->populate();

		if ($template)
		{
			return $engine($template, $blueprint->ordered_nodes);
		}

		return new NavigationElement($blueprint, 'ol', [

			NavigationElement::CSS_CLASS_NAMES => $args['css-class-names']

		]);
	}

	/*
	 * Support
	 */

	/**
	 * @return Page
	 */
	static private function get_request_page()
	{
		return app()->request->context->page;
	}
}
