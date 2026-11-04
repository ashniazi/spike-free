<?php

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Opcodes\Spike\BillableKeyType;

uses(RefreshDatabase::class);

class BillableKeyTypeUuidModel extends Model
{
    use HasUuids;
}

class BillableKeyTypeUlidModel extends Model
{
    use HasUlids;
}

test('an integer-keyed billable model gets integer billable columns', function () {
    expect(BillableKeyType::resolve())->toBe(BillableKeyType::Integer)
        ->and(Schema::getColumnType('spike_credit_transactions', 'billable_id'))->toBe('integer');
});

test('the key type follows the billable model', function () {
    expect(BillableKeyType::forModel(BillableKeyTypeUuidModel::class))->toBe(BillableKeyType::Uuid)
        ->and(BillableKeyType::forModel(BillableKeyTypeUlidModel::class))->toBe(BillableKeyType::Ulid)
        ->and(BillableKeyType::forModel(testBillableClass()))->toBe(BillableKeyType::Integer);
});

test('the config flag overrides detection', function () {
    config(['spike.billable_key_type' => 'uuid']);

    expect(BillableKeyType::resolve())->toBe(BillableKeyType::Uuid);
});

test('each key type builds its own columns', function (BillableKeyType $type, string $morphIdType, string $foreignKeyType) {
    Schema::create('billable_key_type_probe', function (Blueprint $table) use ($type) {
        $type->morphs($table, 'billable');
        $type->foreignKey($table, 'user_id');
    });

    expect(Schema::getColumnType('billable_key_type_probe', 'billable_id'))->toBe($morphIdType)
        ->and(Schema::getColumnType('billable_key_type_probe', 'billable_type'))->toBe('varchar')
        ->and(Schema::getColumnType('billable_key_type_probe', 'user_id'))->toBe($foreignKeyType);

    Schema::drop('billable_key_type_probe');
})->with([
    'int' => [BillableKeyType::Integer, 'integer', 'integer'],
    'uuid' => [BillableKeyType::Uuid, 'varchar', 'varchar'],
    'ulid' => [BillableKeyType::Ulid, 'varchar', 'varchar'],
]);
