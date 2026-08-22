<?php
namespace App\Http\Controllers;
use App\Models\User;use Illuminate\Http\Request;use Illuminate\Support\Facades\Auth;use Inertia\Inertia;
class AuthController extends Controller {
 public function create(){return Inertia::render('Auth/Login');}
 public function store(Request $r){$credentials=$r->validate(['email'=>'required|email','password'=>'required']);if(!Auth::attempt($credentials,$r->boolean('remember')))return back()->withErrors(['email'=>'Those details do not match our records.'])->onlyInput('email');$r->session()->regenerate();return redirect()->route('dashboard');}
 public function register(){return Inertia::render('Auth/Register');}
 public function save(Request $r){$data=$r->validate(['name'=>'required|string|max:80','email'=>'required|email|unique:users','password'=>'required|min:8|confirmed','invite_code'=>'required','fpl_entry_id'=>'nullable|integer']);abort_unless(hash_equals((string)config('app.invite_code'),(string)$data['invite_code']),422,'Invalid invite code.');$user=User::create($data);Auth::login($user);return redirect()->route('dashboard');}
 public function destroy(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('login');}
}
