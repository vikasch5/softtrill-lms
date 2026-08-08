<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'key';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get($key, $default = null)
    {
        $setting = self::find($key);
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function set($key, $value)
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get all settings starting with a specific prefix.
     */
    public static function getBulk($prefix)
    {
        return self::where('key', 'like', $prefix . '%')
            ->pluck('value', 'key')
            ->toArray();
    }
}
