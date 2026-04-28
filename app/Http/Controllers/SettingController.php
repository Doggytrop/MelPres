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
        $data = $request->except(['_token', '_method']);

        // Get all boolean settings to handle unchecked checkboxes
        $booleanSettings = Setting::where('type', 'boolean')->pluck('key')->toArray();

        foreach ($data as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if (!$setting) continue;

            // Handle boolean values
            if ($setting->type === 'boolean') {
                $value = '1'; // If it's in the data, it's checked
            }

            $setting->update(['value' => $value]);
        }

        // Set unchecked booleans to 0
        foreach ($booleanSettings as $key) {
            if (!array_key_exists($key, $data)) {
                Setting::where('key', $key)->update(['value' => '0']);
            }
        }

        return redirect()->route('settings.index')
                         ->with('success', 'Configuración guardada exitosamente');
    }
}