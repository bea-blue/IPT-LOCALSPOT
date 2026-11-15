<?php
namespace App\Controllers;

use App\Support\Roles;
use SpotCore\Auth;
use SpotCore\Controller;
use SpotCore\Database;
use SpotCore\Request;
use SpotCore\Response;
use SpotCore\Session;
use SpotCore\Validator;

/**
 * One login page for everyone. Staff go to the staff panel, owners to their
 * shops, members back to Explore. Sign-up always creates a 'member' account:
 * only a developer can give someone a staff role.
 */
final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect(self::home());
        }
        return $this->view('auth/login', ['title' => 'Log in']);
    }

    public function login(Request $request): Response
    {
        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '' || !Auth::attempt($email, $password)) {
            // One generic message: it never reveals whether the email exists.
            Session::flash('error', 'Invalid email or password.');
            Session::flash('old', ['email' => $email]);
            return $this->redirect('/login');
        }

        $user = Auth::user();
        Session::flash('success', 'Welcome back, ' . $user['name'] . '!');
        return $this->redirect(self::home());
    }

    private static function home(): string
    {
        $role = Auth::role();
        return Roles::isStaff($role) ? '/admin' : ($role === 'owner' ? '/my/shops' : '/');
    }

    public function showRegister(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/');
        }
        return $this->view('auth/register', ['title' => 'Create an account']);
    }

    public function register(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/');
        }
        // Honeypot: real visitors never see or fill this field.
        if ($request->input('website', '') !== '') {
            return $this->redirect('/');
        }
        $data = $request->only(['name', 'email', 'password', 'password_confirmation']);
        $data['email'] = strtolower(trim((string) ($data['email'] ?? '')));
        $data['name'] = trim((string) ($data['name'] ?? ''));

        $validator = new Validator($data, [
            'name'                  => 'required|max:60',
            'email'                 => 'required|email|max:191|unique:users,email',
            'password'              => 'required|min:8|max:72',
            'password_confirmation' => 'required|same:password',
        ], ['name' => 'Name', 'email' => 'Email', 'password' => 'Password', 'password_confirmation' => 'Password confirmation']);

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            // Passwords are never flashed back into the form.
            Session::flash('old', ['name' => $data['name'], 'email' => $data['email']]);
            Session::flash('error', 'Please fix the highlighted fields.');
            return $this->redirect('/register');
        }

        $id = Database::insert('users', [
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role'          => 'member',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        Auth::login($id);
        Session::flash('success', 'Welcome to LocalSpot, ' . $data['name'] . '! You can now write reviews.');
        return $this->redirect('/');
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        Session::start();
        Session::flash('success', 'You have been logged out.');
        return $this->redirect('/');
    }
}
