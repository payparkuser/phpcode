@if(Session::has('flash_error'))

    <div class="col-sm-12 col-xs-12 col-md-12 alert alert-danger text-capitalize">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{Session::get('flash_error')}}
    </div>
@endif


@if(Session::has('flash_success'))
    <div class="col-sm-12 col-xs-12 col-md-12 alert alert-success text-capitalize" >
        <button type="button" class="close" data-dismiss="alert">×</button>
        {!! Session::get('flash_success') !!}
    </div>
@endif

@if(Session::has('flash_warning'))
    <div class="col-sm-12 col-xs-12 col-md-12 alert alert-warning text-capitalize" >
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{Session::get('flash_warning')}}
    </div>
@endif

