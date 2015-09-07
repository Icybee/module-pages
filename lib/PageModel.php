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

use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\Routing\Pattern;

use Icybee\Modules\Nodes\NodeModel;

/**
 * @method Page offsetGet($offset)
 */
class PageModel extends NodeModel
{
	/**
	 * Before saving the record, we make sure that it is not its own parent.
	 *
	 * @inheritdoc
	 */
	public function save(array $properties, $key = null, array $options = [])
	{
		if ($key && isset($properties[Page::PARENTID]) && $key == $properties[Page::PARENTID])
		{
			throw new \Exception('A page cannot be its own parent.');
		}

		if (empty($properties[Page::SITE_ID]))
		{
			throw new \Exception('site_id is empty.');
		}

		unset(self::$blueprint_cache[$properties[Page::SITE_ID]]);

		return parent::save($properties, $key, $options);
	}

	/**
	 * Before deleting the record, we make sure that it is not used as a parent page or as a
	 * location target.
	 *
	 * @inheritdoc
	 */
	public function delete($key)
	{
		$site_id = $this->select('site_id')->filter_by_nid($key)->rc;

		if ($site_id)
		{
			unset(self::$blueprint_cache[$site_id]);
		}

		return parent::delete($key);
	}

	/**
	 * Changes the order of the query with "weight, create".
	 *
	 * @param Query $query
	 *
	 * @param int $direction < 0 for descending, ascending otherwise.
	 *
	 * @return Query
	 */
	protected function scope_ordered(Query $query, $direction=1)
	{
		$direction = $direction < 0 ? 'DESC' : 'ASC';

		return $query->order("weight {$direction}, created_at {$direction}");
	}

	/**
	 * Returns the blueprint of the pages tree.
	 *
	 * @param int $site_id Identifier of the website.
	 *
	 * @return Blueprint
	 */
	public function blueprint($site_id)
	{
		if (isset(self::$blueprint_cache[$site_id]))
		{
			return self::$blueprint_cache[$site_id];
		}

		$query = $this
		->select('nid, parentid, is_online, is_navigation_excluded, pattern')
		->filter_by_site_id($site_id)->ordered;

		return self::$blueprint_cache[$site_id] = Blueprint::from($query);
	}

	/**
	 * Holds the cached blueprint for each website.
	 *
	 * @var Blueprint[]
	 */
	static private $blueprint_cache = [];

	/**
	 * Returns the home page of the specified site.
	 *
	 * The record cache is used to retrieve or store the home page. Additionally the home page
	 * found is stored for each site.
	 *
	 * @param int $site_id Identifier of the site.
	 *
	 * @return Page
	 */
	public function find_home($site_id)
	{
		if (isset(self::$home_by_site_id[$site_id]))
		{
			return self::$home_by_site_id[$site_id];
		}

		$home = $this->where('site_id = ? AND parentid = 0 AND is_online = 1', $site_id)->ordered->one;

		if (!$home)
		{
			throw new \LogicException("There is no home yet, or maybe all pages are offline?");
		}

		$stored = $this->activerecord_cache->retrieve($home->nid);

		if ($stored)
		{
			$home = $stored;
		}
		else
		{
			$this->activerecord_cache->store($home);
		}

		self::$home_by_site_id[$site_id] = $home;

		return $home;
	}

	static private $home_by_site_id = [];

	/**
	 * Finds a page using its path.
	 *
	 * @param string $path
	 *
	 * @return Page
	 */
	public function find_by_path($path)
	{
		$pos = strrpos($path, '.');
		$extension = null;

		if ($pos && $pos > strrpos($path, '/'))
		{
			$extension = substr($path, $pos);
		 	$path = substr($path, 0, $pos);
		}

		$l = strlen($path);

		if ($l && $path{$l - 1} == '/')
		{
			$path = substr($path, 0, -1);
		}

		#
		# matching site
		#

		$site = $this->app->site;
		$site_id = $site->site_id;
		$site_path = $site->path;

		if ($site_path)
		{
			if (strpos($path, $site_path) !== 0)
			{
				return null;
			}

			$path = substr($path, strlen($site_path));
		}

		if (!$path)
		{
			#
			# The home page is requested, we load the first parentless online page of the site.
			#

			$page = $this->find_home($site_id);

			if (!$page)
			{
				return null;
			}

			if (!$this->activerecord_cache->retrieve($page->nid))
			{
				$this->activerecord_cache->store($page);
			}

			return $page;
		}

		$parts = explode('/', $path);

		array_shift($parts);

		$parts_n = count($parts);

		$query = $this
		->select('nid, parentid, slug, pattern')
		->filter_by_site_id($site_id)
		->ordered;

		$tries = Blueprint::from($query)->tree;

		/* @var $try Page */

		$try = null;
		$pages_by_ids = [];
		$vars = [];

		for ($i = 0 ; $i < $parts_n ; $i++)
		{
			if ($try)
			{
				$tries = $try->children;
			}

			$part = $path_part = $parts[$i];

			#
			# first we search for a matching slug
			#

			foreach ($tries as $try)
			{
				$pattern = $try->pattern;

				if ($pattern)
				{
					$stripped = preg_replace('#<[^>]+>#', '', $pattern);
					$nparts = substr_count($stripped, '/') + 1;
					$path_part = implode('/', array_slice($parts, $i, $nparts));

					$pattern = Pattern::from($pattern);

					if (!$pattern->match($path_part, $path_captured))
					{
						$try = null;

						continue;
					}

					#
					# found matching pattern !
					# we skip parts ate by the pattern
					#

					$i += $nparts - 1;

					#
					# even if the pattern matched, $match is not guaranteed to be an array,
					# 'feed.xml' is a valid pattern. // FIXME-20110327: is it still ?
					#

					if (is_array($path_captured))
					{
						$vars = $path_captured + $vars;
					}

					break;
				}
				else if ($part == $try->slug)
				{
					break;
				}

				$try = null;
			}

			#
			# If `try` is null at this point it's that the path could not be matched.
			#

			if (!$try)
			{
				return null;
			}

			#
			# otherwise, we continue
			#

			$pages_by_ids[$try->nid] = [

				'url_part' => $path_part,
				'url_variables' => $vars

			];
		}

		#
		# append the extension (if any) to the last page of the branch
		#

		$pages_by_ids[$try->nid]['url_part'] .= $extension;

		#
		# All page objects have been loaded, we need to set up some additional properties, link
		# each page to its parent and propagate the online status.
		#

		/* @var $parent Page */

		$parent = null;
		$pages = $this->find(array_keys($pages_by_ids));

		foreach ($pages as $page)
		{
			$page->url_part = $pages_by_ids[$page->nid]['url_part'];
			$page->url_variables = $pages_by_ids[$page->nid]['url_variables'];

			if ($parent && !$parent->is_online)
			{
				$page->is_online = false;
			}

			$parent = $page;
		}

		return $page;
	}
}
