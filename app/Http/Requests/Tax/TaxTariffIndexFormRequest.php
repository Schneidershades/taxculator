<?php

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Tax\TaxVersionIndexFormRequest;

class TaxTariffIndexFormRequest extends TaxVersionIndexFormRequest
{
    public function rules(): array
    {
        $base = parent::rules();
        $base['tax_year'] = ['required', 'integer', 'min:2000', 'max:2100'];
        return $base;
    }
}
