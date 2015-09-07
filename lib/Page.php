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

use ICanBoogie\Routing\Pattern;

use Icybee\Binding\Core\PrototypedBindings;
use Icybee\Modules\Nodes\Node;
use Icybee\Modules\Sites\Site;

/**
 * Representation of a page.
 *
 * @method string render() Renders the instance into a string. This is a prototype method.
 *
 * @property-read PageModel $model
 * @property Page $next
 * @property Page $previous
 *
 * @property-read string $absolute_url The absolute URL of the page.
 * @property Content $body
 * @property Page[] $children
 * @property-read int $children_count
 * @property Content[] $contents
 * @property-read int $depth The depth of the page in the hierarchy.
 * @property-read int $descendants_count The number of descendants.
 * @property-read string $description
 * @property-read string $document_title
 * @property-read string $extension
 * @property-read bool $has_child `true` if the page has children, `false` otherwise.
 * @property-read Page $home
 * @property-read bool $is_accessible Whether the page is accessible or not.
 * @property-read bool $is_active Whether the page is active or not.
 * @property-read bool $is_home Whether the page is the home page of the site or not.
 * @property-read bool $is_trail Whether the page is in the navigation trail or not.
 * @property-read Page $location The page this page redirects to.
 * @property Page[] $navigation_children Navigation children of the page.
 * @property Page $parent Parent page of the page.
 * @property-read string $template
 * @property-read string $url The URL of the page.
 * @property-read string $url_pattern The URL pattern of the page.
 */
class Page extends Node
{
	use PrototypedBindings;

	const MODEL_ID = 'pages';

	const PARENT_ID = 'parent_id';
	const LOCATIONID = 'location_id';
	const PATTERN = 'pattern';
	const WEIGHT = 'weight';
	const TEMPLATE = 'template';
	const LABEL = 'label';
	const IS_NAVIGATION_EXCLUDED = 'is_navigation_excluded';

	/**
	 * The identifier of the parent page.
	 *
	 * @var int
	 */
	public $parent_id;

	/**
	 * The identifier of the page the page is redirected to.
	 *
	 * @var int
	 */
	public $location_id;

	/**
	 * The pattern used to create the URL of the nodes displayed by the page.
	 *
	 * @var string
	 */
	public $pattern;

	/**
	 * Weight of the page in the hierarchy.
	 *
	 * @var int
	 */
	public $weight;

	/**
	 * Template used to render the page.
	 *
	 * @var string
	 */
	public $template;

	/**
	 * Returns the template for the page.
	 *
	 * This function is only called if the {@link pattern} property was empty during construct. The
	 * template is guested according the place of the page in the hierarchy:
	 *
	 * - The page is the home page: `home.html`
	 * - The page has a parent which is not the home page: the template of the parent.
	 * - Otherwise: `page.html`
	 *
	 * @return string
	 */
	protected function get_template()
	{
		if ($this->is_home)
		{
			return 'home.html';
		}
		else if ($this->parent && !$this->parent->is_home)
		{
			return $this->parent->template;
		}

		return 'page.html';
	}

	/**
	 * Returns the extension used by the page's template.
	 *
	 * @return string ".html" if the template is "page.html".
	 */
	protected function get_extension()
	{
		$extension = pathinfo($this->template, PATHINFO_EXTENSION);

		return $extension ? '.' . $extension : '.html';
	}

	/**
	 * The text to use instead of the title when it is used in the navigation of the breadcrumb.
	 *
	 * @var string
	 */
	public $label;

	/**
	 * Returns the label for the page.
	 *
	 * This function is only called if the {@link label} property was empty during construct. It
	 * returns the {@link $title} property.
	 *
	 * @return string
	 */
	protected function get_label()
	{
		return $this->title;
	}

	/**
	 * Whether the page is excluded from the navigation.
	 *
	 * @var bool
	 */
	public $is_navigation_excluded;

	/**
	 * @var string Part of the URL captured by the pattern.
	 * @todo-20130307: rename as "path_part"
	 */
	public $url_part;

	/**
	 * @var array Variables captured from the URL using the pattern.
	 * @todo-20130327: rename as "path_params"
	 */
	public $url_variables = [];

	/**
	 * @var Node Node object currently acting as the body of the page.
	 * @todo-20130327: use request's context instead
	 * @deprecated
	 */
	public $node;

	public function __construct($model = self::MODEL_ID)
	{
		if (empty($this->label))
		{
			unset($this->label);
		}

		if (empty($this->template))
		{
			unset($this->template);
		}

		parent::__construct($model);
	}

	public function __sleep()
	{
		$keys = parent::__sleep();

		// TODO-20130327: is this necessary?

		if (isset($this->template))
		{
			$keys['template'] = 'template';
		}

		return $keys;
	}

	public function __toString()
	{
		try
		{
			return (string) $this->render();
		}
		catch (\Exception $e)
		{
			return \ICanBoogie\Debug::format_alert($e);
		}
	}

