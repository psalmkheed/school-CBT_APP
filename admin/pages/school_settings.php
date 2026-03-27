<?php
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo "Access Denied.";
    exit;
}

$stmt = $conn->query("SELECT * FROM school_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
?>

<div class="fadeIn w-full p-4 md:p-8 max-w-7xl mx-auto">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold text-gray-800">System Settings</h1>
            <p class="text-sm text-gray-500 mt-1">Configure global application parameters and integrations</p>
        </div>
        <button type="submit" form="schoolSettingsForm" id="btnSaveConfigTop" class="hidden md:flex bg-gray-900 hover:bg-black text-white px-8 py-3 rounded-xl font-bold shadow-xl shadow-gray-200 transition-all items-center gap-2 transform hover:-translate-y-1">
            <i class="bx bx-check-circle text-xl"></i> Save All Settings
        </button>
    </div>

    <form id="schoolSettingsForm" enctype="multipart/form-data" class="flex flex-col lg:flex-row items-start gap-8 pb-20">
        
        <!-- Left Sidebar Tabs -->
        <div class="w-full lg:w-[280px] shrink-0 lg:sticky lg:top-[100px] z-10">
            <div class="bg-white rounded-3xl p-4 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 flex flex-col gap-2">
                <button type="button" onclick="switchSettingsTab('general')" id="tab-general" class="tab-btn w-full text-left px-5 py-3 rounded-2xl text-sm font-semibold transition-all flex items-center gap-3 bg-blue-50 text-blue-600 whitespace-nowrap">
                    <i class="bx bx-buildings text-xl"></i> General Details
                </button>
                <button type="button" onclick="switchSettingsTab('maintenance')" id="tab-maintenance" class="tab-btn w-full text-left px-5 py-3 rounded-2xl text-sm font-semibold text-gray-500 hover:bg-gray-50 transition-all flex items-center gap-3 whitespace-nowrap">
                    <i class="bx bx-spanner text-xl"></i> Maintenance
                </button>
                <button type="button" onclick="switchSettingsTab('pwa')" id="tab-pwa" class="tab-btn w-full text-left px-5 py-3 rounded-2xl text-sm font-semibold text-gray-500 hover:bg-gray-50 transition-all flex items-center gap-3 whitespace-nowrap">
                    <i class="bx bx-mobile-alt text-xl"></i> PWA Settings
                </button>
                <button type="button" onclick="switchSettingsTab('sms')" id="tab-sms" class="tab-btn w-full text-left px-5 py-3 rounded-2xl text-sm font-semibold text-gray-500 hover:bg-gray-50 transition-all flex items-center gap-3 whitespace-nowrap">
                    <i class="bx bxs-message-bubble text-xl"></i> SMS Config
                </button>
                <button type="button" onclick="switchSettingsTab('email')" id="tab-email" class="tab-btn w-full text-left px-5 py-3 rounded-2xl text-sm font-semibold text-gray-500 hover:bg-gray-50 transition-all flex items-center gap-3 whitespace-nowrap">
                    <i class="bx bx-envelope text-xl"></i> Email Config
                </button>
                <button type="button" onclick="switchSettingsTab('notifications')" id="tab-notifications" class="tab-btn w-full text-left px-5 py-3 rounded-2xl text-sm font-semibold text-gray-500 hover:bg-gray-50 transition-all flex items-center gap-3 whitespace-nowrap">
                    <i class="bx bx-bell text-xl"></i> Notifications
                </button>
                <button type="button" onclick="switchSettingsTab('newsletter')" id="tab-newsletter" class="tab-btn w-full text-left px-5 py-3 rounded-2xl text-sm font-semibold text-gray-500 hover:bg-gray-50 transition-all flex items-center gap-3 whitespace-nowrap">
                    <i class="bx bx-news text-xl"></i> Newsletter
                </button>
                <button type="button" onclick="switchSettingsTab('ai_integration')" id="tab-ai_integration" class="tab-btn w-full text-left px-5 py-3 rounded-2xl text-sm font-semibold text-gray-500 hover:bg-gray-50 transition-all flex items-center gap-3 whitespace-nowrap">
                    <i class="bx bxs-sparkles-alt text-xl"></i> AI Integration
                </button>
            </div>
        </div>

        <!-- Right Content Area -->
        <div class="w-full lg:flex-1 space-y-6">
            
            <!-- General Details Tab -->
            <div id="content-general" class="tab-content bg-white rounded-3xl p-6 md:p-8 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 block">
                <h2 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-4 mb-6">School Identity & Contacts</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">School Name</label>
                        <input type="text" name="school_name" value="<?= htmlspecialchars($config['school_name'] ?? '') ?>"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all"
                            required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Tagline /
                            Motto</label>
                        <input type="text" name="school_tagline"
                            value="<?= htmlspecialchars($config['school_tagline'] ?? '') ?>"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Official Address</label>
                        <input type="text" name="school_address" value="<?= htmlspecialchars($config['school_address'] ?? '') ?>"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Email Address</label>
                        <input type="email" name="school_email" value="<?= htmlspecialchars($config['school_email'] ?? '') ?>"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Primary Phone Number</label>
                        <input type="text" name="school_phone_number" value="<?= htmlspecialchars($config['school_phone_number'] ?? '') ?>"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Secondary Phone Number
                            (Optional)</label>
                        <input type="text" name="school_phone_number_2"
                            value="<?= htmlspecialchars($config['school_phone_number_2'] ?? '') ?>"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                    </div>
                    </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 border-t border-gray-100 pt-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Brand Primary Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="school_primary" value="<?= htmlspecialchars($config['school_primary'] ?? '#2563eb') ?>"
                                class="h-12 w-16 p-1 bg-white border border-gray-200 rounded-xl cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($config['school_primary'] ?? '#2563eb') ?>" readonly
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Brand Secondary Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="school_secondary"
                                value="<?= htmlspecialchars($config['school_secondary'] ?? '#3b82f6') ?>"
                                class="h-12 w-16 p-1 bg-white border border-gray-200 rounded-xl cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($config['school_secondary'] ?? '#3b82f6') ?>" readonly
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-500">
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Bank Account Details (For Manual Payments)</label>
                        <textarea name="account_details" rows="3" placeholder="Bank Name: Example Bank&#10;Account Name: School Name LTD&#10;Account Number: 0123456789" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-emerald-100 focus:border-emerald-400 transition-all"><?= htmlspecialchars($config['account_details'] ?? '') ?></textarea>
                        <p class="text-[10px] text-gray-400 font-medium mt-1 italic">This information will be shown to parents when they
                            click "Pay Now".</p>
                    </div>
                    </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 border-t border-gray-100 pt-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Active Session</label>
                        <input type="text" name="academic_session" value="<?= htmlspecialchars($config['academic_session'] ?? '') ?>"
                            placeholder="e.g. 2025/2026"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold">
                        </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Active Term</label>
                        <select name="active_term" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold">
                            <option value="First Term" <?= ($config['active_term'] ?? '') == 'First Term' ? 'selected' : '' ?>>First Term</option>
                            <option value="Second Term" <?= ($config['active_term'] ?? '') == 'Second Term' ? 'selected' : '' ?>>Second Term</option>
                            <option value="Third Term" <?= ($config['active_term'] ?? '') == 'Third Term' ? 'selected' : '' ?>>Third Term</option>
                        </select>
                    </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 border-t border-gray-100 pt-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Big Logo (Horizontal)</label>
                            <input type="file" name="school_logo" accept="image/*" class="mb-2 text-xs">
                            <?php if (!empty($config['school_logo'])): ?>
                                <img src="<?= APP_URL . ltrim($config['school_logo'], '/') ?>"
                                class="h-12 object-contain bg-gray-50 border p-1 rounded">
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Small Logo (Square)</label>
                        <input type="file" name="school_logo_small" accept="image/*" class="mb-2 text-xs">
                        <?php if (!empty($config['school_logo_small'])): ?>
                            <img src="<?= APP_URL . ltrim($config['school_logo_small'], '/') ?>"
                                class="h-12 object-contain bg-gray-50 border p-1 rounded">
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Official Signature</label>
                        <input type="file" name="signature" accept="image/*" class="mb-2 text-xs">
                        <?php if (!empty($config['signature'])): ?>
                            <img src="<?= APP_URL . ltrim($config['signature'], '/') ?>"
                                class="h-12 object-contain bg-gray-50 border p-1 rounded">
                        <?php endif; ?>
                    </div>
                    </div>
                    </div>

            <!-- Maintenance Tab -->
            <div id="content-maintenance" class="tab-content bg-white rounded-3xl p-6 md:p-8 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 hidden">
                <h2 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-4 mb-6">Maintenance Mode</h2>
                <div class="bg-red-50 text-red-700 p-4 rounded-xl border border-red-100 text-sm font-medium mb-6">
                    <i class="bx bx-error-triangle mr-1"></i> Enabling maintenance mode will block all non-admin users from accessing the application. Use this only during upgrades or serious downtime.
                </div>
                
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="maintenance_mode" value="0">
                    <input type="checkbox" name="maintenance_mode" value="1" class="w-5 h-5 text-red-600 rounded bg-gray-100 border-gray-300 focus:ring-red-500 focus:ring-2" <?= ($config['maintenance_mode'] ?? 0) == 1 ? 'checked' : '' ?>>
                    <span class="text-gray-800 font-bold">Put the application into maintenance mode</span>
                </label>
                </div>
                
                <!-- PWA Settings Tab -->
                <div id="content-pwa"
                    class="tab-content bg-white rounded-3xl p-6 md:p-8 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 hidden">
                    <h2 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-4 mb-6">Progressive Web App (PWA) Settings
                    </h2>
                    <p class="text-xs text-gray-500 mb-6">Configure how the app installs on mobile devices via the browser.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">PWA Icon (512x512)</label>
                        <input type="file" name="pwa_icon" accept="image/png" class="mb-2 text-xs">
                        <?php if (!empty($config['pwa_icon'])): ?>
                            <img src="<?= APP_URL . ltrim($config['pwa_icon'], '/') ?>"
                                class="h-16 w-16 object-contain bg-gray-50 border p-1 rounded-xl">
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Display Mode</label>
                        <select name="pwa_display" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                            <option value="fullscreen" <?= ($config['pwa_display'] ?? '') == 'fullscreen' ? 'selected' : '' ?>>Fullscreen</option>
                            <option value="standalone" <?= ($config['pwa_display'] ?? '') == 'standalone' ? 'selected' : '' ?>>Standalone</option>
                            <option value="minimal-ui" <?= ($config['pwa_display'] ?? '') == 'minimal-ui' ? 'selected' : '' ?>>Minimal UI</option>
                            <option value="browser" <?= ($config['pwa_display'] ?? '') == 'browser' ? 'selected' : '' ?>>Browser</option>
                        </select>
                        </div>
                        <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Theme Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="pwa_theme_color" value="<?= htmlspecialchars($config['pwa_theme_color'] ?? '#ffffff') ?>"
                                class="h-10 w-12 p-1 bg-white border border-gray-200 rounded-lg cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($config['pwa_theme_color'] ?? '#ffffff') ?>" readonly
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Background Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="pwa_bg_color" value="<?= htmlspecialchars($config['pwa_bg_color'] ?? '#ffffff') ?>"
                                class="h-10 w-12 p-1 bg-white border border-gray-200 rounded-lg cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($config['pwa_bg_color'] ?? '#ffffff') ?>" readonly
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Status Bar Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="pwa_status_bar_color"
                                value="<?= htmlspecialchars($config['pwa_status_bar_color'] ?? '#ffffff') ?>"
                                class="h-10 w-12 p-1 bg-white border border-gray-200 rounded-lg cursor-pointer">
                            <input type="text" value="<?= htmlspecialchars($config['pwa_status_bar_color'] ?? '#ffffff') ?>" readonly
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-500">
                        </div>
                    </div>
                    </div>
                    </div>

            <!-- SMS Config Tab -->
            <div id="content-sms" class="tab-content bg-white rounded-3xl p-6 md:p-8 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 hidden">
                <h2 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-4 mb-6">SMS Service Configuration</h2>
                
                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">SMS From (Sender ID)</label>
                    <input type="text" name="sms_from" value="<?= htmlspecialchars($config['sms_from'] ?? '') ?>"
                        placeholder="e.g. SCHOOLNAME" maxlength="11"
                        class="w-full md:w-1/2 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                    <p class="text-xs text-gray-400 mt-1">May be restricted by your SMS provider to 11 alphanumeric characters max.
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">SMS Gateway Provider</label>
                        <select name="sms_provider" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                            <option value="">Select Provider</option>
                            <option value="twilio" <?= ($config['sms_provider'] ?? '') == 'twilio' ? 'selected' : '' ?>>Twilio</option>
                            <option value="termii" <?= ($config['sms_provider'] ?? '') == 'termii' ? 'selected' : '' ?>>Termii</option>
                            <option value="africastalking" <?= ($config['sms_provider'] ?? '') == 'africastalking' ? 'selected' : '' ?>>Africa's Talking</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">API Key / Account SID</label>
                        <input type="text" name="sms_api_key" value="<?= htmlspecialchars($config['sms_api_key'] ?? '') ?>" placeholder="Enter API Key or SID" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">API Secret / Auth Token</label>
                        <input type="password" name="sms_api_secret" value="<?= (!empty($config['sms_api_secret'])) ? '********' : '' ?>" placeholder="Leave blank to keep existing" class="w-full md:w-1/2 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                    </div>
                </div>

            <!-- Email Config Tab -->
            <div id="content-email" class="tab-content bg-white rounded-3xl p-6 md:p-8 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 hidden">
                <h2 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-4 mb-6">Email Service (SMTP) Configuration</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Mail From Address</label>
                        <input type="email" name="mail_from_address" value="<?= htmlspecialchars($config['mail_from_address'] ?? 'customerservice@school.com') ?>"
                            placeholder="customerservice@fleetcart.envaysoft.com"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Mail From Name</label>
                            <input type="text" name="mail_from_name"
                                value="<?= htmlspecialchars($config['mail_from_name'] ?? 'Customer Service') ?>"
                                placeholder="e.g. Customer Service"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Mail Host</label>
                            <input type="text" name="mail_host" value="<?= htmlspecialchars($config['mail_host'] ?? '') ?>"
                                placeholder="smtp.mailtrap.io"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Mail Port</label>
                            <input type="text" name="mail_port" value="<?= htmlspecialchars($config['mail_port'] ?? '') ?>"
                                placeholder="587 or 465"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Mail Username</label>
                            <input type="text" name="mail_username" value="<?= htmlspecialchars($config['mail_username'] ?? '') ?>"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Mail Password</label>
                            <input type="password" name="mail_password" value="<?= (!empty($config['mail_password'])) ? '********' : '' ?>"
                                placeholder="Leave blank to keep existing"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium">
                        </div>
                        <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Mail Encryption</label>
                        <select name="mail_encryption" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium">
                            <option value="">Please Select</option>
                            <option value="tls" <?= ($config['mail_encryption'] ?? '') == 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= ($config['mail_encryption'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                        </select>
                        </div>
                    </div>
                </div>

                <!-- Test Email Component -->
                <div class="mt-8 border-t border-gray-100 pt-8">
                    <h3 class="text-sm font-bold text-gray-800 mb-2">Test Email Configuration</h3>
                    <p class="text-xs text-gray-500 mb-4">You must save your email settings above before testing. Enter an email address below to send a test message.</p>
                    <div class="flex flex-col sm:flex-row gap-3 items-center">
                        <input type="email" id="testEmailTarget" placeholder="recipient@example.com" class="w-full sm:w-64 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                        <button type="button" onclick="sendTestEmail()" id="btnTestEmail" class="w-full sm:w-auto bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white px-6 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2 text-sm whitespace-nowrap border border-blue-100 hover:border-blue-600">
                            <i class="bx bx-send text-lg"></i> Send Test
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notifications Tab -->
            <div id="content-notifications" class="tab-content bg-white rounded-3xl p-6 md:p-8 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 hidden">
                <h2 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-4 mb-6">User Notification Settings</h2>
                
                <div class="space-y-6">
                    <div>
                        <h4 class="text-sm font-bold text-gray-800 mb-3">Customer Notification Settings</h4>
                        
                        <label class="flex items-center gap-3 cursor-pointer mb-3">
                            <input type="hidden" name="notify_welcome_sms" value="0">
                            <input type="checkbox" name="notify_welcome_sms" value="1" class="w-5 h-5 text-blue-600 rounded bg-gray-100 border-gray-300 focus:ring-blue-500" <?= ($config['notify_welcome_sms'] ?? 0) == 1 ? 'checked' : '' ?>>
                                    <div>
                                        <span class="text-gray-800 font-bold block">Welcome SMS</span>
                                        <span class="text-xs text-gray-500 block">Send welcome SMS after registration</span>
                                    </div>
                                </label>
                    
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="hidden" name="notify_welcome_email" value="0">
                                    <input type="checkbox" name="notify_welcome_email" value="1"
                                        class="w-5 h-5 text-blue-600 rounded bg-gray-100 border-gray-300 focus:ring-blue-500"
                                        <?= ($config['notify_welcome_email'] ?? 0) == 1 ? 'checked' : '' ?>>
                                    <div>
                                        <span class="text-gray-800 font-bold block">Welcome Email</span>
                                        <span class="text-xs text-gray-500 block">Send welcome email after registration</span>
                                    </div>
                                </label>
                            </div>
                    
                            <div class="border-t border-gray-100 pt-6">
                                <h4 class="text-sm font-bold text-gray-800 mb-3">Fee / Order Notification Settings</h4>
                    
                                <label class="flex items-center gap-3 cursor-pointer mb-3">
                                    <input type="hidden" name="notify_new_fee_admin" value="0">
                                    <input type="checkbox" name="notify_new_fee_admin" value="1"
                                        class="w-5 h-5 text-blue-600 rounded bg-gray-100 border-gray-300 focus:ring-blue-500"
                                        <?= ($config['notify_new_fee_admin'] ?? 0) == 1 ? 'checked' : '' ?>>
                                    <div>
                                        <span class="text-gray-800 font-bold block">New Order Admin Email</span>
                                        <span class="text-xs text-gray-500 block">Send new order (or fee payment) notification to the
                                            admin</span>
                                    </div>
                                </label>
                    
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="hidden" name="notify_invoice_email" value="0">
                                    <input type="checkbox" name="notify_invoice_email" value="1"
                                        class="w-5 h-5 text-blue-600 rounded bg-gray-100 border-gray-300 focus:ring-blue-500"
                                        <?= ($config['notify_invoice_email'] ?? 0) == 1 ? 'checked' : '' ?>>
                                    <div>
                                        <span class="text-gray-800 font-bold block">Invoice Email</span>
                                        <span class="text-xs text-gray-500 block">Send invoice email to the customer after payment</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Newsletter Tab -->
                    <div id="content-newsletter"
                        class="tab-content bg-white rounded-3xl p-6 md:p-8 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 hidden">
                        <h2 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-4 mb-6">Newsletter Integrations</h2>
                
                <label class="flex items-center gap-3 cursor-pointer mb-6 border-b border-gray-50 pb-6">
                    <input type="hidden" name="newsletter_enabled" value="0">
                    <input type="checkbox" name="newsletter_enabled" value="1" class="w-5 h-5 text-blue-600 rounded bg-gray-100 border-gray-300 focus:ring-blue-500" <?= ($config['newsletter_enabled'] ?? 0) == 1 ? 'checked' : '' ?>>
                    <div>
                        <span class="text-gray-800 font-bold block">Newsletter</span>
                        <span class="text-xs text-gray-500 block">Allow users to subscribe to your newsletter</span>
                    </div>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Mailchimp API Key *</label>
                        <input type="text" name="mailchimp_api_key" value="<?= htmlspecialchars($config['mailchimp_api_key'] ?? '') ?>"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Mailchimp List ID *</label>
                        <input type="text" name="mailchimp_list_id" value="<?= htmlspecialchars($config['mailchimp_list_id'] ?? '') ?>"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 transition-all">
                    </div>
                </div>
            </div>

            <!-- AI Integration Tab -->
            <div id="content-ai_integration" class="tab-content bg-white rounded-3xl p-6 md:p-8 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 hidden">
                <h2 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-4 mb-6">AI System Integration</h2>
                <div class="bg-purple-50 border border-purple-100 rounded-2xl p-4 mb-6 flex gap-3 items-start">
                    <i class="bx bx-info-circle text-purple-500 text-xl mt-0.5"></i>
                    <p class="text-[11px] font-bold text-purple-700 italic leading-relaxed">Power intelligent features like the Teachers' Exam AI Generator and Students' AI Study Path using hyper-fast language models across the platform.</p>
                </div>
                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Groq Cloud API Key</label>
                        <input type="password" name="groq_api_key" value="<?= (!empty($config['groq_api_key'])) ? '********' : '' ?>" placeholder="Enter Groq API Key (gsk_...)"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 transition-all">
                        <p class="text-xs text-gray-400 mt-2 font-medium">Get your free API key from <a href="https://console.groq.com/keys" target="_blank" class="text-blue-500 hover:text-blue-700 underline font-bold">Groq Console</a>.</p>
                    </div>
                </div>
            </div>

            <!-- Bottom Mobile Button Backup -->
            <div class="flex justify-end pt-4 pb-8 md:hidden">
                <button type="submit" id="btnSaveConfigMobile" class="bg-gray-900 hover:bg-black text-white px-8 py-4 rounded-xl font-bold shadow-xl shadow-gray-200 transition-all flex w-full justify-center items-center gap-2">
                    <i class="bx bx-check-circle text-xl"></i> Save All Settings
                </button>
            </div>
            
        </div>
    </form>
</div>

<script>
    // Tab Switching Logic (Global for AJAX loaded inline onclick handlers)
    window.switchSettingsTab = function(tabName) {
        // Hide all contents
        $('.tab-content').addClass('hidden').removeClass('block');
        // Reset all buttons
        $('.tab-btn').removeClass('bg-blue-50 text-blue-600').addClass('text-gray-500 hover:bg-gray-50');
        
        // Show target
        $('#content-' + tabName).removeClass('hidden').addClass('block');
        // Highlight active button
        $('#tab-' + tabName).addClass('bg-blue-50 text-blue-600').removeClass('text-gray-500 hover:bg-gray-50');
    }

    $('#schoolSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        // Exclude the dummy blank password if not changed
        let pwdField = $(this).find('input[name="mail_password"]');
        if (pwdField.val() === '********') pwdField.prop('disabled', true);

        let smsSecretField = $(this).find('input[name="sms_api_secret"]');
        if (smsSecretField.val() === '********') smsSecretField.prop('disabled', true);

        let groqField = $(this).find('input[name="groq_api_key"]');
        if (groqField.val() === '********') groqField.prop('disabled', true);
        
        let fd = new FormData(this);
        pwdField.prop('disabled', false); // re-enable it on the UI
        smsSecretField.prop('disabled', false);
        groqField.prop('disabled', false);
        
        let btn1 = $('#btnSaveConfigTop');
        let btn2 = $('#btnSaveConfigMobile');
        let ogText1 = btn1.html();
        let ogText2 = btn2.html();
        
        let spinnerText = '<i class="bx bxs-loader-dots bx-spin text-xl"></i> Saving...';
        btn1.html(spinnerText).prop('disabled', true);
        btn2.html(spinnerText).prop('disabled', true);
        
        $.ajax({
            url: BASE_URL + 'admin/auth/settings_api.php?action=update_config',
            type: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            success: function(res) {
                if(res.status === 'success') {
                    showAlert('success', res.message);
                    setTimeout(() => loadPage(BASE_URL + "admin/pages/school_settings.php"), 1000);
                } else {
                    showAlert('error', res.message);
                    btn1.html(ogText1).prop('disabled', false);
                    btn2.html(ogText2).prop('disabled', false);
                }
            },
            error: function() {
                showAlert('error', 'Network error occurred. Please try again.');
                btn1.html(ogText1).prop('disabled', false);
                btn2.html(ogText2).prop('disabled', false);
            }
        });
    });

    // Test Email Logic attached globally
    window.sendTestEmail = function() {
        let email = $('#testEmailTarget').val();
        if(!email) {
            showAlert('error', 'Please enter an email address to test.');
            return;
        }

        let btn = $('#btnTestEmail');
        let ogText = btn.html();
        btn.html('<i class="bx bx-loader-alt bx-spin text-lg"></i> Sending...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/test_email.php',
            type: 'POST',
            data: { test_email: email },
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    showAlert('success', res.message);
                    $('#testEmailTarget').val('');
                } else {
                    showAlert('error', res.message);
                }
            },
            error: function() {
                showAlert('error', 'Network error occurred. The SMTP settings might be incorrect causing a timeout.');
            },
            complete: function() {
                btn.html(ogText).prop('disabled', false);
            }
        });
    };
</script>
