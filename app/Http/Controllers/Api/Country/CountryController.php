<?php

namespace App\Http\Controllers\Api\Country;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;

class CountryController extends Controller
{
    public function index ()
    {
    	return Country::all();
    }

    public function show ($id)
    {
    	return Country::findOrFail($id);
    }
}
