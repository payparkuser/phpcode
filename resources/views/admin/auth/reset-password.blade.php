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


                            <form class="form-horizontal" role="form" method="POST" action="{{route('admin.reset_password.update')}}">
                                    {{ csrf_field() }}


                                    @if(Request::get('token'))
                                    <input type="hidden" id="reset_token" name="reset_token" value="{{Request::get('token') ?? ''}}">
                                    @endif
                                    
                                    <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                                        <label for="password" class="col-md-6 control-label">{{tr('password')}}</label>

                                        <div class="form-group col-md-12 mb-4">
                                            <input id="password" type="password" class="form-control" name="password" required>

                                            @if ($errors->has('password'))
                                            <span class="help-block">
                                                <strong>{{ $errors->first('password') }}</strong>
                                            </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="form-group{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
                                        <label for="password-confirm" class="col-md-6 control-label">{{tr('confirm_password')}}</label>
                                        <div class="form-group col-md-12 mb-4">
                                            <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required>

                                            @if ($errors->has('password_confirmation'))
                                            <span class="help-block">
                                                <strong>{{ $errors->first('password_confirmation') }}</strong>
                                            </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="col-md-12 col-md-offset-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-btn fa-refresh"></i> {{tr('reset_password')}}
                                            </button>
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