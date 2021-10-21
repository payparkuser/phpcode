@extends('layouts.admin') 

@section('title')

{{$page_title}}

@endsection

@section('breadcrumb')

    <li class="breadcrumb-item"><a href="{{ route('admin.spaces.index') }}">{{tr('parking_space')}}</a></li>
    
    <li class="breadcrumb-item active" aria-current="page">
        <span>{{$page_title}}</span>
        <!-- <span>{{ tr('view_spaces') }}</span> -->
    </li>
           
@endsection 

@section('content')

<div class="col-lg-12 grid-margin stretch-card">
    
    <div class="card">

        <div class="card-header bg-card-header ">

            <h4 class="text-uppercase"><b>{{$page_title}}</b>

                <a class="btn btn-secondary pull-right" href="{{route('admin.spaces.create')}}">
                    <i class="fa fa-plus"></i> {{tr('add_space')}}
                </a>

                <div class="admin-action template-demo pull-right">

                    <div class="dropdown">

                        <button class="btn btn-secondar dropdown-toggle" type="button" id="dropdownMenuOutlineButton1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{tr('bulk_action')}}
                        </button>

                        <div class="dropdown-menu" aria-labelledby="dropdownMenuOutlineButton1">
                                                  
                            <a class="dropdown-item action_list" href="#" id="bulk_delete">
                                {{tr('delete')}}
                            </a>
                                                   
                            <a class="dropdown-item action_list" href="#" id="bulk_approve">
                                {{ tr('approve') }} 
                            </a> 

                            <a class="dropdown-item action_list" href="#" id="bulk_decline">
                                {{ tr('decline') }} 
                            </a>  

                        </div>

                    </div>

                </div>

                <div class="bulk_action">

                    <form  action="{{route('admin.spaces.bulk_action')}}" id="spaces_form" method="POST" role="search">

                        @csrf

                        <input type="hidden" name="action_name" id="action" value="">

                        <input type="hidden" name="selected_spaces" id="selected_ids" value="">

                        <input type="hidden" name="page_id" id="page_id" value="{{ (request()->page) ? request()->page : '1' }}">

                    </form>
                        
                </div>

            </h4>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <form class="col-6 row pull-right" action="{{route('admin.spaces.index')}}" method="GET" role="search">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search_key"
                            placeholder="{{tr('space_search_placeholder')}}" required> <span class="input-group-btn">
                            &nbsp
                            <button type="submit" class="btn btn-default">
                                <span class="glyphicon glyphicon-search"> <i class="fa fa-search" aria-hidden="true"></i>
                                </span>
                            </button>
                            <a href="{{route('admin.spaces.index')}}" id="clear" class="btn btn-default reset-btn">
                                <span class="glyphicon glyphicon-search"> <i class="fa fa-eraser" aria-hidden="true"></i>
                                </span>
                              </a>
                        </span>
                    </div>
            
                </form>
            
                <table id="order-listing" class="table">
                   
                    <thead>
                        <tr>
                            <th>
                                <input id="check_all" type="checkbox">
                            </th>
                            <th>{{ tr('s_no') }}</th>
                            <th>{{ tr('space_name') }}</th>
                            <th>{{ tr('provider') }}</th>
                            <th>{{ tr('location') }}</th>
                            <th>{{ tr('status') }}</th>
                            <th>{{ tr('verify') }}</th>
                            <th>{{ tr('action') }}</th>
                        </tr>
                 
                    </thead>

                    <tbody>
                        
                    	@foreach($hosts as $h => $host_details)

	                    	<tr>
                                <td><input type="checkbox" name="row_check" class="faChkRnd" id="{{$host_details->id}}" value="{{$host_details->id}}"></td>

	                    		<td>{{$h+$hosts->firstItem()}}</td>

	                    		<td>
                                    <a href="{{ route('admin.spaces.view', ['host_id' => $host_details->id]) }}">{{$host_details->host_name ?? tr('not_available')}}</a>
	                    			<br>
                                    <br><small class="text-gray">{{ common_date($host_details->updated_at,Auth::guard('admin')->user()->timezone) }}</small>
	                    		</td>

	                    		<td>
                                    <a href="{{route('admin.providers.view', ['provider_id' => $host_details->provider_id])}}">
                                        {{$host_details->provider_name ?? tr('not_available') }}
                                    </a>
                                </td>

	                    		<td class="white-space-nowrap">
                                    <a href="{{route('admin.service_locations.view' , ['service_location_id' => $host_details->service_location_id] )}}">{{$host_details->location ?: tr('not_available')}}</a>
                                </td>

	                    		<td>

	                    			@if($host_details->admin_status == ADMIN_SPACE_APPROVED) 

                                        <span class="badge badge-outline-success">
                                        	{{ tr('ADMIN_SPACE_APPROVED') }} 
                                        </span>

                                    @else

                                        <span class="badge badge-outline-warning">
                                        	{{ tr('ADMIN_SPACE_PENDING') }} 
                                        </span>

                                    @endif

                                    <br>
                                    
                                    <br>

                                    @if($host_details->status == SPACE_OWNER_PUBLISHED) 

                                        <span class="badge badge-success">
                                        	{{ tr('SPACE_OWNER_PUBLISHED') }} 
                                        </span>

                                    @else

                                        <span class="badge badge-danger">
                                        	{{ tr('SPACE_OWNER_UNPUBLISHED') }} 
                                        </span>

                                    @endif

	                    		</td>
	                    		
                                <td>

	                    			@if($host_details->is_admin_verified == ADMIN_SPACE_VERIFIED) 

                                        <span class="badge badge-outline-success">
                                        	{{ tr('verified') }} 
                                        </span>

                                    @else

                                        <a class="badge badge-info" onclick="return confirm(&quot;{{tr('host_verify_confirmation' , $host_details->host_name)}}&quot;);" href="{{ route('admin.spaces.verification_status', ['host_id' => $host_details->id]) }}"> 
                                            {{ tr('verify') }} 
                                        </a>

                                    @endif

	                    		</td>
	                    		

	                    		<td>                                    
                                   
                                    <div class="template-demo">
                                   
                                        <div class="dropdown">
                                   
                                            <button class="btn btn-outline-primary  dropdown-toggle btm-sm" type="button" id="dropdownMenuOutlineButton1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                {{tr('action')}}
                                            </button>
                                   
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuOutlineButton1">

                                                <a class="dropdown-item" href="{{ route('admin.spaces.view', ['host_id' => $host_details->id]) }}">
                                                    {{tr('view')}}
                                                </a>
                                                  
                                                @if(Setting::get('is_demo_control_enabled') == NO)
                                                
                                                    <a class="dropdown-item" href="{{ route('admin.spaces.edit', ['host_id' => $host_details->id]) }}">
                                                        {{tr('edit')}}
                                                    </a>
                                                    
                                                    <a class="dropdown-item" onclick="return confirm(&quot;{{tr('host_delete_confirmation' , $host_details->host_name)}}&quot;);" href="{{ route('admin.spaces.delete', ['host_id' => $host_details->id]) }}">

                                                        {{ tr('delete') }}
                                                    </a>

                                                @else

                                                    <a class="dropdown-item text-muted" href="javascript:;">
                                                        {{tr('edit')}}
                                                    </a>

                                                    <a class="dropdown-item text-muted" href="javascript:;">
                                                        {{ tr('delete') }}
                                                    </a>

                                                @endif

                                                <div class="dropdown-divider"></div>
                                            
                                                @if($host_details->admin_status == APPROVED)

                                                    <a class="dropdown-item" href="{{ route('admin.spaces.status', ['host_id' => $host_details->id] ) }}" 
                                                    onclick="return confirm(&quot;{{$host_details->host_name}} - {{tr('host_decline_confirmation')}}&quot;);"> 
                                                        {{tr('decline')}}
                                                    </a>

                                                @else

                                                    <a class="dropdown-item" href="{{ route('admin.spaces.status', ['host_id' => $host_details->id] ) }}">
                                                        {{tr('approve')}}
                                                    </a>
                                                       
                                                @endif

                                                <div class="dropdown-divider"></div>

                                                <a class="dropdown-item"href="{{ route('admin.spaces.availability.create', ['host_id' => $host_details->id] ) }}">
                                                    {{tr('availability')}}
                                                </a>

                                                <a class="dropdown-item" href="{{ route('admin.spaces.gallery.index', ['host_id' => $host_details->id] ) }}">
                                                        {{tr('gallery')}}
                                                </a>

                                            </div>
                                
                                        </div>
                                
                                    </div>
                                
                                </td>	                    	

                            </tr>

                        @endforeach
                                                             
                    </tbody>

                </table>

                <div class="pull-right">{{$hosts->appends(request()->query())->links()}}</div>
            
            </div>

        </div>
    
    </div>

