<?php

namespace Icybee\Modules\Pages;

use ICanBoogie;
use Icybee;

$hooks = Hooks::class . '::';

return [

	Icybee\Modules\Sites\Site::class . '::lazy_get_home' => $hooks . 'get_home',
	ICanBoogie\Core::class . '::get_page' => $hooks . 'get_page',
	Page::class . '::render' => $hooks . 'render_page'

];
