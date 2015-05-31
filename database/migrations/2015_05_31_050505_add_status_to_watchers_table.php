<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusToWatchersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('watchers', function(Blueprint $table)
		{

			$table->boolean('text_status')->default(1)->after('class_number');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('watchers', function(Blueprint $table)
		{
		
			$table->dropColumn('text_status');
			
		});
	}

}
