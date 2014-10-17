<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/sandbox';

require __DIR__ . '/../vendor/autoload.php';

#
# Create the _core_ instance used for the tests.
#

global $core;

$core = new \ICanBoogie\Core(\ICanBoogie\array_merge_recursive(\ICanBoogie\get_autoconfig(), [

	'module-path' => [

		realpath(__DIR__ . '/../')

	]

]));

$core->boot();
$core->document = \Brickrouge\get_document();