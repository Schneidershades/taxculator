<?php

namespace App\Http\Controllers\Api\V1\Country;

use App\Models\Country;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CountryController extends Controller
{
    public function index()
    {
        return $this->showAll(Country::all());
    }

    public function show($id)
    {
        return Country::findOrFail($id);
    }
}
