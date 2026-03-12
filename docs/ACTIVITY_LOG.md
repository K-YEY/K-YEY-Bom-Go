# Activity Log System

## نظام تسجيل الأنشطة - Activity Log

النظام يسجل **تلقائياً** جميع العمليات المهمة بالنظام:

### ماذا يتم تسجيله؟

✅ **إنشاء سجلات جديدة** - Create
✅ **تعديل البيانات** - Update  
✅ **حذف البيانات** - Delete
✅ **استعادة السجلات** - Restore
✅ **الحذف الدائم** - Force Delete
✅ **تغيير الحالات** - Status Changes

### الموديلات المراقبة

الموديلات التالية يتم تسجيل عملياتها تلقائياً:

- User
- Expense / ExpenseCategory
- Content
- Setting
- Governorate / Plan / PlanPrice
- Material / MaterialRequest / MaterialRequestItem
- PickupRequest / Visit
- RefusedReason
- Order / ShipperCollection / ShipperReturn
- ClientSettlement / ClientReturn
- Role

## مثال عملي

### 1) عند تعديل Order

```php
// في OrderController::update()
$order->update([
    'status' => 'DELIVERED',
    'latest_status_note' => 'تم التسليم بنجاح'
]);
// ✅ يسجل تلقائياً في activity_logs:
// {
//   "user_id": 1,
//   "entity_type": "Order",
//   "entity_id": 145,
//   "action": "updated",
//   "old_values": {
//       "status": "OUT_FOR_DELIVERY",
//       "latest_status_note": null
//   },
//   "new_values": {
//       "status": "DELIVERED",
//       "latest_status_note": "تم التسليم بنجاح"
//   },
//   "label": "Updated Order #145"
// }
```

### 2) عند إنشاء User

```php
User::create([
    'name' => 'أحمد علي',
    'username' => 'ahmed',
    'password' => '...'
]);
// ✅ يسجل تلقائياً:
// {
//   "action": "created",
//   "entity_type": "User",
//   "label": "Created User",
//   "new_values": { ... all user data ... }
// }
```

### 3) عند حذف ExpenseCategory

```php
$category->delete();
// ✅ يسجل:
// {
//   "action": "deleted",
//   "entity_type": "ExpenseCategory",
//   "label": "Deleted ExpenseCategory",
//   "old_values": { ... category data ... }
// }
```

## استخدام ActivityLogService يدويًا

للحالات الخاصة، يمكن استخدام `ActivityLogService` مباشرة:

### تسجيل إجراء مخصص

```php
use App\Support\Services\ActivityLogService;

// تسجيل تغيير حالة
ActivityLogService::logStatusChange(
    model: $order,
    field: 'status',
    oldValue: 'PENDING',
    newValue: 'SHIPPED',
    meta: ['reason' => 'تم الشحن من المستودع الرئيسي']
);

// تسجيل إجراء عام
ActivityLogService::logAction(
    model: $order,
    action: 'assigned_to_shipper',
    label: 'تم تعيين الطلب للشاحن أحمد',
    meta: ['shipper_id' => 7]
);

// تسجيل تحديث مخصص
ActivityLogService::logUpdated(
    model: $user,
    oldValues: ['commission_rate' => 0.05],
    newValues: ['commission_rate' => 0.10],
    label: 'تم تحديث معدل العمولة',
    meta: ['reason' => 'ترقية الشاحن']
);
```

## عرض Activity Logs

### الـ API Endpoints

```bash
# عرض جميع السجلات
GET /api/activity-logs

# عرض سجل واحد
GET /api/activity-logs/1
```

### مثال Response

```json
{
  "id": 1,
  "user_id": 1,
  "entity_type": "Order",
  "entity_id": 145,
  "action": "updated",
  "label": "Updated Order #145",
  "old_values": {
    "status": "OUT_FOR_DELIVERY"
  },
  "new_values": {
    "status": "DELIVERED"
  },
  "ip_address": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "created_at": "2026-03-12T14:30:00Z"
}
```

## Columns المسجلة

### الحقول الأساسية

| الحقل              | الوصف                                 |
| ------------------ | ------------------------------------- |
| `user_id`          | الموظف الذي قام بالعملية              |
| `login_session_id` | جلسة تسجيل الدخول                     |
| `entity_type`      | نوع السجل (Order, User, ...)          |
| `entity_id`        | معرف السجل                            |
| `action`           | نوع العملية (created/updated/deleted) |
| `label`            | وصف العملية                           |
| `old_values`       | القيم القديمة (JSON)                  |
| `new_values`       | القيم الجديدة (JSON)                  |
| `ip_address`       | عنوان IP للموظف                       |
| `user_agent`       | متصفح/جهاز الموظف                     |
| `created_at`       | وقت العملية                           |

## الأعمدة المتجاهلة

الأعمدة التالية **لا تُسجَّل** لأسباب أمنية:

- `password`
- `remember_token`
- `two_factor_secret`
- `two_factor_recovery_codes`
- `created_at` / `updated_at` (timestamps)

## الصلاحيات

لعرض Activity Logs تحتاج صلاحية:

```
activity-log.page
activity-log.view
activity-log.column.*.view
```

الأدوار:

- `activity-log-viewer` - يمكنه عرض جميع السجلات

## أمثلة استخدام متقدمة

### تتبع من قام بالعملية

```php
$log = ActivityLog::find(1);
$log->user; // الموظف الذي قام بالعملية
$log->loginSession; // جلسة تسجيل الدخول
```

### البحث عن تاريخ التغييرات

```php
// جميع التحديثات على طلب معين
ActivityLog::where('entity_type', 'Order')
    ->where('entity_id', 145)
    ->where('action', 'updated')
    ->orderByDesc('created_at')
    ->get();

// جميع عمليات موظف معين في اليوم
ActivityLog::where('user_id', 1)
    ->whereDate('created_at', '2026-03-12')
    ->orderByDesc('created_at')
    ->get();
```

### تقارير التدقيق

```php
// من غيّر تكلفة الشحن؟
ActivityLog::where('entity_type', 'Order')
    ->where('action', 'updated')
    ->whereJsonContains('old_values->shipping_fee')
    ->with('user')
    ->get();
```

## الفوائد

✅ **التتبع الدقيق** - معرفة من عدل وماذا وإلى ماذا
✅ **الأمان** - اكتشاف العمليات غير المسموح بها
✅ **التدقيق** - تقارير شاملة عن كل العمليات
✅ **حل المشاكل** - معرفة سبب تغيير البيانات
✅ **الامتثال** - الالتزام بالمتطلبات القانونية

## الملاحظات الهامة

- السجلات تُحذف تلقائياً بعد 90 يوماً (يمكن تخصيصها)
- Activity Logs نفسها **لا تُسجَّل** (لتجنب النمو غير المحدود)
- جميع القيم الحساسة يجب تصفيتها قبل الحفظ
