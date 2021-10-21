@extends('layouts.admin') 

@section('title', tr('add_vehicle_details'))

@section('breadcrumb')

    <li class="breadcrumb-item active" aria-current="page">
    	<span>{{tr('add_vehicle_details')}}</span>
    </li>
           
@endsection 

@section('content')

<div class="col-lg-12 grid-margin stretch-card">

    <div class="row flex-grow">

        <div class="col-12 grid-margin">

            <div class="card">

            	<div class="card-header bg-card-header">

            		<h4>

            			View Host details

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
							                <img src="{{$provider_details->picture}}" class="img-lg rounded" alt="image">
							                <div class="ml-lg-3">
							                    <h6>{{$provider_details->name}}</h6>
							                    <p class="text-muted">{{$provider_details->mobile}}</p>

							                    <a href="{{route('admin.providers.view', [])}}" class="mt-2 text-success font-weight-bold">View Provider</a>
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
								            	@if($host->admin_status == ADMIN_HOST_APPROVED)

		                                        	<span class="badge badge-outline-success text-uppercase">{{ tr('ADMIN_HOST_APPROVED') }}</span> 

		                                        @else

		                                        	<span class="badge badge-outline-warning text-uppercase">{{ tr('ADMIN_HOST_PENDING') }} </span>

		                                        @endif
								            </td>
								        </tr>

								        <tr>
								            <td><b>{{tr('host_owner_status')}}</b></td>
								            <td class="text-uppercase">
								            	@if($host->status == HOST_OWNER_PUBLISHED)

					                                <span class="badge badge-success badge-md text-uppercase">{{ tr('HOST_OWNER_PUBLISHED') }}</span> 

		                                        @else

		                                        	<span class="badge badge-danger badge-md text-uppercase">{{ tr('HOST_OWNER_UNPUBLISHED') }}</span>

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
            			
            			</div>
            			
            			<!-- right side end -->

            		</div>
            		
            	</div>
            
            </div>

        </div>
	</div>
</div>


@endsection