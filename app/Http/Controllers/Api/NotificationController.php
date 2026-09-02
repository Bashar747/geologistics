<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SentNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    // عرض إشعارات المستخدم الحالي بس (أدمن/موزّع يقدروا يشوفوا كل الإشعارات)
    public function index(Request $request)
    {
        $user = $request->user();

        $query = SentNotification::query();

        if (! in_array($user->role, ['admin', 'dispatcher'], true)) {
            $query->where('user_id', $user->id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    // عرض إشعار واحد بالتفصيل
    public function show(Request $request, SentNotification $notification)
    {
        $user = $request->user();

        if (! in_array($user->role, ['admin', 'dispatcher'], true) && $notification->user_id !== $user->id) {
            return response()->json(['message' => 'You are not authorized to view this notification'], 403);
        }

        return response()->json($notification);
    }

    // إرسال إشعار لمستخدم معين (أدمن/موزّع فقط)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
            'channel' => ['required', 'in:sms,push,email'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // هون بمكان ربط أي خدمة خارجية حقيقية لاحقاً (Twilio, Firebase, ...)
        // حالياً منسجل الإشعار ومنعتبره "sent" فوراً كمحاكاة
        $notification = SentNotification::create([
            'user_id' => $request->user_id,
            'channel' => $request->channel,
            'message' => $request->message,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notification sent successfully',
            'notification' => $notification,
        ], 201);
    }

    // إعادة محاولة إرسال إشعار فشل (أدمن/موزّع فقط)
    public function retry(SentNotification $notification)
    {
        if ($notification->status !== 'failed') {
            return response()->json(['message' => 'Only failed notifications can be retried'], 422);
        }

        // محاكاة إعادة الإرسال
        $notification->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notification resent successfully',
            'notification' => $notification->fresh(),
        ]);
    }
}