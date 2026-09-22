<?php

namespace App\Exceptions;

use BadMethodCallException;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use InvalidArgumentException;
use Mockery\Exception\InvalidOrderException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->renderable(function (NotFoundHttpException $e, $request) {
            $header['x-supported-by'] = env('APP_NAME');

            $response = array(
                'metaData' => array(
                    "code" => $e->getStatusCode(),
                    "message" => $e->getMessage() != '' ? $e->getMessage() :  'Not Found Http Exception'
                ),
                'response' => null,
            );
            return response()->json($response, $e->getStatusCode(), $header);
        });
        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            $header['x-supported-by'] = env('APP_NAME');
   
            $response = array(
                'metaData' => array(
                    "code" => $e->getStatusCode(),
                    "message" =>  $e->getMessage() != '' ? $e->getMessage() : 'Method Not Allowed'
                ),
                'response' => null,
            );
            return response()->json($response, $e->getStatusCode(), $header);
        });
        $this->renderable(function (BadMethodCallException $e, $request) {
            $header['x-supported-by'] = env('APP_NAME');
           
            $response = array(
                'metaData' => array(
                    "code" => 400,
                    "message" =>  $e->getMessage() != '' ? $e->getMessage() : 'Bad Method Call Exception'
                ),
                'response' => null,
            );
            return response()->json($response, 400, $header);
        });
        // $this->renderable(function (InvalidArgumentException  $e, $request) {
        //     $header['x-supported-by'] = env('APP_NAME');
   
        //     $response = array(
        //         'metaData' => array(
        //             "code" => $e->getStatusCode(),
        //             "message" =>  $e->getMessage() != '' ? $e->getMessage() : 'Method Not Allowed'
        //         ),
        //         'response' => null,
        //     );
        //     return response()->json($response, $e->getStatusCode(), $header);
        // });

    }
}
