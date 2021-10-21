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

                            <h2>{{tr('reset_password')}}</h2>


                            @include('notifications.notify')


                            <form class="form-horizontal" role="form" method="POST" @if($is_email_configured==YES) action="{{route('admin.forgot_password.update')}}" method="POST" @else action="javascript:void(0)" @endif>
                                {{ csrf_field() }}

                                <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                                    <label for="email" class="col-md-12 control-label">{{tr('email_address')}}</label>

                                    <div class="col-md-12">
                                        <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required pattern="^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$">

                                        @if ($errors->has('email'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-md-12 col-md-offset-4">
                                        <button type="submit" class="btn btn-warning btn-pill mb-4">
                                            <i class="fa fa-btn fa-envelope"></i> {{tr('reset')}}
                                        </button>&nbsp;&nbsp;
                                        <a href="{{route('admin.login')}}" class="btn btn-warning btn-pill mb-4">
                                            {{tr('login')}}
                                        </a>
                                    </div>
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