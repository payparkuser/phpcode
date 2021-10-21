@extends('layouts.admin') 

@section('title', tr('view_space'))

@section('breadcrumb')

    <li class="breadcrumb-item"><a href="{{route('admin.spaces.index')}}">{{tr('parking_space')}}</a></li>

    <li class="breadcrumb-item active" aria-current="page">
        <span>{{tr('view_space')}}</span>
    </li>
           
@endsection

@section('content')
	
	<div class="col-lg-12 grid-margin stretch-card">

    	<div class="row flex-grow">

	        <div class="col-12 grid-margin">

	            <div class="card">

	            	<div class="card-header bg-card-header">

	            		<h4>

            			{{tr('view_space_details')}}

	            		<div class="pull-right">

	            			@if(Setting::get('is_demo_control_enabled') == NO)

	                            <a href="{{ route('admin.spaces.edit', ['host_id' => $host->id] ) }}" class="btn btn-dark btn-fw"><i class="mdi mdi-border-color"></i>{{tr('edit')}}</a>

	                            <a onclick="return confirm(&quot;{{tr('sub_category_delete_confirmation' , $host->name)}}&quot;);" href="{{ route('admin.spaces.delete', ['host_id' => $host->id] ) }}"  class="btn btn-danger btn-fw"><i class="mdi mdi-delete"></i>{{tr('delete')}}</a>

	                        @else
	                            <a href="javascript:;" class="btn btn-dark btn-fw"><i class="mdi mdi-border-color"></i>{{tr('edit')}}</a>
	                            
	                            <a href="javascript:;" class="btn btn-danger btn-fw"><i class="mdi mdi-delete"></i>{{tr('delete')}}</a>

	                        @endif

	            			
	            		</div>

	            		</h4>
	            	</div>

	            	<div class="card-body">

	            		<div class="row">

	            			<div class="col-12">
	            				<div class="text-muted text-uppercase">{{tr('space_name')}}</div>
	            			</div>
	            			<div class="col-12"><p>{{$host->host_name}}</p></div>

	            		</div>

	            		<div class="row">

	            			<!-- left side start -->

	            			<div class="col-6">

	            				<div class="mb-3">

		            				<img src="{{$host->picture}}" class="img img-responsive img-thumbnail" style="height: 350px;">

		            			</div>

	            				<div class="">

		            				@if($host->admin_status == APPROVED)

			                            <a class="btn btn-danger btn-fw" href="{{ route('admin.spaces.status', ['host_id' => $host->id] ) }}" onclick="return confirm(&quot;{{$host->host_name}} - {{tr('host_decline_confirmation')}}&quot;);"> <i class="mdi mdi-loop"></i>
			                                {{tr('decline')}}
			                            </a>

			                        @else

			                            <a class="btn btn-success" href="{{ route('admin.spaces.status', ['host_id' => $host->id] ) }}"><i class="mdi mdi-loop"></i>
			                                {{tr('approve')}}
			                            </a>
			                                                   
			                        @endif

			                        @if($host->is_admin_verified != ADMIN_SPACE_VERIFIED)
	                                
	                                	<a class="btn btn-outline-success btn-fw" href="{{ route('admin.spaces.verification_status', ['host_id' => $host->id]) }}">{{tr('verify')}} </a>

	                                @endif

									<a href="{{ route('admin.spaces.availability.create', ['host_id' => $host->id] ) }}" class="btn btn-success">
										<i class="mdi mdi-message-text"></i>
										{{tr('availability')}}
			                    		
			                    	</a>

		                    		<a class="btn btn-primary" href="{{ route('admin.spaces.gallery.index', ['host_id' => $host->id] ) }}">
	                                   {{tr('gallery')}}
	                                </a>

			                    </div>

			  					<br>

			  					<div class="text-uppercase mb-3">
	            					<h5>{{tr('address')}}</h5>
	            				</div>

				              	<div class="grid-margin stretch-card">
					                <div class="map-container">
					                    <div id="map-with-marker" class="google-map"></div>
					                </div>
				              	</div>

								<div class="grid-margin stretch-card">
									<div class="card card-shadow-remove">
					                    <div class="card-body">
					                     
					                    	<div class="wrapper about-user">
						                      	<i class="fa fa-location-arrow"></i>
						                        <span>{{ $host->street_details.', '. $host->city.','.$host->state.','.$host->zipcode}} </span>
					                      	</div>

					                      	<div class="info-links">
					                        
				                          		<i class="mdi mdi-earth"></i>
				                          		<span>{{ $host->full_address }} </span>
				                        
					                      	</div>
					                    </div>
					                </div>

	                  			</div>

	                  			<div class="text-uppercase mb-3">
	            					<h5>{{tr('description')}}</h5>
	            				</div>

								<div>
									<div class="card card-shadow-remove">
					                    <div class="card-body">
											<p class="card-text">{{ $host->description }}</p>
										</div>
									</div>
								</div>
	            				 
	            			</div>

	            			<!-- left side end -->

	            			<!-- right side start -->

	            			<div class="col-6">
	            				
	            				<div class="text-uppercase mb-3">
	            					<h5>{{tr('provider_details')}}</h5>
	            				</div>

	            				<div class="grid-margin stretch-card">
								    <div class="card card-shadow-remove">
								        <div class="card-body">
								            <div class="d-lg-flex flex-row text-center text-lg-left">	
								                <img src="{{$host->provider_image}}" class="img-lg rounded" alt="image">
								                <div class="ml-lg-3">
								                    <h6>{{$host->provider_name}}</h6>
								                    <p class="text-muted">{{$host->providerDetails->mobile ?? ''}}</p>

								                    <a href="{{route('admin.providers.view', ['provider_id' => $host->provider_id])}}" class="mt-2 text-success font-weight-bold">{{tr('view_provider')}}</a>
								                </div>
								            </div>
								        </div>
								    </div>
								
								</div>

								<div class="text-uppercase">
	            					<h5>{{tr('space_details')}}</h5>
	            				</div>

	            				<div class="grid-margin stretch-card">

						        	<table class="table table-bordered custom-table">
									    <tbody>
									        <tr>
									            <td><b>{{tr('host_type')}}</b></td>
									            <td class="text-uppercase">{{$host->host_type}}</td>
									        </tr>

									        <tr>
									            <td><b>{{tr('space_owner_type')}}</b></td>
									            <td class="text-uppercase">{{$host->host_owner_type}}</td>
									        </tr>

									        <tr>
									            <td><b>{{tr('available_space')}}</b></td>
									            <td class="text-uppercase">{{$host->total_spaces}}</td>
									        </tr>

									        <tr>
									            <td><b>{{tr('service_location')}}</b></td>
									            <td class=""><a href="{{route('admin.service_locations.view' , ['service_location_id' => $host->service_location_id] )}}">{{$host->location_name ?? '-' }}</a></td>
									        </tr>

									        <tr>
									            <td><b>{{tr('dimension')}}</b></td>
									            <td class="text-uppercase">{{$host->dimension}}</td>
									        </tr>

									        <tr>
			                                    <td><b>{{ tr('access_method')}} </b> </td>
			                                    <td class="text-uppercase">
			                                        <span class="text-info">{{($host->access_method == ACCESS_METHOD_SECRET_CODE) ? 'Secret Code' : 'Key'}}</span>
			                                    </td>
			                                </tr>

									        <tr>
									            <td><b>{{tr('instant_booking_is')}}</b></td>
									            <td class="text-uppercase">
									            	@if($host->is_automatic_booking  == YES)
		                                        		<span class="text-success">{{tr('on')}}</span> 
		                                        	@else
		                                        		<span class="text-success">{{tr('off')}}</span> 
		                                        	@endif
									            </td>
									        </tr>

									        <tr>
									            <td><b>{{tr('host_admin_status')}}</b></td>
									            <td class="text-uppercase">
									            	@if($host->admin_status == ADMIN_SPACE_APPROVED)

			                                        	<span class="badge badge-outline-success text-uppercase">{{ tr('ADMIN_SPACE_APPROVED') }}</span> 

			                                        @else

			                                        	<span class="badge badge-outline-warning text-uppercase">{{ tr('ADMIN_SPACE_PENDING') }} </span>

			                                        @endif
									            </td>
									        </tr>

									        <tr>
									            <td><b>{{tr('host_owner_status')}}</b></td>
									            <td class="text-uppercase">
									            	@if($host->status == SPACE_OWNER_PUBLISHED)

						                                <span class="badge badge-success badge-md text-uppercase">{{ tr('SPACE_OWNER_PUBLISHED') }}</span> 

			                                        @else

			                                        	<span class="badge badge-danger badge-md text-uppercase">{{ tr('SPACE_OWNER_UNPUBLISHED') }}</span>

			                                        @endif
									            </td>
									        </tr>

									        <tr>
									            <td><b>{{tr('verified_status')}}</b></td>
									            <td class="text-uppercase">
									            	@if($host->is_admin_verified == ADMIN_SPACE_VERIFIED)

						                                <span class="badge badge-success badge-md text-uppercase">{{tr('verified')}}</span> 
						                            @else
						                                        
						                                <a class="badge badge-info" href="{{route('admin.spaces.verification_status', ['host_id' => $host->id])}}">{{ tr('verify') }} </a>

													@endif
									            </td>
									        </tr>
									    </tbody>
									</table>
								        
								</div>

	            				<div class="text-uppercase">
	            					<h5>{{tr('pricing_details')}}</h5>
	            				</div>

	            				<div class="grid-margin stretch-card">

						        	<table class="table table-bordered custom-table">
									    <tbody>
									        <tr>
									            <td><b>{{tr('per_hour')}}</b></td>
									            <td class="text-uppercase">{{formatted_amount($host->per_hour)}}</td>
									        </tr>

									        <tr>
									            <td><b>{{tr('per_day')}}</b></td>
									            <td class="text-uppercase">{{formatted_amount($host->per_day)}}</td>
									        </tr>

									        <tr>
									            <td><b>{{tr('per_month')}}</b></td>
									            <td class="text-uppercase">{{formatted_amount($host->per_month)}}</td>
									        </tr>
									    </tbody>
									</table>
								        
								</div>

								<div class="text-uppercase mb-3">
	            					<h5>{{tr('access_note')}}</h5>
	            				</div>

								<div class="card card-shadow-remove mb-3">
				                    <div class="card-body">
										<p class="card-text">{{ $host->access_note }}</p>
									</div>
								</div>

								
								<div class="text-uppercase mb-3">
	            					<h5>{{tr('amenities')}}</h5>
	            				</div>
	            				
								<div class="card card-shadow-remove">
					                <div class="card-body">
					                  	<!-- <h4 class="card-title">{{tr('amenities')}}</h4> -->
					                  		<ul class="list-arrow">
						                  	@foreach($amenities as $amenitie_details)
							                    <li>{{$amenitie_details->value}}</li>
							                @endforeach
					                  	</ul>
					                </div>
					            </div>
					            
	            			
	            			</div>
	            			
	            			<!-- right side end -->

	            		</div>
	            		
	            	</div>
	            
	            </div>

	        </div>
		</div>
	</div>

@endsection

@section('scripts')
	
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{Setting::get('google_api_key')}}&amp;callback=initMap"></script>

<script type="text/javascript">
	
function initMap() {
  //Map location
  var MapLocation = {
    lat: {{ $host->latitude }},
    lng: {{ $host->longitude }}
  };

  // Map Zooming
  var MapZoom = 6;

  // Basic Map
  var MapWithMarker = new google.maps.Map(document.getElementById('map-with-marker'), {
    zoom: MapZoom,
    center: MapLocation
  });

  var marker_1 = new google.maps.Marker({
    position: MapLocation,
    map: MapWithMarker
  });

}

</script>
@endsection