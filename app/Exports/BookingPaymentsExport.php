<?php

namespace App\Exports;

use App\BookingPayment;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
  
class BookingPaymentsExport implements FromView 
{

    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View {

       return view('exports.booking_payments', [
           'booking_payments' => BookingPayment::all()
       ]);

    }

}