	/**
	 * Returns the previous online sibling for the page.
	 *
	 * @return Page|false The previous sibling, or false if there is none.
	 */
	protected function lazy_get_previous()
	{
		return $this->model
		->where('is_online = 1 AND nid != ? AND parent_id = ? AND site_id = ? AND weight <= ?', $this->nid, $this->parent_id, $this->site_id, $this->weight)
		->order('weight desc, created_at desc')
		->one;
	}

	/**
	 * Returns the next online sibling for the page.
	 *
	 * @return Page|false The next sibling, or false if there is none.
	 */
	protected function lazy_get_next()
	{
		return $this->model
		->where('is_online = 1 AND nid != ? AND parent_id = ? AND site_id = ? AND weight >= ?', $this->nid, $this->parent_id, $this->site_id, $this->weight)
		->order('weight, created_at')->one;
	}

	/**
	 * Returns the URL of the page.
	 *
	 * @return string
	 */
	protected function get_url()
	{
		if ($this->location)
		{
			return $this->location->url;
		}

		$url_pattern = $this->url_pattern;

		if ($this->is_home)
		{
			return $url_pattern;
		}

		$url = null;

		if (!Pattern::is_pattern($url_pattern))
		{
			return $url_pattern;
		}

		if ($this->url_variables)
		{
			return (string) Pattern::from($url_pattern)->format($this->url_variables);
		}

		$page = isset($this->app->request->context->page) ? $this->app->request->context->page : null;

		if (!$page)
		{
			return '#url-pattern-could-not-be-resolved';
		}

		/* @var $page Page */

		return (string) Pattern::from($url_pattern)->format($page->url_variables);
	}

	/**
	 * Returns the absolute URL of the pages.
	 *
	 * @return string The absolute URL of the page.
	 */
	protected function get_absolute_url()
	{
		$site = $this->site;

		return $site->url . substr($this->url, strlen($site->path));
	}

	public function translation($language=null)
	{
		/* @var $translation Page */
		$translation = parent::translation($language);

		if ($translation->nid != $this->nid && isset($this->url_variables))
		{
			$translation->url_variables = $this->url_variables;
		}

		return $translation;
	}

	protected function lazy_get_translations()
	{
		$translations = parent::lazy_get_translations();

		if (!$translations || empty($this->url_variables))
		{
			return $translations;
		}

		foreach ($translations as $translation)
		{
			$translation->url_variables = $this->url_variables;
		}

		return $translations;
	}

	/**
	 * Returns the URL pattern of the page.
	 *
	 * @return string
	 */
	protected function get_url_pattern()
	{
		$site = $this->site;

		if ($this->is_home)
		{
			return $site->path . '/';
		}

		$parent = $this->parent;
		$pattern = $this->pattern;

		return ($parent ? $parent->url_pattern : $site->path . '/')
		. ($pattern ? $pattern : $this->slug)
		. ($this->has_child ? '/' : $this->extension);
	}

	/**
	 * Returns if the page is accessible or not in the navigation tree.
	 */
	protected function get_is_accessible()
	{
		if ($this->app->user->is_guest && $this->site->status != Site::STATUS_OK)
		{
			return false;
		}

		return ($this->parent && !$this->parent->is_accessible) ? false : $this->is_online;
	}

	/**
	 * Checks if the page is the home page.
	 *
	 * A page is considered a home page when the page has no parent, its weight value is zero and
	 * it is online.
	 *
	 * @return bool `true` if the page record is the home page, `false` otherwise.
	 */
	protected function get_is_home()
	{
		return (!$this->parent_id && !$this->weight && $this->is_online);
	}

	/**
	 * Checks if the page record is the active page.
	 *
	 * The global variable `page` must be defined in order to identify the active page.
	 *
	 * @return bool true if the page record is the active page, false otherwise.
	 *
	 * @todo-20130327: create the set_active_page() and get_active_page() helpers ?
	 */
	protected function get_is_active()
	{
		return $this->app->request->context->page->nid == $this->nid;
	}

	/**
	 * Checks if the page record is in the active page trail.
	 *
	 * The global variable `page` must be defined in order to identify the active page.
	 *
	 * @return bool true if the page is in the active page trail, false otherwise.
	 */
	protected function get_is_trail()
	{
		$node = $this->app->request->context->page;

		while ($node)
		{
			if ($node->nid == $this->nid)
			{
				return true;
			}

			$node = $node->parent;
		}

		return false;
	}

	/**
	 * Returns the location target for the page record.
	 *
	 * @return Page|null The location target, or null if there is none.
	 */
	protected function get_location()
	{
		return $this->location_id ? $this->model[$this->location_id] : null;
	}

	/**
	 * Returns the home page for the page record.
	 *
	 * @return Page
	 */
	protected function get_home()
	{
		return $this->model->find_home($this->site_id);
	}

