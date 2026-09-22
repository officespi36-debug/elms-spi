<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
  <title>Security Alert: New Login</title>
  
  <style>
    html, body {
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      background-color: #ffffff;
      -webkit-text-size-adjust: 100%;
      -ms-text-size-adjust: 100%;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #1c1e21;
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

    @media only screen and (max-width: 580px) {
      .email-wrapper {
        padding: 24px 16px !important;
      }
      .details-card {
        padding: 16px 16px !important;
      }
      .details-label, .details-value {
        font-size: 13px !important;
      }
    }
  </style>
</head>

<body style="margin: 0; padding: 0; background-color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #1c1e21;">

  <!-- Preheader text for email clients -->
  <div style="display: none; font-size: 1px; color: #ffffff; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
    Security Alert: A new login to your SPI E-LMS account ({{ $email }}) was detected.
  </div>

  <!-- Outer Centering Table -->
  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; width: 100%; margin: 0; padding: 40px 16px;">
    <tr>
      <td align="center" valign="top">
        
        <!-- Main Content Wrapper (Meta-style Clean Card) -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-wrapper" style="max-width: 540px; margin: 0 auto; text-align: left;">
          
          <!-- Centered Brand Logo (Meta style) -->
          <tr>
            <td align="center" style="padding-bottom: 36px; text-align: center;">
              <table border="0" cellpadding="0" cellspacing="0" align="center" style="margin: 0 auto;">
                <tr>
                  <td align="center" valign="middle" style="padding-right: 10px;">
                    <img src="https://raw.githubusercontent.com/Kosalsensok/AI-Based-E-Learning-Platform-for-Saint-Paul-Institute-/main/public/images/logo_transparent.png" alt="SPI Logo" width="38" height="38" style="display: block; width: 38px; height: 38px; border-radius: 50%; object-fit: cover;">
                  </td>
                  <td align="center" valign="middle">
                    <span style="font-size: 22px; font-weight: 800; color: #1c1e21; letter-spacing: -0.4px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                      SPI E-LMS
                    </span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Salutation (Meta style: "Hi kosalsensok,") -->
          <tr>
            <td style="padding-bottom: 16px;">
              <p style="margin: 0; font-size: 16px; font-weight: 600; color: #1c1e21; line-height: 1.4;">
                Hi {{ $userName }},
              </p>
            </td>
          </tr>

          <!-- Notification Lead Text -->
          <tr>
            <td style="padding-bottom: 24px;">
              <p style="margin: 0; font-size: 15px; color: #1c1e21; line-height: 1.5;">
                We detected a recent login to your SPI E-LMS account. Please review the login details below:
              </p>
            </td>
          </tr>

          <!-- Details Box (Modeled after Meta's gray code/info container: #f0f2f5) -->
          <tr>
            <td style="padding-bottom: 24px;">
              <table border="0" cellpadding="0" cellspacing="0" width="100%" class="details-card" style="background-color: #f0f2f5; border: 1px solid #e4e6eb; border-radius: 10px; padding: 18px 22px; margin: 0 auto;">
                <tr>
                  <td align="left" class="details-label" style="padding: 9px 0; border-bottom: 1px solid #e4e6eb; font-size: 13.5px; font-weight: 500; color: #65676b; width: 35%;">
                    Account
                  </td>
                  <td align="right" class="details-value" style="padding: 9px 0; border-bottom: 1px solid #e4e6eb; font-size: 14px; font-weight: 600; color: #1c1e21; word-break: break-all;">
                    {{ $email }}
                  </td>
                </tr>
                <tr>
                  <td align="left" class="details-label" style="padding: 9px 0; border-bottom: 1px solid #e4e6eb; font-size: 13.5px; font-weight: 500; color: #65676b;">
                    Time
                  </td>
                  <td align="right" class="details-value" style="padding: 9px 0; border-bottom: 1px solid #e4e6eb; font-size: 14px; font-weight: 600; color: #1c1e21;">
                    {{ $time }} (Cambodia)
                  </td>
                </tr>
                <tr>
                  <td align="left" class="details-label" style="padding: 9px 0; border-bottom: 1px solid #e4e6eb; font-size: 13.5px; font-weight: 500; color: #65676b;">
                    Device
                  </td>
                  <td align="right" class="details-value" style="padding: 9px 0; border-bottom: 1px solid #e4e6eb; font-size: 14px; font-weight: 600; color: #1c1e21;">
                    {{ $device }} ({{ $browser }})
                  </td>
                </tr>
                <tr>
                  <td align="left" class="details-label" style="padding: 9px 0; border-bottom: 1px solid #e4e6eb; font-size: 13.5px; font-weight: 500; color: #65676b;">
                    IP Address
                  </td>
                  <td align="right" class="details-value" style="padding: 9px 0; border-bottom: 1px solid #e4e6eb; font-size: 14px; font-weight: 700; color: #0866ff; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;">
                    {{ $ip }}
                  </td>
                </tr>
                <tr>
                  <td align="left" class="details-label" style="padding: 9px 0; font-size: 13.5px; font-weight: 500; color: #65676b;">
                    Role
                  </td>
                  <td align="right" class="details-value" style="padding: 9px 0; font-size: 14px; font-weight: 700; color: #16a34a;">
                    {{ $role }}
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Security Verification / Disregard Text (Meta style) -->
          <tr>
            <td style="padding-bottom: 20px;">
              <p style="margin: 0 0 14px 0; font-size: 14.5px; color: #1c1e21; line-height: 1.5;">
                If you did not try to log in, someone else may be attempting to access your account. Please secure your account immediately:
              </p>
            </td>
          </tr>

          <!-- Action Button (Meta-style Primary Blue Button) -->
          <tr>
            <td style="padding-bottom: 28px;">
              <table border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background-color: #0866ff; border-radius: 8px; padding: 12px 28px;">
                    <a href="{{ $secureAccountUrl }}" target="_blank" style="color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; display: inline-block; letter-spacing: 0.2px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                      Secure Account
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Safe Confirmation Note -->
          <tr>
            <td style="padding-bottom: 32px;">
              <p style="margin: 0; font-size: 14px; color: #65676b; line-height: 1.5;">
                If this was you, you can safely disregard this message.
              </p>
            </td>
          </tr>

          <!-- Divider Line (Meta style) -->
          <tr>
            <td style="border-top: 1px solid #e4e6eb; padding-top: 24px;">
              <!-- Meta-style Compact Subtle Footer -->
              <p style="margin: 0 0 6px 0; font-size: 12px; color: #8a8d91; line-height: 1.5;">
                This message was sent to <a href="mailto:{{ $email }}" style="color: #65676b; text-decoration: underline;">{{ $email }}</a> at your request.
              </p>
              <p style="margin: 0 0 6px 0; font-size: 12px; color: #8a8d91; line-height: 1.5;">
                Saint Paul Institute • E-Learning Management System
              </p>
              <p style="margin: 0; font-size: 12px; color: #8a8d91; line-height: 1.5;">
                <a href="https://spilms.tech" target="_blank" style="color: #0866ff; text-decoration: none; font-weight: 600;">spilms.tech</a> &nbsp;•&nbsp; &copy; {{ date('Y') }} SPI E-LMS. All rights reserved.
              </p>
            </td>
          </tr>

        </table>
        <!-- End Main Content Wrapper -->

      </td>
    </tr>
  </table>

</body>
</html>
