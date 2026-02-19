<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    public function __construct(
        private readonly RbacService $rbacService
    ) {}

    /**
     * GET /api/v1/roles
     * List all roles for the current account.
     */
    public function index(Request $request): JsonResponse
    {
        $roles = $this->rbacService->listRoles($request->user()->account_id);

        return response()->json([
            'success' => true,
            'data'    => RoleResource::collection($roles),
        ]);
    }

    /**
     * GET /api/v1/roles/{id}
     * Show a specific role with its permissions.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $role = \App\Models\Role::withoutGlobalScopes()
            ->where('account_id', $request->user()->account_id)
            ->where('id', $id)
            ->with(['permissions', 'users'])
            ->withCount('users')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => new RoleResource($role),
        ]);
    }

    /**
     * POST /api/v1/roles
     * Create a new custom role.
     */
    public function store(CreateRoleRequest $request): JsonResponse
    {
        $role = $this->rbacService->createRole(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الدور بنجاح.',
            'data'    => new RoleResource($role),
        ], Response::HTTP_CREATED);
    }

    /**
     * POST /api/v1/roles/from-template
     * Create a role from a predefined template.
     */
    public function createFromTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'template' => ['required', 'string', 'max:50'],
            'name'     => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_-]+$/'],
        ]);

        $role = $this->rbacService->createFromTemplate(
            $request->input('template'),
            $request->user(),
            $request->input('name')
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الدور من القالب بنجاح.',
            'data'    => new RoleResource($role),
        ], Response::HTTP_CREATED);
    }

    /**
     * PUT /api/v1/roles/{id}
     * Update a role's info and/or permissions.
     */
    public function update(UpdateRoleRequest $request, string $id): JsonResponse
    {
        $role = $this->rbacService->updateRole(
            $id,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الدور بنجاح.',
            'data'    => new RoleResource($role),
        ]);
    }

    /**
     * DELETE /api/v1/roles/{id}
     * Delete a custom role.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->rbacService->deleteRole($id, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الدور بنجاح.',
        ]);
    }

    /**
     * POST /api/v1/roles/{roleId}/assign/{userId}
     * Assign a role to a user.
     */
    public function assignToUser(Request $request, string $roleId, string $userId): JsonResponse
    {
        $user = $this->rbacService->assignRoleToUser($userId, $roleId, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين الدور للمستخدم بنجاح.',
            'data'    => [
                'user_id' => $user->id,
                'roles'   => RoleResource::collection($user->roles),
            ],
        ]);
    }

    /**
     * DELETE /api/v1/roles/{roleId}/revoke/{userId}
     * Revoke a role from a user.
     */
    public function revokeFromUser(Request $request, string $roleId, string $userId): JsonResponse
    {
        $user = $this->rbacService->revokeRoleFromUser($userId, $roleId, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'تم سحب الدور من المستخدم بنجاح.',
            'data'    => [
                'user_id' => $user->id,
                'roles'   => RoleResource::collection($user->roles),
            ],
        ]);
    }

    /**
     * GET /api/v1/permissions
     * Get the full permissions catalog (grouped).
     */
    public function permissionsCatalog(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->rbacService->getPermissionsCatalog(),
        ]);
    }

    /**
     * GET /api/v1/roles/templates
     * Get available role templates.
     */
    public function templates(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->rbacService->getTemplates(),
        ]);
    }

    /**
     * GET /api/v1/users/{id}/permissions
     * Get effective permissions for a specific user.
     */
    public function userPermissions(Request $request, string $userId): JsonResponse
    {
        $user = \App\Models\User::withoutGlobalScopes()
            ->where('account_id', $request->user()->account_id)
            ->where('id', $userId)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => [
                'user_id'     => $user->id,
                'is_owner'    => $user->is_owner,
                'permissions' => $user->allPermissions(),
            ],
        ]);
    }
}
