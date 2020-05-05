<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class CouponRequest extends FormRequest
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
        return [
            'coupon_name' => [
                'required',
                'max:100'
            ],
            'coupon_explain'   => 'required|max:100',
            'coupon_type'   => 'required|numeric',
            'end_time'   => 'required|date_format:Y-m-d',
            'total_num'   => 'required|numeric',
            'user_num'   => 'required|numeric',
            'is_rec'   => 'required|numeric',
            'use_notice'   => 'required|max:100',
            'careful_matter'   => 'required|max:100',
        ];
    }

    public function messages()
    {
        return [

        ];
    }
}