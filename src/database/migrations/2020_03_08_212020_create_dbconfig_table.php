<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDbconfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable(false);
            $table->string('technical_name')->nullable(false)->unique();
            $table->enum('type',['int', 'binary', 'boolean', 'string', 'float', 'array', 'collection', 'json', 'object']);
            $table->longText('description')->nullable(true);
            $table->longText('value')->nullable(false);
            $table->boolean('crypted')->default(FALSE);
            $table->bigInteger('cache_duration')->nullable(true);
            $table->enum('cache_management',['fix', 'auto'])->nullable(true);
            $table->timestamps();

            $table->index('technical_name');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('configurations');
    }
}
