<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUdrDriveFolderAccessTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('udr_drive_folder_access_m')) {
            Schema::create('udr_drive_folder_access_m', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('kdprofile');
                $table->integer('udrrootid');
                $table->string('accessmode', 20)->default('all');
                $table->integer('createdby')->nullable();
                $table->integer('updatedby')->nullable();
                $table->timestamps();

                $table->unique(['kdprofile', 'udrrootid'], 'uq_udr_folder_access_profile_root');
                $table->index('udrrootid', 'idx_udr_folder_access_root');
            });
        }

        if (!Schema::hasTable('udr_drive_folder_access_user_t')) {
            Schema::create('udr_drive_folder_access_user_t', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('folderaccessfk');
                $table->integer('loginuserfk');
                $table->timestamps();

                $table->unique(
                    ['folderaccessfk', 'loginuserfk'],
                    'uq_udr_folder_access_loginuser'
                );
                $table->index('loginuserfk', 'idx_udr_folder_access_loginuser');
                $table->foreign('folderaccessfk', 'fk_udr_folder_access_user_access')
                    ->references('id')
                    ->on('udr_drive_folder_access_m')
                    ->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('udr_drive_folder_access_user_t');
        Schema::dropIfExists('udr_drive_folder_access_m');
    }
}
