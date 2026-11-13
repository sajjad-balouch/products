<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;

class SitePage extends Model
{
    use Audited;

    protected $guarded = ['id'];

    protected $casts = ['is_home' => 'boolean', 'is_published' => 'boolean'];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function sections()
    {
        return $this->hasMany(SiteSection::class)->orderBy('sort')->orderBy('id');
    }
}
