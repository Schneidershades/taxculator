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
    *   @OA\Property(property="taxClasses", type="object", type="array",
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

    public $taxClasses;


     /**
     * @OA\Property(
     *      title="Transaction Description",
     *      description="Description of the transaction ",
     *      example="true/false"
     * )
     *
     * @var boolean
     */
    private $offer_nhf;


     /**
     * @OA\Property(
     *      title="Transaction Description",
     *      description="Description of the transaction ",
     *      example="true/false"
     * )
     *
     * @var boolean
     */
    private $offer_pension;

     /**
     * @OA\Property(
     *      title="Transaction Description",
     *      description="Description of the transaction ",
     *      example="true/false"
     * )
     *
     * @var boolean
     */
    private $offer_life_insurance;

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
            'taxClasses.basic_salary' => 'numeric',
            'taxClasses.housing' =>'numeric',
            'taxClasses.clothing' =>'numeric',
            'taxClasses.utility' =>'numeric',
            'taxClasses.lunch' =>'numeric',
            'taxClasses.education' =>'numeric',
            'taxClasses.vacation' =>'numeric',

            'offer_nhf' => 'required|boolean',
            'offer_pension' => 'required|boolean',
            'offer_life_insurance' => 'required|boolean',
        ];
    }
}
