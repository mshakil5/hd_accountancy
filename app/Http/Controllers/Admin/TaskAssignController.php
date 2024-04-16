<?php

namespace App\Http\Controllers\Admin;

use App\Models\Task;
use App\Models\User;
use App\Models\Client;
use App\Models\TaskAssign;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TaskAssignController extends Controller
{
    public function index()
    {
        $clients = Client::orderby('id','DESC')->get();
        $tasks = Task::orderby('id','DESC')->get();
        $staffs = User::where('type','3')->orderby('id','DESC')->get();
        $data = TaskAssign::orderby('id','DESC')->get();
        return view('admin.task_assign.index',compact('data','clients','tasks','staffs'));
    }

    public function store(Request $request)
    {
        // return $request->all();
        $data = new TaskAssign;
        $data->client_id = $request->client_id;
        $data->staff_id = $request->staff_id;
        $data->note = $request->note;
        $data->date = $request->date;
        $data->created_by =  Auth::id();
        
        if ($data->save()) {

        $data->tasks()->attach($request->tasks);
           

            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Created Successfully.</b></div>";
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
        $info = TaskAssign::where($where)->with('tasks')->get()->first();
        return response()->json($info);
    }
    public function update(Request $request)
    {
        $data = TaskAssign::find($request->codeid);
        $data->staff_id = $request->staff_id;
        $data->note = $request->note;
        $data->date = $request->date;
        $data->updated_by =  Auth::id();
        
        $tasks = $request->input('tasks', []);
        $data->tasks()->sync($tasks);

        if ($data->save()) {
            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Updated Successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        }else{
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        }
    }
}
