<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Settings;

class SettingsController extends Controller
{
    /**
     * Get Settings
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSettings(Request $request): JsonResponse
    {
        try {
            $category = $request->query('category');

            if ($category) {
                $validator = Validator::make(['category' => $category], [
                    'category' => 'required|in:general,notifications'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid category',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $settings = Settings::getByCategory($category);
            } else {
                $settings = Settings::all();
            }

            $formattedSettings = [];
            foreach ($settings as $setting) {
                $formattedSettings[$setting->key] = [
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'category' => $setting->category,
                    'description' => $setting->description,
                    'updated_at' => $setting->updated_at
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $formattedSettings
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Settings
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:255',
            'settings.*.value' => 'required',
            'settings.*.type' => 'sometimes|string|in:string,int,boolean,json',
            'settings.*.category' => 'sometimes|string|in:general,notifications',
            'settings.*.description' => 'sometimes|nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $updatedSettings = [];
            $errors = [];

            foreach ($request->settings as $settingData) {
                $key = $settingData['key'];
                $value = $settingData['value'];
                $type = $settingData['type'] ?? 'string';
                $category = $settingData['category'] ?? 'general';
                $description = $settingData['description'] ?? null;

                // Validate value based on type
                if (!$this->validateSettingValue($value, $type)) {
                    $errors[] = "Invalid value for setting '{$key}'. Expected type: {$type}";
                    continue;
                }

                $success = Settings::setByKey($key, $value, $type, $category, $description);

                if ($success) {
                    $updatedSettings[] = $key;
                } else {
                    $errors[] = "Failed to update setting '{$key}'";
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Some settings failed to update',
                    'errors' => $errors,
                    'updated_settings' => $updatedSettings
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'updated_settings' => $updatedSettings
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate setting value based on type
     *
     * @param string $value
     * @param string $type
     * @return bool
     */
    private function validateSettingValue(string $value, string $type): bool
    {
        switch ($type) {
            case 'number':
                return is_numeric($value);

            case 'boolean':
                return in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no']);

            case 'json':
                return json_decode($value) !== null;

            case 'string':
            default:
                return true;
        }
    }
}
