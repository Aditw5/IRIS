<?php

namespace App\Providers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Request::macro('subdomain', function () {
            $domain = $this->getHost();
            $array = explode(".", $domain);
            return current($array);
        });

        Request::macro('domain', function () {
            $domain = $this->getHost();
            $array = explode(".", $domain);
            return (array_key_exists(count($array) - 2, $array)?$array[count($array) - 2] : "")
                .".".
                $array[count($array) - 1];
        });

        /////////////////////// RESPONSE DOWNLOAD //////////////////

        Response::macro('fShow', function ($namaFile, $storage = 'local') {
   
            $localDisk = empty(config('app.s3telkom'));

            if ($localDisk) {
         
                $disk = Storage::disk($storage);
            } else {
                $disk = Storage::disk('s3'.$storage);
            }

            if (!$disk->exists($namaFile)) {
                abort(404);
            }

            $file = $disk->read($namaFile);

            $type = $disk->mimeType($namaFile);

            $headers = [
                'Content-type' => $type
            ];

            return Response::make($file, 200, $headers);
        });
        Response::macro('fShowPdf', function ($namaFile, $namaFileDownload, $storage = 'local') {

            $localDisk = empty(config('app.s3telkom'));

            $storageName = ($localDisk)?$storage:'s3'.$storage;
            $disk = Storage::disk($storageName);

            if (!$disk->exists($namaFile)) {
                abort(404);
            }

            $file = $disk->read($namaFile);
    
            $headers = [
                'Content-type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$namaFileDownload.'"',
            ];
    
            return Response::make($file, 200, $headers);
        });

        Response::macro('fPreviewPDF', function ($namaFile, $norec, $storage = 'local') {

            $localDisk = empty(config('app.s3telkom'));

            $storageName = ($localDisk)?$storage:'s3'.$storage;
            $disk = Storage::disk($storageName);

            if (!$disk->exists($namaFile)) {
                abort(404);
            }

            $file = $disk->read($namaFile);

            $b64Doc = chunk_split(base64_encode($file));
            $sourcePdf = "data:application/pdf;base64,".$b64Doc;
            $sk = DB::table('struksurat_t as sk')->where('norec', $norec)->first();
            return view('report.preview-pdf', compact('sourcePdf', 'sk'));
        });
        
        Response::macro('fDownload', function (
            $namaFile,
            $namaFileDownload,
            $storage = 'local'
        ) {
            
            $localDisk = empty(config('app.s3telkom'));

            $storageName = ($localDisk)?$storage:'s3'.$storage;
            $disk = Storage::disk($storageName);

            if (!$disk->exists($namaFile)) {
                abort(404);
            }

            $file = $disk->read($namaFile);

            $type = $disk->mimeType($namaFile);
    
            $headers = [
                'Content-type'        => $type,
                'Content-Disposition' => 'attachment; filename="'.$namaFileDownload.'"',
            ];
    
            return Response::make($file, 200, $headers);
        });

        /////////////////////// RESPONSE DOWNLOAD //////////////////

        Request::macro('fPut', function ($namaFile, $content, $storage = 'local') {

            $localDisk = empty(config('app.s3telkom'));
            $storageName = ($localDisk)?$storage:'s3'.$storage;
            Storage::disk($storageName)->put($namaFile, $content);
            
        });

        Request::macro('fSaveMove', function (
            $formFile,
            $fileLama,
            $path,
            $namaFileBaru,
            $storage = 'local',
            $tambahExt = false
        ) {

            $localDisk = empty(config('app.s3telkom'));

            $storageName = ($localDisk)?$storage:'s3'.$storage;
            $disk = Storage::disk($storageName);
            
            if (empty($formFile)) {
                $extension = ($tambahExt)?pathinfo($fileLama, PATHINFO_EXTENSION):'';

                if ($disk->exists($fileLama)) {
                    $disk->move($fileLama, $path.$namaFileBaru.'.'.$extension);
                }
            } else {
                $extension = ($tambahExt)?$formFile->getClientOriginalExtension():'';

                $fileUpload = File::get($formFile->getRealPath());
                $disk->put($path.$namaFileBaru.'.'.$extension, $fileUpload);
            }

            return $path.$namaFileBaru.'.'.$extension;

        });
    }
}
