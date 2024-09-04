<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientSchedule;

class BookingController extends Controller
{
    public function index()
    {
        $data = ClientSchedule::orderBy('id', 'DESC')->get();
        return view('admin.booking.index', compact('data'));
    }
}
