<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestTbl extends Model
{
    use HasFactory;

    protected $table = "test_tbls";

    protected $primaryKey = "id";

}
