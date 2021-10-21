<?php

namespace App\Exports;

use App\ProviderSubscriptionPayment;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
  
class ProviderSubscriptionPaymentsExport implements FromView 
{

    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View {

       return view('exports.provider_subscription_payments', [
           'provider_subscription_payments' => ProviderSubscriptionPayment::all()
       ]);

    }

}