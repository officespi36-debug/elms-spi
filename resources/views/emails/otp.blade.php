<!DOCTYPE html>
<html lang="km" translate="no" class="notranslate" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
  <title>Verify Your Email</title>
  
  <style>
    /* Global Reset & Client Specific Styles */
    html, body {
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      background-color: #f1f5f9;
      -webkit-text-size-adjust: 100%;
      -ms-text-size-adjust: 100%;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #0f172a;
    }
    table, td {
      mso-table-lspace: 0pt !important;
      mso-table-rspace: 0pt !important;
    }
    img {
      -ms-interpolation-mode: bicubic;
      border: 0;
      height: auto;
      line-height: 100%;
      outline: none;
      text-decoration: none;
    }
    a {
      color: inherit;
      text-decoration: none;
    }

    /* Khmer Specific Font Styling (Siemreap & Kantumruy Priority) */
    .km-font {
      font-family: 'Siemreap', 'Khmer OS Siemreap', 'Kantumruy Pro', 'Battambang', 'Noto Sans Khmer', 'Khmer Sangam MN', 'Khmer OS', Arial, sans-serif !important;
    }

    /* Responsive Rules */
    @media only screen and (max-width: 580px) {
      .email-outer-td {
        padding: 16px 8px !important;
      }
      .email-card {
        max-width: 100% !important;
        padding: 28px 18px 24px 18px !important;
        border-radius: 12px !important;
      }
      .otp-code {
        font-size: 32px !important;
        letter-spacing: 4px !important;
      }
      .footer-cell-left, .footer-cell-right {
        display: block !important;
        width: 100% !important;
        text-align: center !important;
      }
      .footer-social-wrap {
        margin-top: 14px !important;
        text-align: center !important;
      }
      .footer-social-table {
        margin: 0 auto !important;
      }
    }
  </style>
</head>

