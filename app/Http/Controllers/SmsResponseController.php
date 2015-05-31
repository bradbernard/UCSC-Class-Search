<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Request;
use Twilio;
use Response;
use DB;
use Config;
use Log;

class SmsResponseController extends Controller {

   private $separator = ' ';

   private $listSelect = [

      'terms.term_name', 'classes.class_title', 'classes.class_id', 'classes.class_number',
      'watchers.id', 'terms.term_id',

   ];

   private $twilio = null;
   private $from = null;
   private $body = null;
   private $termId = null;
   private $args = null;
   private $classNumber = null;

   public function postSms()
   {
      $params = Request::all();

      $this->from = $params['From'];
      $this->body = $params['Body'];

      return $this->parseBody();
   }

   public function parseBody()
   {
      //list {term_id} (2158) --> Shows classes signed up for that term
      //list --> Shows all classes signed up for with that number
      //remove {term_id} {class_number} (2158 22581) --> Removes a class that they signed up for
      //add {term_id} {class_number} --> Adds a class to watch for that term
      //terms --> Shows all terms
      //info --> shows all commands

      Log::info("From: {$this->from}");

      $this->twilio = Twilio::from('twilio');
      $this->args = explode($this->separator, strtolower(trim($this->body)));

      if(count($this->args) == 1)
      {
         $commands = ['info', 'terms', 'list'];

         if(!in_array($this->args[0], $commands))
         {
            return $this->errorResponse();
         }

         if($this->args[0] == 'info')
         {
            return $this->errorResponse();
         }
         else if($this->args[0] == 'terms')
         {
            return $this->termsResponse();
         }
         else if($this->args[0] == 'list')
         {
            return $this->listResponseAll();
         }

      }
      else if(count($this->args) == 2)
      {
         $commands = ['list'];

         if(!in_array($this->args[0], $commands))
         {
            return $this->errorMessage("Invalid command.");
         }

         if($this->args[0] == 'list')
         {
            if($this->validTerm())
            {
               return $this->listResponseTerm();
            }
            else
            {
               return $this->errorMessage("Invalid term ID.");
            }
         }
      }
      else if(count($this->args) == 3)
      {
         $commands = ['add', 'remove'];
         
         if(!in_array($this->args[0], $commands))
         {
            return $this->errorMessage("Invalid command.");
         }
         
         if($this->args[0] == 'add')
         {
            if($this->validTerm())
            {
               if($this->validClass())
               {
                  if(!$this->watchingAlready())
                  {
                     return $this->addResponse();
                  }
                  else
                  {
                     return $this->errorMessage("You are already watching this class.");
                  }
               }
               else
               {
                  return $this->errorMessage("Invalid class number.");
               }
            }
            else
            {
               return $this->errorMessage("Invalid term ID.");
            }
         }

         if($this->args[0] == 'remove')
         {
            if($this->validTerm())
            {
               if($this->validClass())
               {
                  if($this->watchingAlready())
                  {
                     return $this->removeResponse();
                  }
                  else
                  {
                     return $this->errorMessage("You are not watching this class so you cannot remove it.");
                  }
               }
               else
               {
                  return $this->errorMessage("Invalid class number.");
               }
            }
            else
            {
               return $this->errorMessage("Invalid term ID.");
            }
         }
      }
      else
      {
         return $this->errorResponse();
      }
   }

   private function listResult($termId = -1)
   {
      if($termId == -1)
      {
         return DB::table('watchers')
               ->select($this->listSelect)
               ->join('terms', 'terms.term_id', '=', 'watchers.term_id')
               ->join('classes', function($join)
               {
                  $join->on('classes.class_number', '=', 'watchers.class_number');
                  $join->on('classes.term_id', '=', 'watchers.term_id');
               })
               ->where('watchers.phone_number', $this->from)
               ->groupBy('watchers.id')
               ->get();
      }
      else
      {
         return DB::table('watchers')
               ->select($this->listSelect)
               ->join('terms', 'terms.term_id', '=', 'watchers.term_id')
               ->join('classes', function($join)
               {
                  $join->on('classes.class_number', '=', 'watchers.class_number');
                  $join->on('classes.term_id', '=', 'watchers.term_id');
               })
               ->where('watchers.phone_number', $this->from)
               ->where('watchers.term_id', $termId)
               ->groupBy('watchers.id')
               ->get();
      }
   }
   
   private function errorMessage($error)
   {
      $twiml = $this->twilio->twiml(function($message) use ($error) {

         $message->message($error);

      });

      return $this->makeResponse($twiml);
   }
   
   private function watchingAlready()
   {
      if($this->termId == '*' && $this->classNumber == '*')
      {
         return true;
      }

      if(is_numeric($this->termId) && $this->classNumber == '*')
      {
         return true;
      }

      if(is_numeric($this->classNumber) && $this->termId == '*')
      {
         $response = $this->errorMessage("Wildcard only allowed on both term ID and class number or on class number and not on term ID. Like: * * or {num} * not * {num}");
         $response->send();
         die();
      }

      $exists = DB::table('watchers')->select('id')
               ->where('term_id', $this->termId)
               ->where('class_number', $this->classNumber)
               ->where('phone_number', $this->from)
               ->count();
               
      if($exists > 0)
      {
         return true;
      }
      
      return false;
   }
   
