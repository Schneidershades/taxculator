<?php

namespace App\Http\Controllers\Api\Country;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;

class CountryController extends Controller
{
	/**
    * @OA\Get(
    *      path="/api/v1/location/countries",
    *      operationId="all_countries",
    *      tags={"location"},
    *      summary="Get all countries",
    *      description="Get all countries",
    *      @OA\Response(
    *          response=200,
    *          description="Successful operation",
    *          @OA\MediaType(
    *             mediaType="application/json",
    *         ),
    *       ),
    * )
    */

    public function index ()
    {
    	return Country::all();
    }

    public function show ($id)
    {
    	return Country::findOrFail($id);
    }
}
