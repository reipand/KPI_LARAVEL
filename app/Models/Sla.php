<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Sla extends Model
{
    use BelongsToTenant;

    protected $table = 'sla';

    protected $fillable = [
        'tenant_id',
        'nama_pekerjaan',
        'jabatan',
        'position_id',
        'durasi_jam',
        'keterangan',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
