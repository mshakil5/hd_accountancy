<?php

namespace App\Http\Controllers\Admin;

use App\Models\SubService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SubServiceController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'sub_services.*' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }
        
        $serviceId = $request->input('service_id');
        $subServiceNames = $request->input('sub_services');

        foreach ($subServiceNames as $subServiceName) {
            $subService = new SubService([
                'name' => $subServiceName,
                'service_id' => $serviceId
            ]);
            $subService->save();
        }

      return response()->json(['status' => 300, 'message' => 'Sub-services created successfully']);
    }

}
