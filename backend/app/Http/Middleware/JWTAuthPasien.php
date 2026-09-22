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
use App\Models\Master\Pasien;
use App\Models\Standar\LoginPasien;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Webpatser\Uuid\Uuid;

class JWTAuthPasien
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
        $token =  $request->header('token');
        session()->forget('skip_encrypt');
        $request['skip_encrypt'] = $request->header('skip_encrypt');
        session(['skip_encrypt' => $request['skip_encrypt']]);

        if (!$token) {
            $token =  $request->header('x-token');
            $usernameForBridging = $request->header('x-username');
            $tokenForBridging = true;
            if (!$token) {
                $token =  $request->input('token');
                $tokenForBridging = false;
            }
        }

        if ($token) {
            $arr = explode('.', $token);
            if (count($arr) == 4) {
                $token = $arr[0] . '.' . $arr[1] . '.' . $arr[2];
                $kdProfile = base64_decode($arr[3]);
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

                    $now = date('Y-m-d H:i:s');
                    if (!($exp >= $now)) {
                        $data = array(
                            "metaData" => array(
                                "message" => "Token Expired",
                                "code" => $tokenForBridging ? 201 : 401
                            )
                        );
                        return response()->json($data, $data['metaData']['code']);
                    }

                    $userData = $this->userData;
                    $file_nameX = null;

                    try {
                        $method = $request->method();
                        if ($method == 'POST' || $method == 'PUT' || $method == 'DELETE') {
                            //delete file 2 bulan ke belakang
                            $cekDATA =  DB::connection('mongodb')->table('LogJSON')
                                ->where(
                                    'date',
                                    '<',
                                    Carbon::now()->subMonth(2)->startOfMonth()->format('Y-m-d H:i:s')
                                )->delete();
                            // $cekDATA =  DB::table('log_json')
                                // ->where(
                                //     'date',
                                //     '<',
                                //     Carbon::now()->subMonth(2)->startOfMonth()->format('Y-m-d H:i:s')
                                // )->delete();

                            $file_nameX['method'] = $method;
                            $file_nameX['api'] =  $request->url();
                            $file_nameX['id'] =  substr(Uuid::generate(), 0, 32);
                            $file_nameX['status'] = null;
                            $file_nameX['body'] = $request->input();//json_encode($request->input());
                            $file_nameX['date'] = date("Y-m-d H:i:s");
                            $file_nameX['response'] = null;
                            $file_nameX['userfk'] = $userData['id'];
                            $file_nameX['namauser'] = $userData['namauser'];
                            $file_nameX['header'] =  $request->header();// json_encode($request->header());
                            $file_nameX['host'] =   getHostByName(getHostName());
                            $file_nameX['pc_name'] =  gethostname();
                            $insert = [
                                $file_nameX
                            ];
                            // DB::table('log_json')->insert($insert);
                            DB::connection('mongodb')->table('LogJSON')->insert($insert);
                        }
                    } catch (\Exception $ex) {
                        dd($ex);
                    }
                    if ($file_nameX != null) {
                        $userData['request_json'] = $file_nameX;
                        Cache::put('request_json', $file_nameX);
                    }
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
        $time['exp'] = date('Y-m-d H:i:s', $dataToken->exp );
        $time['now'] = date('Y-m-d H:i:s');

        $user = Pasien::where('nocm',  $dataToken->sub)
               ->where('kdprofile', $kdProfile)->first();
        if (!$user) {
            return false;
        }

        if ($usernameForBridging != null && $usernameForBridging != 'NonBridging') {
            if ($dataToken->sub != $usernameForBridging) {
                return false;
            }
        }

        $filterUser = array(
            'id'    => $user->id,
            "namauser" => $user->namauser,
            "kdprofile" => $user->kdprofile,
            "exp" => $time['exp']
        );
        $peg = Pasien::where('id',  $user->nocmfk)->where('kdprofile', $user->kdprofile)->first();
        $profile = Profile::where('id',  $user->kdprofile)->first();
        $this->setUserData($filterUser);

        session(['userData' => $this->getUserData()]);
        session(['pasien' => $peg]);
        session(['profile' => $profile]);
        session(['kdProfile' =>  $user->kdprofile]);
        session(['session_login' =>  array(
            'userData' => $this->getUserData(),
            'pegawai' => $peg,
            'profile' => $profile
        )]);
        Cache::put('session_login', array(
            'userData' => $this->getUserData(),
            'pasien' => $peg,
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
