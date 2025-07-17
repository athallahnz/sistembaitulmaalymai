<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SidebarSetting extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'logo_path',
        'background_login',
        'background_color',
        'cta_background_color',
        'link_color',
        'link_hover_color',
        'link_active_color',
        'link_active_border_color',
        'cta_button_color',
        'cta_button_hover_color',
        'cta_button_text_color',
    ];
}
