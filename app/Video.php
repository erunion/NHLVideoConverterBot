<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $table = 'videos';
    protected $fillable = ['id', 'thing_id', 'url', 'title', 'converted_url', 'filesize', 'runtime'];
}
