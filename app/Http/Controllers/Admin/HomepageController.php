<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Softcode;
use App\Models\Master;

class HomepageController extends Controller
{
    public function homepageIntro()
    {
        $softcode = Softcode::where('name', 'Homepage Intro')->first();
        $homePageIntro = $softcode ? Master::where('softcode_id', $softcode->id)->first() : null;
        return view('admin.home_page.intro', compact('homePageIntro'));
    }

    public function homepageIntroUpdate(Request $request)
    {
        $request->validate([
            'short_title' => 'required|string|max:255',
            'long_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Homepage Intro')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $homePageIntro = Master::where('softcode_id', $softcode->id)->first();
        if (!$homePageIntro) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $homePageIntro->short_title = $request->input('short_title');
        $homePageIntro->long_description = $request->input('long_description');

         if ($request->hasFile('meta_image')) {
            if ($homePageIntro->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $homePageIntro->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $homePageIntro->meta_image = $imageName;
         }

        $homePageIntro->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function homepageOurValues()
    {
        $softcode = Softcode::where('name', 'Homepage Our Values')->first();
        $homePageOurValues = $softcode ? Master::where('softcode_id', $softcode->id)->first() : null;
        return view('admin.home_page.our_values', compact('homePageOurValues'));
    }

    public function homepageOurValuesUpdate(Request $request)
    {
        $request->validate([
            'short_title' => 'required|string|max:255',
            'long_title' => 'required|string|max:255',
            'long_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Homepage Our Values')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $homePageOurValues = Master::where('softcode_id', $softcode->id)->first();
        if (!$homePageOurValues) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $homePageOurValues->short_title = $request->input('short_title');
        $homePageOurValues->long_title = $request->input('long_title');
        $homePageOurValues->long_description = $request->input('long_description');

         if ($request->hasFile('meta_image')) {
            if ($homePageOurValues->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $homePageOurValues->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $homePageOurValues->meta_image = $imageName;
         }

        $homePageOurValues->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }
}
