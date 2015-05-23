<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClasses1Table extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('classes_1', function(Blueprint $table)
		{
			$table->increments('id');

			$table->integer('term_id');

			$table->integer('class_number');
			$table->string('class_id', 25);
			$table->string('class_title', 50);

			$table->string('type', 10);
			$table->string('days', 10);
			$table->string('times', 50);
			$table->string('instructors', 150);

			$table->boolean('status');
			$table->integer('capacity');
			$table->integer('enrollment_total');
			$table->integer('available_seats');

			$table->string('location', 50);
			$table->string('hash', 40);

			$table->unique('hash');
			$table->index(['term_id', 'class_number']);

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('classes_1');
	}

}
