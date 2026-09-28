/**
 * AutoScan AI - Multi-Language Translation System (i18n.js)
 * Languages Supported: English (en), Chinese (zh)
 * Persists user preference in localStorage
 */

const translations = {
    en: {
        nav: {
            tagline: "Plate Scanner & Registry",
            scan_register: "Scanner & Register",
            vehicles_directory: "Registry Directory",
            system_manual: "System Manual"
        },
        header: {
            title: "AI Vehicle License Plate Scanner",
            subtitle: "Real-time WebRTC camera scanning, OCR plate extraction, and dynamic registration fee calculation."
        },
        scanner: {
            title: "Plate Recognition Scanner",
            subtitle: "Capture live camera frame or upload image",
            tab_camera: "Live Camera",
            tab_upload: "Upload File",
            camera_inactive: "Camera is inactive",
            camera_start: "Start Camera",
            camera_stop: "Stop Camera",
            camera_switch: "Flip Camera",
            capture_scan: "Capture & Scan Frame",
            drop_title: "Drag & drop license plate image here",
            drop_sub: "Supports JPG, PNG, WebP (Max 8MB) or click to browse",
            processing: "Scanning plate with AI OCR engine...",
            result_detected: "Detected Plate Number",
            confidence: "Confidence",
            reticle_guide: "ALIGN LICENSE PLATE WITHIN BOX",
            autofilled: "License plate automatically populated into form!",
            error_unreadable: "Unable to read license plate clearly. Please adjust lighting or reposition the camera.",
            error_no_camera: "Camera access denied or unavailable. You can use image upload instead."
        },
        form: {
            title: "Vehicle Information Registration",
            subtitle: "Manual vehicle specifications for 100% data integrity",
            plate_number: "License Plate Number",
            plate_placeholder: "e.g., ABC1234",
            plate_tag: "Scanner Auto-fill",
            owner_name: "Owner Full Name",
            owner_placeholder: "e.g., Alexander Tan",
            phone: "Phone Number",
            phone_placeholder: "e.g., +60123456789",
            make: "Vehicle Make / Brand",
            make_placeholder: "e.g., Toyota, Honda, Proton",
            model: "Vehicle Model",
            model_placeholder: "e.g., Corolla, Civic, Saga",
            color: "Vehicle Color",
            color_placeholder: "e.g., Pearl White, Obsidian Black",
            body_type: "Vehicle Body Type",
            select_body_type: "Select body type...",
            currency: "Preferred Currency",
            base_fee: "Base Registration Fee:",
            converted_fee: "Payable Fee:",
            exchange_rate: "Exchange Rate:",
            rate_source: "Live FX Feed",
            submit_btn: "Confirm & Register Vehicle",
            submitting: "Registering Vehicle...",
            registered_success: "Vehicle registered successfully in the database!"
        },
        registry: {
            title: "Registered Vehicles Directory",
            subtitle: "Search, filter, and review registered vehicle records and payment logs",
            search_placeholder: "Search plate, owner, make, model...",
            all_types: "All Body Types",
            filter_date_from: "From Date",
            filter_date_to: "To Date",
            btn_filter: "Search",
            btn_reset: "Reset",
            btn_export: "Export CSV",
            th_plate: "Plate Number",
            th_owner: "Owner Details",
            th_vehicle: "Vehicle Info",
            th_fee: "Fee Paid",
            th_date: "Registered At",
            th_actions: "Action",
            view_details: "View",
            no_records: "No registered vehicles found matching your criteria.",
            stat_total: "Total Vehicles",
            stat_today: "Registered Today",
            stat_fees: "Total Revenue (USD)",
            stat_rates: "Currency Engine",
            page_showing: "Showing",
            page_of: "of",
            page_records: "records"
        },
        manual: {
            title: "System Usage Manual & Documentation",
            subtitle: "Technical specifications, API guides, and troubleshooting workflows",
            nav_overview: "1. Overview & Architecture",
            nav_scanner: "2. Camera & Scanner Workflow",
            nav_exchange: "3. Exchange Rate Engine",
            nav_api: "4. REST API Documentation",
            nav_security: "5. Security & Protection",
            nav_hosting: "6. Shared Hosting (iFastNet)"
        },
        footer: {
            desc: "Next-generation vehicle license plate recognition and dynamic registration system powered by Vanilla JS, WebRTC, and real-time exchange rates.",
            nav_title: "Quick Links",
            security_title: "Security & Standards",
            all_rights: "All rights reserved. Designed for performance, reliability, and security."
        }
    },
    zh: {
        nav: {
            tagline: "车牌识别与登记系统",
            scan_register: "扫描与登记",
            vehicles_directory: "车辆档案库",
            system_manual: "系统使用手册"
        },
        header: {
            title: "AI 智能车辆车牌扫描系统",
            subtitle: "基于 WebRTC 摄像头实时扫描、OCR 文字识别提取车牌及动态外汇实时登记费换算。"
        },
        scanner: {
            title: "车牌识别扫描仪",
            subtitle: "实时摄像头抓帧或上传静态车牌图片",
            tab_camera: "实时摄像头",
            tab_upload: "上传图片",
            camera_inactive: "摄像头未启动",
            camera_start: "开启摄像头",
            camera_stop: "关闭摄像头",
            camera_switch: "切换前后摄像头",
            capture_scan: "抓帧并识别车牌",
            drop_title: "拖拽车牌图片到此区域",
            drop_sub: "支持 JPG、PNG、WebP（最大 8MB），或点击选择文件",
            processing: "AI OCR 正在识别车牌字符...",
            result_detected: "识别出的车牌号码",
            confidence: "置信度",
            reticle_guide: "请将车牌对准框内区域",
            autofilled: "车牌号已自动填充到登记表单！",
            error_unreadable: "无法清晰识别车牌。请调整光线或重新对准摄像头角度。",
            error_no_camera: "无法访问摄像头或权限被拒绝。您可以使用图片上传功能。"
        },
        form: {
            title: "车辆信息登记表",
            subtitle: "车辆属性人工核实录入，确保数据 100% 准确性",
            plate_number: "车牌号码",
            plate_placeholder: "例如：ABC1234",
            plate_tag: "扫描自动填充",
            owner_name: "车主全名",
            owner_placeholder: "例如：陈大文 / Alexander Tan",
            phone: "联系电话",
            phone_placeholder: "例如：+60123456789",
            make: "车辆品牌 / 厂商",
            make_placeholder: "例如：Toyota, Honda, Proton",
            model: "车辆型号",
            model_placeholder: "例如：Corolla, Civic, Saga",
            color: "车身颜色",
            color_placeholder: "例如：珍珠白、星空黑",
            body_type: "车身类型",
            select_body_type: "请选择车身类型...",
            currency: "首选结算货币",
            base_fee: "基准登记规费：",
            converted_fee: "应缴费用：",
            exchange_rate: "当前汇率：",
            rate_source: "实时外汇数据",
            submit_btn: "确认并登记车辆",
            submitting: "正在登记...",
            registered_success: "车辆已成功登记并存入数据库！"
        },
        registry: {
            title: "已登记车辆档案目录",
            subtitle: "浏览、检索已登记车辆信息及换算规费记录",
            search_placeholder: "搜索车牌号、车主、品牌、型号...",
            all_types: "全部车身类型",
            filter_date_from: "开始日期",
            filter_date_to: "结束日期",
            btn_filter: "搜索",
            btn_reset: "重置",
            btn_export: "导出 CSV",
            th_plate: "车牌号码",
            th_owner: "车主信息",
            th_vehicle: "车辆规格",
            th_fee: "实缴费用",
            th_date: "登记时间",
            th_actions: "操作",
            view_details: "查看详情",
            no_records: "未找到符合条件的登记车辆记录。",
            stat_total: "累计登记车辆",
            stat_today: "今日登记数",
            stat_fees: "累计基准收入 (USD)",
            stat_rates: "汇率缓存引擎",
            page_showing: "显示第",
            page_of: "条，共",
            page_records: "条记录"
        },
        manual: {
            title: "系统操作手册与开发文档",
            subtitle: "技术规格、API 接口文档及常见问题排错流程",
            nav_overview: "1. 架构与概述",
            nav_scanner: "2. 摄像头与扫描工作流",
            nav_exchange: "3. 实时汇率引擎",
            nav_api: "4. REST API 接口文档",
            nav_security: "5. 安全机制与防护",
            nav_hosting: "6. 虚拟主机部署 (iFastNet)"
        },
        footer: {
            desc: "基于原生 JavaScript、WebRTC 与实时外汇数据构建的新一代智能车牌识别与车辆登记系统。",
            nav_title: "快速导航",
            security_title: "安全机制与标准",
            all_rights: "版权所有。遵循高性能、高可靠与严密安全设计。"
        }
    }
};

