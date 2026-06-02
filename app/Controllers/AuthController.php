<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Auth\Auth;
use Core\Database\Connection;
use Core\Request;
use Core\Response;

class AuthController
{
    public function __construct(
        private readonly Auth $auth,
        private readonly Connection $db,
    ) {
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return view('auth/login', ['title' => 'Login']);
    }

    public function login(Request $request, Response $response): Response
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($this->auth->attempt($validated['email'], $validated['password'])) {
            return $response->redirect('/');
        }

        session()->flash('error', 'E-mail ou senha incorretos.');
        return $response->redirect('/login');
    }

    public function showRegister(Request $request, Response $response): Response
    {
        return view('auth/register', ['title' => 'Cadastro']);
    }

    public function register(Request $request, Response $response): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        // Verifica se email já existe
        $existing = $this->db->table(config('auth.table', 'users'))
            ->where('email', $validated['email'])
            ->first();

        if ($existing !== null) {
            session()->flash('error', 'Este e-mail já está em uso.');
            return $response->redirect('/register');
        }

        // Cria o usuário
        $userId = $this->db->table(config('auth.table', 'users'))->insert([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Auth::hash($validated['password']),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Auto-login
        $user = $this->db->table(config('auth.table', 'users'))
            ->where('id', $userId)
            ->first();

        $this->auth->login($user);

        return $response->redirect('/');
    }

    public function logout(Request $request, Response $response): Response
    {
        $this->auth->logout();
        return $response->redirect('/login');
    }
}
