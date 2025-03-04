<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Softcode;
use App\Models\Master;

class MetaDataController extends Controller
{
    public function homeMeta()
    {
        $softCode = Softcode::where('name', 'Homepage Meta')->first();
        if ($softCode) {
            $data = Master::where('softcode_id', $softCode->id)->first();
        } else {
            $data = null;
        }

        return view('admin.meta.home', compact('data'));
    }

    public function homeMetaUpdate(Request $request)
    {
        $request->validate([
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Homepage Meta')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $data = Master::where('softcode_id', $softcode->id)->first();
        if (!$data) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $data->meta_title = $request->input('meta_title');
        $data->meta_description = $request->input('meta_description');

        if ($request->hasFile('meta_image')) {
            if ($data->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $data->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $data->meta_image = $imageName;
         }

        $data->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function serviceMeta()
    {
        $softCode = Softcode::where('name', 'Servicepage Meta')->first();
        if ($softCode) {
            $data = Master::where('softcode_id', $softCode->id)->first();
        } else {
            $data = null;
        }

        return view('admin.meta.service', compact('data'));
    }

    public function serviceMetaUpdate(Request $request)
    {
        $request->validate([
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Servicepage Meta')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $data = Master::where('softcode_id', $softcode->id)->first();
        if (!$data) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $data->meta_title = $request->input('meta_title');
        $data->meta_description = $request->input('meta_description');

        if ($request->hasFile('meta_image')) {
            if ($data->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $data->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $data->meta_image = $imageName;
         }

        $data->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function packageMeta()
    {
        $softCode = Softcode::where('name', 'Packagepage Meta')->first();
        if ($softCode) {
            $data = Master::where('softcode_id', $softCode->id)->first();
        } else {
            $data = null;
        }

        return view('admin.meta.package', compact('data'));
    }

    public function packageMetaUpdate(Request $request)
    {
        $request->validate([
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Packagepage Meta')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $data = Master::where('softcode_id', $softcode->id)->first();
        if (!$data) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $data->meta_title = $request->input('meta_title');
        $data->meta_description = $request->input('meta_description');

        if ($request->hasFile('meta_image')) {
            if ($data->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $data->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $data->meta_image = $imageName;
         }

        $data->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function contactMeta()
    {
        $softCode = Softcode::where('name', 'Contactpage Meta')->first();
        if ($softCode) {
            $data = Master::where('softcode_id', $softCode->id)->first();
        } else {
            $data = null;
        }

        return view('admin.meta.contact', compact('data'));
    }

    public function contactMetaUpdate(Request $request)
    {
        $request->validate([
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Contactpage Meta')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $data = Master::where('softcode_id', $softcode->id)->first();
        if (!$data) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $data->meta_title = $request->input('meta_title');
        $data->meta_description = $request->input('meta_description');

        if ($request->hasFile('meta_image')) {
            if ($data->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $data->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $data->meta_image = $imageName;
         }

        $data->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function getQuotationMeta()
    {
        $softCode = Softcode::where('name', 'Getquotationpage Meta')->first();
        if ($softCode) {
            $data = Master::where('softcode_id', $softCode->id)->first();
        } else {
            $data = null;
        }

        return view('admin.meta.get-quotation', compact('data'));
    }

    public function getQuotationMetaUpdate(Request $request)
    {
        $request->validate([
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Getquotationpage Meta')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $data = Master::where('softcode_id', $softcode->id)->first();
        if (!$data) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $data->meta_title = $request->input('meta_title');
        $data->meta_description = $request->input('meta_description');

        if ($request->hasFile('meta_image')) {
            if ($data->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $data->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $data->meta_image = $imageName;
         }

        $data->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function careerMeta()
    {
        $softCode = Softcode::where('name', 'Careerpage Meta')->first();
        if ($softCode) {
            $data = Master::where('softcode_id', $softCode->id)->first();
        } else {
            $data = null;
        }

        return view('admin.meta.career', compact('data'));
    }

    public function careerMetaUpdate(Request $request)
    {
        $request->validate([
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Careerpage Meta')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $data = Master::where('softcode_id', $softcode->id)->first();
        if (!$data) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $data->meta_title = $request->input('meta_title');
        $data->meta_description = $request->input('meta_description');

        if ($request->hasFile('meta_image')) {
            if ($data->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $data->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $data->meta_image = $imageName;
         }

        $data->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function ourTeamMeta()
    {
        $softCode = Softcode::where('name', 'Ourteampage Meta')->first();
        if ($softCode) {
            $data = Master::where('softcode_id', $softCode->id)->first();
        } else {
            $data = null;
        }

        return view('admin.meta.our-team', compact('data'));
    }

    public function ourTeamMetaUpdate(Request $request)
    {
        $request->validate([
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Ourteampage Meta')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $data = Master::where('softcode_id', $softcode->id)->first();
        if (!$data) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $data->meta_title = $request->input('meta_title');
        $data->meta_description = $request->input('meta_description');

        if ($request->hasFile('meta_image')) {
            if ($data->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $data->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $data->meta_image = $imageName;
         }

        $data->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function faqMeta()
    {
        $softCode = Softcode::where('name', 'Faqpage Meta')->first();
        if ($softCode) {
            $data = Master::where('softcode_id', $softCode->id)->first();
        } else {
            $data = null;
        }

        return view('admin.meta.faq', compact('data'));
    }

    public function faqMetaMetaUpdate(Request $request)
    {
        $request->validate([
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Faqpage Meta')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $data = Master::where('softcode_id', $softcode->id)->first();
        if (!$data) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $data->meta_title = $request->input('meta_title');
        $data->meta_description = $request->input('meta_description');

        if ($request->hasFile('meta_image')) {
            if ($data->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $data->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $data->meta_image = $imageName;
         }

        $data->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function privacyMeta()
    {
        $softCode = Softcode::where('name', 'Privacypage Meta')->first();
        if ($softCode) {
            $data = Master::where('softcode_id', $softCode->id)->first();
        } else {
            $data = null;
        }

        return view('admin.meta.privacy', compact('data'));
    }

    public function privacyMetaUpdate(Request $request)
    {
        $request->validate([
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Privacypage Meta')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $data = Master::where('softcode_id', $softcode->id)->first();
        if (!$data) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $data->meta_title = $request->input('meta_title');
        $data->meta_description = $request->input('meta_description');

        if ($request->hasFile('meta_image')) {
            if ($data->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $data->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $data->meta_image = $imageName;
         }

        $data->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }

    public function termsMeta()
    {
        $softCode = Softcode::where('name', 'Termspage Meta')->first();
        if ($softCode) {
            $data = Master::where('softcode_id', $softCode->id)->first();
        } else {
            $data = null;
        }

        return view('admin.meta.terms', compact('data'));
    }

    public function termsMetaUpdate(Request $request)
    {
        $request->validate([
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'meta_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $softcode = Softcode::where('name', 'Termspage Meta')->first();
        if (!$softcode) {
            return redirect()->back()->withErrors(['error' => 'Softcode not found.']);
        }

        $data = Master::where('softcode_id', $softcode->id)->first();
        if (!$data) {
            return redirect()->back()->withErrors(['error' => 'Not found.']);
        }

        $data->meta_title = $request->input('meta_title');
        $data->meta_description = $request->input('meta_description');

        if ($request->hasFile('meta_image')) {
            if ($data->meta_image) {
               $oldImagePath = public_path('images/meta_image/' . $data->meta_image);
               if (file_exists($oldImagePath)) {
                     unlink($oldImagePath);
               }
            }

            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/meta_image'), $imageName);
            $data->meta_image = $imageName;
         }

        $data->save();

        return redirect()->back()->with('success', 'Updated successfully.');
    }
}
