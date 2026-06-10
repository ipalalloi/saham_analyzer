<?php
class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) redirect('dashboard');
        if (method_is_post()) {
            Csrf::verify();
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            if (Auth::attempt($email, $password)) redirect('dashboard');
            Session::flash('error', 'Email atau password tidak valid.');
        }
        $this->renderAuth('auth/login');
    }
    public function logout(): void
    {
        Auth::logout(); redirect('login');
    }
}
