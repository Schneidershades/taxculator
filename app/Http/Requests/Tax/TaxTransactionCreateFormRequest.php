<?php

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;

/**
/**
 * @OA\Schema(
 *      title="Tax Transaction Form Request Fields",
 *      description="Tax Transaction request body data",
 *      type="object",
 *      required={"email"}
 * )
 */

class TaxTransactionCreateFormRequest extends FormRequest
{
    /**
    *   @OA\Property(property="taxClasses", type="array", type="array",
    *            @OA\Items(
    *                @OA\Property(property="basic_salary", type="int", example="10000"),
    *                @OA\Property(property="housing", type="int", example="10000"),
    *                @OA\Property(property="clothing", type="int", example="10000"),
    *                @OA\Property(property="utility", type="int", example="10000"),
    *                @OA\Property(property="lunch", type="int", example="10000"),
    *                @OA\Property(property="education", type="int", example="10000"),
    *                @OA\Property(property="vacation", type="int", example="10000"),
    *            ),
    *        ),
    *    ),
    */

    private $taxClasses;

    /**
    *   @OA\Property(property="taxDeductions", type="array", type="array",
    *            @OA\Items(
    *                @OA\Property(property="nhf", type="int", example=true),
    *                @OA\Property(property="pension", type="int", example=true),
    *            ),
    *        ),
    *    ),
    */

    private $taxDeductions;


    /**
     * @OA\Property(
     *      title="country_id",
     *      description="country_id",
     *      example="1"
     * )
     *
     * @var int
     */
    public $country_id;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'taxClasses' => 'required|array',
            'taxClasses.*.basic_salary' => 'required|numeric',
            'taxClasses.*.housing' =>'numeric',
            'taxClasses.*.clothing' =>'numeric',
            'taxClasses.*.utility' =>'numeric',
            'taxClasses.*.lunch' =>'numeric',
            'taxClasses.*.education' =>'numeric',
            'taxClasses.*.vacation' =>'numeric',

            'taxDeductions' => 'required|array',
            'taxDeductions.*.nhf' => 'boolean',
            'taxDeductions.*.pension' => 'boolean',

            'country_id' => 'required|int',
        ];
    }
}
