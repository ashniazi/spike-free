<?php

namespace Opcodes\Spike;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;

/**
 * The key type of the billable models, which Spike's migrations use for every column that points at a billable.
 *
 * Set `spike.billable_key_type` to `int`, `uuid` or `ulid`. Left null, it follows the first billable model:
 * HasUuids means uuid, HasUlids means ulid, anything else an unsigned big integer, as upstream Spike assumed.
 */
enum BillableKeyType: string
{
    case Integer = 'int';
    case Uuid = 'uuid';
    case Ulid = 'ulid';

    public static function resolve(): self
    {
        $configured = config('spike.billable_key_type');

        if (filled($configured)) {
            return self::from($configured);
        }

        return self::forModel(config('spike.billable_models.0', 'App\Models\User'));
    }

    /**
     * @param  class-string  $billableModel
     */
    public static function forModel(string $billableModel): self
    {
        $traits = class_uses_recursive($billableModel);

        return match (true) {
            in_array(HasUuids::class, $traits, true) => self::Uuid,
            in_array(HasUlids::class, $traits, true) => self::Ulid,
            default => self::Integer,
        };
    }

    /**
     * A `{name}_type` and `{name}_id` pair with its index, keyed for this type.
     */
    public function morphs(Blueprint $table, string $name): void
    {
        match ($this) {
            self::Integer => $table->morphs($name),
            self::Uuid => $table->uuidMorphs($name),
            self::Ulid => $table->ulidMorphs($name),
        };
    }

    /**
     * A column holding a billable's key.
     */
    public function foreignKey(Blueprint $table, string $column): ColumnDefinition
    {
        return match ($this) {
            self::Integer => $table->unsignedBigInteger($column),
            self::Uuid => $table->uuid($column),
            self::Ulid => $table->ulid($column),
        };
    }
}
