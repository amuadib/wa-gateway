<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// use Illuminate\Support\Facades\Log;

class AcceptMessageController extends Controller
{
    function acceptMessage(Request $request)
    {
        $data = $request->all();
        if (!isset($data['token'])) {
            return [
                'error' => true,
                'message' => 'Token not found'
            ];
        }
        if (!$this->isValidToken($data['token'])) {
            return [
                'error' => true,
                'message' => 'Invalid Token'
            ];
        }

        if (isset($data['data']) && is_array(json_decode($data['data'], true))) {
            foreach (json_decode($data['data'], true) as $d) {
                $inserts[] = [
                    'token' => $data['token'],
                    'number' => $this->sanitizeNumber($d['number']),
                    'name' => $d['name'],
                    'message' => $d['message'],
                    'sessionId' => $d['sessionId'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }

            \App\Models\Message::insert($inserts);
        } else {
            if (!isset($data['number']) or $data['number'] == '') {
                return [
                    'error' => true,
                    'message' => 'Invalid Number'
                ];
            }
            if (!isset($data['message']) or $data['message'] == '') {
                return [
                    'error' => true,
                    'message' => 'Invalid Message'
                ];
            }
            $data['number'] = $this->sanitizeNumber($data['number']);
            if ($data['number'] == '') {
                return [
                    'error' => true,
                    'message' => 'Invalid Number'
                ];
            }
            \App\Models\Message::create($data);
        }

        return [
            'error' => false,
            'message' => 'Success'
        ];
    }

    private function sanitizeNumber($number)
    {
        if ($number[0] == '+') {
            return $number;
        }
        $clean_number = preg_replace("/^62/", '', preg_replace("/^0/", '', preg_replace("/[^0-9]/", '', $number)));
        if (strlen($clean_number) > 13) {
            return '';
        }

        if ($clean_number[0] != '8') {
            return '';
        }

        return $clean_number;
    }
    private function isValidToken(String $token)
    {
        return in_array(
            $token,
            \Cache::get('valid_token', function () {
                $tokens = \App\Models\Token::pluck('token')->toArray();
                if (!\Cache::has('valid_token')) {
                    \Cache::put('valid_token', $tokens, 3600);
                }
                return $tokens;
            })
        );
    }
}
