<?php

namespace App\Models;

use App\Traits\HasAuditLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Settings extends Model
{
    use HasUuids;
    use HasAuditLogs;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'category'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get setting by key
     *
     * @param string $key
     * @return mixed
     */
    public static function getByKey(string $key)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : null;
    }

    /**
     * Set setting by key
     *
     * @param string $key
     * @param string $value
     * @param string $type
     * @param string $category
     * @param string|null $description
     * @return bool
     */
    public static function setByKey(string $key, string $value, string $type = 'string', string $category = 'general', ?string $description = null): bool
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'category' => $category,
                'description' => $description
            ]
        );

        return (bool) $setting;
    }

    /**
     * Get settings by category
     *
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByCategory(string $category)
    {
        return self::where('category', $category)->get();
    }
}
