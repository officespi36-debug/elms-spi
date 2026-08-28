<!DOCTYPE html>
<html lang="km">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Security Alert: New Login to SPI E-LMS</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #0b0f19;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, 'Kantumruy Pro', 'Battambang', sans-serif;
      color: #e2e8f0;
      -webkit-font-smoothing: antialiased;
    }
    table {
      border-collapse: collapse;
    }
    .wrapper {
      width: 100%;
      table-layout: fixed;
      background-color: #0b0f19;
      padding: 30px 0;
    }
    .container {
      max-width: 600px;
      margin: 0 auto;
      background-color: #111827;
      border: 1px solid #1f2937;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
    }
    .header {
      background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
      padding: 28px 24px;
      text-align: center;
      border-bottom: 1px solid #1e293b;
    }
    .header h1 {
      margin: 0;
      color: #ffffff;
      font-size: 20px;
      font-weight: 700;
      letter-spacing: 0.5px;
    }
    .header p {
      margin: 6px 0 0 0;
      color: #94a3b8;
      font-size: 12px;
    }
    .badge {
      display: inline-block;
      margin-top: 12px;
      background-color: rgba(59, 130, 246, 0.15);
      border: 1px solid rgba(59, 130, 246, 0.4);
      color: #60a5fa;
      padding: 4px 12px;
      border-radius: 9999px;
      font-size: 11px;
      font-weight: 600;
    }
    .content {
      padding: 28px 24px;
    }
    .greeting {
      font-size: 15px;
      font-weight: 600;
      color: #ffffff;
      margin-bottom: 12px;
    }
    .lead-text {
      font-size: 13px;
      line-height: 1.6;
      color: #cbd5e1;
      margin-bottom: 20px;
    }
    .details-box {
      background-color: #1e293b;
      border: 1px solid #334155;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 24px;
    }
    .detail-row {
      display: flex;
      justify-content: space-between;
      padding: 7px 0;
      border-bottom: 1px solid #334155;
      font-size: 12.5px;
    }
    .detail-row:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }
    .detail-label {
      color: #94a3b8;
      font-weight: 500;
    }
    .detail-value {
      color: #f8fafc;
      font-weight: 600;
      text-align: right;
    }
    .alert-notice {
      background-color: rgba(239, 68, 68, 0.1);
      border-left: 3px solid #ef4444;
      padding: 12px 14px;
      border-radius: 6px;
      margin-bottom: 24px;
      font-size: 12px;
      line-height: 1.5;
      color: #fca5a5;
    }
    .btn-container {
      text-align: center;
      margin-bottom: 20px;
    }
    .btn-danger {
      display: inline-block;
      background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
      color: #ffffff !important;
      text-decoration: none;
      font-weight: 600;
      font-size: 13px;
      padding: 12px 24px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }
    .footer {
      background-color: #0f172a;
      padding: 20px 24px;
      text-align: center;
      border-top: 1px solid #1e293b;
      font-size: 11px;
      color: #64748b;
      line-height: 1.6;
    }
    .footer a {
      color: #60a5fa;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <table class="container" width="100%" cellpadding="0" cellspacing="0">
      <!-- Header -->
      <tr>
        <td class="header">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td align="center">
                <div style="font-size: 28px; margin-bottom: 4px;">🏛️</div>
                <h1>វិទ្យាស្ថាន សន្តប៉ូល | SAINT PAUL INSTITUTE</h1>
                <p>SPI E-LMS Official Academic Platform</p>
                <div class="badge">🛡️ សេចក្តីជូនដំណឹងសុវត្ថិភាព / Security Login Alert</div>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- Body Content -->
      <tr>
        <td class="content">
          <div class="greeting">
            សួស្តី {{ $userName }} 👋
          </div>
          <div class="lead-text">
            យើងខ្ញុំបានកត់ត្រាការចូលប្រើប្រាស់គណនី SPI E-LMS របស់អ្នកដោយជោគជ័យ។ ប្រសិនបើនេះជាសកម្មភាពផ្ទាល់របស់អ្នក លោកអ្នកមិនចាំបាច់ចាត់វិធានការអ្វីឡើយ។<br>
            <span style="font-size: 11.5px; color: #94a3b8;">(We detected a successful login to your SPI E-LMS account. If this was you, no further action is needed.)</span>
          </div>

          <!-- Login Details Card -->
          <div class="details-box">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="detail-label" style="padding: 6px 0; color: #94a3b8; font-size: 12.5px;">👤 គណនី / Account:</td>
                <td class="detail-value" style="padding: 6px 0; color: #f8fafc; font-size: 12.5px; font-weight: 600; text-align: right;">{{ $email }}</td>
              </tr>
              <tr>
                <td class="detail-label" style="padding: 6px 0; color: #94a3b8; font-size: 12.5px; border-top: 1px solid #334155;">⏰ ពេលវេលា / Time:</td>
                <td class="detail-value" style="padding: 6px 0; color: #f8fafc; font-size: 12.5px; font-weight: 600; text-align: right; border-top: 1px solid #334155;">{{ $time }} (Cambodia)</td>
              </tr>
              <tr>
                <td class="detail-label" style="padding: 6px 0; color: #94a3b8; font-size: 12.5px; border-top: 1px solid #334155;">📱 ឧបករណ៍ / Device:</td>
                <td class="detail-value" style="padding: 6px 0; color: #f8fafc; font-size: 12.5px; font-weight: 600; text-align: right; border-top: 1px solid #334155;">{{ $device }} ({{ $browser }})</td>
              </tr>
              <tr>
                <td class="detail-label" style="padding: 6px 0; color: #94a3b8; font-size: 12.5px; border-top: 1px solid #334155;">🌐 អាសយដ្ឋាន IP / IP:</td>
                <td class="detail-value" style="padding: 6px 0; color: #38bdf8; font-size: 12.5px; font-weight: 600; font-family: monospace; text-align: right; border-top: 1px solid #334155;">{{ $ip }}</td>
              </tr>
              <tr>
                <td class="detail-label" style="padding: 6px 0; color: #94a3b8; font-size: 12.5px; border-top: 1px solid #334155;">🎓 តួនាទី / Role:</td>
                <td class="detail-value" style="padding: 6px 0; color: #4ade80; font-size: 12.5px; font-weight: 600; text-align: right; border-top: 1px solid #334155;">{{ $role }}</td>
              </tr>
            </table>
          </div>

          <!-- Alert Warning Box -->
          <div class="alert-notice">
            <strong>⚠️ មិនមែនជាអ្នកមែនទេ? (Wasn't you?)</strong><br>
            ប្រសិនបើលោកអ្នកមិនបាន Login ចូលប្រើប្រាស់នៅពេលនេះទេ សូមចុចប៊ូតុងខាងក្រោមជាបន្ទាន់ ដើម្បីប្តូរពាក្យសម្ងាត់ និងទប់ស្កាត់ការលួចចូលគណនីរបស់អ្នក។
          </div>

          <!-- CTA Button -->
          <div class="btn-container">
            <a href="{{ $secureAccountUrl }}" class="btn-danger" target="_blank">
              🔒 ការពារគណនី / ប្តូរពាក្យសម្ងាត់ (Secure Account)
            </a>
          </div>

          <div style="text-align: center; font-size: 11px; color: #64748b; margin-top: 12px;">
            ឬចូលទៅកាន់គេហទំព័រផ្ទាល់៖ <a href="https://spilms.tech" style="color: #60a5fa;">https://spilms.tech</a>
          </div>
        </td>
      </tr>

      <!-- Footer -->
      <tr>
        <td class="footer">
          <div style="font-weight: 600; color: #94a3b8; margin-bottom: 4px;">
            🏛️ វិទ្យាស្ថាន សន្តប៉ូល (Saint Paul Institute)
          </div>
          <div>
            📍 ភូមិអង្គរុក្ខា ឃុំតាភេម ស្រុកត្រាំកក់ ខេត្តតាកែវ ព្រះរាជាណាចក្រកម្ពុជា
          </div>
          <div style="margin-top: 6px;">
            🌐 វេបសាយ: <a href="https://spilms.tech">spilms.tech</a> | 📞 ទូរស័ព្ទ: +855 96 608 5750 | ✈️ Bot: <a href="https://t.me/spi_elms_auth_bot">@spi_elms_auth_bot</a>
          </div>
          <div style="margin-top: 8px; font-size: 10px; color: #475569;">
            សារនេះត្រូវបានផ្ញើដោយស្វ័យប្រវត្តិពីប្រព័ន្ធសុវត្ថិភាព SPI E-LMS។ សូមកុំឆ្លើយតបមកកាន់អ៊ីមែលនេះ។
          </div>
        </td>
      </tr>
    </table>
  </div>
</body>
</html>
