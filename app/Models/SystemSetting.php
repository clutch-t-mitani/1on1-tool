<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    private const KEY_MASTER_DATE = 'master_date_override';

    /**
     * システム上の「現在の日付」を返す。
     * 管理者による上書きがなければサーバーの現在時刻を返す。
     */
    public static function getMasterDate(): Carbon
    {
        $override = self::where('key', self::KEY_MASTER_DATE)->value('value');

        return $override !== null
            ? Carbon::parse($override)
            : now();
    }

    /** 管理者によるシステム日付の上書きを保存する。 */
    public static function setMasterDate(Carbon $date): void
    {
        self::updateOrCreate(
            ['key' => self::KEY_MASTER_DATE],
            ['value' => $date->toDateTimeString()],
        );
    }

    /** システム日付の上書きを解除し、サーバー時刻に戻す。 */
    public static function clearMasterDate(): void
    {
        self::where('key', self::KEY_MASTER_DATE)->delete();
    }
}
