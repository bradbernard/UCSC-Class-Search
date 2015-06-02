<?php namespace App\Http\Controllers;

use Twilio;
use DB;
use Config;
use File;
use Artisan;

class InsertController extends Controller {

   public function insertTerms()
   {
      $terms = DB::table('terms')->select(['term_id', 'offset', 'summer'])->get();
      DB::table(Config::get('table.inactive'))->truncate();

      foreach($terms as $term)
      {
         $this->insertTerm($term);
      }

      $this->switchActive();
   }

   private function switchActive()
   {
      $write = [

         'active'       => Config::get('table.inactive'),
         'inactive'     => Config::get('table.active'),

      ];

      File::put(config_path() . '/table.php', "<?php \n\nreturn " . $this->var_export54($write) . ";\n");
      Artisan::call('config:cache');
   }

   public function getConfig()
   {
      return [

         'active'    => Config::get('table.active'),
         'inactive'  => Config::get('table.inactive'),

      ];
   }

   public function insertTerm($term)
   {

      $client = new \GuzzleHttp\Client([

         'defaults'  => [

            'cookies'               => true,
            'timeout'               => 20.0,
            'connect_timeout'       => 20.0,
            'verify'                => false,
            'headers'               => [

               'User-Agent'         => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2398.0 Safari/537.36',
               'Accept'             => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
               'Accept-Encoding'    => 'gzip, deflate, sdch',
               'Accept-Language'    => 'en-US,en;q=0.8',
               'Dnt'                => '1',
               'Pragma'             => 'no-cache',
               'Cache-Control'      => 'no-cache',
               'Host'               => 'pisa.ucsc.edu',

            ],

         ]

      ]);

      $searchBody = [

         'action'                      => 'results',
         'binds[:term]'                => $term->term_id,
         'binds[:reg_status]'          => 'all',
         'binds[:catalog_nbr_op]'      => '=',
         'binds[:instr_name_op]'       => '=',
         'binds[:crse_units_op]'       => '=',

      ];

      $searchOptions = [

         'body'               => $searchBody,
         'headers'            => [

            'Referer'         => 'https://pisa.ucsc.edu/class_search/index.php',
            'Content-Type'    => 'application/x-www-form-urlencoded',

         ],

      ];
      
      $search = $client->post('https://pisa.ucsc.edu/class_search/index.php', $searchOptions);
      
      $responseBody = [

         'action'                      => 'update_segment',
         'Rec_Dur'                     => '2000',
         'sel_col[class_nbr]'          => '1',
         'sel_col[class_id]'           => '1',
         'sel_col[class_title]'        => '1',
         'sel_col[type]'               => '1',
         'sel_col[days]'               => '1',
         'sel_col[times]'              => '1',
         'sel_col[instr_name]'         => '1',
         'sel_col[status]'             => '1',
         'sel_col[enrl_cap]'           => '1',
         'sel_col[enrl_tot]'           => '1',
         'sel_col[seats_avail]'        => '1',
         'sel_col[location]'           => '1',
         'sel_col[course_materials]'   => '1',

      ];

      $responseOptions = [

         'body'               => $responseBody,
         'headers'            => [
         
            'Referer'         => 'https://pisa.ucsc.edu/class_search/index.php',
            'Content-Type'    => 'application/x-www-form-urlencoded',
         
         ],

      ];

      $response = $client->post('https://pisa.ucsc.edu/class_search/index.php', $responseOptions);
            
      $html = new \Htmldom();
      $html->load($response->getBody());

      $tableName = Config::get('table.inactive');
      $rows = $html->find('#results_table tbody tr');

      foreach($rows as $trow)
      {
         $spots = $trow->find('td');

         if(count($spots) == 0)
         {
            continue;
         }

         $offset = $term->offset;
         $status = $this->getStatus($spots[$offset + 7]);

         $data = [

            'term_id'               => $term->term_id,

            'class_number'          => htmlspecialchars_decode($spots[$offset +  0]->plaintext),
            'class_id'              => htmlspecialchars_decode($spots[$offset + 1]->plaintext),
            'class_title'           => htmlspecialchars_decode($spots[$offset + 2]->plaintext),

            'type'                  => htmlspecialchars_decode($spots[$offset + 3]->plaintext),
            'days'                  => htmlspecialchars_decode($spots[$offset + 4]->plaintext),
            'times'                 => htmlspecialchars_decode($spots[$offset + 5]->plaintext),
            'instructors'           => htmlspecialchars_decode(trim(preg_replace('/\s\s+/', ' ', $spots[$offset + 6]->plaintext))),

            'status'                => $status,
            'capacity'              => htmlspecialchars_decode($spots[$offset + 8]->plaintext),
            'enrollment_total'      => htmlspecialchars_decode($spots[$offset + 9]->plaintext),
            'available_seats'       => htmlspecialchars_decode($spots[$offset + 10]->plaintext),

            'location'              => htmlspecialchars_decode($spots[$offset + 11]->plaintext),

            'created_at'            => \Carbon\Carbon::now(),
            'updated_at'            => \Carbon\Carbon::now(),

         ];
         
         if($term->summer == 1)
         {
            $data['session'] = htmlspecialchars_decode($spots[0]->plaintext);
         }

         DB::table($tableName)->insert($data);
         
         foreach($spots as $spot)
         {
            $spot->clear();
            $spot = null;
         }

         $trow->clear();
         $trow = null;
      }

      $rows = null;

      $html->clear();
      $html = null;

      $client = null;
      $resposne = null;
      $search = null;

   }

   private function getHash($data)
   {
      $string = '';
      foreach($data as $key => $value)
      {
         if($key == 'created_at' || $key == 'updated_at') continue;

         $string .= $value;
      }

      return sha1($string);
   }

   private function getStatus($tdetail)
   {
      $status = 0;

      $text = $tdetail->find('img', 0)->alt;

      if($text == 'Open')
      {
         $status = 1;
      }
      else if($text == 'Waitlist')
      {
         $status = 2;
      }
      
      $tdetail->clear();
      
      return $status;
   }
   
   private function var_export54($var, $indent="")
   {
      switch (gettype($var)) {
          case "string":
              return '"' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '"';
          case "array":
              $indexed = array_keys($var) === range(0, count($var) - 1);
              $r = [];
              foreach ($var as $key => $value) {
                  $r[] = "$indent    "
                       . ($indexed ? "" : $this->var_export54($key) . " => ")
                       . $this->var_export54($value, "$indent    ");
              }
              return "[\n" . implode(",\n", $r) . "\n" . $indent . "]";
          case "boolean":
              return $var ? "TRUE" : "FALSE";
          default:
              return var_export($var, TRUE);
      }
  }

   private function br2nl($string)
   {
       return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
   }

}
