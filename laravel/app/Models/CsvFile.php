<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvFile extends Model
{
    protected $fillable = ['user_id', 'filename'];
}