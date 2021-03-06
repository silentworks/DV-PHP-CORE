<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableMetaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('table_metas', function($table){
           $table->increments('id');
           $table->string('table_name');
           $table->json('validation');
           $table->json('relations');
           $table->json('schema');
           $table->integer('count');
           $table->boolean('access');
           $table->integer('service_id')->unsigned();   
           $table->timestamps();
           $table->foreign('service_id')->references('id')->on('services')
                   ->onDelete('cascade');
          
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::drop('tableMeta');
    }
}
