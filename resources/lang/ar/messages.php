<?php

return [
    'app_title' => 'مولد Magic',
    'magic'     => 'ماجيك',
    'generator' => 'مولد',

    // Generator Dashboard
    'dashboard_title'       => 'لوحة التحكم',
    'dashboard_desc'        => 'اختر جدولاً، هيّئ الأعمدة، وأنشئ وحدة CRUD كاملة.',
    'tech_stack'            => '1. التقنية',
    'select_table'          => '2. اختر جدولاً',
    'column_config'         => '3. إعدادات الأعمدة',
    'columns'               => 'عموداً',
    'no_columns'            => 'لا توجد أعمدة متاحة',
    'no_columns_desc'       => 'تمت تصفية الأعمدة التلقائية والطوابع الزمنية.',
    'search_tables'         => 'ابحث عن جدول...',
    'choose_table'          => '— اختر جدولاً —',
    'no_tables_match'       => 'لا توجد جداول تطابق بحثك',

    // Column headers
    'column'       => 'العمود',
    'input_type'   => 'نوع الإدخال',
    'advanced'     => 'متقدم',

    // Messages
    'success_message'             => 'تم إنشاء السجل بنجاح.',
    'delete_confirmation_message' => 'هل أنت متأكد من حذف هذا السجل؟',
    'display_name_column'         => 'عمود الاسم المعروض',
    'folder_architecture'         => 'هيكل المجلدات',
    'group_files'                 => 'تجميع الملفات في مجلدات بأسماء الجداول',

    // Actions
    'auto_fill_validation' => 'تعبئة التحقق تلقائياً',
    'export_config'        => 'تصدير الإعدادات',
    'import_config'        => 'استيراد الإعدادات',
    'generate_crud'        => 'إنشاء CRUD',
    'generating'           => 'جارٍ الإنشاء...',

    // Advanced tab
    'model_options'      => 'خيارات الموديل',
    'soft_deletes'       => 'الحذف الناعم',
    'soft_deletes_desc'  => 'إضافة trait SoftDeletes ودعم عمود deleted_at',
    'timestamps'         => 'الطوابع الزمنية',
    'timestamps_desc'    => 'تعيين public $timestamps = true/false في الموديل',
    'code_generation'    => 'توليد الكود',
    'form_request'       => 'صنف FormRequest',
    'form_request_desc'  => 'توليد app/Http/Requests/{Model}Request.php',
    'policy'             => 'صنف Policy',
    'policy_desc'        => 'توليد app/Policies/{Model}Policy.php',
    'api_routes'         => 'مسارات API',
    'api_routes_desc'    => 'إضافة مسارات RESTful إلى routes/api.php',
    'api_prefix'         => 'بادئة مسار API',
    'files_to_generate'  => 'الملفات المراد إنشاؤها',
    'files'              => 'ملفاً',

    // Output
    'generated_output' => 'نتيجة الإنشاء',
    'written'          => 'تم الإنشاء',
    'pending'          => 'معلق',

    // Status
    'select_table_first'   => 'الرجاء اختيار جدول أولاً.',
    'generated_success'    => 'ملفاً تم إنشاؤها بنجاح.',
    'routes_appended'      => 'تم إضافة مسارات الوحدة إلى routes/web.php.',
    'api_routes_appended'  => 'تم إضافة مسارات API إلى routes/api.php.',
    'generation_failed'    => 'فشل الإنشاء: ',
    'config_copied'        => 'تم نسخ الإعدادات إلى الحافظة. الصقها في استيراد لاستعادتها.',
    'invalid_config'       => 'إعدادات JSON غير صالحة.',
    'config_imported'      => 'تم استيراد الإعدادات بنجاح.',
    'paste_config'         => 'الصق JSON الإعدادات للاستيراد',
];
