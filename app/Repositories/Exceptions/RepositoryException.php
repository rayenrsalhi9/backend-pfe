<?php
namespace App\Repositories\Exceptions;

use Exception;

class RepositoryException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        $code = $this->getCode();
        if ($code < 100 || $code >= 600) {
            $code = 500;
        }
        return response()->json([
            'message' => $this->getMessage()
        ], $code);
    }
}
