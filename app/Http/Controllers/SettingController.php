<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $groups = Setting::all()->groupBy('group');
        return view('settings.index', compact('groups'));
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method', 'company_logo_file']);

        // Handle logo upload
        if ($request->hasFile('company_logo_file')) {
            $path = $request->file('company_logo_file')->store('logos', 'public');
            Setting::set('company_logo', $path);
        }

        foreach ($data as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if (!$setting) continue;

            if ($setting->type === 'boolean') {
                $value = isset($data[$key]) ? '1' : '0';
            }

            $setting->update(['value' => $value]);
        }

        // Handle unchecked booleans
        Setting::where('type', 'boolean')->each(function ($setting) use ($data) {
            if (!array_key_exists($setting->key, $data)) {
                $setting->update(['value' => '0']);
            }
        });

        return redirect()->route('settings.index')
                         ->with('success', 'Configuración guardada correctamente.');
    }
}