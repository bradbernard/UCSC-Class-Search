<?php namespace App\Http\Controllers;

use Twilio;

class SearchController extends Controller {

   function searchMine()
   {

      $options = [

         [
            'class' => [

               'name'            => 'CMPE 12 - 01',
               'teacherFull'     => 'Dunne,M.J.',
               'time'            => '08:00AM-09:45AM',
               'days'            => 'TuTh',
               'type'            => 'LEC',
               'location'        => 'Humn Lecture Hall',
               'credits'         => '5',
               'teacherShort'    => 'Dunne',
               'subjectShort'    => 'CMPE',
               'matches'         => '6'
            
            ],
            'number' => getenv('BRAD_NUMBER'),
            'termId' => 2158
         ],
         [
            'class' => [

               'name'            => 'CMPS 112 - 01',
               'teacherFull'     => 'Mackey,W.F.',
               'time'            => '03:30PM-04:40PM',
               'days'            => 'MWF',
               'type'            => 'LEC',
               'location'        => 'Kresge Clrm 327',
               'credits'         => '5',
               'teacherShort'    => 'Mackey',
               'subjectShort'    => 'CMPS',
               'matches'         => '6'

            ],
            'number' => getenv('BRAD_NUMBER'),
            'termId' => 2158
         ],
         [
            'class' => [

               'name'            => 'CMPS 130 - 01',
               'teacherFull'     => 'Van Gelder,A.',
               'time'            => '02:00PM-03:10PM',
               'days'            => 'MWF',
               'type'            => 'LEC',
               'location'        => 'Steven Acad 175',
               'credits'         => '5',
               'teacherShort'    => 'Van Gelder',
               'subjectShort'    => 'CMPS',
               'matches'         => '6'

            ],
            'number' => getenv('BRAD_NUMBER'),
            'termId' => 2158
         ],
         [
            'class' => [

               'name'            => 'CMPS 12B - 01',
               'teacherFull'     => 'Whitehead,N.O.',
               'time'            => '08:00AM-09:45AM',
               'days'            => 'TuTh',
               'type'            => 'LEC',
               'location'        => 'Thim Lecture 003',
               'credits'         => '5',
               'teacherShort'    => 'Whitehead',
               'subjectShort'    => 'CMPS',
               'matches'         => '6'

            ],
            'number' => getenv('CHRIS_NUMBER'),
            'termId' => 2158
         ],
         [
            'class'  => [

               'name'            => 'CMPS 5J - 01',
               'teacherFull'     => 'Tantalo,P.',
               'time'            => '05:00PM-06:45PM',
               'days'            => 'MW',
               'type'            => 'LEC',
               'location'        => 'Media Theater M110',
               'credits'         => '5',
               'teacherShort'    => 'Tantalo',
               'subjectShort'    => 'CMPS',
               'matches'         => '6'

            ],
            'number' => getenv('MATT_NUMBER'),
            'termId' => 2158
         ]

      ];

      foreach($options as $option)
      {
         $this->searchClass($option);
      }
      
   }

   function searchClass($options)
   {

      $number  = $options['number'];
      $class   = $options['class'];
      $termId  = $options['termId'];

      $body = [

         'action'                      => 'results',
         'binds[:term]'                => $termId,
         'binds[:reg_status]'          => 'all',
         'binds[:subject]'             => $class['subjectShort'],
         'binds[:instr_name_op]'       => 'contains',
         'binds[:instructor]'          => $class['teacherShort'],
         'binds[:crse_units_exact]'    => $class['credits']

      ];

      $options = [

         'body' => $body

      ];

      $client = new \GuzzleHttp\Client();
      $response = $client->post('https://pisa.ucsc.edu/class_search/index.php', $options);

      $html = new \Htmldom();
      $html->load($response->getBody());

      foreach($html->find('#results_table tbody') as $tbody)
      {
         foreach($tbody->find('tr') as $trow)
         {
            foreach($trow->find('td') as $tdetail)
            {
               if($tdetail->plaintext == $class['name'])
               {
                  if($this->validateClass($trow, $class))
                  {
                     $seats = $this->openSeats($trow, $class);

                     if($seats['availableSeats'] > 0)
                     {
                        if($this->sendText($number, $class, $seats, $trow))
                        {
                           echo 'Sent text to ' . $number . '. There are ' . $seats['availableSeats'] . ' open seats in ' . $class['name'] . ".\n";

                           break 3;
                        }
                     }
                  }
               }
            }
         }
      }
   }

