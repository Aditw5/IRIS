<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webpatser\Uuid\Uuid;
class LogRequestsAndResponses
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
       
        //   // Log the request
        //   Log::info('Request:', [
        //     'user' =>  session('userData'),
        //     'url'     => $request->fullUrl(),
        //     'method'  => $request->method(),
        //     'headers' => $request->header(),
        //     'input'   => $request->all(),
        // ]);

        // Get the response
        $response = $next($request);

        // // Log the response
        // Log::info('Response:', [
        //     'status'  => $response->status(),
        //     'headers' => $response->headers->all(),
        //     'content' => $response->content(),
        // ]);

     
        try {
            if($response->status() != 200){
                if ($request->header('iskiosk') || $request->input('iskiosk')) {} else {
                    $method = $request->method();
                    if ($method == 'POST' || $method == 'PUT' || $method == 'DELETE') {
                        //delete file 14 hari ke belakang
                        $file_nameX['id'] =  substr(Uuid::generate(), 0, 32);
                        $cekDATA =  DB::connection('mongodb')->table('LogJSON_ERROR')
                            ->where(
                                'date',
                                '<',
                                Carbon::now()->subDay(3)->format('Y-m-d H:i:s')
                            )->delete();
                        // $cekDATA =  DB::table('log_json')
                        // ->where(
                        //     'date',
                        //     '<',
                        //     Carbon::now()->subMonth(2)->startOfMonth()->format('Y-m-d H:i:s')
                        // )->delete();

                        $file_nameX['method'] = $method;
                        $file_nameX['api'] =  $request->url();

                        $file_nameX['status'] =  $response->status();
                        $file_nameX['body'] = $request->input(); //json_encode($request->input());
                        $file_nameX['date'] = date("Y-m-d H:i:s");
                        $file_nameX['response'] = json_decode($response->content());
                        $file_nameX['userfk'] = session('userData')['id'];
                        $file_nameX['namauser'] = session('userData')['namauser'];
                        $file_nameX['header'] =  $request->header(); // json_encode($request->header());
                        $file_nameX['host'] =   getHostByName(getHostName());
                        $file_nameX['pc_name'] =  gethostname();
                        // dd($request->header('host'));
                        $insert = [
                            $file_nameX
                        ];
                        // DB::table('log_json')->insert($insert);
                        DB::connection('mongodb')->table('LogJSON_ERROR')->insert($insert);
                    }
                }
            }
            // DB::connection('mongodb')->table('LogJSON')->where('id',  $file_nameX['id'])->update([
            //     'response' => json_decode($response->content()),
            //     'status' => $response->status()
            // ]);
        }catch(Exception $e){

        }
        return $response;
    }
}
