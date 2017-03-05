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

chdir(__DIR__);

require __DIR__ . '/../vendor/autoload.php';

$app = boot();
$app->document = \Brickrouge\get_document();
$app->template_resolver->add_path(__DIR__ . '/templates');
