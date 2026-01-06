<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'location' => 'sometimes|required|string|max:255',
            'purchase_date' => 'sometimes|required|date|before_or_equal:today',
            'in_use' => 'sometimes|boolean',
        ];
    }

    /**
     * Mensagens de erro personalizadas
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do dispositivo é obrigatório',
            'location.required' => 'A localização é obrigatória',
            'purchase_date.required' => 'A data de compra é obrigatória',
            'purchase_date.date' => 'A data de compra deve ser uma data válida',
            'purchase_date.before_or_equal' => 'A data de compra não pode ser futura',
        ];
    }
}