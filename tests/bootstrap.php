<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie;

require __DIR__ . '/../vendor/autoload.php';

#
# Create the _core_ instance used for the tests.
#

$app = new Core(array_merge_recursive(get_autoconfig(), [

	'module-path' => [

		realpath(__DIR__ . '/../')

	]

]));

$app->boot();
$app->document = \Brickrouge\get_document();
