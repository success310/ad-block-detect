<?php

namespace App\Http\Requests;

use App\Models\Paste;
use Illuminate\Foundation\Http\FormRequest;

class StorePasteRequest extends FormRequest
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
        $paste = $this->route()->parameter('paste');
        $methods = $this->route()->methods;
        $args = [
            'isLinksClickable' => 'required|boolean',
            'allowEmbedding' => 'required|boolean',
            'allowRaw' => 'required|boolean',
            'title' => 'nullable',
            'expiration' => 'nullable',
            'videoEmbed' => 'nullable',
            'password' => 'nullable',
            'timezone' => 'nullable',
            'bgColor' => 'required',
            'detailsColor' => 'required',
            'boxColor' => 'required',
            'textColor' => 'required',
            'textContent' => 'required',
        ];
        if ($methods[0] == "PUT")
            $args['slug'] = 'nullable|unique:pastes,slug,' . $paste->id;
        else if ($methods[0] == "POST")
            $args['slug'] = 'nullable|unique:pastes,slug';


        return $args;
    }
}
