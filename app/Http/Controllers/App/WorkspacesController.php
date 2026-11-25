<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * WorkspacesController
 *
 * Switching between the workspaces the user belongs to (the account menu lists them while multiple workspaces are
 * on).
 */
class WorkspacesController extends Controller
{
    /** Makes the workspace current; a workspace the user isn't in is a 404, so its existence isn't given away. */
    public function switch(Request $request, Workspace $workspace): JsonResponse
    {
        abort_unless($request->user()->switchWorkspace($workspace), 404);

        $this->redirect = route('dashboard');

        return $this->jsonSuccess(__('Switched to :name', ['name' => $workspace->name]));
    }

    /** Registers the workspace routes. */
    public static function routes(): void
    {
        Route::post('/{workspace}/switch', [self::class, 'switch'])->name('workspaces.switch');
    }
}
