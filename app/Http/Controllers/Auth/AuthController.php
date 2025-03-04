<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login API
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Ces identifiants ne correspondent pas à nos enregistrements.'],
            ]);
        }

        // Vérifier si l'utilisateur a le rôle admin
        if (!$user->hasRole('admin')) {
            throw ValidationException::withMessages([
                'email' => ['Vous n\'avez pas accès à l\'administration.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        // Ajouter les rôles et permissions à la réponse
        $user = $request->user();
        $roles = $user->roles->pluck('name');
        $permissions = $user->getAllPermissions()->pluck('name');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $roles,
            'permissions' => $permissions,
            'created_at' => $user->created_at
        ]);
    }

    /**
     * Logout API
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie']);
    }
}
