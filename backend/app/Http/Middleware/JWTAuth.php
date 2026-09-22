<?php

namespace App\Http\Middleware;

use App\Models\Master\Pegawai;
use App\Models\Master\Profile;
use App\Models\Standar\LoginUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Namshi\JOSE\JWS;
use App\Http\Controllers\Controller;
use App\Models\Master\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Webpatser\Uuid\Uuid;

class JWTAuth
{
    protected $userData;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $tokenForBridging = false;
        $usernameForBridging = 'NonBridging';
        $token = $request->cookie('token');

        if (!$token) {
            $token = $request->header('token');
        }

        session()->forget('skip_encrypt');
        $request['skip_encrypt'] = $request->header('skip_encrypt');
        session(['skip_encrypt' => $request['skip_encrypt']]);

        if (!$token) {
            $token = $request->header('x-token');
            $usernameForBridging = $request->header('x-username');
            $tokenForBridging = true;
        }

        if (!$token) {
            $token = $request->input('token');
            $tokenForBridging = false;
        }

        $kdProfile = null;
        if ($token) {
            $arr = explode('.', $token);
            if (count($arr) == 4) {
                $token = $arr[0] . '.' . $arr[1] . '.' . $arr[2];
                $kdProfile = base64_decode($arr[3]);
            }
            if (empty($kdProfile)) {
                $data = array(
                    "metaData" => array(
                        "message" => "Token Tidak Valid .",
                        "code" => $tokenForBridging ? 201 : 401
                    )
                );
                return response()->json($data, $data['metaData']['code']);
            }
            if (!$this->checkToken($token, $usernameForBridging, $kdProfile)) {
                $data = array(
                    "metaData" => array(
                        "message" => "Token Tidak Valid",
                        "code" => $tokenForBridging ? 201 : 401
                    )
                );
                return response()->json($data, $data['metaData']['code']);
            } else {
                if ($this->userData != null) {
                    $exp = date('Y-m-d H:i:s', strtotime($this->userData['exp']));
                    if ($request->header('iskiosk') || $request->input('iskiosk')) {
                    } else {
                        $now = date('Y-m-d H:i:s');
                    }
                    $userData = $this->userData;
                    $file_nameX = null;
                    $request->merge(compact('userData'));
                }
            }
        } else {
            $data = array(
                "metaData" => array(
                    "message" => "Token Tidak tersedia",
                    "code" => $tokenForBridging ? 201 : 401
                )
            );
            return response()->json($data, $data['metaData']['code']);
        }
        return $next($request);
    }
    protected function  checkToken($token, $usernameForBridging, $kdProfile = null)
    {
        try {
            $jws = JWS::load($token);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        if (!$jws->verify(config('app.JWT_KEY'), config('app.JWT_ALG'))) {
            return false;
        }

        $dataToken = (object)$jws->getPayload();
        $time['exp'] = date('Y-m-d H:i:s', $dataToken->exp);
        $time['now'] = date('Y-m-d H:i:s');

        $user = LoginUser::where('namauser',  $dataToken->sub)
            ->where('kdprofile', $kdProfile)
            ->where('statusenabled', true)
            ->first();

        $isUserLogin = false;

        if (!$user) {
            $user = User::where('email', $dataToken->sub)
                ->where('kdprofile', $kdProfile)
                ->where('statusenabled', true)
                ->first();
            $isUserLogin = true;
        }
        if (!$user) {
            return false;
        }

        if ($usernameForBridging != null && $usernameForBridging != 'NonBridging') {
            if ($dataToken->sub != $usernameForBridging) {
                return false;
            }
        }

        $filterUser = [
            'id' => $user->id,
            'namauser' => $isUserLogin ? $user->email : $user->namauser,
            'kdprofile' => $user->kdprofile,
            'exp' => $time['exp']
        ];
        $peg = null;
        if (!$isUserLogin) {
            $peg = Pegawai::where('id', $user->objectpegawaifk)
                ->where('kdprofile', $user->kdprofile)
                ->first();
        } else {
            $peg = $user;
        }
        $profile = Profile::where('id',  $user->kdprofile)->first();
        $this->setUserData($filterUser);

        session(['userData' => $this->getUserData()]);
        session(['pegawai' => $peg]);
        session(['profile' => $profile]);
        session(['kdProfile' =>  $user->kdprofile]);
        session(['kelompokuser_id' =>  $user->objectkelompokuserfk]);
        session(['session_login' =>  array(
            'userData' => $this->getUserData(),
            'pegawai' => $peg,
            'kelompokuser_id' => $user->objectkelompokuserfk,
            'profile' => $profile
        )]);
        Cache::put('session_login', array(
            'userData' => $this->getUserData(),
            'pegawai' => $peg,
            'kelompokuser_id' => $user->objectkelompokuserfk,
            'profile' => $profile
        ));
        Cache::put('kdProfile',  $user->kdprofile);
        return true;
    }


    protected function setUserData($data)
    {
        $this->userData = $data;
    }

    protected function getUserData()
    {
        return $this->userData;
    }
    protected function getKdProfile()
    {
        return  session('kdProfile');
    }
}
