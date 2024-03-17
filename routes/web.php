<?php

use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/by-week', function () {
    $response = [];
    $start = '2024-03-15';

    $end = '2030-06-11';

    $startDayWeekOfDateStart = Carbon::parse($start)->startOfWeek();

    $endDayWeekOfDateStart = Carbon::parse($start)->endOfWeek();

    $endDayWeekOfDateEnd = Carbon::parse($end)->endOfWeek();

    while ($endDayWeekOfDateStart->lte($endDayWeekOfDateEnd)) {
        $startDate = $startDayWeekOfDateStart->format('Y-m-d');
        $endDate = Carbon::parse($startDate)->endOfWeek()->format('Y-m-d');

        $key = "$startDate,$endDate";

        $response[$key] = [
            "receipt" => 1,
            "expense_voucher" => 2
        ];

        $endDayWeekOfDateStart->addWeeks(1);
        $startDayWeekOfDateStart->addWeeks(1);
    }
    dd($response);
});

Route::get('/by-month', function () {
    $response = [];
    $start = '2024-03-15';
    $end = '2030-06-11';

    $startCarbon = Carbon::createFromFormat('Y-m-d', $start)->startOfMonth();
    $endCarbon = Carbon::createFromFormat('Y-m-d', $end)->endOfMonth();
    while ($startCarbon->lte($endCarbon)) {
        $key =  $startCarbon->format('Y-m');
        $response[$key] = [
            "receipt" => 1,
            "expense_voucher" => 2
        ];

        $startCarbon->addMonth();
    }

    dd($response);
});

Route::get('/by-year', function () {
    $response = [];
    $start = '2024-03-15';
    $end = '2030-06-11';

    $startCarbon = Carbon::createFromFormat('Y-m-d', $start)->startOfYear();
    $endCarbon = Carbon::createFromFormat('Y-m-d', $end)->endOfYear();
    while ($startCarbon->lte($endCarbon)) {
        $key =  $startCarbon->format('Y');
        $response[$key] = [
            "receipt" => 1,
            "expense_voucher" => 2
        ];

        $startCarbon->addYear();
    }

    dd($response);
});
