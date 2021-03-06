<?php

class ServiceController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'HomeController@showWelcome');
	|
	*/

	public function getIndex() {
		$data = Session::all();
		$username = $data['username'][0];
		$hn = $data['hn'][0];

		$services = DB::table('Service2')->where('Service2.HN','=',$hn)
						->where('status','=','false')
						
						->join('Service', function($join)       {
							$join->on('Service2.HN','=','Service.HN')
							->on('Service2.serviceID','=','Service.serviceID');
						})	
						->join('ServiceType','Service2.serviceID','=','ServiceType.serviceID')					
						->get(['ServiceType.name','Service.date','Service2.status','Service.ServiceID']);

		return View::make('patient/service.index', array('username' => $username, 
														 'services' => $services));
	}

	public function postIndex(){
		$data = Session::all();
		$username = $data['username'][0];
		$hn = $data['hn'][0];
		$serviceID = Input::get('serviceid');
		$service_success = DB::statement("UPDATE Service2 SET status='true' where HN = '".$hn."' AND "."serviceID = '".$serviceID."'");
		return Redirect::to('service');
	}


	public function getAddorder() {
		$data = Session::all();
		$doctorid = $data['doctorID'][0];
		$patients =  Medrecord::where('doctorID','=',$doctorid)->get();
		$serviceTypes = ServiceType::all();

		return View::make('doctor/service.order', array('doctorid' => $doctorid,
														'patients' => $patients,
														'serviceTypes' => $serviceTypes));
	}

	public function postAddorder() {
		// Get input data
		$data = Session::all();
		$doctorid = $data['doctorID'][0];
		$hn = Input::get('HN');
		$status = Input::get('status');

		// Check Duplicate HN & serviceID
		$serviceTypeName = Input::get('serviceTypeName');
		$serviceID = ServiceType::where('name','=', $serviceTypeName)							
								->pluck('serviceID');

		$chkServiceType = Service::where('HN','=',$hn)
									->where('serviceID','=', $serviceID)
									->pluck('serviceID');

		if($serviceID == $chkServiceType){
			return Redirect::to('orderservice');
		}

		//dateTime from Medrecord
		$dateTime = Medrecord::where('HN','=',$hn)
							 ->where('doctorID','=',$doctorid)
							 ->pluck('dateTime');
		$dateTime = new DateTime();
		$dateTime = date_format($dateTime, 'Y-m-d H:i:s');

		// Service Date Time variable
		$temp = explode("/",Input::get('serviceDate'));
		$day = $temp[1];
		$month = $temp[0];
		$year = $temp[2];
		$serviceTime = explode(":",Input::get('serviceTime'));
		$serviceTimeDate = $day.'-'.$month.'-'.$year.' '.'00:00:00';

		// Create store variable for service2
		$service2 = new Service2();
		$temp = explode("'", $hn);
		if(count($temp)==1) $service2->HN = $hn;
		else {
			$service2->HN = explode("'", $hn)[0];
		}
		$service2->serviceID = $serviceID;
		$service2->status = $status;

		// service2 save success
		try{
			$service2_success = $service2->save();
		} catch (PDOException $exception) {
			//return Response::make('Save to Database error! ' . $exception->getCode());	
		}
		
		$queryService = 'INSERT INTO Service (date, dateTime, serviceID, HN) 
		VALUES (\''.$serviceTimeDate.'\',\''.$dateTime.'\',\''.$serviceID.'\',\''.$hn.'\') ';
		DB::unprepared($queryService);
		// Example SQL INJECTION (HN Textbox)
//		HN0000002'); UPDATE Doctor SET firstName='XXX' WHERE doctorID='DN00005';

		return Redirect::to('/doctor');
	}

}