/**
 * Current language state
 */
let currentLanguage = localStorage.getItem('autoscan_lang') || 'en';

/**
 * Retrieve translation by dotted path (e.g. 'scanner.title')
 *
 * @param {string} path
 * @param {string} fallback
 * @returns {string}
 */
function t(path, fallback = '') {
    const keys = path.split('.');
    let val = translations[currentLanguage];

    for (const key of keys) {
        if (val && typeof val === 'object' && key in val) {
            val = val[key];
        } else {
            // Fallback to English
            let enVal = translations.en;
            for (const enKey of keys) {
                if (enVal && typeof enVal === 'object' && enKey in enVal) {
                    enVal = enVal[enKey];
                } else {
                    return fallback || path;
                }
            }
            return typeof enVal === 'string' ? enVal : (fallback || path);
        }
    }

    return typeof val === 'string' ? val : (fallback || path);
}

/**
 * Apply translations to DOM elements with data-i18n and data-i18n-placeholder
 *
 * @param {string} lang
 */
function applyLanguage(lang) {
    if (!translations[lang]) {
        lang = 'en';
    }
    currentLanguage = lang;
    localStorage.setItem('autoscan_lang', lang);
    document.documentElement.lang = lang;

    // Update active state on switcher buttons
    const btnEn = document.getElementById('langBtnEn');
    const btnZh = document.getElementById('langBtnZh');
    if (btnEn && btnZh) {
        btnEn.classList.toggle('active', lang === 'en');
        btnZh.classList.toggle('active', lang === 'zh');
    }

    // Update text content
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        const translated = t(key);
        if (translated) {
            el.textContent = translated;
        }
    });

    // Update placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        const translated = t(key);
        if (translated) {
            el.placeholder = translated;
        }
    });

    // Dispatch event for components that need re-rendering
    window.dispatchEvent(new CustomEvent('languageChanged', { detail: { language: lang } }));
}

/**
 * Initialize on page load
 */
document.addEventListener('DOMContentLoaded', () => {
    // Bind language buttons
    const btnEn = document.getElementById('langBtnEn');
    const btnZh = document.getElementById('langBtnZh');

    if (btnEn) {
        btnEn.addEventListener('click', () => applyLanguage('en'));
    }
    if (btnZh) {
        btnZh.addEventListener('click', () => applyLanguage('zh'));
    }

    // Apply stored or default language
    applyLanguage(currentLanguage);
});

// Export globally
window.t = t;
window.applyLanguage = applyLanguage;
window.getCurrentLanguage = () => currentLanguage;
