<?php namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		'App\Console\Commands\Inspire',
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{

		$schedule->call('App\Http\Controllers\InsertController@insertTerms')
					->name('InsertTerms')
					->withoutOverlapping()
					->cron('*/1 * * * * *')
					->thenPing(env('INSERTCONTROLLER_PING'));

		$schedule->call('App\Http\Controllers\NotifyController@checkClasses')
					->name('NotifyController@checkOpen')
					->withoutOverlapping()
					->cron('*/1 * * * * *')
					->thenPing(env('NOTIFYCONTROLLER_PING'));

	}

}
