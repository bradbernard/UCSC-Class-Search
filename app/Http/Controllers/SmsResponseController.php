<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Twilio;

class SmsResponseController extends Controller {

   private $separator = ' ';

   public function postSms()
   {
      $params = Request::all();

      $from = $params['From'];
      $body = $params['Body'];

      $this->parseBody($from, $body);
   }

   public function parseBody($from, $body)
   {
      //list {term_id} (2158) --> Shows classes signed up for that term
      //list --> Shows all classes signed up for with that number
      //remove {term_id} {class_number} (2158 22581) --> Removes a class that they signed up for
      //add {term_id} {class_number} --> Adds a class to watch for that term
      //terms --> Shows all terms
      //help --> shows all commands

      $twilio = Twilio::from('twilio');
      $args = explode($this->separator, $body);

      if(count($args) == 0)
      {
         $twiml = $twilio->twiml(function($message) {

            $responseBody  = "Available commands:\n";
            $responseBody .= "remove {term_id} {class_num}\n";
            $responseBody .= "add {term_id} {class_num}\n";
            $responseBody .= "list {term_id}\n";
            $responseBody .= "terms\n";
            $responseBody .= "list\n";
            $responseBody .= "help\n";

            $message->message($responseBody);

         });

         return $twiml;
      }

   }

}
