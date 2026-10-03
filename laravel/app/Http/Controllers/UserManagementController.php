<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Services\Admin\AdminRoleService;
use App\Services\Cognito\CognitoAuthException;
use App\Services\Users\UserAdminService;
use App\Services\Users\UserCsvExporter;
use App\Support\OrganizationName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $name = $this->queryString($request, 'name');
        $organization = $this->queryString($request, 'organization');
        $farmName = $this->queryString($request, 'farm_name');
        $cognitoSub = $this->queryString($request, 'cognito_sub');
        $role = $request->query('role') === AppUser::ROLE_ADMIN ? AppUser::ROLE_ADMIN : '';

        $query = AppUser::query();
        $this->whereLike($query, 'name', $name);
        if ($organization !== '') {
            $this->whereLike($query, 'organization', OrganizationName::normalize($organization) ?? $organization);
        }
        $this->whereLike($query, 'cognito_sub', $cognitoSub);
        if ($farmName !== '') {
            $query->whereHas('farms', function (Builder $farms) use ($farmName): void {
                $this->whereLike($farms, 'farm_name', $farmName);
            });
        }
        if ($role !== '') {
            $query->where('role', $role);
        }

        $users = $query->orderByDesc('id')->paginate(20);

        return view('user_management.index', [
            'users' => $users,
            'filters' => [
                'name' => $name,
                'organization' => $organization,
                'farm_name' => $farmName,
                'cognito_sub' => $cognitoSub,
                'role' => $role,
            ],
        ]);
    }

    public function show(Request $request, AppUser $user): View|JsonResponse
    {
        $payload = $this->present($user, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['data' => $payload]);
        }

        return view('user_management.show', [
            'user' => $user,
            'roleEvents' => $user->adminRoleEvents()->with('actor')->limit(50)->get(),
        ]);
    }

    public function update(Request $request, AppUser $user, UserAdminService $admin): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255'],
        ], [], [
            'name' => 'ユーザー名',
            'email' => 'メールアドレス',
        ]);

        if (! isset($validated['name']) && ! isset($validated['email'])) {
            throw ValidationException::withMessages(['name' => '変更する項目を入力してください。']);
        }

        try {
            if (isset($validated['name']) && $validated['name'] !== $user->name) {
                $admin->updateName($user, $validated['name']);
            }
            if (isset($validated['email']) && strcasecmp($validated['email'], (string) $user->email) !== 0) {
                $admin->updateEmail($user->fresh() ?? $user, $validated['email']);
            }
        } catch (CognitoAuthException $e) {
            $field = isset($validated['email']) && ! isset($validated['name']) ? 'email' : 'name';
            throw ValidationException::withMessages([
                $field => $this->cognitoMessage($e),
            ]);
        }

        $user->refresh();
        $message = '保存しました。';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => $this->present($user, $request->user()),
            ]);
        }

        return redirect()->route('user-management.show', $user)->with('success', $message);
    }

    public function export(Request $request, UserCsvExporter $exporter): StreamedResponse|RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['integer'],
        ], [
            'user_ids.required' => 'ダウンロードするユーザーを選択してください。',
        ]);

        $users = AppUser::query()
            ->whereIn('id', array_map('intval', $validated['user_ids']))
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            return redirect()
                ->route('user-management.index', $this->filters($request))
                ->with('error', 'ダウンロードするユーザーを選択してください。');
        }

        $fileName = 'users_'.now(config('measurements.display_timezone'))->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($exporter, $users): void {
            $out = fopen('php://output', 'w');
            try {
                $exporter->write($out, $users);
            } finally {
                fclose($out);
            }
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroySelected(Request $request, UserAdminService $admin): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['integer'],
        ], [
            'user_ids.required' => '削除するユーザーを選択してください。',
        ]);

        $actor = $request->user();
        $ids = array_map('intval', $validated['user_ids']);
        $includesSelf = in_array((int) $actor->id, $ids, true);

        try {
            $deleted = $admin->deleteMany($actor, $ids);
        } catch (CognitoAuthException $e) {
            throw ValidationException::withMessages([
                'account' => $this->cognitoMessage($e),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        if ($deleted === 0 && $includesSelf) {
            return redirect()
                ->route('user-management.index', $this->filters($request))
                ->withErrors(['account' => '自分自身は削除できません。']);
        }

        $message = "{$deleted}件のユーザーを削除しました。";
        if ($includesSelf) {
            $message .= '自分自身は削除していません。';
        }

        return redirect()
            ->route('user-management.index', $this->filters($request))
            ->with('success', $message);
    }

    /**
     * 管理者を一般ユーザーに戻す。自分自身は外せない。
     */
    public function revokeAdmin(Request $request, AppUser $user, AdminRoleService $adminRoles): RedirectResponse
    {
        if ($user->is($request->user())) {
            return redirect()->route('user-management.show', $user)
                ->withErrors(['role' => '自分自身の管理者権限は外せません。']);
        }

        if (! $user->isAdmin()) {
            return redirect()->route('user-management.show', $user);
        }

        $adminRoles->revoke($user, $request->user(), [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('user-management.show', $user)
            ->with('success', '一般ユーザーに戻しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(AppUser $user, AppUser $actor): array
    {
        $events = $user->adminRoleEvents()->with('actor')->limit(50)->get();

        return [
            'id' => $user->id,
            'cognito_sub' => $user->cognito_sub,
            'name' => $user->name,
            'email' => $user->email,
            'organization' => $user->organization,
            'role' => $user->role,
            'role_label' => $user->isAdmin() ? '管理者' : '一般ユーザー',
            'is_self' => $user->is($actor),
            'can_revoke' => $user->isAdmin() && ! $user->is($actor),
            'created_at' => $user->created_at?->copy()->timezone(config('measurements.display_timezone'))->format('Y-m-d H:i'),
            'events' => $events->map(fn ($event) => [
                'at' => $event->created_at?->timezone(config('measurements.display_timezone'))->format('Y-m-d H:i'),
                'label' => $event->actionLabel(),
                'actor' => $event->actor?->name ?? 'サーバー上のコマンド',
                'ip' => $event->ip_address,
            ])->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function filters(Request $request): array
    {
        return array_filter([
            'name' => $this->queryString($request, 'name'),
            'organization' => $this->queryString($request, 'organization'),
            'farm_name' => $this->queryString($request, 'farm_name'),
            'cognito_sub' => $this->queryString($request, 'cognito_sub'),
            'role' => $request->input('role') === AppUser::ROLE_ADMIN ? AppUser::ROLE_ADMIN : '',
        ], fn ($value) => $value !== '');
    }

    private function queryString(Request $request, string $key): string
    {
        $value = $request->input($key);

        return is_string($value) ? trim($value) : '';
    }

    private function whereLike(Builder $query, string $column, string $value): void
    {
        if ($value === '') {
            return;
        }

        $escaped = addcslashes($value, '\\%_');
        $query->where($column, 'like', '%'.$escaped.'%');
    }

    private function cognitoMessage(CognitoAuthException $e): string
    {
        return match ($e->errorCode) {
            CognitoAuthException::ALIAS_EXISTS => 'このメールアドレスは既に使われています。',
            CognitoAuthException::USER_NOT_FOUND => 'Cognito 上のユーザーが見つからないため、変更できません。',
            CognitoAuthException::NOT_CONFIGURED => $e->userMessage(),
            default => '保存できませんでした。しばらくしてから再度お試しください。',
        };
    }
}
