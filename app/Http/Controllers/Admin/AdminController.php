<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index(){
        if (!Auth::check()) {
            return redirect()->route('login.view');
        }

        return view('admin.dashboard');
    }

    public function logout(Request $request){
        Auth::logout();


        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.view');
    }
}
