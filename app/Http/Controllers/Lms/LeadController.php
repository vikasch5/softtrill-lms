<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function fieldAddIndex(){
        $field = [];
        return view('lms.pages.field-add', compact('field'));
    }
}
