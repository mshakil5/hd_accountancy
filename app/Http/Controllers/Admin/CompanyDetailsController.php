<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CompanyDetails;

class CompanyDetailsController extends Controller
{
    public function index()
    {
        $data = CompanyDetails::all()->first();
        return view('admin.company.index',compact('data'));
    }

    public function update(Request $request)
    {
        $data = CompanyDetails::find($request->codeid);

        $request->validate([
            'company_name' => 'required|string|max:255',
            'email1' => 'nullable|email|max:255',
            'email2' => 'nullable|email|max:255',
            'phone1' => 'nullable|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'phone3' => 'nullable|string|max:20',
            'phone4' => 'nullable|string|max:20',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'youtube' => 'nullable|string|max:255',
            'tawkto' => 'nullable|string|max:255',
            'google_appstore_link' => 'nullable|string|max:255',
            'google_play_link' => 'nullable|string|max:255',
            'opening_time' => 'nullable|string|max:10',
            'footer_link' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'about_us' => 'nullable|string',
            'footer_content' => 'nullable|string',
            'google_map' => 'nullable|string',
            'fav_icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'footer_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($request->hasFile('fav_icon')) {
            if ($data->fav_icon && file_exists(public_path('images/company/' . $data->fav_icon))) {
                unlink(public_path('images/company/' . $data->fav_icon));
            }
            $favIconName = rand(100000, 999999) . '_fav_icon.' . $request->fav_icon->extension();
            $request->fav_icon->move(public_path('images/company'), $favIconName);
            $data->fav_icon = $favIconName;
        }

        if ($request->hasFile('company_logo')) {
            if ($data->company_logo && file_exists(public_path('images/company/' . $data->company_logo))) {
                unlink(public_path('images/company/' . $data->company_logo));
            }
            $companyLogoName = rand(100000, 999999) . '_company_logo.' . $request->company_logo->extension();
            $request->company_logo->move(public_path('images/company'), $companyLogoName);
            $data->company_logo = $companyLogoName;
        }

        if ($request->hasFile('footer_logo')) {
            if ($data->footer_logo && file_exists(public_path('images/company/' . $data->footer_logo))) {
                unlink(public_path('images/company/' . $data->footer_logo));
            }
            $footerLogoName = rand(100000, 999999) . '_footer_logo.' . $request->footer_logo->extension();
            $request->footer_logo->move(public_path('images/company'), $footerLogoName);
            $data->footer_logo = $footerLogoName;
        }

        $data->company_name = $request->company_name;
        $data->email1 = $request->email1;
        $data->email2 = $request->email2;
        $data->phone1 = $request->phone1;
        $data->phone2 = $request->phone2;
        $data->phone3 = $request->phone3;
        $data->phone4 = $request->phone4;
        $data->address1 = $request->address1;
        $data->address2 = $request->address2;
        $data->website = $request->website;
        $data->facebook = $request->facebook;
        $data->instagram = $request->instagram;
        $data->twitter = $request->twitter;
        $data->linkedin = $request->linkedin;
        $data->youtube = $request->youtube;
        $data->tawkto = $request->tawkto;
        $data->google_appstore_link = $request->google_appstore_link;
        $data->google_play_link = $request->google_play_link;
        $data->opening_time = $request->opening_time;
        $data->footer_link = $request->footer_link;
        $data->currency = $request->currency;
        $data->about_us = $request->about_us;
        $data->footer_content = $request->footer_content;
        $data->google_map = $request->google_map;

        $data->save();

        return redirect()->back()->with('success', 'Company details updated successfully.');
    }
}
