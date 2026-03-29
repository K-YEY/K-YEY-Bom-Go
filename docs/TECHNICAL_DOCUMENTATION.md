# دليل المطورين لنظام إدارة الشحنات (Developer Documentation)

هذا المستند مخصص للمبرمجين الذين سيعملون على النظام مستقبلاً. يشرح الهيكل العام، طريقة عمل الكود، والمنطق البرمجي (Business Logic) الخاص بكل قسم.

---

## 1. نظرة عامة على النظام (Project Overview)

النظام عبارة عن منصة لإدارة اللوجستيات والشحن (Logistics System)، يربط بين **العملاء (Clients)**، **المندوبين (Shippers/Representatives)**، و **الإدارة (Admins)**.

- **الهدف الأساسي:** تتبع الطرود من لحظة الإنشاء حتى التسليم النهائي، ومعالجة التحصيلات المالية المعقدة والعمولات.

---

## 2. التقنيات المستخدمة (Tech Stack)

### Backend

- **Framework:** Laravel 10/11.
- **API Design:** RESTful API.
- **Authentication:** Laravel Sanctum (Bearer Token).
- **Architecture:** Controller-based logic مع استخدام Models قوية.

### Frontend

- **Framework:** Vue.js 3 (Composition API).
- **Language:** TypeScript.
- **Build Tool:** Vite.
- **Design:** Vuetify / Custom Tailwind styles (بناءً على القالب المستخدم).
- **State Management:** Pinia.

---

## 3. هيكلة المشروع (Project Structure)

### الـ Backend (Laravel)

- **Controllers:** تجدها في `app/Http/Controllers/Api/`.
  - `Orders/OrderController.php`: العصب الرئيسي للنظام (إدارة الطلبات).
  - `Orders/ShipperCollectionController.php`: مسؤول عن تحصيلات المناديب.
  - `Orders/ClientSettlementController.php`: مسؤول عن تسويات العملاء.
- **Models:** تجدها في `app/Models/`. كل Model يمثل جدولاً في قاعدة البيانات مع علاقاته (Relationships).
- **Middlewares:** تستخدم للتحقق من الصلاحيات ووضع الموقع (مثل ساعات العمل).

### الـ Frontend (Vue/TS)

- **Pages:** في `resources/ts/pages/`. كل ملف يمثل صفحة (مثل `orders/index.vue`).
- **Views:** في `resources/ts/views/`. تحتوي على المكونات (Components) الخاصة بكل صفحة لتقليل حجم ملفات الصفحات.
- **Plugins:** في `resources/ts/plugins/`. التعريفات الخاصة بـ Axios, I18n, وما إلى ذلك.

---

## 4. منطق العمل الأساسي (Core Business Logic)

### أ- دورة حياة الطلب (Order Life Cycle)

يبدأ الطلب بحالة `PENDING` ويمر بعدة مراحل:

1. **الاستلام:** يتم الموافقة عليه وتعيينه لمندوب (`OUT_FOR_DELIVERY`).
2. **التسليم:** ينتهي بحالة `DELIVERED` (تم التسليم) أو `HOLD` (مؤجل) أو `UNDELIVERED` (لم يتم التسليم).
3. **التحصيل المالي:** بمجرد تسليم الطلب، يتم إدراجه في "تحصيل مندوب".

### ب- النظام المالي (Financial System)

هذا هو الجزء الأخطر والأهم في الكود:

1. **تحصيل المندوب (Shipper Collection):**
    - المندوب يسلم المبالغ التي حصلها من الزبائن.
    - النظام يحسب: (صافي المبلغ = إجمالي المحصل من الزبون - عمولة المندوب).
    - لا يمكن تعديل الطلب مالياً بعد "اعتماده" في تحصيل المندوب.
2. **تسويه العميل (Client Settlement):**
    - الشركة تدفع للعميل ثمن بضاعته بعد خصم مصاريف الشحن.
    - النظام يحسب: (المبلغ الصافي للعميل = إجمالي الـ COD - مصاريف الشحن).

### ج- تفصيل حالات الطلب (Order Status Codes)

من المهم جداً فهم معاني الحالات في قاعدة البيانات (جدول `orders` عمود `status`):

