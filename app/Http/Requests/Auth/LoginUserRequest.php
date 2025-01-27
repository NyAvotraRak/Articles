<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginUserRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation qui s'appliquent à la requête.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'mot_de_passe' => ['required', 'string'],
        ];
    }

    /**
     * Tente d'authentifier les informations d'identification fournies dans la requête.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited(); // Vérifie si la tentative est bloquée par le throttling.

        $credentials = $this->only('email', 'mot_de_passe');

        if (!Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey()); // Ajoute un essai échoué.

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey()); // Réinitialise les tentatives si authentification réussie.
    }

    /**
     * S'assure que la requête de connexion n'est pas limitée par le taux.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this)); // Déclenche un événement de verrouillage.

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Génère une clé unique pour limiter le nombre de tentatives de connexion.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}
