<?php

namespace App\Http\Controllers;

use App\Helpers\Valet;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use App\Traits\JsonRespon;
use App\Master\LoginUser;
use App\Models\Master\KelompokTransaksi;
use App\Models\Master\Profile;
use Illuminate\Support\Facades\Hash;

class Controller extends BaseController
{
    use JsonRespon;
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    protected $kdProfile;
    protected $is_encrypt;

    protected $current_user = null;
    protected $skip_authentication;

    //result
    protected $transStatus = true;
    protected $transMessage = null;
    public function __construct($is_encrypt = false)
    {
        $this->is_encrypt = $is_encrypt;

        $this->kdProfile = session("kdProfile");
        if (empty($this->kdProfile)) {
            $this->kdProfile = Cache::get('kdProfile');
        }
    }

    public function respond($data, $status = 200, $message = "OK", $header = [])
    {
        try {

            $response = array(
                'metaData' => array(
                    "code" => $status,
                    "message" => $message,
                ),
                'response' => $data,
            );

            if (count($header) == 0) {
                $header['x-supported-by'] = env('APP_NAME');
            }

            if (session('skip_encrypt') == true) {
            } else {
                // if ($this->is_encrypt  == true) {
                //     $key = env('APP_NAME');
                //     $output = false;
                //     $encrypt_method = "AES-256-CBC";
                //     $string = \LZCompressor\LZString::compressToEncodedURIComponent(json_encode($data));
                //     $key =  hex2bin(hash('sha256', $key));
                //     $iv =  substr(hex2bin(hash('sha256', $key)), 0, 16);
                //     $output = openssl_encrypt($string, $encrypt_method, $key, OPENSSL_RAW_DATA, $iv);
                //     $outputenc = base64_encode($output);

                //     //  // decrypt
                //     // $output = openssl_decrypt(base64_decode($outputenc), $encrypt_method, $key, OPENSSL_RAW_DATA, $iv);
                //     // $xz = \LZCompressor\LZString::decompressFromEncodedURIComponent($output);

                //     // $result = json_decode($xz);
                //     // $res['bahan'] = $string1;
                //     // $res['encrypt'] = $outputenc;
                //     // $res['decrypt'] = $result;

                //     $response['response']  = $outputenc;
                // }
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . ' ' . $e->getLine());
        }
        // $reqs = session('request_json_'.$this->getUserId());

        // try {
        //     if (!empty($reqs)) {
        //      DB::connection('mongodb')->table('LogJSON')->where('id', $reqs['id'])->update([
        //         'response' => $response,
        //         'status' => $status
        //      ]);
               
        //     }
        // } catch (\Exception $ex) {
        // }
        return response()->json($response, $status, $header);
    }
    public function settingFix($namaField)
    {
        $Query = DB::table('settingdatafixed_m')
            ->where('namafield', '=', $namaField)
            ->where('statusenabled', '=', true);
        if ($this->kdProfile) {
            $Query->where('kdprofile', '=', $this->kdProfile);
        }
        $settingDataFixed = $Query->first();
        if (!empty($settingDataFixed)) {
            return $settingDataFixed->nilaifield;
        } else {
            return 0;
        }
    }
    public function kelompokTransaksi($namaField)
    {
        $Query = DB::table('kelompoktransaksi_m')
            ->where('kelompoktransaksi', '=', $namaField)
            ->where('statusenabled', '=', true);
        if ($this->kdProfile) {
            $Query->where('kdprofile', '=', $this->kdProfile);
        }
        $res = $Query->first();
        if (!empty($res)) {
            return $res->id;
        } else {
            $id = $this->SEQUENCE_MASTER(new KelompokTransaksi(), 'id', $this->kdProfile); //$this->Uuid4();
            $dataPS = new KelompokTransaksi();
            $dataPS->id = $id;
            $dataPS->kdprofile = $this->kdProfile;
            $dataPS->statusenabled = true;
            $dataPS->kelompoktransaksi =  $namaField;
            $dataPS->save();
            return $dataPS->id;
        }
    }
    public function profile()
    {
        return Profile::where('id', $this->kdProfile)->first();
    }
    public static function static_profile()
    {
        return Profile::where('id', session("kdProfile"))->first();
    }
    public function getUserId()
    {
        try {
            $cache = session('session_login');
            $response = $cache['userData']['id'];
        } catch (Exception $d) {
            $response = null;
        }
        return  $response;
    }
    public function getUsername()
    {
        try {
            $cache = session('session_login');
            $response = $cache['userData']['namauser'];
        } catch (Exception $d) {
            $response = null;
        }
        return  $response;
    }
    public function getPegawai()
    {
        try {
            $cache = session('session_login');
            $response = $cache['pegawai'];
        } catch (Exception $d) {
            $response = null;
        }
        return  $response;
    }
    public function getProfile()
    {
        try {
            $cache = session('session_login');
            $response = $cache['profile'];
        } catch (Exception $d) {
            $response = null;
        }
        return  $response;
    }

