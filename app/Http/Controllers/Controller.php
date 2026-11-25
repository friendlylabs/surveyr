<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Controller
 *
 * Base controller. Properties set on a controller ($this->users = ...) collect in $data, which is handed to the view
 * (view('...', $this->data)) or merged into the JSON body by jsonSuccess / jsonError; `redirect` in that body is
 * followed by the frontend (public/assets/js/app.js submitForm).
 */
abstract class Controller
{
    /** Values set on the controller, for the view or the JSON body. */
    protected array $data = [];

    /** Collects an undeclared property into $data. */
    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    /** Reads a declared property, else the value collected in $data (null when unset). */
    public function __get(string $name): mixed
    {
        property_exists($this, $name) ?
            $data = $this->$name :
            $data = $this->data[$name] ?? null;

        return $data;
    }

    /**
     * Generate an error page response.
     *
     * This method returns a response object that renders an error page
     * view based on the provided HTTP status code. The view file should
     * be located in the "errors" directory and named according to the
     * status code (e.g., "404.blade.php" for a 404 error).
     *
     * @param  int  $code  The HTTP status code for the error page.
     * @return Response The response object containing the error page view.
     */
    protected function errorPage($code)
    {
        return response()->view(
            "errors.$code",
            $this->data,
            $code
        );
    }

    /**
     * Sends a JSON response indicating an error.
     *
     * The response body is { success: false, message } merged with every value set on the controller.
     *
     * @param  string  $message  The error message to include in the response.
     * @param  int  $code  The HTTP status code for the error response (default is 500).
     * @return JsonResponse The JSON response with the error data.
     */
    protected function jsonError($message, $code = 500)
    {
        $response = array_merge([
            'success' => false,
            'message' => $message,
        ], $this->data);

        return response()->json($response, $code);
    }

    /**
     * Sends a JSON response indicating a successful operation.
     *
     * The response body is { success: true, message } merged with every value set on the controller.
     *
     * @param  string  $message  The success message to include in the response.
     * @return JsonResponse The JSON response containing the success status and message.
     */
    protected function jsonSuccess($message)
    {
        $response = array_merge([
            'success' => true,
            'message' => $message,
        ], $this->data);

        return response()->json($response);
    }

    /**
     * Handles exceptions and returns a JSON response.
     *
     * This method sets a default error message and status, and optionally includes
     * debugging information (exception message, line, and file) if the application
     * is in debug mode.
     *
     * @param  \Exception  $e  The exception to handle.
     * @return JsonResponse A JSON response containing the error details.
     */
    protected function jsonException($e)
    {
        Log::error('Exception occurred', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        if (config('app.debug')) {
            $this->debug = [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];
        }

        $response = array_merge([
            'success' => false,
            'message' => 'An unexpected error occurred',
        ], $this->data);

        return response()->json($response, 500);
    }

    /**
     * Database exception evaluator to return a user-friendly error message.
     *
     * This method evaluates database-related exceptions and provides
     * user-friendly error messages based on the SQLSTATE error codes.
     *
     * @param  QueryException  $e  The database exception to evaluate.
     * @return JsonResponse
     */
    protected function dbExceptionEvaluator($e)
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $message = $e->errorInfo[2] ?? null;

        Log::error('Database exception occurred', [
            'sql_state' => $sqlState,
            'message' => $e->getMessage(),
            'error_info' => $e->errorInfo,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'sql' => $e->getSql() ?? null,
            'bindings' => $e->getBindings() ?? null,
        ]);

        if (! $message) {
            switch ($sqlState) {
                case '23000': // Integrity constraint violation
                    $message = 'This content violates a database constraint.';
                    break;
                case '22001': // String data, right truncation
                    $message = 'The data you are trying to save is too long.';
                    break;
                case '42000': // Syntax error or access violation
                    $message = 'There is an issue with the database query syntax or permissions.';
                    break;
                case 'HY000': // General error
                    $message = 'A general database error occurred. Please try again later.';
                    break;
                case '42S02': // Table or view not found
                    $message = 'The requested table or view does not exist in the database.';
                    break;
                case '42S22': // Column not found
                    $message = 'A required column is missing in the database.';
                    break;
                case '23001': // Restrict violation
                    $message = 'This operation violates a database restriction.';
                    break;
                default:
                    $message = 'An unexpected database error occurred. Please contact support.';
            }
        }

        if (config('app.debug')) {
            $this->exception = [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];
        }

        $response = array_merge([
            'success' => false,
            'message' => $message,
        ], $this->data);

        return response()->json($response, 500);
    }
}