- `PENDING`: طلب جديد لم يتم تأكيده.
- `OUT_FOR_DELIVERY`: الطلب مع المندوب وفي الطريق للعميل.
- `DELIVERED`: تم التسليم بنجاح وتحصيل المبلغ.
- `HOLD`: تم تأجيل التسليم لسبب ما (سيظهر في `latest_status_note`).
- `UNDELIVERED`: لم يتم التسليم (رفض أو عنوان خطأ) وسيعود للمستودع.

### د- الصلاحيات التلقائية (Global Scopes)

يستخدم الموديل `Order` سمة (Trait) تسمى `ScopesByUserRole`. هذه السمة تقوم بتصفية البيانات تلقائياً:

- **العميل (Client):** يرى فقط طلباته الخاصة.
- **المندوب (Shipper):** يرى فقط الطلبات المعينة له.
- **الأدمن (Admin):** يرى كل الطلبات (إلا لو كانت هناك صلاحيات تقيد ذلك).
*هذا يعني أنك لا تحتاج لإضافة `where('user_id', ...)` يدوياً في كل مكان.*

---

## 5. نظام الصلاحيات (ACL & Permissions)

النظام مبني على نظام صلاحيات دقيق جداً:

- **صلاحيات الصفحات:** `order.page`, `client.page`.
- **صلاحيات الأفعال:** `order.create`, `order.update`.
- **صلاحيات الأعمدة (Column-Level Permissions):**
  - وهذا نظام متطور تم تنفيذه في `OrderController`. يمكنك تحديد من يرى أعمدة معينة (مثل السعر) ومن يمكنه تعديلها.
  - يتم ذلك عبر دوال مثل `authorizeEditableColumns` و `filterVisibleColumns`.

---

## 6. ملاحظات تقنية هامة للمبرمج (Important Tech Notes)

### ساعات العمل (Working Hours)

هناك حماية في الـ Backend تمنع إنشاء الطلبات خارج ساعات العمل المحددة في الإعدادات. يتم ذلك عبر دالة `checkWorkingHours`.

### التقارير والإكسل (Import/Export)

- يستخدم النظام مكتبة `Maatwebsite/Excel`.
- تجد ملفات الاستيراد في `app/Imports` والتصدير في `app/Exports`.

### حساب التكاليف (Order Totals Calculation)

يتم حساب الـ `shipping_fee` و الـ `commission_amount` تلقائياً عند إنشاء الطلب بناءً على:

- **المحافظة (Governorate):** كل محافظة لها سعر شحن مختلف في "خطة الأسعار" (Plan) الخاصة بالعميل.
- **خطة العميل (Client Plan):** كل عميل مرتبط بخطة أسعار محددة.

---

## 7. واجهة المندوب (Shipper Mobile App API)

تم تخصيص مجموعة من الـ APIs لتعمل مع تطبيق الجوال الخاص بالمندوب (تحت المسار `/api/shipper-app/`):

- **نقطة البداية (Init):** تجلب أسباب الرفض المفعلة حالياً.
- **إدارة الطلبات:**
  - `GET /orders`: عرض الطلبات المسندة للمندوب فقط (مع Pagination).
  - `PATCH /orders/{id}/status`: تحديث حالة الطلب لـ (تسليم، تأجيل، فشل تسليم) مع تسجيل أسباب الرفض والملاحظات.
- **البحث السريع (Scan):** البحث عن طلب معين عبر الكود أو الباركود.
- **الإحصائيات (Stats):** تعرض عدد الطلبات الكلية، الطلبات التي لم يتم توصيلها، والمبالغ المالية المطلوب توريدها.

---

## 8. كيفية التثبيت والبدء (Setup)

1. **Backend:**

    ```bash
    composer install
    php artisan migrate --seed
    php artisan key:generate
    php artisan serve
    ```

2. **Frontend:**

    ```bash
    npm install
    npm run dev
    ```

---

## 8. نصيحة للمستقبل (Recommendations)

- عند إضافة أي حالة طلب (Status) جديدة، تأكد من تحديث الثوابت (Constants) في كلا من `Order.php` و `OrderController.php`.
- لا تقم بتعديل الدوال المالية في `OrderController` دون مراجعة نظام العمولات في `ShipperCollectionController`.
- اعتمد دائماً على الـ `authorizePermission` قبل أي عملية لضمان أمن البيانات.

---

**تم إعداد هذا المستند لضمان استمرارية العمل بأفضل جودة.**
