<?php

namespace App\Exports;

use App\User;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
  
class UsersExport implements FromView
{

    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
       return view('exports.users', [
           'users' => User::all()
       ]);
    }

}