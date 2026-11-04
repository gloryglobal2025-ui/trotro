<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SettingHelper;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('display_name')->get();
        $groups = Setting::select('group')->distinct()->pluck('group');

        return view('admin.settings.index', compact('settings', 'groups'));
    }

    public function create()
    {
        $groups = Setting::select('group')->distinct()->pluck('group');

        return view('admin.settings.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:settings,key',
            'value' => 'nullable|string',
            'type' => 'required|in:string,integer,boolean,float,json',
            'group' => 'required|string',
            'display_name' => 'required|string',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        Setting::create($validated);

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Setting created successfully.');
    }

    public function edit(Setting $setting)
    {
        $groups = Setting::select('group')->distinct()->pluck('group');

        return view('admin.settings.edit', compact('setting', 'groups'));
    }

    public function update(Request $request, Setting $setting)
    {
        $validated = $request->validate([
            'value' => 'nullable|string',
            'type' => 'sometimes|required|in:string,integer,boolean,float,json',
            'group' => 'sometimes|required|string',
            'display_name' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        $setting->update($validated);
        SettingHelper::clearCache($setting->key);

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Setting updated successfully.');
    }

    public function destroy(Setting $setting)
    {
        $setting->delete();
        SettingHelper::clearCache($setting->key);

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Setting deleted successfully.');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string',
        ]);

        $updatedCount = 0;
        foreach ($validated['settings'] as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if ($setting) {
                $setting->update(['value' => $value ?? '']);
                SettingHelper::clearCache($key);
                $updatedCount++;
            }
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', "Settings updated successfully. ({$updatedCount} settings updated)");
    }
}