   function validateClass($trow, $class)
   {
      $matches = 0;
      //$index = 0;
      $requiredMatches = $class['matches'];

      foreach($trow->find('td') as $tdetail)
      {
         if(in_array($tdetail->plaintext, $class, true))
         {
            $matches++;
         }

         //echo "<pre>", ($index++), ': ', $tdetail->plaintext, "</pre><br/>";
      }

      if($matches == $requiredMatches)
      {
         return true;
      }

      return false;
   }

   function openSeats($trow, $class)
   {
      $capacity            = $trow->find('td', 8)->plaintext;
      $enrollmentTotal     = $trow->find('td', 9)->plaintext;
      $availableSeats      = $trow->find('td', 10)->plaintext;

      //echo "$capacity $enrollmentTotal $availableSeats <br/>";

      return [

         'availableSeats'        => $availableSeats,
         'enrollmentTotal'       => $enrollmentTotal,
         'capacity'              => $capacity

      ];
   }

   function sendText($number, $class, $seats, $trow)
   {

      $twilio = Twilio::from('twilio');

      $message     = 'There are ' . $seats['availableSeats'] . ' available seats!' . "\n";
      $message    .= 'Seats Filled: ' . $seats['enrollmentTotal'] . '/' . $seats['capacity'] . "\n";
      $message    .= 'Class: ' . $class['name'] . "\n";
      $message    .= 'Name: ' . $trow->find('td', 2)->plaintext . "\n";
      $message    .= 'Teacher: ' . $class['teacherFull'] . "\n";
      $message    .= 'Location: ' . $class['location'] . "\n";
      $message    .= 'Days: ' . $class['days'] . "\n";
      $message    .= 'Time: ' . $class['time'] . "\n";
      $message    .= 'Type: ' . $class['type'] . "\n";
      $message    .= 'Credits: ' . $class['credits'] . "\n";

      return $twilio->message($number, $message);
   }
   
   function statusCheck()
   {
      return [

         'status' => 'healthy'

      ];
   }

   //$class = [
   //
   //   'name'            => 'CMPE 12 - 01',
   //   'teacherFull'     => 'Dunne,M.J.',
   //   'time'            => '08:00AM-09:45AM',
   //   'days'            => 'TuTh',
   //   'type'            => 'LEC',
   //   'location'        => 'Humn Lecture Hall',
   //   'credits'         => '5',
   //   'teacherShort'    => 'Dunne',
   //   'subjectShort'    => 'CMPE',
   //   'matches'         => '5'
   //
   //];

   //$class = [
   //
   //   'name'            => 'CMPS 129 - 01',
   //   'teacherFull'     => 'Long,D.D.',
   //   'time'            => '03:30PM-04:40PM',
   //   'days'            => 'MWF',
   //   'type'            => 'LEC',
   //   'location'        => 'J Baskin Engr 165',
   //   'credits'         => '5',
   //   'teacherShort'    => 'Long',
   //   'subjectShort'    => 'CMPS',
   //   'matches'         => '6'
   //
   //];

   //$class = [
   //
   //   'name'            => 'CMPS 280S - 01',
   //   'teacherFull'     => 'Long,D.D.',
   //   'time'            => '01:00PM-03:00PM',
   //   'days'            => 'M',
   //   'type'            => 'SEM',
   //   'location'        => 'Engineer 2 599',
   //   'credits'         => '2',
   //   'teacherShort'    => 'Long',
   //   'subjectShort'    => 'CMPS',
   //   'matches'         => '6'
   //
   //];

}
