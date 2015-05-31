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
				$this->doCheck((array)$check);
			}
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

		$message = '';

		$message    .= $class->enrollment_total . '/' . $class->capacity . " (" . $class->available_seats . " open)\n";
      $message    .= $class->class_id . ' (' . $class->class_number . ')' . "\n";
      $message    .= $class->class_title . "\n";
      $message    .= $class->instructors . "\n";
		$message		.= $class->term_name . "\n";
		$message		.= "\n";
		$message		.= Carbon::now("PST")->format('n/j g:i A') . "\n";

		//$message    .= 'Location: ' . $class->location . "\n";
      //$message    .= 'Days: ' . $class->days . "\n";
      //$message    .= 'Times: ' . $class->times . "\n";
      //$message    .= 'Type: ' . $class->type . "\n";

      return $twilio->message($number, $message);
   }

}
