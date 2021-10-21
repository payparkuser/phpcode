<?php

namespace App\Exports;

use App\Booking;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
  
class BookingsExport implements FromView 
{

    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View {

       return view('exports.bookings', [
           'bookings' => Booking::all()
       ]);

    }

}