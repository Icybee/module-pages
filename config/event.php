<?php

namespace Icybee\Modules\Pages;

use Brickrouge;
use ICanBoogie;
use Icybee;

$hooks = Hooks::class . '::';

return [

	Brickrouge\Document::class . '::render_title:before' => $hooks . 'before_document_render_title',
	ICanBoogie\HTTP\RequestDispatcher::class . '::alter' => $hooks . 'on_http_dispatcher_alter',
	ICanBoogie\Module\Operation\SaveOperation::class . '::process' => $hooks . 'invalidate_cache',
	ICanBoogie\Module\Operation\DeleteOperation::class . '::process' => $hooks . 'invalidate_cache',
	Icybee\Modules\Files\File::class . '::move' => $hooks . 'on_file_move',
	Icybee\Modules\Pages\Page::class . '::move' => $hooks . 'on_page_move',
	Icybee\Modules\Nodes\Operation\OnlineOperation::class . '::process' => $hooks . 'invalidate_cache',
	Icybee\Modules\Nodes\Operation\OfflineOperation::class . '::process' => $hooks . 'invalidate_cache'

];
