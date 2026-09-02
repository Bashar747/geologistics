<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    // عرض سجل التدقيق مع فلاتر اختيارية - أدمن فقط
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,name,phone,role');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return response()->json(
            $query->latest('created_at')->paginate(30)
        );
    }

    // عرض سجل واحد بالتفصيل
    public function show(AuditLog $auditLog)
    {
        return response()->json($auditLog->load('user:id,name,phone,role'));
    }
}