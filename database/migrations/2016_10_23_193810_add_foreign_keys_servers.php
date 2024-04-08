<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysServers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->unsignedInteger('node')->change();
            $table->unsignedInteger('owner')->change();
            $table->unsignedInteger('allocation')->change();
            $table->unsignedInteger('service')->change();
            $table->unsignedInteger('option')->change();

            $table->foreign('node')->references('id')->on('nodes');
            $table->foreign('owner')->references('id')->on('users');
            $table->foreign('allocation')->references('id')->on('allocations');
            $table->foreign('service')->references('id')->on('services');
            $table->foreign('option')->references('id')->on('service_options');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign('servers_node_foreign');
            $table->dropForeign('servers_owner_foreign');
            $table->dropForeign('servers_allocation_foreign');
            $table->dropForeign('servers_service_foreign');
            $table->dropForeign('servers_option_foreign');

            $table->dropIndex('servers_node_foreign');
            $table->dropIndex('servers_owner_foreign');
            $table->dropIndex('servers_allocation_foreign');
            $table->dropIndex('servers_service_foreign');
            $table->dropIndex('servers_option_foreign');

            $table->dropColumn('deleted_at');

            $table->mediumInteger('node')->change();
            $table->mediumInteger('owner')->change();
            $table->mediumInteger('allocation')->change();
            $table->mediumInteger('service')->change();
            $table->mediumInteger('option')->change();
        });
    }
}