    public function getAge($tgllahir, $now)
    {
        $datetime = new \DateTime(date($tgllahir));
        return $datetime->diff(new \DateTime($now))
            ->format('%ythn %mbln %dhr');
    }
    public function Uuid4()
    {
        return Uuid::uuid4();
    }
    public function getNamaPegawai()
    {
        try {
            $cache = session('session_login');
            $response = $cache['pegawai']->namalengkap;
        } catch (Exception $d) {
            $response = null;
        }
        return  $response;
    }
    public function getPegawaiId()
    {
        try {
            $cache = session('session_login');
            $response = $cache['pegawai']->id;
        } catch (Exception $d) {
            $response = null;
        }
        return  $response;
    }
    public function getKelompokUserId()
    {
        try {
            $cache = session('session_login');
            $response = $cache['kelompokuser_id'];
        } catch (Exception $d) {
            $response = null;
        }
        return  $response;
    }
    public function now()
    {
        return date('Y-m-d H:i:s');
    }

    public function statusBayar($index)
    {
        $data = [
            'Belum Verifikasi',
            'Verifikasi',
            'Bayar Sebagian',
            'Lunas',
        ];
        return $data[$index];
    }
    public function getAgeYear($tgllahir, $now)
    {
        $datetime = new \DateTime(date($tgllahir));
        return $datetime->diff(new \DateTime($now))
            ->format('%y');
    }
    public function hashing_password($pass)
    {
        if (config('app.IS_PASSWORD_HASH')) {
            return Hash::make($pass);
        } else {
            return sha1($pass);
        }
    }
    public function respondV2($data, $status = 200, $message = "OK", $header = [])
    {
        return response()->json($data, $status, $header);
    }

    /**
     * Build a browser-facing URL for a file exposed by the same-origin web server.
     *
     * Runtime files live in shared public folders and are served by the frontend
     * Nginx container, so Laravel must not turn these paths into APP_URL-based
     * absolute URLs. Normalising separators before basename() also keeps Windows
     * paths stored by legacy data from escaping the configured public folder.
     */
    protected function publicFileUrl($folder, $file)
    {
        $folder = trim(str_replace('\\', '/', (string) $folder), '/');
        $segments = array_values(array_filter(explode('/', $folder), function ($segment) {
            return $segment !== '';
        }));

        if (empty($segments)) {
            throw new \InvalidArgumentException('Folder file publik tidak valid.');
        }

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..' || !preg_match('/^[A-Za-z0-9_-]+$/', $segment)) {
                throw new \InvalidArgumentException('Folder file publik tidak valid.');
            }
        }

        $filename = basename(str_replace('\\', '/', trim((string) $file)));
        if ($filename === '' || $filename === '.' || $filename === '..') {
            throw new \InvalidArgumentException('Nama file publik tidak valid.');
        }

        return '/' . implode('/', array_map('rawurlencode', $segments)) . '/' . rawurlencode($filename);
    }
}
