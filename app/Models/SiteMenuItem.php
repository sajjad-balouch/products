<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;

class SiteMenuItem extends Model
{
    use Audited;

    protected $guarded = ['id'];

    protected $casts = ['new_tab' => 'boolean', 'is_visible' => 'boolean'];

    public function page()
    {
        return $this->belongsTo(SitePage::class, 'site_page_id');
    }
}
