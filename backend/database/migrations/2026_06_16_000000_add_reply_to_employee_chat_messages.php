<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReplyToEmployeeChatMessages extends Migration
{
    public function up()
    {
        Schema::table('chat_message_t', function (Blueprint $table) {
            $table->unsignedBigInteger('reply_to_message_id')->nullable()->after('senderfk');
            $table->index('reply_to_message_id', 'chat_message_reply_index');
            $table->foreign('reply_to_message_id')
                ->references('id')
                ->on('chat_message_t')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('chat_message_t', function (Blueprint $table) {
            $table->dropForeign(['reply_to_message_id']);
            $table->dropIndex('chat_message_reply_index');
            $table->dropColumn('reply_to_message_id');
        });
    }
}
