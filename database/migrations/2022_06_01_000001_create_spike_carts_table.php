<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Opcodes\Spike\BillableKeyType;

/**
 * @package Spike
 * @see https://spike.opcodes.io/docs
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('spike_carts', function (Blueprint $table) {
            $table->id();

            BillableKeyType::resolve()->morphs($table, 'billable'); // int, uuid or ulid: see spike.billable_key_type
            $table->string('promotion_code_id')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->string('stripe_checkout_session_id')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('spike_carts');
    }
};
