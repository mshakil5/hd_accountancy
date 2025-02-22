<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
      return view('admin.reports.index');
    }

    public function createReport()
    {
      return view('admin.reports.create');
    }
}
