<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppLog extends Model
{
    protected $table = 'logs';

    public $timestamps = false;

    protected $fillable = [
        'table_name',
        'record_id',
        'action',
        'original_data',
        'new_data',
        'user_id',
        'user_name',
        'created_at',
    ];

    protected $casts = [
        'record_id' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function originalPayload(): ?array
    {
        return $this->decodeJson($this->original_data);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function newPayload(): ?array
    {
        return $this->decodeJson($this->new_data);
    }

    /**
     * @return array<string, array{from: mixed, to: mixed}>
     */
    public function changedFields(): array
    {
        $original = $this->originalPayload() ?? [];
        $new = $this->newPayload() ?? [];
        $keys = array_unique(array_merge(array_keys($original), array_keys($new)));
        $changed = [];

        foreach ($keys as $key) {
            $from = array_key_exists($key, $original) ? $original[$key] : null;
            $to = array_key_exists($key, $new) ? $new[$key] : null;

            if ($this->valuesEqual($from, $to)) {
                continue;
            }

            $changed[$key] = [
                'from' => $from,
                'to' => $to,
            ];
        }

        return $changed;
    }

    /**
     * @param  array<string, mixed>|null  $original
     * @param  array<string, mixed>|null  $new
     */
    public static function record(
        string $tableName,
        int $recordId,
        string $action,
        ?array $original,
        ?array $new = null,
        $user = null
    ): self {
        $user = $user ?: auth()->user();

        return self::query()->create([
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'original_data' => $original !== null ? json_encode($original, JSON_UNESCAPED_UNICODE) : null,
            'new_data' => $new !== null ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
            'user_id' => $user ? (int) $user->id : null,
            'user_name' => $user ? (string) ($user->nama ?: $user->username) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson($value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function valuesEqual($a, $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (string) (0 + $a) === (string) (0 + $b);
        }

        return (string) $a === (string) $b;
    }
}
