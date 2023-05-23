<?php

namespace App\Http\Controllers;

use App\Firm;
use App\FirmPackage;
use App\FirmRolePermission;
use App\FirmUser;
use App\FirmUserOther;
use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\PackageRoleResource;
use App\Http\Resources\PackageWisePermissionsResource;
use App\Http\Resources\ProfileResource;
use App\Mail\UserRegister;
use App\Mail\UserRegisterAdmin;
use App\Mail\UserRegisterAdminOtherIntegration;
use App\PackageRole;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login() {
        return view('auth.login');
    }
    public function doLogin(Request $request) {
        $credentials = $request->only('email', 'password');
        if (Auth::guard("users")->attempt($credentials)) {
            HelperLibrary::logActivity('User Login');
            return $request->filled("redirect_to") ? redirect($request->redirect_to) : redirect()->intended('/');
        } elseif (Auth::guard("firm_users")->attempt($credentials)) {
            HelperLibrary::logActivity('User Login');
            return $request->filled("redirect_to") ? redirect($request->redirect_to) : redirect()->intended('/');
        } else {
            return redirect(route("login"))
                ->with("msg-type", "danger")
                ->with("msg", "Password didn't Matched.");
        }
    }
    public function logout() {
        if (Auth::guard("firm_users")->check() || Auth::guard("users")->check()) {
            HelperLibrary::logActivity('User Logout');
            Auth::guard("firm_users")->logout();
            Auth::guard("users")->logout();
        }
        return redirect(route("login"))
            ->with("msg-type", "success")
            ->with("msg", "Successfully Logout.");
    }
    public function profile() {
        if (Auth::guard("firm_users")->check()) {
            $user = FirmUser::find(Auth::guard("firm_users")->user()->id);
        } elseif (Auth::guard("users")->check()) {
            $user = User::find(Auth::guard("users")->user()->id);
        }
        return new ProfileResource($user);
    }
    public function profileSave(Request $request) {
        if (Auth::guard("firm_users")->check()) {
            $user = FirmUser::find(Auth::guard("firm_users")->user()->id);
        } elseif (Auth::guard("users")->check()) {
            $user = User::find(Auth::guard("users")->user()->id);
        }
        $user->display_name = $request->display_name;
        $user->first_name = $request->first_name;
        $user->middle_name = $request->middle_name;
        $user->last_name = $request->last_name;
        $user->save();
        return response()->json([
            "success" => true,
            "data" => [
                "display_name" => $user->display_name,
                "first_name" => $user->first_name,
                "middle_name" => $user->middle_name,
                "last_name" => $user->last_name
            ]
        ]);
    }
    public function profilePasswordSave(Request $request) {
        if (Auth::guard("firm_users")->check()) {
            $user = FirmUser::find(Auth::guard("firm_users")->user()->id);
        } elseif (Auth::guard("users")->check()) {
            $user = User::find(Auth::guard("users")->user()->id);
        }
        $user->password = Hash::make($request->pass);
        $user->save();
        return response()->json([
            "success" => true
        ]);
    }
    public function register() {
        return view('auth.register');
    }
    public function doRegister(Request $request) {
        $captcha = false;
        if (isset($_POST['g-recaptcha-response'])) {
            $captcha=$_POST['g-recaptcha-response'];
        }
        $secretKey = "6LclCZkUAAAAAJoudhpN9pKPM_O3OaOpAafrpKx9";
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) .  '&response=' . urlencode($captcha);
        $response = file_get_contents($url);
        $responseKeys = json_decode($response,true);
        $check = FirmUser::where("email", $request->email);
        if ($check->count() == 0 && $captcha && $responseKeys["success"]) {
            if ($request->password == $request->cpassword) {
                if ($request->integration == "other") {
                    $row = new FirmUserOther();
                    $row->company_name = $request->company_name;
                    $row->first_name = $request->first_name;
                    $row->last_name = $request->last_name;
                    $row->email = $request->email;
                    $row->password = $request->password;
                    $row->address_1 = "Blank";
                    $row->address_2 = "Blank";
                    $row->city = "Blank";
                    $row->state = "Blank";
                    $row->zipcode = "Blank";
                    $row->contact = "Blank";
                    $row->package = "Standard Package";
                    $row->integration_name = $request->integration_name;
                    $row->save();
                    Mail::to("info@firmtrak.com")->queue(new UserRegisterAdminOtherIntegration($row));
                    return redirect()->route("register")
                        ->with("msg-type", "success")
                        ->with("msg", "Successfully registered. We will contact you shortly.");
                } else {
                    $firm = new Firm;
                    $firm->name = $request->company_name;
                    $firm->integration = $request->integration;
                    $firm->package = 'foundation_plus';
                    $firm->trial_ends_at = now()->addDays(HelperLibrary::getSettings(["trial_period"])->trial_period + 1);
                    $user = new FirmUser;
                    $user->firm_id = 0;
                    $user->first_name = $request->first_name;
                    $user->last_name = $request->last_name;
                    $user->display_name = $user->last_name . ", " . $user->first_name;
                    $user->email = $request->email;
                    $user->password = Hash::make($request->password);
                    $user->firm_role_id = 0;
                    $firm->save();
                    $user->save();
                    $firm->users()->save($user);
                    $firm->createIntegration();
                    $firm->makeDefaultDefinitions();
                    try {
                        Mail::to($user->email)->queue(new UserRegister($user));
                        Mail::to("info@firmtrak.com")->queue(new UserRegisterAdmin($user));
                    } catch(\Exception $e){
                        Log::error("Email sent Error AuthController@register");
                    }
                    return redirect()->route("login")
                        ->with("msg-type", "success")
                        ->with("msg", "Successfully registered. Please login now.");
                }
            } else {
                return redirect()->route("register")
                    ->with("msg-type", "danger")
                    ->with("msg", "Password Didn't Match. Please try again.");
            }
        } else if(!$captcha) {
            return redirect()->route("register")
            ->with("msg-type", "danger")
            ->with("msg", "Please Check Captcha");
        }else if (!$responseKeys["success"]){
            return redirect()->route("register")
                ->with("msg-type", "danger")
                ->with("msg", "Invalid Captcha");
        }else {
            return redirect()->route("register")
                ->with("msg-type", "danger")
                ->with("msg", "Email already exists.");
        }
    }
    public function packagePermission() {
        $firm_id = HelperLibrary::getFirmID();
        $role_id = HelperLibrary::getLoggedInUser()->firm_role_id;
        if ($role_id==0) {
            $data = HelperLibrary::getFirmAdminPermissions();
        } else {
            $data = FirmRolePermission::where("firm_id", $firm_id)->where("firm_role_id", $role_id)
                ->where("is_allowed", true)->get();
        }
        return PackageWisePermissionsResource::collection($data);
    }
    public function test() {
        return "Its Working";
    }
}