   private function validTerm()
   {
      $termId = $this->args[1];

      if(is_numeric($termId))
      {
         $exists = DB::table('terms')->select('id')->where('term_id', $termId)->count();

         if($exists == 1)
         {
            $this->termId = $termId;

            return true;
         }
      }
      else
      {
         if($termId == '*')
         {
            if($this->args[0] == 'remove')
            {
               $this->termId = '*';

               return true;
            }
            else
            {
               $response = $this->errorMessage("Wildcards only allowed on remove command.");
               $response->send();
               die();
            }
         }
         else
         {
            $response = $this->errorMessage("Invalid term ID.");
            $response->send();
            die();
         }
      }

      return false;
   }
   
   private function validClass()
   {
      $classNumber = $this->args[2];
      
      if(is_numeric($classNumber))
      {
         $exists = DB::table(Config::get('table.active'))->select('id')->where('class_number', $classNumber)->groupBy('id')->count();
         
         if($exists == 1)
         {
            $this->classNumber = $classNumber;
            
            return true;
         }
      }
      else
      {
         if($classNumber == '*')
         {
            if($this->args[0] == 'remove')
            {
               $this->classNumber = '*';

               return true;
            }
            else
            {
               $response = $this->errorMessage("Wildcards only allowed on remove command.");
               $response->send();
               die();
            }
         }
         else
         {
            $response = $this->errorMessage("Invalid class number.");
            $response->send();
            die();
         }
      }
      
      return false;
   }

   private function addResponse()
   {
      DB::table('watchers')->insert([

         'phone_number'    => $this->from,
         'term_id'         => $this->termId,
         'class_number'    => $this->classNumber,
         'created_at'      => \Carbon\Carbon::now(),
         'updated_at'      => \Carbon\Carbon::now(),

      ]);

      return $this->listResponseAll();
   }

   private function removeResponse()
   {
      $res = DB::table('watchers')->where('phone_number', $this->from);

      if($this->termId != '*')
      {
         $res = $res->where('term_id', $this->termId);
      }

      if($this->classNumber != '*')
      {
         $res = $res ->where('class_number', $this->classNumber);
      }

      $res->delete();

      return $this->listResponseAll();
   }

   private function listResponseTerm()
   {
      $terms = [];

      $watchers = $this->listResult($this->termId);

      foreach($watchers as $watch)
      {
         if(!in_array($watch->term_id, $terms))
         {
            $terms[$watch->term_id] = $watch->term_name;
         }
      }

      $twiml = $this->twilio->twiml(function($message) use($watchers, $terms) {

         $responseBody = '';

         if(count($terms) > 0)
         {
            $responseBody  = "Watching:\n";

            foreach($terms as $key => $term)
            {
               $responseBody .= '- ' . $term . ' (' . $key . ')' . "\n";

               foreach($watchers as $watcher)
               {
                  if($key == $watcher->term_id)
                  {
                     $responseBody .= '-- ' . $watcher->class_id . ' (' . $watcher->class_number . ')' . "\n";
                  }
               }
            }
         }
         else
         {
            $responseBody .= "You are not watching any classes in this term.";
         }

         $message->message($responseBody);

      });
      
      return $this->makeResponse($twiml);
   }

   private function listResponseAll()
   {
      $terms = [];

      $watchers = $this->listResult();
      
      foreach($watchers as $watch)
      {
         if(!in_array($watch->term_id, $terms))
         {
            $terms[$watch->term_id] = $watch->term_name;
         }
      }

      $twiml = $this->twilio->twiml(function($message) use($watchers, $terms) {

         $responseBody = '';

         if(count($terms) > 0)
         {
            $responseBody  .= "Watching:\n";

            foreach($terms as $key => $term)
            {
               $responseBody .= '- ' . $term . ' (' . $key . ')' . "\n";

               foreach($watchers as $watcher)
               {
                  if($key == $watcher->term_id)
                  {
                     $responseBody .= '-- ' . $watcher->class_id . ' (' . $watcher->class_number . ')' . "\n";
                  }
               }
            }
         }
         else
         {
            $responseBody .= 'You are not watching any classes.';
         }

         $message->message($responseBody);

      });
      
      return $this->makeResponse($twiml);
   }
   
   private function termsResponse()
   {
      $terms = DB::table('terms')->select(['term_id', 'term_name'])->get();

      $twiml = $this->twilio->twiml(function($message) use($terms) {

         $responseBody  = "Terms:\n";

         foreach($terms as $term)
         {
            $responseBody .= '- ' . $term->term_id . " --> " . $term->term_name . "\n";
         }

         $message->message($responseBody);

      });

      return $this->makeResponse($twiml);
   }

   private function errorResponse()
   {
      $twiml = $this->twilio->twiml(function($message) {

         $responseBody  = "Commands:\n";
         $responseBody .= "- remove {term_id | *} [class_num | *]\n";
         $responseBody .= "- add {term_id} {class_num}\n";
         $responseBody .= "- list [term_id]\n";
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
