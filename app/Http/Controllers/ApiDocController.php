<?php namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Service;
use App\Http\Controllers\Controller;
use \App\Helpers\DevlessHelper as DLH;

class ApiDocController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$services = Service::all();
		return view('api_docs.index', compact('services'));
	}

	/**
	 *Get schema to generate request payload
	 *
	 * @return Response
	 */
	public function schema($table_name)
	{
			$schema_data = \DB::table('table_metas')->where('table_name', $table_name)->first();
			$schema = json_decode($schema_data->schema);
			return $schema->field;
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function script($service_name)
	{
		$script = \DB::table('services')->where('name', $service_name)->value('script');
		return $script;
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		$tables = \DB::table('table_metas')->where('service_id', $id)->get();
		return $tables;
	}

}
