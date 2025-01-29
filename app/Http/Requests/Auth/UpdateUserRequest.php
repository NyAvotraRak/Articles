<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
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
            'nom_utilisateur' => 'required|string|max:255',
            'prenom_utilisateur' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->route('user'), // Permet de valider tout en excluant l'utilisateur actuel
            'mot_de_passe' => 'nullable|string|min:8', // Le mot de passe est optionnel pour les mises à jour
            'telephones' => 'nullable|array', // Le champ telephones doit être un tableau si présent
            'telephones.*' => 'nullable|string|regex:/^\d{10}$/', // Validation pour 10 chiffres
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

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom_utilisateur.required' => 'Le champ "Nom" est obligatoire.',
            'nom_utilisateur.string' => 'Le champ "Nom" doit être une chaîne de caractères.',
            'nom_utilisateur.max' => 'Le champ "Nom" ne peut pas dépasser 255 caractères.',

            'prenom_utilisateur.required' => 'Le champ "Prénom" est obligatoire.',
            'prenom_utilisateur.string' => 'Le champ "Prénom" doit être une chaîne de caractères.',
            'prenom_utilisateur.max' => 'Le champ "Prénom" ne peut pas dépasser 255 caractères.',

            'email.required' => 'Le champ "Email" est obligatoire.',
            'email.email' => 'Veuillez fournir une adresse e-mail valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',

            'mot_de_passe.string' => 'Le mot de passe doit être une chaîne de caractères.',
            'mot_de_passe.min' => 'Le mot de passe doit comporter au moins 8 caractères.',
            'mot_de_passe.confirmed' => 'La confirmation du mot de passe ne correspond pas.',

            'telephones.array' => 'Le champ "Téléphones" doit être un tableau.',
            'telephones.*.string' => 'Chaque numéro de téléphone doit être une chaîne de caractères.',
            'telephones.*.size' => 'Chaque numéro de téléphone doit contenir exactement 10 caractères.',
            'telephones.*.regex' => 'Chaque numéro de téléphone doit contenir exactement 10 chiffres.',
        ];
    }
}
