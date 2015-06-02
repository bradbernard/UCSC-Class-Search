<?php namespace App\Http\Controllers;

use Twilio;
use DB;
use Config;
use Carbon\Carbon;

class NotifyController extends Controller {

	public function getWatchers($termId)
	{
		return DB::table('watchers')->select(['term_id', 'class_number', 'phone_number'])->where('term_id', $termId)->get();
	}

   public function checkOpen()
   {
		$terms = DB::table('terms')->select('term_id')->get();

		foreach($terms as $term)
		{
			$checks = $this->getWatchers($term->term_id);

			foreach($checks as $check)
			{
				$this->doCheck((array) $check);
			}
		}
   }
	
	public function checkClasses()
	{
		$terms = DB::table('terms')->select('term_id')->get();
		
		$select = [

			Config::get('table.active') . '.enrollment_total', Config::get('table.active') . '.capacity',
			Config::get('table.active') . '.available_seats', Config::get('table.active') . '.class_id',
			Config::get('table.active') . '.class_number', Config::get('table.active') . '.class_title',
			Config::get('table.active') . '.instructors', 'terms.term_name', Config::get('table.active') . '.status',
			Config::get('table.active') . '.term_id', 'watchers.phone_number',

		];

		foreach($terms as $term)
		{
			DB::table('watchers')
			->select($select)
			->join('terms', 'terms.term_id', '=', 'watchers.term_id')
			->join(Config::get('table.active'), function($join)
			{
				$join->on(Config::get('table.active') . '.class_number', '=', 'watchers.class_number');
				$join->on(Config::get('table.active') . '.term_id', '=', 'watchers.term_id');
			})
			->where('watchers.term_id', $term->term_id)
			->where('watchers.text_status', DB::raw(Config::get('table.active') . '.status'))
			->chunk(500, function($watchers)
			{
				foreach($watchers as $watcher)
				{
					$this->sendText($watcher->phone_number, $watcher);
				}

			});

			DB::table('watchers')
			->join(Config::get('table.active'), function($join)
			{
				$join->on(Config::get('table.active') . '.class_number', '=', 'watchers.class_number');
				$join->on(Config::get('table.active') . '.term_id', '=', 'watchers.term_id');
			})
			->where('watchers.term_id', $term->term_id)
			->where('watchers.text_status', DB::raw(Config::get('table.active') . '.status'))
			->update(['watchers.text_status' => DB::raw('IF (watchers.text_status = 1, 0, 1)')]);
		}
	}

   public function doCheck($options)
   {
      $class = DB::table(Config::get('table.active'))
						->join('terms', 'terms.term_id', '=', Config::get('table.active') . '.term_id')
                  ->where(Config::get('table.active') . '.term_id', $options['term_id'])
                  ->where(Config::get('table.active') . '.class_number', $options['class_number'])
                  ->where('status', 1)
                  ->get();

      if(count($class) == 1)
      {
         $this->sendText($options['phone_number'], $class[0]);
      }
   }

	public function sendText($number, $class)
   {
      $twilio = Twilio::from('twilio');

		$message = "Class open!\n";

		if($class->status == 0)
		{
			$message	= "Class closed!\n";
		}

		$message    .= $class->enrollment_total . '/' . $class->capacity . ' (' . $class->available_seats . " open)\n";
      $message    .= $class->class_id . ' (' . $class->class_number . ")\n";
      $message    .= $class->class_title . "\n";
      $message    .= $class->instructors . "\n";
		$message		.= $class->term_name . ' (' . $class->term_id . ")\n";

		if($class->session != null)
		{
			$message .= $class->session . "\n";
		}

		$message		.= Carbon::now("PST")->format('n/j g:i:s A') . "\n";

      $twilio->message($number, $message);
   }

}
