<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMensajesRequest extends FormRequest
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
            'idac_actividades' => ['required', 'integer'],  // id de la actividad
            'idu_users' => ['required', 'exists:users,idu'], // id del usuario
            'mensaje' => ['required', 'string'], // mensaje
            'fecha' => ['required', 'date'], // fecha
        ];
    }
}
