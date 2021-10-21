@extends('layouts.admin') 

@section('title', tr('reviews'))

@section('breadcrumb')

    <li class="breadcrumb-item" aria-current="page">
    	<a href="javascript:void(0)">{{tr('reviews')}}</a>
    </li>

    @if($sub_page == 'reviews-user')
    <li class="breadcrumb-item active">{{tr('user_reviews')}}</li>
    @else
    <li class="breadcrumb-item active">{{tr('provider_reviews')}}</li>
    @endif
         
@endsection 

@section('styles')

<link rel="stylesheet" type="text/css" href="{{asset('admin-assets/css/star-rating-svg.css')}}">

@endsection

@section('content') 

<div class="col-lg-12 grid-margin stretch-card">
        
    <div class="card">

        <div class="card-header bg-card-header ">
        
        @if($sub_page == 'reviews-user')
            <h4 class="">{{ tr('user_reviews') }}</h4>
        @else
            <h4 class="">{{ tr('provider_reviews') }}</h4>
        @endif
        
        </div>

        <div class="card-body">

            <div class="table-responsive">


            <form class="col-6 row pull-right" action="{{ $sub_page == 'reviews-user' ? route('admin.reviews.users') : route('admin.reviews.providers')}}" method="GET" role="search">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search_key" value="{{Request::get('search_key')??''}}"
                                placeholder="{{tr('user_reviews_search_placeholder')}}" required> <span class="input-group-btn">
                                &nbsp
                                <button type="submit" class="btn btn-default">
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-search" aria-hidden="true"></i>
                                    </span>
                                </button>
                                  <a href="{{ $sub_page == 'reviews-user' ? route('admin.reviews.users') : route('admin.reviews.providers')}}" type="reset" class="btn btn-default reset-btn" >
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-eraser" aria-hidden="true"></i>
                                    </span>
                                  </a>
                            </span>
                        </div>
                
                    </form>



                <table id="order-listing" class="table">

                    <thead>
                        <th>{{ tr('s_no') }}</th>
                        <th>{{ tr('bookings') }}</th>
                        <th>{{ tr('user') }}</th>
                        <th>{{ tr('provider') }}</th>
                        <th>{{ tr('date') }}</th>
                        <th>{{ tr('rating') }}</th>
                        <th>{{ tr('comment') }}</th>
                        <th>{{ tr('action') }}</th>
                    </thead>

                    <tbody>
                     
                        @foreach($reviews as $i => $review_details)

                            <tr>
                                <td>{{ $i+$reviews->firstItem() }}</td>

                                <td>

                                    <a href="{{ route('admin.bookings.view', ['booking_id' => $review_details->bookingDetails->id ?? '0' ] ) }}">
                                        {{ $review_details->bookingDetails->unique_id ?? tr('user_not_avail') }}
                                    </a>
                                </td>
                               
                                <td>

                                    <a href="{{ route('admin.users.view', ['user_id' => $review_details->userDetails->id ?? '0' ] ) }}">
                                        {{ $review_details->userDetails->name ?? tr('user_not_avail') }}
                                    </a>
                                </td>

                                <td>

                                    <a href="{{route('admin.providers.view', ['provider_id' => $review_details->providerDetails->id ?? '0' ])}}">

                                        {{$review_details->providerDetails->name ?? tr('provider_not_avail')}}
                                    </a>
                                </td>
                                
                                <td>
                                    {{ common_date($review_details->created_at,Auth::guard('admin')->user()->timezone) }}
                                </td>

                                <td>
                                    <div class="my-rating-{{$i}}"></div>
                                </td> 
                                
                                <td>{{ substr($review_details->review, 0, 50) }}...</td>

                                <td>
                                
                                    @if($sub_page == 'reviews-user')
                                   
                                        <a class="btn btn-outline-primary" href="{{ route('admin.reviews.users.view', ['booking_review_id' => $review_details->id])}}">{{tr('view')}}</a> 
                                   
                                    @else
                                       
                                        <a class="btn btn-outline-primary" href="{{ route('admin.reviews.providers.view', ['booking_review_id' => $review_details->id])}}">{{tr('view')}}</a> 

                                    @endif                                        
                                       
                                </td>
  
                            </tr>

                        @endforeach
                                                             
                    </tbody>
                
                </table>

                <div class="pull-right">{{$reviews->appends(request()->input())->links()}}</div>

            </div>

        </div>

    </div>

</div>

@endsection

@section('scripts')

     <script type="text/javascript" src="{{asset('admin-assets/js/jquery.star-rating-svg.min.js')}}"> </script>

    <script>
        <?php foreach ($reviews as $i => $review_details) { ?>
            $(".my-rating-{{$i}}").starRating({
                starSize: 25,
                initialRating: "{{$review_details->ratings}}",
                readOnly: true,
                callback: function(currentRating, $el){
                    // make a server call here
                }
            });
        <?php } ?>

        $(document).ready(function(){
             setTimeout(function(){
                $('.sorting').each(function(){
                    var head = $('.table').find('thead').last().find('th').last().text();
                    if(head == $(this).html()){
                    $(this).removeClass('sorting')
                    }  
                });  
             },500);
        });
    </script>

@endsection

