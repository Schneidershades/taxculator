<?php

namespace App\Http\Requests\Ingestion;

use Illuminate\Foundation\Http\FormRequest;

class CsvUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required','file','mimes:csv,txt','max:5120'],
            'bank_account_id' => ['nullable','integer','exists:bank_accounts,id'],
            'mapping_profile_id' => ['nullable','integer','exists:mapping_profiles,id'],
        ];
    }
}
