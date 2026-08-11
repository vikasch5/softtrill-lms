<?php

namespace App\Http\Controllers\Lms;

use App\Models\Lead;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class DialerController extends Controller
{
    /**
     * Proxy the external dialer API call.
     */
    public function call(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer|exists:leads,id',
        ]);

        $lead = Lead::find($request->input('lead_id'));
        $phone = $lead->phone_number;

        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'No phone number found for this lead.']);
        }

        $agentId = UserDetails::where('user_id', auth()->id())
            ->value('employee_id');

        $serverIp = gethostbyname(gethostname());
        $serverIp = '106.215.115.249:8082';

        $params = http_build_query([
            'source' => 'test',
            'user' => '7777',
            'pass' => '7777',
            'agent_user' => $agentId,
            'function' => 'external_dial',
            'value' => $phone,
            'phone_code' => '0',
            'search' => 'YES',
            'preview' => 'NO',
            'focus' => 'NO',
        ]);

        $fullUrl = "http://{$serverIp}/Client-Dir/api.php?" . $params;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPGET => true,
        ]);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $curlError) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to reach dialer server: ' . ($curlError ?: 'Unknown cURL error.'),
            ], 502);
        }

        $responseBody = trim($responseBody);

        if (str_starts_with($responseBody, 'SUCCESS')) {
            return response()->json([
                'success' => true,
                'message' => $responseBody,
            ]);
        }

        $errorMessages = [
            'ERROR: no active session for this agent' => 'Agent has no active session. Please log in to the dialer first.',
            'ERROR: agent not logged in' => 'Agent is not logged in to the dialer.',
            'ERROR: invalid user or pass' => 'Invalid dialer credentials.',
            'ERROR: not logged in' => 'Dialer authentication failed.',
            'ERROR: agent already in active call' => 'Agent is already on an active call.',
            'ERROR: phone number invalid' => 'The phone number is invalid.',
            'ERROR: no campaign for agent' => 'No campaign is assigned to this agent.',
        ];

        $humanMessage = $errorMessages[$responseBody] ?? 'Dialer error';
        Log::info('dialer respose :', [$responseBody]);

        return response()->json([
            'success' => false,
            'message' => $humanMessage,
            'raw' => $responseBody,
        ], 422);
    }

    /**
     * Proxy the dialer external_hangup API call.
     */
    public function hangup(Request $request)
    {
        $agentId = UserDetails::where('user_id', auth()->id())
            ->value('employee_id');
        $serverIp = gethostbyname(gethostname());
        // $_SERVER['SERVER_ADDR'];
        $serverIp = '106.215.115.249:8082';

        $params = http_build_query([
            'source' => 'test',
            'user' => '7777',
            'pass' => '7777',
            'agent_user' => $agentId,
            'function' => 'external_hangup',
            'value' => '1',
        ]);

        $fullUrl = "http://{$serverIp}/agc/api.php?" . $params;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPGET => true,
        ]);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $curlError) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to reach dialer server: ' . ($curlError ?: 'Unknown cURL error.'),
            ], 502);
        }

        $responseBody = trim($responseBody);

        if (str_starts_with($responseBody, 'SUCCESS')) {
            return response()->json([
                'success' => true,
                'message' => $responseBody,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Hangup failed: ' . $responseBody,
            'raw' => $responseBody,
        ], 422);
    }

    /**
     * Proxy the dialer external_status API call.
     */
    public function status(Request $request)
    {
        $agentId = UserDetails::where('user_id', auth()->id())->value('employee_id');
        $serverIp = gethostbyname(gethostname());
        $serverIp = '106.215.115.249:8082';
        $params = http_build_query([
            'source' => 'test',
            'user' => '7777',
            'pass' => '7777',
            'agent_user' => $agentId,
            'function' => 'external_status',
            'value' => 'CALLED',
        ]);

        $fullUrl = "http://{$serverIp}/agc/api.php?" . $params;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPGET => true,
        ]);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $curlError) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to reach dialer server: ' . ($curlError ?: 'Unknown cURL error.'),
            ], 502);
        }

        $responseBody = trim($responseBody);

        if (str_starts_with($responseBody, 'SUCCESS')) {
            return response()->json([
                'success' => true,
                'message' => $responseBody,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Status update failed: ' . $responseBody,
            'raw' => $responseBody,
        ], 422);
    }
}
