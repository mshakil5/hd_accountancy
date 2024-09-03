<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Validator;
use App\Models\Softcode;
use App\Models\Master;
use App\Models\TimeSlot;
use App\Models\Package;
use App\Models\BusinessService;

class FrontendController extends Controller
{
    public function index()
    {
        $homeIntroSoftcode = Softcode::where('name', 'Homepage Intro')->first();
        if ($homeIntroSoftcode) {
            $homePageIntro = Master::where('softcode_id', $homeIntroSoftcode->id)->first();
        } else {
            $homePageIntro = null;
        }

        $homeOurValuesSoftcode = Softcode::where('name', 'Homepage Our Values')->first();
        if ($homeOurValuesSoftcode) {
            $homeOurValues = Master::where('softcode_id', $homeOurValuesSoftcode->id)->first();
        } else {
            $homeOurValues = null;
        }

        $timeSlots = TimeSlot::orderBy('id', 'desc')->get();

        $packages = Package::orderBy('price', 'asc')->get();

        $businessServices = BusinessService::orderBy('id', 'asc')->get();
        
        return view('frontend.homepage.index', compact('homePageIntro', 'homeOurValues', 'timeSlots', 'packages', 'businessServices'));
    }

    public function contact()
    {
        $softcode = Softcode::where('name', 'Contact')->first();
        if ($softcode) {
            $contactHeading = Master::where('softcode_id', $softcode->id)->first();
        } else {
            $contactHeading = null;
        }
        return view('frontend.contact.index', compact('contactHeading'));
    }

    public function storeStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|integer',
            'business_name' => 'required|string|max:255',
            'yearly_turnover' => 'nullable|integer',
            'interested_service' => 'array',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $contact = Contact::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'business_name' => $request->input('business_name'),
            'yearly_turnover' => $request->input('yearly_turnover'),
            'interested_service' => json_encode($request->input('interested_service')),
            'message' => $request->input('message'),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Message sent successfully!');
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
        $accountingSolutionCode = Softcode::where('name', 'Accounting Solution')->first();
        if ($accountingSolutionCode) {
            $accountingSolution = Master::where('softcode_id', $accountingSolutionCode->id)->first();
        } else {
            $accountingSolution = null;
        }

        $taxSolutionCode = Softcode::where('name', 'Tax Solution')->first();
        if ($taxSolutionCode) {
            $taxSolution = Master::where('softcode_id', $taxSolutionCode->id)->first();
        } else {
            $taxSolution = null;
        }

        $otherSolutionCode = Softcode::where('name', 'Other Solution')->first();
        if ($otherSolutionCode) {
            $otherSolution = Master::where('softcode_id', $otherSolutionCode->id)->first();
        } else {
            $otherSolution = null;
        }

        $businessStartupCode = Softcode::where('name', 'Business Start-up')->first();
        if ($businessStartupCode) {
            $businessStartUp = Master::where('softcode_id', $businessStartupCode->id)->first();
        } else {
            $businessStartUp = null;
        }

        $companySecretarialCode = Softcode::where('name', 'Company Secretarial')->first();
        if ($companySecretarialCode) {
            $companySecretarial = Master::where('softcode_id', $companySecretarialCode->id)->first();
        } else {
            $companySecretarial = null;
        }

        $bankruptcyLiquidationCode = Softcode::where('name', 'Bankruptcy and Liquidation')->first();
        if ($bankruptcyLiquidationCode) {
            $bankruptcyLiquidation = Master::where('softcode_id', $bankruptcyLiquidationCode->id)->first();
        } else {
            $bankruptcyLiquidation = null;
        }


        return view('frontend.services.index', compact('accountingSolution', 'taxSolution', 'otherSolution', 'businessStartUp', 'companySecretarial', 'bankruptcyLiquidation'));
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
