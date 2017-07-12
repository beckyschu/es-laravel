<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VpnServerBlockage extends Model
{
    public function server()
    {
        return $this->belongsTo(VpnServer::class);
    }

    public function crawl()
    {
        return $this->belongsTo(Crawl::class);
    }
}
