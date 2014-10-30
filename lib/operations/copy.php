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

class CopyOperation extends \ICanBoogie\Operation
{
	protected function get_controls()
	{
		return [

			self::CONTROL_PERMISSION => Module::PERMISSION_CREATE,
			self::CONTROL_RECORD => true

		] + parent::get_controls();
	}

	protected function process()
	{
		$record = $this->record;
		$key = $this->key;
		$title = $record->title;

		unset($record->nid);
		unset($record->is_online);
		unset($record->created_at);
		unset($record->updated_at);

		$record->uid = $this->app->user_id;
		$record->title .= ' (copie)';
		$record->slug .= '-copie';

		$contentsModel = $this->module->model('contents');
		$contents = $contentsModel->where([ 'pageid' => $key ])->all;

		$nid = $this->module->model->save((array) $record);

		if (!$nid)
		{
			\ICanBoogie\log_error('Unable to copy page %title (#:nid)', [ 'title' => $title, 'nid' => $key ]);

			return;
		}

		$this->response->message = $this->format('Page %title was copied to %copy', [

			'title' => $title,
			'copy' => $record->title

		]);

		foreach ($contents as $record)
		{
			$record->pageid = $nid;
			$record = (array) $record;

			$contentsModel->insert($record, [

				'on duplicate' => $record

			]);
		}

		return [ $key, $nid ];
	}
}