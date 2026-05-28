<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi Pendaftaran</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0f172a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 480px; background-color: #1e293b; border-radius: 16px; border: 1px solid #334155; overflow: hidden;">
                    
                    <tr>
                        <td style="padding: 32px 32px 0; text-align: center;">
                            <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; font-size: 28px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 14px; text-align: center;">💰</div>
                            <h1 style="color: #ffffff; font-size: 20px; font-weight: 700; margin: 16px 0 4px;">Finance AI</h1>
                            <p style="color: #94a3b8; font-size: 14px; margin: 0;">Verifikasi Pendaftaran</p>
                        </td>
                    </tr>

                    
                    <tr>
                        <td style="padding: 32px;">
                            <p style="color: #e2e8f0; font-size: 15px; margin: 0 0 8px;">Halo <strong><?php echo e($userName); ?></strong>,</p>
                            <p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                                Terima kasih telah mendaftar di Finance AI. Gunakan kode OTP berikut untuk menyelesaikan pendaftaran Anda:
                            </p>

                            
                            <div style="background-color: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 20px; text-align: center; margin: 0 0 24px;">
                                <p style="color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 8px;">Kode Verifikasi</p>
                                <p style="color: #ffffff; font-size: 32px; font-weight: 700; letter-spacing: 8px; margin: 0; font-family: 'Courier New', monospace;"><?php echo e($otpCode); ?></p>
                            </div>

                            <p style="color: #94a3b8; font-size: 13px; line-height: 1.5; margin: 0 0 8px;">
                                ⏱ Kode ini berlaku selama <strong style="color: #e2e8f0;">10 menit</strong>.
                            </p>
                            <p style="color: #94a3b8; font-size: 13px; line-height: 1.5; margin: 0;">
                                🔒 Jangan berikan kode ini kepada siapapun.
                            </p>
                        </td>
                    </tr>

                    
                    <tr>
                        <td style="padding: 0 32px 32px;">
                            <div style="border-top: 1px solid #334155; padding-top: 20px;">
                                <p style="color: #64748b; font-size: 12px; text-align: center; margin: 0;">
                                    Jika Anda tidak merasa mendaftar di Finance AI, abaikan email ini.
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>

                <p style="color: #475569; font-size: 11px; text-align: center; margin-top: 24px;">
                    &copy; <?php echo e(date('Y')); ?> Finance AI. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
<?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/emails/registration-otp.blade.php ENDPATH**/ ?>