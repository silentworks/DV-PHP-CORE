<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Service;
use Illuminate\Http\Request;
use App\Service as serviceModel;
use App\Helpers\Helper;
use App\Http\Controllers\ScriptController as Script ;
use App\Http\Controllers\DbController as Db;
use App\Http\Controllers\ViewController as View;
use Session;
use \App\Helpers\DevlessHelper as DLH;
use App\Http\Controllers\ViewController as DvViews;
use Validator;

class ServiceController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$services = Service::orderBy('id', 'desc')->paginate(10);

		return view('services.index', compact('services'));
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return view('services.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function store(Request $request)
	{       
                $service = new Service();
                $service_name_from_form = $request->input("name"); 
                $service_name_from_form = preg_replace('/\s*/', '', $service_name_from_form);
                $service_name = strtolower($service_name_from_form);

                $validator = Validator::make(
                    
                    ['name'=>$service_name],
                    
                    [
                        'name'=>'required|unique:services,name',
                        
                    ]
                    
                    
                    );
                
                 if($validator->fails()){
                     $errors = $validator->messages();
                     DLH::flash("Sorry but service could not be created", 'error');
                     return redirect()->route('services.create')->with('errors',$errors)->withInput();
                 }

                    $service->name = $service_name;
                    $service->description = $request->input("description");
                    $service->username = $request->input("username");
                    $service->password = $request->input('password');
                    $service->database = $request->input('database');
                    $service->hostname = $request->input('hostname');
                    $service->driver = $request->input('driver');
                    $service->resource_access_right = 
                            '{"query":0,"create":0,"update":0,"delete":0,"schema":0,"script":0, "view":0}';
                    $service->active = 1;
                    $service->script = 'echo "Happy Coding";';

                    $connection = 
                    [
                        'username' => $service->username,
                        'password' => $service->password,
                        'database' => $service->database,
                        'hostname' => $service->hostname,
                        'driver'   => $service->driver,
                    ];
                    $db = new Db();

                    if(!$db->check_db_connection($connection)){
                        
                         DLH::flash("Sorry connection could not be made to Database", 'error');
                    }
                    else
                    {
                       //create initial views for service 
                        $views = new DvViews();
                        $type = "init";
                         
                     ($service->save() && $views->create_views($service_name, $type) )
                                        ? 
                        DLH::flash("Service created successfully", 'success'):
                        DLH::flash("Service could not be created", 'error');
                    }
                
                    return $this->edit($service->id);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		$service = Service::findOrFail($id);

		return view('services.show', compact('service'));
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		$service = Service::findOrFail($id);
                $table_meta = \App\TableMeta::where('service_id',$id)->get();
                $count = 0;
                foreach($table_meta as $each_table_meta)
                {
                    $table_meta[$count]  = (json_decode($each_table_meta->schema, true));
                    $count++;
                }
                
		return view('services.edit', compact('service','table_meta'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @param Request $request
	 * @return Response
	 */
	public function update(Request $request, $id)
	{   
                $views = new DvViews();
                    
		if($service = Service::findOrFail($id))
                {
                    if($request->input('call_type') =='solo')
                    {
                        $service->script = $request->input('script');
                        $service->save();
                        Helper::interrupt(626);
                    }
                    
                    
                    
                    $service->description = $request->input("description");
                    $service->username = $request->input("username");
                    $service->password = $request->input('password');
                    $service->database = $request->input('database');
                    $service->hostname = $request->input('hostname');
                    $service->driver = $request->input('driver');
                    $service->active = $request->input("active");
                    
                    $connection = 
                    [
                        'username' => $service->username,
                        'password' => $service->password,
                        'database' => $service->database,
                        'hostname' => $service->hostname,
                        'driver'   => $service->driver,
                    ];
                    $db = new Db();
                    if(!$db->check_db_connection($connection)){
                        
                         DLH::flash("Sorry connection could not be made to Database", 'error');
                    }
                    else
                    {
                    
                     ($service->save())? DLH::flash("Service updated successfully", 'success'):
                        DLH::flash("Changes did not take effect", 'error');
                    }
                }   
		return back();
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
                $service = Service::findOrFail($id);
                $service_name = $service->name;
                $view_path = config('devless')['views_directory'];
                $assets_path = $view_path.$service_name;
                
                $table_meta = \App\TableMeta::where('service_id',$id)->get();
                foreach($table_meta as $meta)
                {
                    $table_name = $meta->table_name;
                    $output = DLH::purge_table($service_name.'_'.$table_name);
                    
                }
		
		if(DLH::deleteDirectory($assets_path ) && $service->delete())
                {
                    DLH::flash("Service deleted successfully", 'success');
                }
                else
                {
                    DLH::flash("Service could not be deleted", 'error');
                }

		return redirect()->route('services.index');
	}
        
        
          /**
           * download service packages
           * @param $request
           * @param $filename
           * 
           */
          public function download_service_package($filename)
          {
              
              $file_path = DLH::get_file($filename);
              if($file_path)
              {
                  // Send Download
                     return \Response::download($file_path, $filename
                     )->deleteFileAfterSend(true);
              }
              else
              {
                  DLH::flash("could not download files");
               }
              
          }
        /**
        * all api calls go through here
        * @param array  $request request params 
        * @param string  $service  service to be accessed
        * @param string $resource resource to be accessed
        * @return Response
        */
        public function api(Request $request, $service, $resource)
        {
            
             //check token and keys
            $is_key_right = ($request->header('Devless-key') == $request['devless_key'])?true : false;
            $is_key_token = ($request->header('Devless-token') == $request['devless_token'] )? true : false;
            $is_admin = Helper::is_admin_login();

            (($is_key_right && $is_key_token) || $is_admin )? true : Helper::interrupt(631);

            $this->resource($request, $service, $resource);
        }
        
        /**
	 * Refer request to the right service and resource  
         * @param array  $request request params 
	 * @param string  $service  service to be accessed
         * @param string $resource resource to be accessed
	 * @return Response
	 */
        public function resource($request, $service_name, $resource, $internal_access=false)
        {  
            $resource = strtolower($resource);
            $service_name = strtolower($service_name);
            ($internal_access == true)? $method = $request['method'] :
            $method = $request->method();
            
            $method = strtoupper($method);
            #check method type and get payload accordingly
         
            if($internal_access == true)
            {
                $parameters = $request['resource'];
                
            }
            else
            {
                $parameters = $this->get_params($method, $request);
                
            }
            
            
            //$resource
            return $this->assign_to_service($service_name, $resource, $method, 
                    $parameters,$internal_access);
        }
        
       
        
        /**
	 * assign request to a devless service .
	 *
         * @param string $service name of service to be access 
	 * @param  string  $resource
         * @param array $method http verb
         * @param array $parameter contains all parameters passed from route
         * @param boolean $internal_service true if service is being called internally
	 * @return Response
	 */
        public function assign_to_service($service_name, $resource, $method,
                $parameters=null,$internal_access=false)
        {       
                $current_service = $this->service_exist($service_name);
               
                //check service access right 
                $is_it_public = $current_service->public;
                $is_admin = Helper::is_admin_login();
                $accessed_internally = $internal_access;
                
                if($is_it_public == 0 || $is_admin == true || 
                        $accessed_internally == true)
                {

                    $resource_access_right = $this->_get_resource_access_right($current_service);
                    $payload = 
                        [
                        'id'=>$current_service->id,  
                        'service_name' =>$current_service->name,
                        'database' =>$current_service->database, 
                        'driver' => $current_service->driver,
                        'hostname' => $current_service->hostname,
                        'username' => $current_service->username,    
                        'password' => $current_service->password,   
                        'calls' =>  $current_service->calls,
                        #'public' => $current_service->public, 
                        'resource_access_right' =>$resource_access_right,    
                        'script' => $current_service->script,
                        'method' => $method,
                        'params' => $parameters, 
                    ]; 
                    //keep names of resources in the singular
                     switch ($resource)
                     {
                        case 'db':

                            $db = new Db(); 
                            $db->access_db($resource,$payload);
                            break;    

                        case 'script':

                             $script = new script;
                             $script->run_script($resource,$payload);
                             break;

                        case 'schema':
                            $db = new Db();
                            $db->create_schema($resource, $payload);
                            break;

                        case 'view':
                            return $payload;

                        default:
                            Helper::interrupt(605); 
                     }
                      
                 
                }
                else
                {
                    Helper::interrupt(624);
                }
                    
            }
                 
            /*
             *check if service exists
             * 
             * @param string $service_name name of service 
             * return array of service values 
             */
            public function service_exist($service_name)
            {
                if($current_service = serviceModel::where('name', $service_name)->
                    where('active',1)->first())
                     {
                             return $current_service;
                     }
                     else
                     {
                         Helper::interrupt(604);
                     }
            }
            
            /*
             * get parameters set in from request
             * 
             * @param string $method reuquest method type 
             * @param array $request request parameters 
             * return array of parameters
             */
            public function get_params($method, $request)
            {
                if(in_array($method,['POST','DELETE','PATCH']))
                {
                     $parameters = $request['resource'];
                     

                }
                else if($method == 'GET')  
                {
                     $parameters = Helper::query_string();

                }
                else
                {
                    Helper::interrupt(608, 'Request method '.$method.
                            ' is not supported');        
                }
                return $parameters;
            }
            
            /*
             * get and convert resource_access_right to array
             * @param object $service service payload
             * @return array resource access right
             */
            private function _get_resource_access_right($service)
            {
                 $resource_access_right = $service->resource_access_right;
                 
                 $resource_access_right = json_decode($resource_access_right, true);
                
                 return $resource_access_right;
            }
            
            /*
             * check user access right 
             * @param object $service service payload
             * @return boolean 
             */
            public function check_resource_access_right_type($access_type)
            {
                $is_user_login = Helper::is_admin_login();
                
                if( ! $is_user_login && $access_type == 0 ){Helper::interrupt(627);}//private
                else if($access_type == 1){return false;}//public
                else if($access_type == 2){return true;}//authentication required
                
                return true;
            }
            
          
                

           
        
}
