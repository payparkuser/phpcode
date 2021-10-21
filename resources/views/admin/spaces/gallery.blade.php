@extends('layouts.admin') 

@section('title', tr('view_spaces'))

@section('breadcrumb')

    <li class="breadcrumb-item"><a href="{{route('admin.spaces.index')}}">{{tr('parking_space')}}</a></li>

    <li class="breadcrumb-item">
        <a href="{{route('admin.spaces.view' , ['host_id' => $host_details->id])}}">{{tr('view_spaces')}}</a>
    </li>

    <li class="breadcrumb-item active" aria-current="page">
        <span>{{tr('gallery')}}</span>
    </li>
           
@endsection


@section('styles')

    <!-- form upload images dropify css-->
  	<link rel="stylesheet" href="{{asset('admin-assets/node_modules/dropify/dist/css/dropify.min.css')}}">
  	
    <!-- form upload images dropify css-->

@endsection


@section('content')
	
	<div class="row user-profile">
            
		<div class="col-lg-12 side-right stretch-card">
			
			<div class="card">
			 	
			 	<div class="card-header bg-card-header ">

		            <h4 class="text-uppercase"><b>{{tr('gallery')}}

		            @if($host_details->host_name) - <a class="text-white" href="{{route('admin.spaces.view', ['host_id' => $host_details->id])}}">{{ $host_details->host_name }}</a> </b> @endif
		            </h4>

        		</div>
				
				<div class="card-body">
					
					<div class="row">
							
						<div class="col-md-6">
								
							<div class="card">

								<form class="forms-sample" id="example-form" action="{{route('admin.spaces.gallery.save')}}" method="POST" enctype="multipart/form-data" role="form">

                					@csrf
						            
						            <div class = "margin">
					            	    <input type="hidden" name="host_id" value="{{$host_details->id}}">
						            	<h4>{{tr('add_images')}}</h4>
								  		<input type="file" class="dropify"  required name="pictures[]" multiple />
									</div>
									
									<div class="card-footer">

									    @if(Setting::get('is_demo_control_enabled') == NO )

									        <button type="submit" class="btn btn-success mr-2">{{ tr('submit') }} </button>

									    @else

									        <button type="button" class="btn btn-success mr-2" disabled>{{ tr('submit') }}</button>
									        
									    @endif

									</div>

								</form>

							</div>

						</div>

					</div>

					<div class="row grid-margin">

						@foreach($hosts_galleries as $key => $gallery)

							<div class="col-sm-3">
								
								<img src="{{ $gallery->picture }}" alt="" style="width: 200px; height: 200px;">
								<br>										
								<a class="btn btn-outline-primary" style="margin : 10px;" href="{{ route('admin.spaces.gallery.delete', ['gallery_id' => $gallery->id]) }}" class="btn btn-primary" onclick="return confirm(&quot;{{tr('gallery_delete_confirmation')}}&quot;);" title="{{ tr('delete')}}" >
								<i class="fa fa-trash-o"></i>
								</a>

							</div>
						
						@endforeach

					</div>
				
				</div>
            
            </div>

		</div>

	</div>

@endsection

@section('scripts')
	
	<script src="{{asset('admin-assets/node_modules/dropify/dist/js/dropify.min.js')}}"></script>

	<script src="{{asset('admin-assets/js/dropify.js')}}"></script>

@endsection