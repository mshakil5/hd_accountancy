<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BusinessValue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BusinessValueController extends Controller
{
    public function index()
    {
        $data = BusinessValue::where('accounting_solution', 0)
                         ->where('tax_solution', 0)
                         ->orderBy('id', 'DESC')
                         ->get();
        return view('admin.business_value.index', compact('data'));
    }

    public function store(Request $request)
    {
        if(empty($request->short_title)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Short Title \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->long_description)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Long Description \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        
        $data = new BusinessValue;
        $data->short_title = $request->short_title;
        $data->long_title = $request->long_title;
        $data->short_description = $request->short_description;
        $data->long_description = $request->long_description;
        $data->created_by =  Auth::id();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/business_value'), $imageName);
            $data->image = 'images/business_value/' . $imageName;
        }

        if ($data->save()) {
            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Created successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        }else{
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        }
    }

    public function edit($id)
    {
        $where = [
            'id'=>$id
        ];
        $info = BusinessValue::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        $data = BusinessValue::find($request->codeid);
        if (!$data) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Business Service not found..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }

        if (empty($request->short_title)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Short Title \" field..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }
        if (empty($request->long_description)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Long Description \" field..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }

        $data->short_title = $request->short_title;
        $data->long_title = $request->long_title;
        $data->short_description = $request->short_description;
        $data->long_description = $request->long_description;
        $data->created_by = Auth::id();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $imagePath = 'images/business_value/' . $imageName;
            $image->move(public_path('images/business_value'), $imageName);

            if ($data->image && file_exists(public_path($data->image))) {
                unlink(public_path($data->image));
            }

            $data->image = $imagePath;
        }

        if ($data->save()) {
            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Updated successfully.</b></div>";
            return response()->json(['status' => 300, 'message' => $message, 'data' => $data]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }

    public function delete($id)
    {
        $data = BusinessValue::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Record not found!']);
        }

        $imagePath = public_path($data->image);

        if ($data->delete()) {
            if ($data->image && file_exists($imagePath)) {
                unlink($imagePath);
            }
            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Deleted successfully.</b></div>";
            return response()->json(['status' => 300, 'message' => $message]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }

    public function AccoutingSolutionindex()
    {
        $data = BusinessValue::where('accounting_solution', 1)
                         ->orderBy('id', 'DESC')
                         ->get();
        return view('admin.accounting_solution.index', compact('data'));
    }

    public function AccoutingSolutionstore(Request $request)
    {
        $requiredFields = [
            'short_title' => 'Short Title',
            'long_description' => 'Long Description',
            'footer_title' => 'Footer Title',
            'header_description' => 'Header Description',
            // 'short_description' => 'Short Description',
            'long_title' => 'Long Title',
            'meta_title' => 'Meta Title',
            'meta_description' => 'Meta Description',
        ];
    
        foreach ($requiredFields as $field => $label) {
            if (empty($request->$field)) {
                $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill the \"$label\" field..!</b></div>";
                return response()->json(['status' => 303, 'message' => $message]);
            }
        }
    
        $data = new BusinessValue;
        $data->short_title = $request->short_title;
        $data->slug = Str::slug($request->short_title);
        $data->long_description = $request->long_description;
        $data->footer_title = $request->footer_title; 
        $data->header_description = $request->header_description; 
        $data->short_description = $request->short_description;
        $data->long_title = $request->long_title;
        $data->meta_title = $request->meta_title;
        $data->meta_description = $request->meta_description;
        $data->accounting_solution = 1;
        $data->updated_by = Auth::id();
    
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $imagePath = 'images/business_value/' . $imageName;
            $image->move(public_path('images/business_value'), $imageName);
            $data->image = $imagePath;
        }
    
        if ($request->hasFile('details_image')) {
            $detailsImage = $request->file('details_image');
            $detailsImageName = rand(10000000, 99999999) . '.' . $detailsImage->getClientOriginalExtension();
            $detailsImagePath = 'images/business_value/' . $detailsImageName;
            $detailsImage->move(public_path('images/business_value'), $detailsImageName);
            $data->details_image = $detailsImagePath;
        }
    
        if ($request->hasFile('meta_image')) {
            $metaImage = $request->file('meta_image');
            $metaImageName = rand(10000000, 99999999) . '.' . $metaImage->getClientOriginalExtension();
            $metaImagePath = 'images/business_value/' . $metaImageName;
            $metaImage->move(public_path('images/business_value'), $metaImageName);
            $data->meta_image = $metaImagePath;
        }
    
        if ($data->save()) {
            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Created successfully.</b></div>";
            return response()->json(['status' => 300, 'message' => $message]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    } 

    public function AccoutingSolutionedit($id)
    {
        $where = [
            'id'=>$id
        ];
        $info = BusinessValue::where($where)->get()->first();
        return response()->json($info);
    }

    public function AccoutingSolutionupdate(Request $request)
    {
        $data = BusinessValue::find($request->codeid);
        if (!$data) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Business Service not found..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }
    
        $requiredFields = [
            'short_title' => 'Short Title',
            'long_description' => 'Long Description',
            'footer_title' => 'Footer Title',
            'header_description' => 'Header Description',
            // 'short_description' => 'Short Description',
            'long_title' => 'Long Title',
            'meta_title' => 'Meta Title',
            'meta_description' => 'Meta Description',
        ];
    
        foreach ($requiredFields as $field => $label) {
            if (empty($request->$field)) {
                $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill the \"$label\" field..!</b></div>";
                return response()->json(['status' => 303, 'message' => $message]);
            }
        }
    
        $data->short_title = $request->short_title;
        $data->slug = Str::slug($request->short_title);
        $data->long_title = $request->long_title;
        $data->short_description = $request->short_description;
        $data->header_description = $request->header_description;
        $data->long_description = $request->long_description;
        $data->footer_title = $request->footer_title;
        $data->meta_title = $request->meta_title;
        $data->meta_description = $request->meta_description;
        $data->updated_by = Auth::id();
    
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $imagePath = 'images/business_value/' . $imageName;
            $image->move(public_path('images/business_value'), $imageName);
    
            if ($data->image && file_exists(public_path($data->image))) {
                unlink(public_path($data->image));
            }
    
            $data->image = $imagePath;
        }
    
        if ($request->hasFile('details_image')) {
            $image = $request->file('details_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $imagePath = 'images/business_value/' . $imageName;
            $image->move(public_path('images/business_value'), $imageName);
    
            if ($data->details_image && file_exists(public_path($data->details_image))) {
                unlink(public_path($data->details_image));
            }
    
            $data->details_image = $imagePath;
        }
    
        if ($request->hasFile('meta_image')) {
            $image = $request->file('meta_image');
            $imageName = rand(10000000, 99999999) . '.' . $image->getClientOriginalExtension();
            $imagePath = 'images/business_value/' . $imageName;
            $image->move(public_path('images/business_value'), $imageName);
    
            if ($data->meta_image && file_exists(public_path($data->meta_image))) {
                unlink(public_path($data->meta_image));
            }
    
            $data->meta_image = $imagePath;
        }
    
        if ($data->save()) {
            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Updated successfully.</b></div>";
            return response()->json(['status' => 300, 'message' => $message, 'data' => $data]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }

    public function AccoutingSolutiondelete($id)
    {
        $data = BusinessValue::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Record not found!']);
        }

        $imagePath = public_path($data->image);
        $detailsImagePath = public_path($data->details_image);
        $metaImagePath = public_path($data->meta_image);

        if ($data->delete()) {
            if ($data->image && file_exists($imagePath)) {
                unlink($imagePath);
            }
            
            if ($data->details_image && file_exists($detailsImagePath)) {
                unlink($detailsImagePath);
            }

            if ($data->meta_image && file_exists($metaImagePath)) {
                unlink($metaImagePath);
            }

            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Deleted successfully.</b></div>";
            return response()->json(['status' => 300, 'message' => $message]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }

    public function Taxindex()
    {
        $data = BusinessValue::where('tax_solution', 1)
                         ->orderBy('id', 'DESC')
                         ->get();
        return view('admin.tax_solution.index', compact('data'));
    }

    public function Taxstore(Request $request)
    {
        if(empty($request->short_title)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Short Title \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->long_description)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Long Description \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        
        $data = new BusinessValue;
        $data->short_title = $request->short_title;
        $data->long_description = $request->long_description;
        $data->tax_solution = 1;
        $data->created_by =  Auth::id();

        if ($data->save()) {
            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Created successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        }else{
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        }
    }

    public function Taxedit($id)
    {
        $where = [
            'id'=>$id
        ];
        $info = BusinessValue::where($where)->get()->first();
        return response()->json($info);
    }

    public function Taxupdate(Request $request)
    {
        $data = BusinessValue::find($request->codeid);
        if (!$data) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Business Service not found..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }

        if (empty($request->short_title)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Short Title \" field..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }
        if (empty($request->long_description)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Long Description \" field..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }

        $data->short_title = $request->short_title;
        $data->long_description = $request->long_description;
        $data->created_by = Auth::id();

        if ($data->save()) {
            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Updated successfully.</b></div>";
            return response()->json(['status' => 300, 'message' => $message, 'data' => $data]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }

    public function Taxdelete($id)
    {
        $data = BusinessValue::find($id);
        if (!$data) {
            return response()->json(['status' => 404, 'message' => 'Record not found!']);
        }

        if ($data->delete()) {
            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Deleted successfully.</b></div>";
            return response()->json(['status' => 300, 'message' => $message]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }
}
