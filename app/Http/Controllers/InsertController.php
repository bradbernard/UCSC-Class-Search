<?php namespace App\Http\Controllers;

use Twilio;
use DB;

class InsertController extends Controller {

   public function insertTerms()
   {
      $this->insertTerms(2158);
   }

   public function insertTerm($termId)
   {

      $client = new \GuzzleHttp\Client([

         'defaults'  => [

            'cookies'               => true,
            'timeout'               => 5.0,
            'connect_timeout'       => 5.0,
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
         'binds[:term]'                => $termId,
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

      foreach($html->find('#results_table tbody') as $tbody)
      {
         foreach($tbody->find('tr') as $trow)
         {
            $spots = $trow->find('td');

            if(count($spots) == 0)
               continue;

            $status = $this->getStatus($spots[7]);

            $data = [

               'term_id'               => $termId,

               'class_number'          => htmlspecialchars_decode($spots[0]->plaintext),
               'class_id'              => htmlspecialchars_decode($spots[1]->plaintext),
               'class_title'           => htmlspecialchars_decode($spots[2]->plaintext),

               'type'                  => htmlspecialchars_decode($spots[3]->plaintext),
               'days'                  => htmlspecialchars_decode($spots[4]->plaintext),
               'times'                 => htmlspecialchars_decode($spots[5]->plaintext),
               'instructors'           => htmlspecialchars_decode(trim(preg_replace('/\s\s+/', ' ', $spots[6]->plaintext))),

               'status'                => $status,
               'capacity'              => htmlspecialchars_decode($spots[8]->plaintext),
               'enrollment_total'      => htmlspecialchars_decode($spots[9]->plaintext),
               'available_seats'       => htmlspecialchars_decode($spots[10]->plaintext),

               'location'              => htmlspecialchars_decode($spots[11]->plaintext),

               'created_at'            => \Carbon\Carbon::now(),
               'updated_at'            => \Carbon\Carbon::now(),

            ];

            $data['hash'] = $this->getHash($data);

            if(DB::table('classes')->where('hash', $data['hash'])->count() == 0)
            {
               DB::table('classes')->insert($data);
            }

         }

      }
      
   }

   private function getHash($data)
   {
      return sha1('UCSC_CLASS_SEARCH' . $data['term_id'] . $data['class_number'] . $data['class_id'] . $data['class_title'] . $data['type'] . $data['days'] . $data['times'] . $data['instructors'] . $data['location']);
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
      
      return $status;
   }

   private function br2nl($string)
   {
       return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
   }

}
