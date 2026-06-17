<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'sfi_mysql_tb_r_departments';
    protected $fillable = ['name'];
}
