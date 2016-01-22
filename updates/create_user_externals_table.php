<?php namespace Klubitus\Facebook\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateUserExternalsTable extends Migration {

    public function up() {
        Schema::create('user_externals', function($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->timestamp('expires_at')->nullable();

            $table->integer('user_id')->references('id')->on('users');
            $table->string('provider', 32);
            $table->text('token');
            $table->text('settings')->nullable();
            $table->bigInteger('external_user_id')->nullable();
        });
    }

    public function down() {
        // Schema::dropIfExists('user_externals');
    }

}
