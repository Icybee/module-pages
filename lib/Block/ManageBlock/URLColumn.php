<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee\Modules\Pages\Block\ManageBlock;

use function ICanBoogie\app;
use ICanBoogie\Routing\Pattern;

/**
 * Representation of the `url` column.
 */
class URLColumn extends \Icybee\Modules\Nodes\Block\ManageBlock\URLColumn
{
	/**
	 * @param \Icybee\Modules\Pages\Page $record
	 *
	 * @inheritdoc
	 */
	public function render_cell($record)
	{
		$t = $this->manager->t;
		$options = $this->manager->options;
		$pattern = $record->url_pattern;

		if ($options->search || $options->filters)
		{
			if (Pattern::is_pattern($pattern))
			{
				return null;
			}

			$url = $record->url;
			$display_url = \ICanBoogie\shorten($url, 64, .5);

			if ($record->location)
			{
				$location = $record->location;
				$title = $t('This page is redirected to: !title (!url)', [

					'!title' => $location->title,
					'!url' => $location->url

				]);

				return <<<EOT
<span class="small">
<i class="icon-mail-forward" title="$title"></i>
<a href="$url">$display_url</a>
</span>
EOT;
			}

			return <<<EOT
<span class="small"><a href="$url">$display_url</a></span>
EOT;
		}

		$rc = '';
		$location = $record->location;

		if ($location)
		{
			$rc .= '<span class="icon-mail-forward" title="' . $t('This page is redirected to: !title (!url)', [

					'!title' => $location->title,
					'!url' => $location->url

				]) . '"></span>';
		}
		else if (!Pattern::is_pattern($pattern))
		{
			$url = (app()->site_id == $record->site_id) ? $record->url : $record->absolute_url;

			$title = $t('Go to the page: !url', [ '!url' => $url ]);

			$rc .= '<a href="' . $url . '" title="' . $title . '" target="_blank"><i class="icon-external-link"></i></a>';
		}

		return $rc;
	}
}
