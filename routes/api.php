<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use NextGenSwitch\VoiceResponse;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('send-call', function(){
	$client = new CurlClient();
	$response = $client->request('post', 
	'http://sg.nextgenswitch.com/api/v1/call',[], 
	['to' => '1000', 'from' => '01518307641', 'response' => route('home')],
	['X-Authorization'=>'RWopQ05OeelxJ6K526gPrvJCfXh5avUjJ2IuMe7tWMnmJ3fXePKiBHdsJOCqUfy8',
	'X-Authorization-Secret'=>'wZHEJvcrCiFbWP1el9PuUvSa1LG02vcyI2EBxdKlWtHZlSXUIIYUyf0QKcvLUbGq']);
	if($response->ok()){
	 echo "<pre>";   print_r($response->getContent()); echo "</pre>";
	}else
		echo $response;
});


Route::post('/', function(){
	$response = getVoices();
	return $response;
	//dd($response->xml());
})->name('home');


Route::post('/hello', function(){
	return getVoices('hello');
	
})->name('hello');

Route::post('/yes_no', function(Request $request){
	if($request->input('speech_result') != ''){       
	
		$response = getWitIntent($request->input('speech_result'));
		
        if(isset($response["entity"]) && $response["entity"]['name'] == "no"){          
            return getVoices('yes_no_no');
        }else{       
           return getVoices('yes_no_yes');
        }
		
    }else{
        $voice_response->say("can not understand sir ");
        return route('hello');
    }
	
})->name('yes_no');



Route::post('/get_date', function(Request $request){
	
	if($request->input('speech_result') != ''){
        return getVoices('get_date');
    }else{
        return getVoices('get_avaialable_yes');        
    }
})->name('get_date');


Route::post('/get_avaialable', function(Request $request){
	
	if($request->input('speech_result') != ''){
        $response = getWitIntent($request->input('speech_result'));
        info($response);
         if(isset($response["entity"]) && $response["entity"]['name'] == "yes"){
            return getVoices('get_avaialable_yes');
         }else{
            return getVoices('get_avaialable_no');    
         }
    }else{
       return getVoices('yes_no_yes');
    }
	
})->name('get_avaialable');


function getVoices($action = ''){
	
	$voice_response = new VoiceResponse();
	
    if(empty($action)){
         $gather = $voice_response->gather(['input'=>'speech','transcript'=>false,'action'=>route('hello')]);
        $gather->say("Hello Sir !!");
    }elseif($action == 'hello'){
         $voice_response->say("Good morning Sir ! I am Ria talking from City Central Bank.");
        $voice_response->say("Am I Talking with");   
        $gather = $voice_response->gather(['input'=>'speech','action'=>route('yes_no')]);
        $gather->say("Mr. Khairul Alam");
    }elseif($action == 'get_avaialable_yes'){
        $voice_response->say("Ok, Sir thanks for your confirmation .");
    }elseif($action == 'get_avaialable_no'){
        $voice_response->say("Can you please tell me when the  money will be available in your account ?");
        $gather = $voice_response->gather(['input'=>'speech','action'=>route('get_date')]);
        $gather->say("Today , tomorrow or some other days ?"); 
    }elseif($action == 'yes_no_yes'){
        $voice_response->say("Sir, You have an E M I due amount fourty five thousand .");
        $gather = $voice_response->gather(['input'=>'speech','action'=>route('get_avaialable')]);
        $gather->say("Can you please confirm the money is available in your account ?");
    }elseif($action == 'yes_no_no'){
        $voice_response->say("Ok Sir, I will Call some other time . Thank you.");
    }elseif($action == 'get_date')
        $voice_response->say("Thanks for your confirmation Sir. Have a good day ! ");

    return   $voice_response->xml();  
}


function getWitIntent($text){
	
	define('BARIER_KEY','ONUSS24K2F3JPI4SAQVQYNI5CCKNUXV6');
	
	$url = 'https://api.wit.ai/message';
	 $params = [
		'v' =>'20240311',
		'q' => $text,
	];

	$queryString = http_build_query($params);


	$ch = curl_init();


	curl_setopt($ch, CURLOPT_URL, $url . '?' . $queryString);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


	$headers = [
		'Authorization: Bearer ' . BARIER_KEY,
		'Content-Type: application/json',
	];

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


	$response = curl_exec($ch);

	$data = array();

	if (curl_errno($ch))
		return false;


	curl_close($ch);

	$response =  json_decode($response, true);
	
	

	/*
	if( count($response['intents']) == 0){
		return false;
	}
	*/

	if( count($response['intents']) > 0){
		$data['intent'] = $response['intents'][0]['name'];            
	}

	if(count($response['entities']) > 0){
		$expected_entities = $response['entities'][array_key_first($response['entities'])];

		$entity = $expected_entities[array_key_first($expected_entities)];

		$data['entity'] = [
			'name' => $entity['name'],
			'value' => $entity['value']
		];
	}


	return $data;


}