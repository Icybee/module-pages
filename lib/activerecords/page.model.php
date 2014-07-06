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
use ICanBoogie\Exception;
use ICanBoogie\Routing\Pattern;

class Model extends \Icybee\Modules\Nodes\Model
{
	/**
	 * Before saving the record, we make sure that it is not its own parent.
	 */
	public function save(array $properties, $key=null, array $options=array())
	{
		if ($key && isset($properties[Page::PARENTID]) && $key == $properties[Page::PARENTID])
		{
			throw new Exception('A page connot be its own parent.');
		}

		if (empty($properties[Page::SITEID]))
		{
			throw new Exception('site_id is empty.');
		}

		unset(self::$blueprint_cache[$properties[Page::SITEID]]);

		return parent::save($properties, $key, $options);
	}

	/**
	 * Before deleting the record, we make sure that it is not used as a parent page or as a
	 * location target.
	 */
	public function delete($key)
	{
		$site_id = $this->select('siteid')->filter_by_nid($key)->rc;

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
	 * @return array[int]object
	 */
	public function blueprint($site_id)
	{
		if (isset(self::$blueprint_cache[$site_id]))
		{
			return self::$blueprint_cache[$site_id];
		}

		$query = $this
		->select('nid, parentid, is_online, is_navigation_excluded, pattern')
		->filter_by_siteid($site_id)->ordered;

		return self::$blueprint_cache[$site_id] = Blueprint::from($query);
	}

	/**
	 * Holds the cached blueprint for each website.
	 *
	 * @var array
	 */
	private static $blueprint_cache = array();

	/**
	 * Returns the home page of the specified site.
	 *
	 * The record cache is used to retrieve or store the home page. Additionnaly the home page
	 * found is stored for each site.
	 *
	 * @param int $siteid Identifier of the site.
	 *
	 * @return Page
	 */
	public function find_home($siteid)
	{
		if (isset(self::$home_by_siteid[$siteid]))
		{
			return self::$home_by_siteid[$siteid];
		}

		$home = $this->where('siteid = ? AND parentid = 0 AND is_online = 1', $siteid)->ordered->one;

		if ($home)
		{
			$stored = $this->retrieve($home->nid);

			if ($stored)
			{
				$home = $stored;
			}
			else
			{
				$this->store($home);
			}
		}

		self::$home_by_siteid[$siteid] = $home;

		return $home;
	}

	private static $home_by_siteid = array();

	/**
	 * Finds a page using its path.
	 *
	 * @param string $path
	 *
	 * @return Page
	 */
	public function find_by_path($path)
	{
		global $core;

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

		$site = $core->site;
		$site_id = $site->siteid;
		$site_path = $site->path;

		if ($site_path)
		{
			if (strpos($path, $site_path) !== 0)
			{
				return;
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
				return;
			}

			if (!$this->retrieve($page->nid))
			{
				$this->store($page);
			}

			return $page;
		}

		$parts = explode('/', $path);

		array_shift($parts);

		$parts_n = count($parts);

		$query = $this
		->select('nid, parentid, slug, pattern')
		->filter_by_siteid($site_id)
		->ordered;

		$tries = Blueprint::from($query)->tree;

		$try = null;
		$pages_by_ids = array();
		$vars = array();

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
				return;
			}

			#
			# otherwise, we continue
			#

			$pages_by_ids[$try->nid] = array
			(
				'url_part' => $path_part,
				'url_variables' => $vars
			);
		}

		#
		# append the extension (if any) to the last page of the branch
		#

		$pages_by_ids[$try->nid]['url_part'] .= $extension;

		#
		# All page objects have been loaded, we need to set up some additionnal properties, link
		# each page to its parent and propagate the online status.
		#

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