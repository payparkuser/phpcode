@extends('layouts.admin.focused')

@section('title', tr('login'))

@section('content')
	
	<div class="container-scroller">
	    <div class="container-fluid page-body-wrapper">
	        <div class="row">
	            <div class="content-wrapper full-page-wrapper d-flex align-items-center auth login-full-bg" style="background: #3a3f51 !important">
	                <div class="row w-100">
	                    <div class="col-lg-4 mx-auto">
	                        <div class="auth-form-dark text-left p-5">
	                            
	                            <h2>{{tr('login')}}</h2>

	                            <h4 class="font-weight-light">{{ tr('hello_text') }}</h4>

	                            @include('notifications.notify')

	                            <form class="pt-2" action="{{ route('admin.login.post') }}" method="POST">

	                            	@csrf


	                            	<input type="hidden" name="timezone" value="" id="userTimezone">

	                                <div class="form-group">

	                                    <label for="email">{{ tr('email') }}</label>

	                                    <input type="email" name="email" class="form-control" id="email" placeholder="{{ tr('email') }}" value="{{old('email') ?: Setting::get('demo_admin_email')}}" autocomplete="off">

	                                    <i class="mdi mdi-account"></i>

	                                    @if ($errors->has('email'))
										    <div class="text-danger">{{ $errors->first('email') }}</div>
										@endif

	                                </div>
	                                
	                                <div class="form-group">

	                                    <label for="password">{{ tr('password') }}</label>

	                                    <input type="password" name="password" class="form-control" id="password" placeholder="{{ tr('password') }}" value="{{old('password') ?: Setting::get('demo_admin_password')}}">

	                                    <i class="mdi mdi-eye"></i>

	                                    @if ($errors->has('password'))
										    <div class="text-danger">{{ $errors->first('password') }}</div>
										@endif

	                                </div>

	                                <div class="mt-5">	                
	                                    <button style="background: green;color: white;" type="submit" class="btn btn-block btn-success btn-lg font-weight-medium" >{{ tr('login')}}</button></br>
										
										@if(!Setting::get('is_demo_control_enabled'))
										<a href="{{route('admin.password.request')}}" ><i class="ft-unlock forgot-btn-css" ></i > {{tr('forgot_password')}} ?</a>
                                        @endif
	                                </div> 

	                            </form>
	                        </div>
	                    </div>
	                </div>
	            </div>
	            <!-- content-wrapper ends -->
	        </div>
	        <!-- row ends -->
	    </div>
	    <!-- page-body-wrapper ends -->
	</div>

@endsection

@section('scripts')

<script src="{{asset('admin-assets/js/jstz.min.js')}}"></script>

<script>
        $(document).ready(function() {

        var dMin = new Date().getTimezoneOffset();
        var dtz = -(dMin/60);
        // alert(dtz);
        $("#userTimezone").val(jstz.determine().name());
    });

</script>

@endsection
