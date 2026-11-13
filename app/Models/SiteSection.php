<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;

class SiteSection extends Model
{
    use Audited;

    protected $guarded = ['id'];

    protected $casts = ['data' => 'array', 'is_visible' => 'boolean'];

    public function page()
    {
        return $this->belongsTo(SitePage::class, 'site_page_id');
    }
}
