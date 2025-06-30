<?php

namespace App\Traits;

use App\Models\Settings;

trait HasSettings
{
    /**
     * Get a setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getSetting(string $key, $default = null)
    {
        $value = Settings::getByKey($key);
        return $value !== null ? $value : $default;
    }

    /**
     * Set a setting value by key
     *
     * @param string $key
     * @param string $value
     * @param string $type
     * @param string $category
     * @param string|null $description
     * @return bool
     */
    public static function setSetting(string $key, string $value, string $type = 'string', string $category = 'general', ?string $description = null): bool
    {
        return Settings::setByKey($key, $value, $type, $category, $description);
    }

    /**
     * Get all settings by category
     *
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getSettingsByCategory(string $category)
    {
        return Settings::getByCategory($category);
    }

    /**
     * Check if a setting exists
     *
     * @param string $key
     * @return bool
     */
    public static function hasSetting(string $key): bool
    {
        return Settings::where('key', $key)->exists();
    }

    /**
     * Delete a setting by key
     *
     * @param string $key
     * @return bool
     */
    public static function deleteSetting(string $key): bool
    {
        $setting = Settings::where('key', $key)->first();
        return $setting ? $setting->delete() : false;
    }
}
