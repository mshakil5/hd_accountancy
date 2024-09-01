<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FrontendController extends Controller
{
    public function index()
    {
        return view('frontend.homepage.index');
    }

    public function contact()
    {
        return view('frontend.contact.index');
    }

    public function pricing()
    {
        return view('frontend.pricing.index');
    }

    public function getQuotation()
    {
        return view('frontend.get-quotation.index');
    }

    public function services()
    {
        return view('frontend.services.index');
    }

    public function ourTeam()
    {
        return view('frontend.our-team.index');
    }

}
