@extends('layouts.admin') 

@section('title', tr('view_amenities'))

@section('breadcrumb')

    <li class="breadcrumb-item"><a href="{{ route('admin.amenities.index') }}">{{tr('amenities')}}</a></li>

    <li class="breadcrumb-item active" aria-current="page">
        <span>{{ tr('view_amenities') }}</span>
    </li>
           
@endsection 

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        
        <div class="card">

            <div class="card-header bg-card-header ">

                <h4 class="">{{tr('view_amenities')}}

                    <a class="btn btn-secondary pull-right" href="{{route('admin.amenities.create')}}">
                        <i class="fa fa-plus"></i> {{tr('add_amenity')}}
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

                        <form  action="{{route('admin.amenities.bulk_action')}}" id="amenities_form" method="POST" role="search">

                            @csrf

                            <input type="hidden" name="action_name" id="action" value="">

                            <input type="hidden" name="selected_amenities" id="selected_ids" value="">

                            <input type="hidden" name="page_id" id="page_id" value="{{ (request()->page) ? request()->page : '1' }}">

                        </form>
                        
                    </div>

                </h4>

            </div>

            <div class="card-body">

            <div class="table-responsive">

                <form class="col-6 row pull-right" action="{{route('admin.amenities.index')}}" method="GET" role="search">

                    <div class="input-group">
                        <input type="text" class="form-control" name="search_key" value="{{Request::get('search_key')??''}}"
                            placeholder="{{tr('amenities_search_placeholder')}}" required> <span class="input-group-btn">
                            &nbsp
                            <button type="submit" class="btn btn-default">
                                <span class="glyphicon glyphicon-search"> <i class="fa fa-search" aria-hidden="true"></i>
                                </span>
                            </button>
                            <a href="{{route('admin.amenities.index')}}" class="btn btn-default reset-btn">
                                <span class="glyphicon glyphicon-search"> <i class="fa fa-eraser" aria-hidden="true"></i>
                                </span>
                        </a>
                        </span>
                    </div>

                </form>

                <div class="table-responsive">
                    
                    <table id="order-listing" class="table">
                       
                        <thead>
                            <tr>
                                <th>
                                    <input id="check_all" type="checkbox">
                                </th>
                                <th>{{tr('s_no')}}</th>
                                <th>{{tr('picture') }}</th>
                                <th>{{tr('space_type')}}</th>
                                <th>{{tr('name')}}</th>
                                <th>{{tr('status')}}</th>
                                <th>{{tr('action')}}</th>
                            </tr>
                        </thead>

                        <tbody>
                         
                            @foreach($amenities as $i => $amenity_details)

                                <tr>
                                    <td><input type="checkbox" name="row_check" class="faChkRnd" id="{{$amenity_details->id}}" value="{{$amenity_details->id}}"></td>

                                    <td>{{$i+$amenities->firstItem()}}</td>
                                    
                                    <td>
                                        <img src="{{ $amenity_details->picture ?: asset('placeholder.jpg') }}" alt="image"> 
                                    </td>

                                    <td class="text-capitalize">
                                        <a href="{{route('admin.amenities.view' , ['amenity_id' => $amenity_details->id] )}}"> {{$amenity_details->type}}
                                        </a>
                                    </td>

                                    <td>
                                        {{$amenity_details->value}}
                                    </td>

                                    <td>                                    
                                        @if($amenity_details->status == APPROVED)

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

                                                <a class="dropdown-item" href="{{ route('admin.amenities.view', ['amenity_id' => $amenity_details->id] ) }}">{{tr('view')}}
                                                    </a>

                                                @if(Setting::get('is_demo_control_enabled') == NO)
                                                
                                                    <a class="dropdown-item" href="{{ route('admin.amenities.edit', ['amenity_id' => $amenity_details->id] ) }}">{{tr('edit')}}
                                                    </a>

                                                    <a class="dropdown-item" 
                                                    onclick="return confirm(&quot;{{tr('amenity_delete_confirmation' , $amenity_details->value)}}&quot;);" href="{{ route('admin.amenities.delete', ['amenity_id' => $amenity_details->id] ) }}" >
                                                        {{ tr('delete') }}
                                                    </a>
                                                    
                                                @else

                                                    <a class="dropdown-item text-muted" href="javascript:;">{{tr('edit')}}
                                                    </a>

                                                    <a class="dropdown-item text-muted" href="javascript:;">{{ tr('delete') }}
                                                    </a>

                                                @endif

                                                <div class="dropdown-divider"></div>

                                                @if($amenity_details->status == APPROVED)

                                                    <a class="dropdown-item" href="{{ route('admin.amenities.status', ['amenity_id' => $amenity_details->id] ) }}" 
                                                    onclick="return confirm(&quot;{{$amenity_details->value}} - {{tr('amenity_decline_confirmation')}}&quot;);"> 
                                                        {{tr('decline')}}
                                                    </a>

                                                @else

                                                    <a class="dropdown-item" href="{{ route('admin.amenities.status', ['amenity_id' => $amenity_details->id] ) }}">
                                                        {{tr('approve')}}
                                                    </a>
                                                       
                                                @endif
                                                
                                            </div>
                                             
                                        </div>
                                        
                                    </td>

                                </tr>

                            @endforeach
                                                                 
                        </tbody>
                    
                    </table>  

                    <div class="pull-right">{{$amenities->appends(request()->input())->links()}}</div>              
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
                        var message = "{{ tr('admin_amenities_delete_confirmation') }}";
                    }else if(selected_action == 'bulk_approve'){
                        var message = "{{ tr('admin_amenities_approve_confirmation') }}";
                    }else if(selected_action == 'bulk_decline'){
                        var message = "{{ tr('admin_amenities_decline_confirmation') }}";
                    }
                    var confirm_action = confirm(message);

                    if (confirm_action == true) {
                      $( "#amenities_form" ).submit();
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

        localStorage.setItem("amenity_checked_items"+page, JSON.stringify(checked_ids));

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

            localStorage.setItem("amenity_checked_items"+page, JSON.stringify(checked_ids));
            get_values();
        } else {
            $("input:checkbox[name='row_check']").prop("checked", false);
            localStorage.removeItem("amenity_checked_items"+page);
            get_values();
        }

    });

    // Get Id values for selected Amenities
    function get_values(){
        var pageKeys = Object.keys(localStorage).filter(key => key.indexOf('amenity_checked_items') === 0);
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