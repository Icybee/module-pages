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

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;

/**
 * Representation of a page content.
 *
 * @method Query filter_by_pageid() filter_by_pageid(int $pageid)
 * Filter contents according to the identifier of a page.
 */
class ContentModel extends Model
{

}
