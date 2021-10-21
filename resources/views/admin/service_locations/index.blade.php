@extends('layouts.admin') 

@section('title', tr('view_service_locations'))

@section('breadcrumb')

    <li class="breadcrumb-item"><a href="{{ route('admin.service_locations.index') }}">{{tr('service_locations')}}</a></li>
    
    <li class="breadcrumb-item active" aria-current="page">
        <span>{{ tr('view_service_locations') }}</span>
    </li>
    
@endsection 

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        
        <div class="card">

            <div class="card-header bg-card-header ">

                <h4 class="">{{tr('view_service_locations')}}

                    <a class="btn btn-secondary pull-right" href="{{route('admin.service_locations.create')}}">
                        <i class="fa fa-plus"></i> {{tr('add_service_location')}}
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

                        <form  action="{{route('admin.service_locations.bulk_action')}}" id="service_locations_form" method="POST" role="search">

                            @csrf

                            <input type="hidden" name="action_name" id="action" value="">

                            <input type="hidden" name="selected_service_locations" id="selected_ids" value="">

                            <input type="hidden" name="page_id" id="page_id" value="{{ (request()->page) ? request()->page : '1' }}">

                        </form>
                        
                    </div>

                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <form class="col-6 row pull-right" action="{{route('admin.service_locations.index')}}" method="GET" role="search">
                    
                        <div class="input-group">
                            <input type="text" class="form-control" name="search_key" value="{{Request::get('search_key')??''}}"
                                placeholder="{{tr('service_location_search_placeholder')}}" required> <span class="input-group-btn">
                                &nbsp
                                <button type="submit" class="btn btn-default">
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-search" aria-hidden="true"></i>
                                    </span>
                                </button>
                                <a href="{{route('admin.service_locations.index')}}" class="btn btn-default reset-btn">
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
                                <th>{{tr('s_no')}}</th>
                                <th>{{tr('name')}}</th>
                                <th>{{tr('address')}}</th>
                                <th>{{tr('picture') }}</th>
                                <th>{{tr('radius') }}</th>
                                <th>{{tr('parking_spaces')}}</th>
                                <th>{{tr('status')}}</th>
                                <th>{{tr('action')}}</th>
                            </tr>
                        </thead>

                        <tbody>   

                        @foreach($service_locations as $i => $service_location_details)

                            <tr>
                                <td><input type="checkbox" name="row_check" class="faChkRnd" id="{{$service_location_details->id}}" value="{{$service_location_details->id}}"></td>

                                <td>{{$i+$service_locations->firstItem()}}</td>
                                
                                <td class="white-space">
                                    <a href="{{route('admin.service_locations.view' , ['service_location_id' => $service_location_details->id] )}}"> 
                                        {{$service_location_details->name}}
                                    </a>
                                </td>

                                <td>{{$service_location_details->address}}</td>

                                <td>
                                    <img src="{{ $service_location_details->picture ?: asset('placeholder.jpg') }}" alt="image"> 
                                </td>
                               
                                <td>{{$service_location_details->cover_radius}} {{tr('km')}}</td>

                                <td><a href="{{route('admin.spaces.index', ['service_location_id' => $service_location_details->id])}}" >{{$service_location_details->hosts()->count()}}</a></td>

                                <td>                                    
                                    @if($service_location_details->status == APPROVED)

                                        <span class="badge badge-outline-success">
                                            {{ tr('approved') }} 
                                        </span>

                                    @else

                                        <span class="badge badge-outline-danger">
                                            {{ tr('declined') }} 
                                        </span>
                                          
                                    @endif
                                </td>

                                <td>   
                                    <div class="dropdown">

                                        <button class="btn btn-outline-primary  dropdown-toggle btn-sm" type="button" id="dropdownMenuOutlineButton1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{tr('action')}}
                                        </button>

                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuOutlineButton1">

                                            <a class="dropdown-item" href="{{ route('admin.service_locations.view', ['service_location_id' => $service_location_details->id] ) }}">
                                                {{tr('view')}}
                                            </a>

                                            @if(Setting::get('is_demo_control_enabled') == NO)
                                            
                                                <a class="dropdown-item" href="{{ route('admin.service_locations.edit', ['service_location_id' => $service_location_details->id] ) }}">
                                                    {{tr('edit')}}
                                                </a>
                                        
                                                <a class="dropdown-item" 
                                                onclick="return confirm(&quot;{{tr('service_location_delete_confirmation' , $service_location_details->name)}}&quot;);" href="{{ route('admin.service_locations.delete', ['service_location_id' => $service_location_details->id] ) }}" >
                                                    {{ tr('delete') }}
                                                </a>

                                            @else

                                                <a class="dropdown-item text-muted" href="javascript:;">{{tr('edit')}}</a>

                                                <a class="dropdown-item text-muted" href="javascript:;">{{ tr('delete') }}</a>

                                            @endif
                                           
                                            <div class="dropdown-divider"></div>

                                            @if($service_location_details->status == APPROVED)

                                                <a class="dropdown-item" href="{{ route('admin.service_locations.status', ['service_location_id' =>  $service_location_details->id] ) }}" 
                                                onclick="return confirm(&quot;{{$service_location_details->name}} - {{tr('service_location_decline_confirmation')}}&quot;);"> 
                                                    {{tr('decline')}}
                                                </a>

                                            @else

                                                <a class="dropdown-item" href="{{ route('admin.service_locations.status', ['service_location_id' =>  $service_location_details->id] ) }}">
                                                    {{tr('approve')}}
                                                </a>
                                                   
                                            @endif

                                            <div class="dropdown-divider"></div>

                                            <a class="dropdown-item" href="{{route('admin.spaces.index', ['service_location_id' => $service_location_details->id])}}">{{tr('parking_spaces')}}</a>

                                            <a class="dropdown-item"  href="{{route('admin.bookings.index', ['service_location_id' => $service_location_details->id])}}">{{tr('bookings')}}</a>

                                        </div>
                                         
                                    </div>
                               
                                </td>
                                   
                            </tr>
                        
                        @endforeach

                        </tbody>

                    </table>  

                    <div class="pull-right">{{$service_locations->appends(request()->query())->links()}}</div>                  
                  
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
                        var message = "{{ tr('admin_service_locations_delete_confirmation') }}";
                    }else if(selected_action == 'bulk_approve'){
                        var message = "{{ tr('admin_service_locations_approve_confirmation') }}";
                    }else if(selected_action == 'bulk_decline'){
                        var message = "{{ tr('admin_service_locations_decline_confirmation') }}";
                    }
                    var confirm_action = confirm(message);

                    if (confirm_action == true) {
                      $( "#service_locations_form" ).submit();
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

        localStorage.setItem("service_location_checked_items"+page, JSON.stringify(checked_ids));

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

            localStorage.setItem("service_location_checked_items"+page, JSON.stringify(checked_ids));
            get_values();
        } else {
            $("input:checkbox[name='row_check']").prop("checked", false);
            localStorage.removeItem("service_location_checked_items"+page);
            get_values();
        }

    });

    // Get Id values for selected Service Location
    function get_values(){
        var pageKeys = Object.keys(localStorage).filter(key => key.indexOf('service_location_checked_items') === 0);
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

@endsection