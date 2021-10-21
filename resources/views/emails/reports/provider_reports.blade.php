<!DOCTYPE html>

<style>
    dt {
        padding: 4px !important;
    }
    
    dd {
        padding: 4px !important;
    }
    
    table {
        font-family: arial, sans-serif;
        border-collapse: collapse;
        float: left;
        margin: 10px;
    }
    
    td,
    th {
        border: 1px solid #dddddd;
        text-align: left;
        padding: 10px;
        font-weight: normal;
    }
    
    tr:nth-child(even) {
        background-color: #f9fafb;
        font-weight: normal;
    }
    
    td:nth-child(odd) {
        color: #0000008a;
        text-transform: capitalize;
    }
    
    .text-gray {
        color: #808080;
    }
    
    .text-capitalize {
        text-transform: capitalize;
    }
    
    .text-success {
        color: green;
    }
</style>
<html>

<head>
    <title>{{Setting::get('site_name')}}</title>
</head>

<body>

    <div>

        <h1>
            <span class="text-capitalize">{{tr('weekly_report_title')}}</span>
        </h1>

        <h4 class="text-success">
            <span class="text-capitalize"><i>{{tr('hey')}} {{ $provider->name }},</i></span>
            <span>{{tr('weekly_report_status')}}</span>
        </h4>

        <div class="row">

            <div class="col-lg-12">

                <h3 class="p-5">{{tr('space_report')}} - {{$week_start_date}} {{tr('to')}} {{$week_end_date}}</h3>
                <table>

                    <tbody>
                        <tr>
                            <td>{{tr('total_spaces')}}</td>
                            <td class="text-uppercase text-gray">{{$total_spaces}}</td>
                        </tr>
                    </tbody>

                </table>

            </div>

            <br>
            
            <div class="col-lg-12">

                <h3 class="p-5">{{tr('booking_report')}}</h3>
                <table>
                    <tbody>
                        <tr>
                            <td>{{tr('total_bookings')}}</td>
                            <td class="text-uppercase text-gray">{{ $total_bookings}}</td>
                        </tr>

                        <tr>
                            <td>{{tr('total_upcoming_bookings')}}</td>
                            <td class="text-uppercase text-gray">{{$total_upcoming_bookings}}</td>
                        </tr>

                        <tr>
                            <td>{{tr('total_provider_earnings')}}</td>
                            <td class="text-uppercase text-gray">{{formatted_amount($total_provider_amount)}}</td>
                        </tr>

                        <tr>
                            <td>{{tr('total_completed_bookings')}}</td>
                            <td class="text-uppercase text-gray"> {{$total_completed_bookings}}</td>
                        </tr>

                        <tr>
                            <td>{{tr('total_cancelled_bookings')}}</td>
                            <td class="text-uppercase text-gray">{{$total_cancelled_bookings}}</td>
                        </tr>
                    </tbody>
                </table>

            </div>

        </div>

    </div>

</body>

</html>