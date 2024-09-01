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

    public function career()
    {
        return view('frontend.career.index');
    }

    public function faq()
    {
        return view('frontend.faq.index');
    }
    
    public function booking()
    {
        return view('frontend.booking.index');
    }

    public function caseStudy()
    {
        return view('frontend.case-study.index');
    }

    public function cloudAccounting()
    {
        return view('frontend.cloud-accounting.index');
    }

    public function digitalBookkeeping()
    {
        return view('frontend.digital-bookkeeping.index');
    }

    public function growBusiness()
    {
        return view('frontend.grow-business.index');
    }

    public function monthlyAccountManagement()
    {
        return view('frontend.monthly-account-management.index');
    }

    public function payroll()
    {
        return view('frontend.payroll.index');
    }

    public function videoTestimonial()
    {
        return view('frontend.video-testimonial.index');
    }

    public function yearEndAccount()
    {
        return view('frontend.year-end-account.index');
    }

}
