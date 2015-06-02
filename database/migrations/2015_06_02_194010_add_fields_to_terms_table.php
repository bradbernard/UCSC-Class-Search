<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsToTermsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('terms', function(Blueprint $table)
		{

			$table->boolean('summer')->default(0)->after('term_name');
         $table->integer('offset')->default(0)->after('term_name');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('terms', function(Blueprint $table)
		{

			$table->dropColumn(['summer', 'offset']);

		});
	}

}
