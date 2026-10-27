<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = [];

    protected $casts = ['price' => 'float'];
}
