<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', ['store' => StoreSetting::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'short_description' => ['nullable', 'string', 'max:280'],
            'whatsapp_number' => ['nullable', 'regex:/^[1-9][0-9]{7,14}$/'],
            'address' => ['nullable', 'string', 'max:1500'],
            'instagram_url' => ['nullable', 'url:https', 'max:255'],
            'facebook_url' => ['nullable', 'url:https', 'max:255'],
        ]);
        StoreSetting::query()->updateOrCreate(['id' => 1], $data);

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
