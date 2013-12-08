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

use Icybee\Modules\Sites\Site;

/**
 * Representation of a page.
 *
 * @property Page $parent Parent page of the page.
 * @property \Icybee\Modules\Sites\Site $site The site the page belongs to.
 * @property-read bool $is_accessible Whether the page is accessible or not.
 * @property-read bool $is_active Wheter the page is active or not.
 * @property-read bool $is_home Whether the page is the home page of the site or not.
 * @property-read bool $is_trail Whether the page is in the navigation trail or not.
 * @property-read array[]Page $navigation_children Navigation children of the page.
 * @property-read int $descendents_count The number of descendents.
 */
class Page extends \Icybee\Modules\Nodes\Node
{
	const PARENTID = 'parentid';
	const LOCATIONID = 'locationid';
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
	public $parentid;

	/**
	 * The identifier of the page the page is redirected to.
	 *
	 * @var int
	 */
	public $locationid;

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

		return $extension ? '.' . $extension : null;
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
	public $url_variables = array();

	/**
	 * @var Node Node object currently acting as the body of the page.
	 * @todo-20130327: use request's context instead
	 */
	public $node;

	/**
	 * @var bool true if the page is cachable, false otherwise.
	 */
	public $cachable = true;

	public function __construct($model='pages')
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

	/**
	 * Returns the previous online sibling for the page.
	 *
	 * @return Page|false The previous sibling, or false if there is none.
	 */
	protected function lazy_get_previous()
	{
		return $this->model
		->where('is_online = 1 AND nid != ? AND parentid = ? AND siteid = ? AND weight <= ?', $this->nid, $this->parentid, $this->siteid, $this->weight)
		->order('weight desc, created_at desc')->one;
	}

	/**
	 * Returns the next online sibling for the page.
	 *
	 * @return Page|false The next sibling, or false if there is none.
	 */
	protected function lazy_get_next()
	{
		return $this->model
		->where('is_online = 1 AND nid != ? AND parentid = ? AND siteid = ? AND weight >= ?', $this->nid, $this->parentid, $this->siteid, $this->weight)
		->order('weight, created_at')->one;
	}

	/**
	 * Returns the URL of the page.
	 *
	 * @return string
	 */
	protected function lazy_get_url()
	{
		global $core;

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

		if (Pattern::is_pattern($url_pattern))
		{
			if ($this->url_variables)
			{
				$url = Pattern::from($url_pattern)->format($this->url_variables);

//				\ICanBoogie\log('URL %pattern rescued using URL variables', array('%pattern' => $pattern));
			}
			else
			{
				$page = isset($core->request->context->page) ? $core->request->context->page : null;

				if ($page && $page->url_variables)
				{
					$url = Pattern::from($url_pattern)->format($page->url_variables);

// 					\ICanBoogie\log("URL pattern %pattern was resolved using current page's variables", array('%pattern' => $pattern));
				}
				else
				{
					$url = '#url-pattern-could-not-be-resolved';
				}
			}
		}
		else
		{
			$url = $url_pattern;
		}

		return $url;
	}

	/**
	 * Returns the absulte URL of the pages.
	 *
	 * @return string The absolute URL of the page.
	 */
	protected function lazy_get_absolute_url()
	{
		$site = $this->site;

		return $site->url . substr($this->url, strlen($site->path));
	}

	public function translation($language=null)
	{
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
	protected function lazy_get_url_pattern()
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
		global $core;

		if ($core->user->is_guest && $this->site->status != Site::STATUS_OK)
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
		return (!$this->parentid && !$this->weight && $this->is_online);
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
		global $core;

		return $core->request->context->page->nid == $this->nid;
	}

	/**
	 * Checks if the page record is in the active page trail.
	 *
	 * The global variable `page` must be defined in order to identifiy the active page.
	 *
	 * @return bool true if the page is in the active page trail, false otherwise.
	 */
	protected function get_is_trail()
	{
		global $core;

		$node = $core->request->context->page; // TODO-20130327: use a get_active_page() helper?

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
	 * @return Icybee\Modules\Pages\Page|null The location target, or null if there is none.
	 */
	protected function get_location()
	{
		return $this->locationid ? $this->model[$this->locationid] : null;
	}

	/**
	 * Returns the home page for the page record.
	 *
	 * @return Icybee\Modules\Pages\Page
	 */
	protected function get_home()
	{
		return $this->model->find_home($this->siteid);
	}

	/**
	 * Returns the parent of the page.
	 *
	 * @return Icybee\Modules\Pages\Page|null The parent page or null is the page has no parent.
	 */
	protected function lazy_get_parent()
	{
		return $this->parentid ? $this->model[$this->parentid] : null;
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
		$blueprint = $this->model->blueprint($this->siteid);
		$pages = $blueprint['pages'];

		if (!$pages[$this->nid]->children)
		{
			return array();
		}

		$ids = array();

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
	 * @return array[int]Page
	 */
	protected function lazy_get_navigation_children()
	{
		$index = $this->model->blueprint($this->siteid)->index;

		if (empty($index[$this->nid]) || !$index[$this->nid]->children)
		{
			return array();
		}

		$ids = array();

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
			return array();
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
		return $this->model->blueprint($this->siteid)->has_children($this->nid);
	}

	/**
	 * Returns the number of children.
	 *
	 * @return int
	 */
	protected function get_children_count()
	{
		return $this->model->blueprint($this->siteid)->children_count($this->nid);
	}

	/**
	 * Returns the number of descendent.
	 *
	 * @return int
	 */
	protected function get_descendents_count()
	{
		return $this->model->blueprint($this->siteid)->index[$this->nid]->descendents_count;
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
	 * @return array[string]\Icybee\Modules\Pages\Pages\Content
	 */
	protected function lazy_get_contents()
	{
		global $core;

		$entries = $core->models['pages/contents']->filter_by_pageid($this->nid);
		$contents = array();

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
	 * @return \Icybee\Modules\Pages\Pages\Content
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
	protected function lazy_get_css_class_names()
	{
		$names = array_merge
		(
			parent::lazy_get_css_class_names(), array
			(
				'type' => 'page',
				'id' => 'page-id-' . $this->nid,
				'slug' => 'page-slug-'. $this->slug,
				'home' => ($this->home->nid == $this->nid),
				'active' => $this->is_active,
				'trail' => (!$this->is_active && $this->is_trail),
				'template' => 'template-' . preg_replace('#\.(html|php)$#', '', $this->template),
				'has-children' => count($this->navigation_children) != 0
			)
		);

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