	/**
	 * Returns the parent of the page.
	 *
	 * @return Page|null The parent page or null is the page has no parent.
	 */
	protected function lazy_get_parent()
	{
		return $this->parent_id ? $this->model[$this->parent_id] : null;
	}

	/**
	 * Return the online children page for this page.
	 *
	 * TODO-20100629: The `children` virtual property should return *all* the children for the page,
	 * we should create a `online_children` virtual property that returns only _online_ children,
	 * or maybe a `accessible_children` virtual property ?
	 */
	protected function lazy_get_children()
	{
		$blueprint = $this->model->blueprint($this->site_id);
		$pages = $blueprint['pages'];

		if (!$pages[$this->nid]->children)
		{
			return [];
		}

		$ids = [];

		foreach ($pages[$this->nid]->children as $nid => $child)
		{
			if (!$child->is_online)
			{
				continue;
			}

			$ids[] = $nid;
		}

		return $this->model->find($ids);
	}

	/**
	 * Returns the page's children that are online and part of the navigation.
	 *
	 * @return Page[]
	 */
	protected function lazy_get_navigation_children()
	{
		$index = $this->model->blueprint($this->site_id)->index;

		if (empty($index[$this->nid]) || !$index[$this->nid]->children)
		{
			return [];
		}

		$ids = [];

		foreach ($index[$this->nid]->children as $nid => $child)
		{
			if (!$child->is_online || $child->is_navigation_excluded || $child->pattern)
			{
				continue;
			}

			$ids[] = $nid;
		}

		if (!$ids)
		{
			return [];
		}

		return $this->model->find($ids);
	}

	/**
	 * Checks if the page as at least one child.
	 *
	 * @return boolean
	 */
	protected function get_has_child()
	{
		return $this->model->blueprint($this->site_id)->has_children($this->nid);
	}

	/**
	 * Returns the number of children.
	 *
	 * @return int
	 */
	protected function get_children_count()
	{
		return $this->model->blueprint($this->site_id)->children_count($this->nid);
	}

	/**
	 * Returns the number of descendant.
	 *
	 * @return int
	 */
	protected function get_descendants_count()
	{
		return $this->model->blueprint($this->site_id)->index[$this->nid]->descendants_count;
	}

	/**
	 * Returns the depth level of this page in the navigation tree.
	 */
	protected function get_depth()
	{
		return $this->parent ? $this->parent->depth + 1 : 0;
	}

	/**
	 * Returns the contents of the page as an array.
	 *
	 * Keys of the array are the contentid, values are the contents objects.
	 *
	 * @return Content[]
	 */
	protected function lazy_get_contents()
	{
		$entries = $this->model->models['pages/contents']->filter_by_pageid($this->nid);
		$contents = [];

		foreach ($entries as $entry)
		{
			$contents[$entry->contentid] = $entry;
		}

		return $contents;
	}

	/**
	 * Returns the body of this page.
	 *
	 * The body is the page's contents object with the 'body' identifier.
	 *
	 * @return Content
	 */
	protected function lazy_get_body()
	{
		$contents = $this->contents;

		return isset($contents['body']) ? $contents['body'] : null;
	}

	/**
	 * Replaces `type` value by "page" and `id` value by "page-id-<nid>".
	 *
	 * The following class names are added:
	 *
	 * - `slug`: "page-slug-<slug>"
	 * - `home`: true if the page is the home page.
	 * - `active`: true if the page is the active page.
	 * - `trail`: true if the page is in the breadcrumb trail.
	 * - `node-id`: "node-id-<nid>" if the page displays a node.
	 * - `node-constructor`: "node-constructor-<normalized_constructor>" if the page displays a node.
	 * - `template`: "template-<name>" the name of the page's template, without its extension.
	 */
	protected function get_css_class_names()
	{
		$names = array_merge(parent::get_css_class_names(), [

			'type' => 'page',
			'id' => 'page-id-' . $this->nid,
			'slug' => 'page-slug-'. $this->slug,
			'home' => ($this->home->nid == $this->nid),
			'active' => $this->is_active,
			'trail' => $this->is_trail,
			'template' => 'template-' . preg_replace('#\.(html|php)$#', '', $this->template),
			'has-children' => count($this->navigation_children) != 0

		]);

		if (isset($this->node))
		{
			$node = $this->node;

			$names['node-id'] = 'node-id-' . $node->nid;
			$names['node-constructor'] = 'node-constructor-' . \ICanBoogie\normalize($node->constructor);
		}

		return $names;
	}

	/**
	 * Return the description for the page.
	 */

	// TODO-20101115: these should be methods added by the "SEO" module

	protected function get_description()
	{
		return $this->metas['description'];
	}

	protected function get_document_title()
	{
		return $this->metas['document_title'] ? $this->metas['document_title'] : $this->title;
	}
}
