<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SidebarSetting;
use Illuminate\Support\Facades\Storage;

class SidebarSettingController extends Controller
{
    public function edit()
    {
        $setting = SidebarSetting::firstOrCreate([], [
            'title' => 'Sistem Baitul Maal',
            'subtitle' => 'Yayasan Masjid Al Iman Surabaya',
        ]);

        return view('admin.sidebar_settings.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'background_login' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5048',
            'background_color' => 'nullable|string',
            'cta_background_color' => 'nullable|string',
            'link_color' => 'nullable|string',
            'link_hover_color' => 'nullable|string',
            'link_active_color' => 'nullable|string',
            'link_active_border_color' => 'nullable|string',
            'cta_button_color' => 'nullable|string',
            'cta_button_hover_color' => 'nullable|string',
            'cta_button_text_color' => 'nullable|string',
        ]);

        $setting = SidebarSetting::first();

        // Simpan logo jika ada
        if ($request->hasFile('logo')) {
            if ($setting->logo_path && Storage::disk('public')->exists($setting->logo_path)) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            $path = $request->file('logo')->store('logos', 'public');
            $setting->logo_path = $path;
        }

        if ($request->hasFile('background_login')) {
            if ($setting->background_login && Storage::disk('public')->exists($setting->background_login)) {
                Storage::disk('public')->delete($setting->background_login);
            }

            $path = $request->file('background_login')->store('backgrounds', 'public');
            $setting->background_login = $path;
        }

        // Simpan seluruh field warna dan teks
        $setting->update([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'background_color' => $request->background_color,
            'cta_background_color' => $request->cta_background_color,
            'link_color' => $request->link_color,
            'link_hover_color' => $request->link_hover_color,
            'link_active_color' => $request->link_active_color,
            'link_active_border_color' => $request->link_active_border_color,
            'cta_button_color' => $request->cta_button_color,
            'cta_button_hover_color' => $request->cta_button_hover_color,
            'cta_button_text_color' => $request->cta_button_text_color,
        ]);

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui.');
    }
}
