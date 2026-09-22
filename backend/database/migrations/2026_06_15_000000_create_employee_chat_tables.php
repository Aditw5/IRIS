<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeChatTables extends Migration
{
    public function up()
    {
        Schema::create('chat_room_m', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('kdprofile');
            $table->boolean('statusenabled')->default(true);
            $table->uuid('norec')->unique();
            $table->string('tipe', 20)->default('private');
            $table->timestamps();

            $table->index(['kdprofile', 'statusenabled', 'tipe'], 'chat_room_filter_index');
        });

        Schema::create('chat_room_member_m', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('kdprofile');
            $table->boolean('statusenabled')->default(true);
            $table->uuid('norec')->unique();
            $table->unsignedBigInteger('roomfk');
            $table->unsignedBigInteger('pegawaifk');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['roomfk', 'pegawaifk'], 'chat_room_member_unique');
            $table->index(['pegawaifk', 'statusenabled'], 'chat_member_pegawai_index');
            $table->foreign('roomfk')->references('id')->on('chat_room_m')->onDelete('cascade');
        });

        Schema::create('chat_message_t', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('kdprofile');
            $table->boolean('statusenabled')->default(true);
            $table->uuid('norec')->unique();
            $table->unsignedBigInteger('roomfk');
            $table->unsignedBigInteger('senderfk');
            $table->string('tipe', 20)->default('text');
            $table->text('message')->nullable();
            $table->string('attachment_url', 500)->nullable();
            $table->string('attachment_name', 255)->nullable();
            $table->string('attachment_mime', 150)->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->timestamps();

            $table->index(['roomfk', 'created_at'], 'chat_message_room_date_index');
            $table->index(['senderfk', 'created_at'], 'chat_message_sender_date_index');
            $table->foreign('roomfk')->references('id')->on('chat_room_m')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_message_t');
        Schema::dropIfExists('chat_room_member_m');
        Schema::dropIfExists('chat_room_m');
    }
}
