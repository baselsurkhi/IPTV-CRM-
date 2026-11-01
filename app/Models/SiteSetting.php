<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $cacheKey = 'site_setting.'.$key;

        return Cache::rememberForever($cacheKey, function () use ($key, $default): ?string {
            $row = static::query()->where('key', $key)->value('value');

            return $row !== null ? (string) $row : $default;
        });
    }

    public static function defaultPlayerApiUrl(): ?string
    {
        $url = trim((string) (static::getValue('iptv.player_api_base_url') ?: config('iptv.player_api_base_url')));

        return $url !== '' ? $url : null;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
        Cache::forget('site_setting.'.$key);
    }

    protected static function booted(): void
    {
        static::saved(fn (SiteSetting $m) => Cache::forget('site_setting.'.$m->key));
        static::deleted(fn (SiteSetting $m) => Cache::forget('site_setting.'.$m->key));
    }
}
