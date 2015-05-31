<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Twilio;
use Response;
use DB;
use Config;
use Log;

class SmsResponseController extends Controller {

   private $separator = ' ';

   public function postSms()
   {
      $params = Request::all();

      $from = $params['From'];
      $body = $params['Body'];

      $from = "+17146550347";
      $body = "Poop";

      return $this->parseBody($from, $body);
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
      
      if(count($args) == 1)
      {
         $commands = ['help', 'terms', 'list'];

         if(!in_array($args[0], $commands))
         {
            return $this->errorResponse($twilio);
         }
         else
         {
            if($args[0] == 'help')
            {
               return $this->errorResponse($twilio);
            }
            else if($args[0] == 'terms')
            {
               return $this->termsResponse($twilio);
            }
            else if($args[0] == 'list')
            {
               return $this->listResponseAll($twilio);
            }
         }
      }

      return $this->errorResponse($twilio);
   }

   private function listResponseAll($twilio)
   {
      $watchers = DB::table('watchers')->select(['class_number', 'term_id'])->get();
      
      $twiml = $twilio->twiml(function($message) use($watchers) {

         $responseBody  = "Watching:\n";

         foreach($watchers as $watcher)
         {
            $responseBody .= '- ' . $watcher->term_id . " " . $watcher->class_number . "\n";
         }

         $message->message($responseBody);

      });
      
      return $this->makeResponse($twiml);
   }
   
   private function termsResponse($twilio)
   {
      $terms = DB::table('terms')->select(['term_id', 'term_name'])->get();

      $twiml = $twilio->twiml(function($message) use($terms) {

         $responseBody  = "Terms:\n";

         foreach($terms as $term)
         {
            $responseBody .= '- ' . $term->term_id . " --> " . $term->term_name . "\n";
         }

         $message->message($responseBody);

      });

      return $this->makeResponse($twiml);
   }

   private function errorResponse($twilio)
   {
      $twiml = $twilio->twiml(function($message) {

         $responseBody  = "Available commands:\n";
         $responseBody .= "- remove {term_id} {class_num}\n";
         $responseBody .= "- add {term_id} {class_num}\n";
         $responseBody .= "- list {term_id}\n";
         $responseBody .= "- terms\n";
         $responseBody .= "- list\n";
         $responseBody .= "- help\n";

         $message->message($responseBody);

      });

      return $this->makeResponse($twiml);
   }

   private function makeResponse($xml)
   {
      $response = Response::make($xml, 200);
      $response->header('Content-Type', 'text/xml');

      return $response;
   }

}
