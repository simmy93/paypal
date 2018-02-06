<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    //
    public function downloadFile(){

    	return response()->download(storage_path('app/archive.zip'));
    }
}