</div>

@endsection

@section('scripts')
    
@if(Session::has('bulk_action'))
<script type="text/javascript">
    $(document).ready(function(){
        localStorage.clear();
    });
</script>
@endif

<script type="text/javascript">

    $(document).ready(function(){
        get_values();

        // Call to Action for Delete || Approve || Decline
        $('.action_list').click(function(){
            var selected_action = $(this).attr('id');
            if(selected_action != undefined){
                $('#action').val(selected_action);
                if($("#selected_ids").val() != ""){
                    if(selected_action == 'bulk_delete'){
                        var message = "{{ tr('admin_spaces_delete_confirmation') }}";
                    }else if(selected_action == 'bulk_approve'){
                        var message = "{{ tr('admin_spaces_approve_confirmation') }}";
                    }else if(selected_action == 'bulk_decline'){
                        var message = "{{ tr('admin_spaces_decline_confirmation') }}";
                    }
                    var confirm_action = confirm(message);

                    if (confirm_action == true) {
                      $( "#spaces_form" ).submit();
                    }
                    // 
                }else{
                    alert('Please select the check box');
                }
            }
        });
    // single check
    var page = $('#page_id').val();
    $(':checkbox[name=row_check]').on('change', function() {
        var checked_ids = $(':checkbox[name=row_check]:checked').map(function() {
            return this.id;
        })
        .get();

        localStorage.setItem("space_checked_items"+page, JSON.stringify(checked_ids));

        get_values();

    });
    // select all checkbox
    $("#check_all").on("click", function () {
        if ($("input:checkbox").prop("checked")) {
            $("input:checkbox[name='row_check']").prop("checked", true);
            var checked_ids = $(':checkbox[name=row_check]:checked').map(function() {
                return this.id;
            })
            .get();

            localStorage.setItem("space_checked_items"+page, JSON.stringify(checked_ids));
            get_values();
        } else {
            $("input:checkbox[name='row_check']").prop("checked", false);
            localStorage.removeItem("space_checked_items"+page);
            get_values();
        }

    });

    // Get Id values for selected Spaces
    function get_values(){
        var pageKeys = Object.keys(localStorage).filter(key => key.indexOf('space_checked_items') === 0);
        var values = Array.prototype.concat.apply([], pageKeys.map(key => JSON.parse(localStorage[key])));

        if(values){
            $('#selected_ids').val(values);
        }

        for (var i=0; i<values.length; i++) {
            $('#' + values[i] ).prop("checked", true);
        }
    }

});
</script>
<script>
function uncheckAll() {
    localStorage.clear();
}
document.querySelector('#clear').addEventListener('click', uncheckAll)
</script>
@endsection