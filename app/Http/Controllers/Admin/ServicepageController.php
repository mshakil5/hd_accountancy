<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Softcode;
use App\Models\Master;

class ServicepageController extends Controller
{
    public function servicepageAccounting()
    {
        $softcode = Softcode::where('name', 'Accounting Solution')->first();
        $accountSolution = $softcode ? Master::where('softcode_id', $softcode->id)->first() : null;
        return view('admin.service_page.account_solution', compact('accountSolution'));
    }

    public function servicepageAccountingUpdate(Request $request)
    {
        $request->validate([
            'short_title' => 'required|string|max:255',
            'long_title' => 'required|string|max:255',
            'short_description' => 'required|string',
            'long_description' => 'required|string',
        ]);

        $softcode = Softcode::where('name', 'Accounting Solution')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $accountSolution = Master::where('softcode_id', $softcode->id)->first();
        if (!$accountSolution) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $accountSolution->short_title = $request->input('short_title');
        $accountSolution->long_title = $request->input('long_title');
        $accountSolution->short_description = $request->input('short_description');
        $accountSolution->long_description = $request->input('long_description');

        $accountSolution->meta_title = $request->input('meta_title');
        $accountSolution->meta_description = $request->input('meta_description');
        $accountSolution->meta_keywords = $request->input('meta_keywords');

        if ($request->hasFile('meta_image')) {
            if ($accountSolution->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $accountSolution->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $accountSolution->meta_image = $imageName;
        }

        $accountSolution->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function servicepageTaxSolution()
    {
        $softcode = Softcode::where('name', 'Tax Solution')->first();
        $taxSolution = $softcode ? Master::where('softcode_id', $softcode->id)->first() : null;
        return view('admin.service_page.tax_solution', compact('taxSolution'));
    }

    public function servicepageTaxSolutionUpdate(Request $request)
    {
        $request->validate([
            'short_title' => 'required|string|max:255',
            'long_title' => 'required|string|max:255',
            'short_description' => 'required|string',
            'long_description' => 'required|string',
        ]);

        $softcode = Softcode::where('name', 'Tax Solution')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $taxSolution = Master::where('softcode_id', $softcode->id)->first();
        if (!$taxSolution) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $taxSolution->short_title = $request->input('short_title');
        $taxSolution->long_title = $request->input('long_title');
        $taxSolution->short_description = $request->input('short_description');
        $taxSolution->long_description = $request->input('long_description');

        $taxSolution->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function servicepageOtherSolution()
    {
        $softcode = Softcode::where('name', 'Other Solution')->first();
        $otherSolution = $softcode ? Master::where('softcode_id', $softcode->id)->first() : null;
        return view('admin.service_page.other_solution', compact('otherSolution'));
    }

    public function servicepageOtherSolutionUpdate(Request $request)
    {
        $request->validate([
            'short_title' => 'required|string|max:255',
            'long_title' => 'required|string|max:255',
            'short_description' => 'required|string',
            'long_description' => 'required|string',
        ]);

        $softcode = Softcode::where('name', 'Other Solution')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $otherSolution = Master::where('softcode_id', $softcode->id)->first();
        if (!$otherSolution) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $otherSolution->short_title = $request->input('short_title');
        $otherSolution->long_title = $request->input('long_title');
        $otherSolution->short_description = $request->input('short_description');
        $otherSolution->long_description = $request->input('long_description');

        $otherSolution->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function servicepageBusinessStartup()
    {
        $softcode = Softcode::where('name', 'Business Start-up')->first();
        $businessStartup = $softcode ? Master::where('softcode_id', $softcode->id)->first() : null;
        return view('admin.service_page.business_startup', compact('businessStartup'));
    }

    public function servicepageBusinessStartupUpdate(Request $request)
    {
        $request->validate([
            'short_title' => 'required|string|max:255',
            'long_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Business Start-up')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $servicepageBusinessStartup = Master::where('softcode_id', $softcode->id)->first();
        if (!$servicepageBusinessStartup) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $servicepageBusinessStartup->short_title = $request->input('short_title');
        $servicepageBusinessStartup->long_description = $request->input('long_description');

        if ($request->hasFile('meta_image')) {
            if ($servicepageBusinessStartup->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $servicepageBusinessStartup->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $servicepageBusinessStartup->meta_image = $imageName;
         }

        $servicepageBusinessStartup->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function servicepageCompanySecretarial()
    {
        $softcode = Softcode::where('name', 'Company Secretarial')->first();
        $companySecreterial = $softcode ? Master::where('softcode_id', $softcode->id)->first() : null;
        return view('admin.service_page.company_secretarial', compact('companySecreterial'));
    }

    public function servicepageCompanySecretarialUpdate(Request $request)
    {
        $request->validate([
            'short_title' => 'required|string|max:255',
            'long_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Company Secretarial')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $companySecreterial = Master::where('softcode_id', $softcode->id)->first();
        if (!$companySecreterial) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $companySecreterial->short_title = $request->input('short_title');
        $companySecreterial->long_description = $request->input('long_description');

        if ($request->hasFile('meta_image')) {
            if ($companySecreterial->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $companySecreterial->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $companySecreterial->meta_image = $imageName;
         }

        $companySecreterial->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function bankruptcyAndLiquidation()
    {
        $softcode = Softcode::where('name', 'Bankruptcy and Liquidation')->first();
        $bankRupty = $softcode ? Master::where('softcode_id', $softcode->id)->first() : null;
        return view('admin.service_page.bankruptcy_and_liquidation', compact('bankRupty'));
    }

    public function bankruptcyAndLiquidationUpdate(Request $request)
    {
        $request->validate([
            'short_title' => 'required|string|max:255',
            'long_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Bankruptcy and Liquidation')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $bankRupty = Master::where('softcode_id', $softcode->id)->first();
        if (!$bankRupty) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $bankRupty->short_title = $request->input('short_title');
        $bankRupty->long_description = $request->input('long_description');

        if ($request->hasFile('meta_image')) {
            if ($bankRupty->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $bankRupty->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $bankRupty->meta_image = $imageName;
         }

        $bankRupty->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

}
