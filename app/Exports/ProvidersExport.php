<?php

namespace App\Exports;

use App\Provider;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
  
class ProvidersExport implements FromView 
{

    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View {

       return view('exports.providers', [
           'providers' => Provider::all()
       ]);

    }

}