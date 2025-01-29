<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProduitRequest extends FormRequest
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
        // Affiche toutes les données reçues dans la requête
        dd($this->all());
        return [
            // 'image_produit' => [$this->isMethod('patch') || $this->isMethod('put') ? 'nullable' : 'required', 'image', 'unique:produits,image_produit,' . $this->route('produit')],// Nullable pour modification
            'nom_produit' => ['required', 'string', 'min:2', 'unique:produits,nom_produit,' . $this->route('produit')],
            'description_produit' => ['required', 'string', 'unique:produits,description_produit,' . $this->route('produit')],
            'prix' => ['required', 'integer', 'min:0'],
            'categorie_nom' => ['required', 'string' ],
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
            'image_produit.required' => 'L\'image est obligatoire.',
            'image_produit.image' => 'ça doit être une image',
            'nom_produit.required' => 'Le nom du produit est obligatoire.',
            'nom_produit.unique' => 'Ce nom produit existe déjà.',
            'description_produit.unique' => 'Cette description produit existe déjà.',
            'description_produit.required' => 'La description est obligatoire.',
            'prix.required' => 'Le prix est obligatoire.',
            'categorie_nom.required' => 'Une catégorie doit être sélectionnée.',
            'categorie_id.exists' => 'La catégorie sélectionnée est invalide.',
        ];
    }
}
