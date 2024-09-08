<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\PackageFeature;
use Illuminate\Support\Str;
use App\Models\TurnOver;
use Illuminate\Support\Facades\Auth;

class PackageController extends Controller
{
    public function index()
    {
        $data = Package::orderBy('id', 'DESC')->get();
        $features = PackageFeature::orderBy('id', 'DESC')->get();
        return view('admin.package.index', compact('data', 'features'));
    }

    public function store(Request $request)
    {
        if(empty($request->name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Package Name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
        }

        if(empty($request->short_title)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Short Title \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
        }

        if(empty($request->price)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \" Price \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
        }

        $existingPackage = Package::where('name', $request->name)->first();
        
        if ($existingPackage) {
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Package with the same name already exists.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
        } else {
            $data = new Package;
            $data->name = $request->name;
            $data->slug = Str::slug($request->name);
            $data->short_title = $request->short_title;
            $data->price = $request->price;
            $data->long_title = $request->long_title;
            $data->short_description = $request->short_description;
            $data->long_description = $request->long_description;
            $data->features = json_encode($request->features);
            
            if ($data->save()) {
                $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Created successfully.</b></div>";
                return response()->json(['status'=> 300,'message'=>$message]);
            }else{
                return response()->json(['status'=> 303,'message'=>'Server Error!!']);
            }
        }
    }

    public function edit($id)
    {
        $where = [
            'id'=>$id
        ];
        $info = Package::where($where)->get()->first();
        $turnovers = TurnOver::where('package_id', $id)->get();
        return response()->json([
            'package' => $info,
            'turnovers' => $turnovers,
        ]);
    }

    public function update(Request $request)
    {
        if (empty($request->name)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Package Name\" field..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }

        if (empty($request->short_title)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Short Title\" field..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }

        if (empty($request->price)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Price\" field..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        }

        $existingPackage = Package::where('name', $request->name)->where('id', '!=', $request->codeid)->first();

        if ($existingPackage) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Package with the same name already exists.</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
        } else {
            $data = Package::find($request->codeid);
            if ($data) {
                $data->name = $request->name;
                $data->short_title = $request->short_title;
                $data->price = $request->price;
                $data->long_title = $request->long_title;
                $data->short_description = $request->short_description;
                $data->long_description = $request->long_description;
                $data->features = json_encode($request->features);

                if ($data->save()) {
                    $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Updated successfully.</b></div>";
                    return response()->json(['status' => 300, 'message' => $message]);
                } else {
                    return response()->json(['status' => 303, 'message' => 'Server Error!!']);
                }
            } else {
                return response()->json(['status' => 404, 'message' => 'Package not found!']);
            }
        }
    }

    public function delete($id)
    {
        if(Package::destroy($id)){
            return response()->json(['success'=>true,'message'=>'Deleted successfully']);
        }else{
            return response()->json(['success'=>false,'message'=>'Delete Failed']);
        }
    }
    
    public function showTurnover(Request $request)
    {
        $packageId = $request->query('package_id');
        $package = Package::findOrFail($packageId);
        $turnovers = TurnOver::where('package_id', $packageId)->get();
        return response()->json([
            'package' => $package,
            'turnovers' => $turnovers,
            'packageId' => $packageId
        ]);
    }

    public function storeTurnover(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',     
            'price_range' => 'required|string',
        ]);

        TurnOver::create([
            'package_id' => $request->input('package_id'),
            'price_range' => $request->input('price_range'),
            'created_by' => Auth::user()->id
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Turnover entry added successfully.',
        ]);
    }

    
}
