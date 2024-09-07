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
use App\Models\BusinessValue;
use App\Models\ClientTestimonial;
use App\Models\CaseStudy;
use App\Models\LatestInsight;
use App\Models\OurTeam;
use App\Models\ClientSchedule;
use App\Models\Quotation;
use App\Models\Career;
use App\Models\WeWorkWithImage;

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

        $businessValues = BusinessValue::orderBy('id', 'asc')->get();

        $clientTestimonial = ClientTestimonial::orderBy('id', 'desc')->take(3)->get();

        $caseStudies = CaseStudy::orderBy('id', 'desc')->get();

        $latestInsights = LatestInsight::orderBy('id', 'desc')->get();

        $weWorkWithImages = WeWorkWithImage::orderBy('id', 'asc')->get();
        
        return view('frontend.homepage.index', compact('homePageIntro', 'homeOurValues', 'timeSlots', 'packages', 'businessServices', 'businessValues', 'clientTestimonial', 'caseStudies', 'latestInsights', 'weWorkWithImages'));
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
        $getQuotationCode = Softcode::where('name', 'Get Quotation Page')->first();
        if ($getQuotationCode) {
            $getQuotation = Master::where('softcode_id', $getQuotationCode->id)->first();
        } else {
            $getQuotation = null;
        }
        return view('frontend.get-quotation.index', compact('getQuotation'));
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
        $ourTeamCode = Softcode::where('name', 'Our Team Page')->first();
        if ($ourTeamCode) {
            $ourTeamPage = Master::where('softcode_id', $ourTeamCode->id)->first();
        } else {
            $ourTeamPage = null;
        }
        $ourTeam = OurTeam::orderBy('id', 'desc')->get();
        return view('frontend.our-team.index', compact('ourTeamPage', 'ourTeam'));
    }

    public function career()
    {
        $code = Softcode::where('name', 'Career Page')->first();
        if ($code) {
            $career = Master::where('softcode_id', $code->id)->first();
        } else {
            $career = null;
        }
        return view('frontend.career.index', compact('career'));
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
        $caseStudySoftcode = Softcode::where('name', 'Case Study')->first();
        if ($caseStudySoftcode) {
            $caseStudy = Master::where('softcode_id', $caseStudySoftcode->id)->first();
        } else {
            $caseStudy = null;
        }
        $caseStudies = CaseStudy::orderby('id','DESC')->get();
        return view('frontend.case-study.index', compact('caseStudy', 'caseStudies'));
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

    public function storeSchedule(Request $request)
    {
        $validatedData = $request->validate([
            'date' => 'required|date',
            'time' => 'required',
            'time_zone' => 'required|string',
            'meet_type' => 'required|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|numeric',
            'discussion' => 'required|string',
        ]);

        ClientSchedule::create($validatedData);

        return response()->json(['message' => 'Meeting scheduled successfully!']);
    }

    public function storeQuotation(Request $request)    
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company_name' => 'required|string|max:255',
            'phone' => 'required',
            'business_type' => 'required|string|max:255',
            'turnover' => 'required|numeric',
            'vat_returns' => 'required|string|max:255',
            'payroll' => 'required|string|max:255',
            'bookkeeping' => 'required|string|max:255',
            'bookkeeping_software' => 'required|string|max:255',
            'management_account' => 'required|string|max:255',
            'bank_accounts' => 'required|integer',
        ]);

        $quotation = Quotation::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'company_name' => $request->input('company_name'),
            'phone' => $request->input('phone'),
            'business_type' => $request->input('business_type'),
            'turnover' => $request->input('turnover'),
            'vat_returns' => $request->input('vat_returns'),
            'payroll' => $request->input('payroll'),
            'bookkeeping' => $request->input('bookkeeping'),
            'bookkeeping_software' => $request->input('bookkeeping_software'),
            'management_account' => $request->input('management_account'),
            'bank_accounts' => $request->input('bank_accounts'),
            'status' => 1,
            'created_by' => auth()->id() ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function storeCareer(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|numeric',
            'linkedin_profile' => 'required|string',
            'yearly_turnover' => 'required|numeric',
            'cv' => 'required|mimes:pdf,docx,doc|max:4096',
            'about_yourself' => 'required|string',
        ]);

        $cv = $request->file('cv');
        $randomNumber = rand(1000000000, 9999999999);
        $cvFileName = $randomNumber . '.' . $cv->getClientOriginalExtension();
        $cv->move(public_path('images/Cv'), $cvFileName);

        $validatedData['cv'] = $cvFileName;

        Career::create($validatedData);

        return response()->json(['message' => 'Career form submitted successfully!']);
    }

    public function latestInsightDetails($slug)
    {
        $latestInsight = LatestInsight::where('slug', $slug)->firstOrFail();
        return view('frontend.latest-insight.index', compact('latestInsight'));
    }

}
