<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveHashFromClassesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('classes', function($table)
      {
          $table->dropColumn('hash');
      });

      Schema::table('classes_1', function($table)
      {
          $table->dropColumn('hash');
      });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('classes', function($table)
      {

         $table->string('hash', 40);

         $table->unique('hash');

      });

      Schema::table('classes_1', function($table)
      {

         $table->string('hash', 40);

         $table->unique('hash');

      });
	}

}
