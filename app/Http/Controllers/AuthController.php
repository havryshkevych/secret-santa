<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function telegramLogin(Request $request)
    {
        $auth_data = $request->all();
        $redirectUrl = $request->input('redirect', route('home'));

        if ($this->checkTelegramAuthorization($auth_data)) {
            $this->loginWithTelegramData($auth_data);

            return redirect($redirectUrl);
        }

        return redirect($redirectUrl)->withErrors(['auth' => 'Telegram authorization failed.']);
    }

    public function webAppLogin(Request $request)
    {
        $initData = $request->input('initData');
        if (! $initData) {
            return response()->json(['success' => false, 'message' => 'No initData provided.'], 400);
        }

        $auth_data = [];
        if ($this->checkWebAppAuthorization($initData, $auth_data)) {
            $user = $this->loginWithTelegramData($auth_data);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid initData.'], 401);
    }

    private function loginWithTelegramData($auth_data)
    {
        $username = isset($auth_data['username']) ? strtolower($auth_data['username']) : null;
        $isAdmin = $username && in_array($username, config('services.telegram.admin_usernames', []));

        $user = User::updateOrCreate(
            ['telegram_id' => $auth_data['id']],
            [
                'name' => ($auth_data['first_name'] ?? 'User').(isset($auth_data['last_name']) ? ' '.$auth_data['last_name'] : ''),
                'telegram_username' => $auth_data['username'] ?? null,
                'is_admin' => $isAdmin,
            ]
        );

        Auth::login($user, true);

        return $user;
    }

    private function checkWebAppAuthorization($initData, &$auth_data)
    {
        parse_str($initData, $data);
        if (! isset($data['hash'])) {
            return false;
        }

        $check_hash = $data['hash'];
        unset($data['hash']);

        $data_check_arr = [];
        foreach ($data as $key => $value) {
            $data_check_arr[] = $key.'='.$value;
        }
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);

        $bot_token = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
        $secret_key = hash_hmac('sha256', $bot_token, 'WebAppData', true);
        $hash = bin2hex(hash_hmac('sha256', $data_check_string, $secret_key, true));

        if (strcmp($hash, $check_hash) !== 0) {
            return false;
        }

        if (isset($data['user'])) {
            $user_data = json_decode($data['user'], true);
            $auth_data = [
                'id' => $user_data['id'],
                'first_name' => $user_data['first_name'] ?? '',
                'last_name' => $user_data['last_name'] ?? '',
                'username' => $user_data['username'] ?? null,
                'auth_date' => $data['auth_date'] ?? time(),
            ];
        }

        return true;
    }

    private function checkTelegramAuthorization($auth_data)
    {
        if (! isset($auth_data['hash'])) {
            return false;
        }

        $check_hash = $auth_data['hash'];

        // Remove non-Telegram fields from validation
        $telegram_data = $auth_data;
        unset($telegram_data['hash']);
        unset($telegram_data['redirect']); // Don't include redirect in hash validation

        $data_check_arr = [];
        foreach ($telegram_data as $key => $value) {
            $data_check_arr[] = $key.'='.$value;
        }
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);

        $bot_token = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
        $secret_key = hash('sha256', $bot_token, true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);

        if (strcmp($hash, $check_hash) !== 0) {
            return false;
        }

        if (isset($telegram_data['auth_date']) && (time() - $telegram_data['auth_date']) > 86400) {
            return false;
        }

        return true;
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->route('home');
    }
}
