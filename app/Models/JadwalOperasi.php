<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalOperasi extends Model
{
    use HasFactory;
    protected $connection = 'mysql4';
    protected $table = 'tabel_jadwal';
}
