<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CategorieRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom_categorie' => [
                'required', 
                'min:2', 
                'unique:categories,nom_categorie,' . $this->route('categorie') // Vérifie l'unicité
            ]
        ];
    }

    public function failedValidation(Validator $validator){

        throw new HttpResponseException(response()->json([

            'success' => false,
            'error' => true,
            'message' => 'Erreur de validation',
            'errorsList' => $validator->errors()
        ]));
    }

    public function messages()
    {
        return [
            'nom_categorie.required' => 'Un nom doit être fourni.',
            'nom_categorie.min' => 'Le nom doit contenir au moins 2 caractères.',
            'nom_categorie.unique' => 'Ce nom de catégorie existe déjà.',
        ];
    }
}
