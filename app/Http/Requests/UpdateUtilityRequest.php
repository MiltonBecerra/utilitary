<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Utility;

class UpdateUtilityRequest extends FormRequest
{
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
        $rules = Utility::$rules;
        $rules['name'] = $rules['name'].",".$this->route("utility");$rules['slug'] = $rules['slug'].",".$this->route("utility");
        return $rules;
    }
}
