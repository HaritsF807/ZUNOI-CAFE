<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * Display the integration setup page with current settings.
     */
    public function integrationIndex()
    {
        $settings = [
            'qris_manual_url' => Setting::getValue('qris_manual_url', 'https://i.ibb.co.com/zTW39St0/G427647447-0703-A01-default.png'),
            'fonnte_token' => Setting::getValue('fonnte_token', 'TokenFonnteAnda123'),
            'tokopay_merchant_id' => Setting::getValue('tokopay_merchant_id', 'M-123456'),
            'tokopay_secret' => Setting::getValue('tokopay_secret', 'SecretKey...'),
        ];

        return Inertia::render('IntegrationSetup', [
            'settings' => $settings
        ]);
    }

    /**
     * Update the integration settings.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'qris_manual_url' => 'required|url|max:1000',
            'fonnte_token' => 'required|string|max:255',
            'tokopay_merchant_id' => 'required|string|max:255',
            'tokopay_secret' => 'required|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->back()->with('success', 'Pengaturan integrasi berhasil diperbarui!');
    }
}
