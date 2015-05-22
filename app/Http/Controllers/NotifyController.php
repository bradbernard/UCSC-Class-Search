<?php namespace App\Http\Controllers;

use Twilio;
use DB;

class NotifyController extends Controller {

   public function checkOpen()
   {
      $checks = [

         [
            'term_id'         => 2158,
            'class_number'    => 21889,
            'phone_number'    => getenv('BRAD_NUMBER'),
         ],
			[
				'term_id'			=> 2158,
				'class_number'		=> 20753,
				'phone_number'		=> getenv('BRAD_NUMBER')
			],
			[
				'term_id'			=> 2158,
				'class_number'		=> 20720,
				'phone_number'		=> getenv('MATT_NUMBER'),
			],
			[
				'term_id'			=> 2158,
				'class_number'		=> 20713,
				'phone_number'		=> getenv('CHRIS_NUMBER')
			],

      ];

		foreach($checks as $check)
		{
			$this->doCheck($check);
		}

   }

   public function doCheck($options)
   {
      $class = DB::table('classes')
                  ->where('term_id', $options['term_id'])
                  ->where('class_number', $options['class_number'])
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

      $message     = 'There are ' . $class->available_seats . ' available seats!' . "\n";
      $message    .= 'Seats Filled: ' . $class->enrollment_total . '/' . $class->capacity . "\n";
      $message    .= 'Class: ' . $class->class_id . ' (#' . $class->class_number . ')' . "\n";
      $message    .= 'Name: ' . $class->class_title . "\n";
      $message    .= 'Teacher: ' . $class->instructors . "\n";
      $message    .= 'Location: ' . $class->location . "\n";
      $message    .= 'Days: ' . $class->days . "\n";
      $message    .= 'Times: ' . $class->times . "\n";
      $message    .= 'Type: ' . $class->type . "\n";

      return $twilio->message($number, $message);
   }

}