<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #0f172a;">

  <!-- Preheader text for email clients -->
  <div style="display: none; font-size: 1px; color: #f1f5f9; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
    Your verification code is {{ $otp }}. Please enter this code to confirm your email address.
  </div>

  <!-- Outer Centering Table -->
  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; width: 100%; margin: 0; padding: 36px 12px;">
    <tr>
      <td align="center" valign="top" class="email-outer-td">
        
        <!-- Main Card (Manus Clean Minimalist Card) -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-card" style="max-width: 520px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; border-collapse: separate; border-spacing: 0; padding: 42px 34px 30px 34px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); text-align: center;">
          
          <!-- Top Centered Logo Lockup -->
          <tr>
            <td align="center" style="padding-bottom: 24px; text-align: center;">
              <table border="0" cellpadding="0" cellspacing="0" align="center" style="margin: 0 auto;">
                <tr>
                  <td align="center" valign="middle" style="padding-right: 8px;">
                    <img src="https://raw.githubusercontent.com/Kosalsensok/AI-Based-E-Learning-Platform-for-Saint-Paul-Institute-/main/public/images/logo_transparent.png" alt="SPI Logo" width="30" height="30" style="display: block; width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                  </td>
                  <td align="center" valign="middle">
                    <span style="font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.4px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                      SPI E-LMS
                    </span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Main Centered Heading -->
          <tr>
            <td align="center" style="padding-bottom: 4px; text-align: center;">
              <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: -0.4px; line-height: 1.3; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
                Verify your email address
              </h1>
            </td>
          </tr>

          <!-- Khmer Sub-Heading with Traditional Smooth Khmer Font -->
          <tr>
            <td align="center" style="padding-bottom: 22px; text-align: center;">
              <span class="km-font" style="font-family: 'Siemreap', 'Khmer OS Siemreap', 'Kantumruy Pro', 'Battambang', 'Noto Sans Khmer', Arial, sans-serif; font-size: 15.5px; font-weight: 500; color: #2563eb; line-height: 1.8; display: inline-block;">
                ផ្ទៀងផ្ទាត់អាសយដ្ឋានអ៊ីមែលរបស់អ្នក
              </span>
            </td>
          </tr>

          <!-- Instruction Text (Clean English + Smooth Khmer) -->
          <tr>
            <td align="center" style="padding-bottom: 24px; text-align: center;">
              <p style="margin: 0 0 6px 0; font-size: 15px; font-weight: 600; color: #1e293b; line-height: 1.5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
                Please enter the verification code to confirm your email address:
              </p>
              <p class="km-font" style="margin: 0; font-size: 14.5px; color: #475569; line-height: 1.8; font-family: 'Siemreap', 'Khmer OS Siemreap', 'Kantumruy Pro', 'Battambang', 'Noto Sans Khmer', Arial, sans-serif;">
                សូមបញ្ចូលលេខកូដផ្ទៀងផ្ទាត់ខាងក្រោម ដើម្បីចូលប្រើប្រាស់គណនីរបស់អ្នក
              </p>
            </td>
          </tr>

          <!-- Rectangular Code Box (Exact Manus Style) -->
          <tr>
            <td align="center" style="padding-bottom: 20px; text-align: center;">
              <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 auto;">
                <tr>
                  <td align="center" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 10px; padding: 22px 16px; text-align: center;">
                    <span class="otp-code notranslate" translate="no" style="font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', 'Courier New', monospace; font-size: 38px; font-weight: 800; letter-spacing: 5px; color: #0f172a; line-height: 1; display: inline-block;">
                      {{ $otp }}
                    </span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Expiry Notice Badge -->
          <tr>
            <td align="center" style="padding-bottom: 22px; text-align: center;">
              <table border="0" cellpadding="0" cellspacing="0" align="center" style="margin: 0 auto;">
                <tr>
                  <td align="center" style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 20px; padding: 6px 18px;">
                    <span style="font-size: 12.5px; font-weight: 600; color: #1d4ed8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                      ⏱️ Code expires in 5 minutes
                    </span>
                    <span style="color: #93c5fd; padding: 0 4px;">•</span>
                    <span class="km-font" style="font-size: 13px; font-weight: 600; color: #1d4ed8; font-family: 'Kantumruy Pro', 'Siemreap', 'Khmer OS Siemreap', 'Noto Sans Khmer', Arial, sans-serif;">
                      មានសុពលភាពត្រឹម ៥ នាទី
                    </span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Disclaimer & Security Text -->
          <tr>
            <td align="center" style="padding-bottom: 12px; text-align: center;">
              <p style="margin: 0 0 5px 0; font-size: 14px; color: #475569; line-height: 1.5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
                If you didn't request this verification code, please ignore this email.
              </p>
              <p class="km-font" style="margin: 0 0 16px 0; font-size: 13.5px; color: #64748b; line-height: 1.8; font-family: 'Siemreap', 'Khmer OS Siemreap', 'Kantumruy Pro', 'Battambang', Arial, sans-serif;">
                ប្រសិនបើអ្នកមិនបានស្នើសុំលេខកូដនេះទេ សូមកុំចាប់អារម្មណ៍អ៊ីមែលនេះ។
              </p>
              <p style="margin: 0; text-align: center;">
                <a href="https://spilms.tech" style="color: #0f172a; text-decoration: none; font-size: 15px; font-weight: 800; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; letter-spacing: -0.2px; border-bottom: 2px solid #2563eb; padding-bottom: 1px;">
                  spilms.tech
                </a>
              </p>
            </td>
          </tr>

          <!-- Footer Divider & Social Media Icons (Compatible with Gmail table styling) -->
          <tr>
            <td style="border-top: 1px solid #e2e8f0; padding-top: 22px; margin-top: 16px;">
              <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                  <!-- Left: Copyright -->
                  <td align="left" valign="middle" style="font-size: 12.5px; color: #64748b; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                    © {{ date('Y') }} SPI E-LMS
                  </td>

                  <!-- Right: Only YouTube & Telegram Icons -->
                  <td align="right" valign="middle">
                    <table border="0" cellpadding="0" cellspacing="0" align="right">
                      <tr>
                        <!-- YouTube (@SokCodeing) -->
                        <td style="padding: 0 8px;">
                          <a href="https://www.youtube.com/@SokCodeing" target="_blank" title="YouTube" style="text-decoration: none; display: inline-block;">
                            <img src="https://img.icons8.com/ios-glyphs/30/111827/youtube-play.png" width="18" height="18" alt="YouTube" style="display: block; width: 18px; height: 18px; border: 0;">
                          </a>
                        </td>
                        <!-- Telegram (@spi_elms_auth_bot) -->
                        <td style="padding: 0 8px;">
                          <a href="https://t.me/spi_elms_auth_bot" target="_blank" title="Telegram" style="text-decoration: none; display: inline-block;">
                            <img src="https://img.icons8.com/ios-glyphs/30/111827/telegram-app.png" width="18" height="18" alt="Telegram" style="display: block; width: 18px; height: 18px; border: 0;">
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

        </table>
        <!-- End Main Card -->

      </td>
    </tr>
  </table>

</body>
</html>
