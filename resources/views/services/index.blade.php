@extends('layout')

@section('header')
<!-- page head start-->
<div class="page-head">
    <h3>Services</h3><span class="sub-title">Service/</span>
</div>
<!-- page head end-->

@endsection

@section('content')

<!--body wrapper start-->
<div class="wrapper">
    <div class="row">
         <div class="col-lg-3 col-sm-6 m-b-30">
                <div class="panel panel-danger">
                    <div class="panel-header"><br><h4><center>Create Service</center></h4></div>
                    <div class="panel-body">
                        <a href="{{ route('services.create') }}">
                        <center><i class="fa fa-plus fa-5x" aria-hidden="true"></i></center>
                        </a>
                        <br>


                    </div>
                </div>

            </div>
        @if($services->count())
             @foreach($services as $service)
             <a href="{{ route('services.edit', $service->id) }}">
            <div class="col-lg-3 col-sm-6 m-b-30">
                <div class="panel panel-danger">
                    <div class="panel-header"><br><h4><center>{{substr(strtoupper($service->name),0,10)}}</center></h4></div>
                    <div class="panel-body" >
                        <center><span title="{{$service->description}}"</span>{{substr($service->description, 0, 20)}}@if(strlen($service->description)>20)...@endif</center>
                        <br><br>
                        <center>
                            
                        <div class="btn-group" role="group">
                            <a type="button" href="{{ route('services.edit', $service->id) }}" class="btn btn-default">Edit</a>
                            <a type="button" href="/service/{{$service->name}}/view/index/" target="_blank" class="btn btn-default">Console</a>
                             <form action="{{ route('services.destroy', $service->id) }}" method="POST" style="display: inline;" onsubmit="if(confirm('Delete? Are you sure?')) { return true } else {return false };">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <button type="submit" class="btn btn-default">Delete</button>
                                    </form>

                        </div>
                        </center>

                    </div>
                </div>

            </div>
             </a>
            @endforeach
        {!! $services->render() !!}
            @endif
    </div>
</div><!--body wrapper end-->


@endsection
