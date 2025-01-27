<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterUserRequest extends FormRequest
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
            'nom_utilisateur' => ['required', 'string', 'min:2', 'max:255'],
            'prenom_utilisateur' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $this->route('register')],
            'mot_de_passe' => ['required', 'string', 'min:8'],
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

    public function messages(): array
    {
        return [
            'nom_utilisateur.required' => 'Un nom doit être fourni.',
            'prenom_utilisateur.required' => 'Un prenom doit être fourni.',
            'email.required' => 'Un email doit être fourni.',
            'email.unique' => 'Cette adresse mail existe déja.',
            'mot_de_passe.required' => 'Le mot de passe est requis.',
        ];
    }
}
