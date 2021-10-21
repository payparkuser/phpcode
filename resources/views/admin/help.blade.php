@extends('layouts.admin') 

@section('title', tr('help'))

@section('breadcrumb')

    <li class="breadcrumb-item active" aria-current="page">
    	<span>{{tr('help')}}</span>
    </li>
           
@endsection 

@section('content') 
	
	<div class="col-lg-12 grid-margin stretch-card">
        
        <div class="card">

            <div class="card-header bg-card-header ">

                <h4 class="">{{tr('help')}}</h4>

            </div>

    		<div class="card-body help">
                <p>
                    We would like to thank you for choosing Rentcubo. Kudos from our team!!
                </p>

                <p>
                    If you want to make any changes to your site, drop a mail to contact@rentcubo.com or Skype us @ contact@rentcubo.com and we will help you out!
                </p>

                <a href="https://www.facebook.com/rentcubo.tech/" target="_blank"><img class="aligncenter size-full wp-image-159 help-image" src="{{asset('helpsocial/Facebook.png')}}" alt="Facebook-100" width="100" height="100"></a>
                &nbsp;

                <a href="https://twitter.com/rentcubo" target="_blank"><img class="size-full wp-image-155 alignleft help-image" src="{{ asset('helpsocial/twitter.png')}}" alt="Twitter" width="100" height="100"></a>
                &nbsp;

                <a href="skype:contact@rentcubo.com?chat" target="_blank"> <img class="wp-image-158 alignleft help-image" src="{{ asset('helpsocial/skype.png')}}" alt="skype" width="100" height="100"></a>
                &nbsp;

                <a href="mailto:contact@rentcubo.com" target="_blank"><img class="size-full wp-image-153 alignleft help-image" src="{{asset('helpsocial/mail.png')}}" alt="Message-100" width="100" height="100"></a>

	             &nbsp;

	            <p>
                    We have this team of innate developers and dedicated team of support to sort out the things for your benefits. Tell us what you like about Rentcubo and we may suggest you the best solution for you :)
                </p>

      			<a href="https://rentcubo.com" target="_blank"><img class="aligncenter help-image size-full wp-image-160" src="{{asset('helpsocial/Money-Box-100.png')}}" alt="Money Box-100" width="100" height="100"></a>

				<p>Cheers!</p>

    		</div>

        </div>

    </div>

@endsection