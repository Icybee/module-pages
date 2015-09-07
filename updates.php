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

use ICanBoogie\Updater\AssertionFailed;
use ICanBoogie\Updater\Update;

/**
 * - Renames table `site_pages` as `pages`.
 * - Renames table `site_pages__contents` as `pages__contents`.
 *
 * @module pages
 */
class Update20111201 extends Update
{
	public function update_table_pages()
	{
		$db = $this->app->db;

		if (!$db->table_exists('site_pages'))
		{
			throw new AssertionFailed('assert_table_exists', 'site_pages');
		}

		$db("RENAME TABLE `{prefix}site_pages` TO `{prefix}pages`");
	}

	public function update_table_pages__contents()
	{
		$db = $this->app->db;

		if (!$db->table_exists('site_pages_contents'))
		{
			throw new AssertionFailed('assert_table_exists', 'site_pages_contents');
		}

		$db("RENAME TABLE `{prefix}site_pages_contents` TO `{prefix}pages__contents`");
	}

	public function update_constructor_type()
	{
		$db = $this->app->db;
		$db("UPDATE `{prefix}nodes` SET constructor = 'pages' WHERE constructor = 'site.pages'");
	}
}

/**
 * @module pages
 */
class Update2015090821 extends Update
{
	/**
	 * Renames column `parentid` as `parent_id`.
	 */
	public function update_column_parent_id()
	{
		$this->module->model
			->assert_has_column('parentid')
			->rename_column('parentid', 'parent_id');
	}

	/**
	 * Renames column `locationid` as `location_id`.
	 */
	public function update_column_location_id()
	{
		$this->module->model
			->assert_has_column('locationid')
			->rename_column('locationid', 'location_id');
	}

	/**
	 * Renames column `pageid` as `page_id`.
	 */
	public function update_column_page_id()
	{
		$this->module->models['contents']
			->assert_has_column('pageid')
			->rename_column('pageid', 'page_id');
	}

	/**
	 * Renames column `contentid` as `content_id`.
	 */
	public function update_column_content_id()
	{
		$this->module->models['contents']
			->assert_has_column('contentid')
			->rename_column('contentid', 'content_id');
	}
}
