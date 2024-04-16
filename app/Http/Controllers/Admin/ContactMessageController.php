<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ContactMessageController extends Controller
{
   public function index()
   {
    $data = Contact::orderby('id','DESC')->get();
    return view('admin.contact_message.index', compact('data'));
   }
}
