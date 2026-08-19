<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Noskiem.org')</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4ef; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#283618;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4ef; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:560px; background-color:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e5e5dc;">
                    <tr>
                        <td style="background-color:#283618; padding:24px 32px;">
                            <span style="color:#fefae0; font-size:20px; font-weight:600;">noskiem.org</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px; background-color:#f4f4ef; border-top:1px solid #e5e5dc;">
                            <p style="margin:0; font-size:12px; color:#8f9485;">„Znajdziemy go noskiem" — noskiem.org</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
